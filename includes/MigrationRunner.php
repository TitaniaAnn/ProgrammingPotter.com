<?php
// includes/MigrationRunner.php
//
// Discovers numbered migration files under sql/, tracks which ones have been
// applied in a `schema_migrations` table, and exposes "run this file" and
// "mark this file as already applied" operations for the admin UI.
//
// Design notes:
//
//   * Migration files match sql/NNN_*.sql. init.sql is excluded — it's the
//     canonical fresh-install schema, not an incremental migration.
//
//   * MySQL DDL implicitly commits each statement. We can't wrap a multi-
//     statement migration in a transaction the way the webhook handler does.
//     If a migration fails mid-file the partial changes stay in the DB; the
//     ledger row is NOT inserted, so the admin can fix and re-run.
//
//   * "Mark applied" exists because production databases already had
//     migrations 001-011 applied manually before this runner existed — the
//     ledger needs to reflect that without re-executing the SQL.
//
//   * The statement splitter is small on purpose. It strips `-- ` line
//     comments and `/* ... */` block comments, then splits on `;` followed
//     by a newline (to avoid breaking on stray semicolons inside string
//     literals — none of our migrations have any, but the rule is cheap
//     insurance). Tests live in tests/MigrationRunnerTest.php.

class MigrationRunner {

    private string $sqlDir;

    public function __construct(?string $sqlDir = null) {
        $this->sqlDir = $sqlDir ?? (defined('ROOT_PATH') ? ROOT_PATH . '/sql' : dirname(__DIR__) . '/sql');
    }

    public function ensureLedger(): void {
        Database::query(
            "CREATE TABLE IF NOT EXISTS schema_migrations (
                version     VARCHAR(255) PRIMARY KEY,
                applied_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                applied_by  INT NULL,
                source      ENUM('run','mark') NOT NULL DEFAULT 'run',
                notes       TEXT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    /**
     * Sorted list of migration filenames found on disk (basenames only,
     * e.g. "001_pottery_images.sql"). Excludes init.sql and any non-numbered
     * file.
     *
     * @return string[]
     */
    public function available(): array {
        if (!is_dir($this->sqlDir)) {
            return [];
        }
        $files = [];
        foreach (scandir($this->sqlDir) ?: [] as $entry) {
            if (preg_match('/^\d+_[A-Za-z0-9_\-]+\.sql$/', $entry)) {
                $files[] = $entry;
            }
        }
        sort($files);
        return $files;
    }

    /**
     * Versions present in the ledger keyed by version → row.
     *
     * @return array<string, array{version:string,applied_at:string,applied_by:?int,source:string,notes:?string}>
     */
    public function applied(): array {
        $this->ensureLedger();
        $rows = Database::fetchAll(
            "SELECT version, applied_at, applied_by, source, notes
               FROM schema_migrations
              ORDER BY version ASC"
        );
        $out = [];
        foreach ($rows as $r) {
            $out[$r['version']] = $r;
        }
        return $out;
    }

    /**
     * @return string[] Filenames present on disk but absent from the ledger.
     */
    public function pending(): array {
        $applied = $this->applied();
        return array_values(array_filter(
            $this->available(),
            fn($f) => !isset($applied[$f])
        ));
    }

    /**
     * Read and parse a migration file into individual statements. Public so
     * tests can exercise the splitter directly without touching the DB.
     *
     * @return string[]
     */
    public function statementsFor(string $version): array {
        $sql = $this->loadFile($version);
        return self::splitStatements($sql);
    }

    /**
     * Apply a migration: split it, exec each statement, record the run.
     * Throws RuntimeException if a statement fails — the ledger row is NOT
     * written in that case, so the admin can re-run after fixing the cause.
     *
     * @return array{statements: array<int, array{sql:string, ok:bool, error:?string, rows:int}>}
     */
    public function apply(string $version, ?int $adminId = null): array {
        $this->ensureLedger();
        $this->assertKnown($version);
        $this->assertNotApplied($version);

        $statements = $this->statementsFor($version);
        $pdo = Database::getInstance();

        $results = [];
        foreach ($statements as $stmt) {
            try {
                $rows = $pdo->exec($stmt);
                $results[] = ['sql' => $stmt, 'ok' => true, 'error' => null, 'rows' => (int) $rows];
            } catch (\Throwable $e) {
                $results[] = ['sql' => $stmt, 'ok' => false, 'error' => $e->getMessage(), 'rows' => 0];
                // Stop on first failure. DDL has already implicitly committed
                // every statement before this one, so partial state stays —
                // the admin sees exactly where we stopped.
                throw new RuntimeException(
                    "Migration $version failed at statement " . (count($results)) . ": " . $e->getMessage(),
                    0,
                    $e
                );
            }
        }

        Database::insert('schema_migrations', [
            'version'    => $version,
            'applied_by' => $adminId,
            'source'     => 'run',
        ]);

        return ['statements' => $results];
    }

    /**
     * Record a migration as applied without running it. Used to reconcile a
     * production database where migrations were applied manually before the
     * runner existed.
     */
    public function markApplied(string $version, ?int $adminId = null, string $notes = ''): void {
        $this->ensureLedger();
        $this->assertKnown($version);
        $this->assertNotApplied($version);

        Database::insert('schema_migrations', [
            'version'    => $version,
            'applied_by' => $adminId,
            'source'     => 'mark',
            'notes'      => $notes ?: null,
        ]);
    }

    /**
     * Drop the ledger row for a migration. Useful only when an admin needs to
     * re-run a migration that was incorrectly marked applied — there's no UI
     * for it yet, but the operation is safe to expose.
     */
    public function unmark(string $version): void {
        $this->ensureLedger();
        $this->assertKnown($version);
        Database::delete('schema_migrations', 'version = ?', [$version]);
    }

    // ---- internals ----------------------------------------------------------

    private function assertKnown(string $version): void {
        if (!in_array($version, $this->available(), true)) {
            throw new RuntimeException("Unknown migration: $version");
        }
    }

    private function assertNotApplied(string $version): void {
        if (isset($this->applied()[$version])) {
            throw new RuntimeException("Migration already applied: $version");
        }
    }

    private function loadFile(string $version): string {
        // assertKnown already verified $version matches the safe regex, so a
        // direct concatenation here cannot escape sqlDir.
        $path = $this->sqlDir . DIRECTORY_SEPARATOR . $version;
        if (!is_file($path)) {
            throw new RuntimeException("Migration file missing: $version");
        }
        $sql = file_get_contents($path);
        if ($sql === false) {
            throw new RuntimeException("Could not read migration: $version");
        }
        return $sql;
    }

    /**
     * Strip comments and split a SQL string into individual statements.
     *
     * Handles:
     *   * `-- ...` line comments (and `# ...`)
     *   * `/* ... *\/` block comments
     *   * Statements terminated by `;` at end of line
     *   * Empty / whitespace-only chunks (filtered out)
     *
     * Does NOT handle string literals containing semicolons. Our migrations
     * don't have any; the splitter throws on a chunk that ends without a
     * trailing `;` to surface that case loudly.
     *
     * @return string[]
     */
    public static function splitStatements(string $sql): array {
        // Strip /* ... */ block comments first (non-greedy, multi-line).
        $sql = preg_replace('!/\*.*?\*/!s', '', $sql) ?? '';

        $clean = [];
        foreach (preg_split('/\R/', $sql) as $line) {
            $trimmed = ltrim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '--') || str_starts_with($trimmed, '#')) {
                continue;
            }
            // Strip an inline trailing `-- comment` if present (after the
            // first whitespace-bracketed `--`).
            if (preg_match('/^(.*?)\s+--\s.*$/', $line, $m)) {
                $line = $m[1];
            }
            $clean[] = $line;
        }
        $body = implode("\n", $clean);

        // Split on `;` followed by end-of-line (or end-of-string).
        $parts = preg_split('/;\s*(\R|$)/', $body);
        $statements = [];
        foreach ($parts as $part) {
            $t = trim($part);
            if ($t !== '') {
                $statements[] = $t;
            }
        }
        return $statements;
    }
}
