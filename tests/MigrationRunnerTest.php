<?php

namespace Tests;

use MigrationRunner;
use PHPUnit\Framework\TestCase;

/**
 * Unit-level coverage for MigrationRunner. The DB-touching paths (apply,
 * markApplied, applied) require a live MySQL connection so they're not
 * exercised here — the splitter and the file-discovery logic are pure and
 * are where the parsing risks live.
 */
final class MigrationRunnerTest extends TestCase {

    private string $tmpDir = '';

    protected function setUp(): void {
        $this->tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'mig_test_' . uniqid();
        \mkdir($this->tmpDir);
    }

    protected function tearDown(): void {
        if ($this->tmpDir && \is_dir($this->tmpDir)) {
            foreach (\scandir($this->tmpDir) ?: [] as $f) {
                if ($f === '.' || $f === '..') continue;
                @\unlink($this->tmpDir . DIRECTORY_SEPARATOR . $f);
            }
            @\rmdir($this->tmpDir);
        }
    }

    private function write(string $name, string $contents): void {
        \file_put_contents($this->tmpDir . DIRECTORY_SEPARATOR . $name, $contents);
    }

    // ---- splitStatements ---------------------------------------------------

    public function test_splits_simple_two_statement_file(): void {
        $sql = "CREATE TABLE foo (id INT);\nALTER TABLE foo ADD COLUMN bar INT;\n";
        $this->assertSame(
            ['CREATE TABLE foo (id INT)', 'ALTER TABLE foo ADD COLUMN bar INT'],
            MigrationRunner::splitStatements($sql)
        );
    }

    public function test_strips_dash_dash_line_comments(): void {
        $sql = "-- top-level comment\nCREATE TABLE foo (id INT);\n-- another\nALTER TABLE foo ADD COLUMN bar INT;\n";
        $this->assertSame(
            ['CREATE TABLE foo (id INT)', 'ALTER TABLE foo ADD COLUMN bar INT'],
            MigrationRunner::splitStatements($sql)
        );
    }

    public function test_strips_hash_line_comments(): void {
        $sql = "# mysql-style comment\nCREATE TABLE foo (id INT);\n";
        $this->assertSame(['CREATE TABLE foo (id INT)'], MigrationRunner::splitStatements($sql));
    }

    public function test_strips_block_comments_including_multiline(): void {
        $sql = "/* one-line block */ CREATE TABLE foo (id INT);\n"
             . "/*\n * multi\n * line\n */\nALTER TABLE foo ADD COLUMN bar INT;\n";
        $this->assertSame(
            ['CREATE TABLE foo (id INT)', 'ALTER TABLE foo ADD COLUMN bar INT'],
            MigrationRunner::splitStatements($sql)
        );
    }

    public function test_handles_multiline_create_table(): void {
        $sql = <<<SQL
CREATE TABLE pottery (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    body TEXT
);
SQL;
        $stmts = MigrationRunner::splitStatements($sql);
        $this->assertCount(1, $stmts);
        $this->assertStringContainsString('CREATE TABLE pottery', $stmts[0]);
        $this->assertStringContainsString('title VARCHAR(255)', $stmts[0]);
    }

    public function test_strips_inline_trailing_comment(): void {
        $sql = "CREATE TABLE foo (id INT); -- inline trailing\n";
        $stmts = MigrationRunner::splitStatements($sql);
        // Inline `-- ` strip happens before the semicolon split, so the
        // statement still parses cleanly.
        $this->assertSame(['CREATE TABLE foo (id INT)'], $stmts);
    }

    public function test_filters_blank_lines_and_extra_whitespace(): void {
        $sql = "\n\n   \n\nCREATE TABLE foo (id INT);\n\n\n";
        $this->assertSame(['CREATE TABLE foo (id INT)'], MigrationRunner::splitStatements($sql));
    }

    public function test_returns_empty_array_for_pure_comment_file(): void {
        $sql = "-- just a header\n-- nothing to do here\n";
        $this->assertSame([], MigrationRunner::splitStatements($sql));
    }

    public function test_treats_trailing_statement_without_semicolon_as_a_statement(): void {
        // We're permissive here — a final statement without a closing `;`
        // gets parsed as a statement so the admin still sees something
        // executed. Migrations we write always close with `;`, so this
        // mainly guards against admin-supplied test fixtures.
        $sql = "CREATE TABLE foo (id INT)";
        $this->assertSame(['CREATE TABLE foo (id INT)'], MigrationRunner::splitStatements($sql));
    }

    // ---- available() filename discovery ------------------------------------

    public function test_available_returns_only_numbered_sql_files_sorted(): void {
        $this->write('010_zzz.sql',     'SELECT 1;');
        $this->write('001_init.sql',    'SELECT 1;');
        $this->write('init.sql',        'SELECT 1;');           // excluded — canonical schema
        $this->write('notes.txt',       'ignored');             // excluded — wrong ext
        $this->write('rollback.sql',    'SELECT 1;');           // excluded — no number prefix
        $this->write('005_middle.sql',  'SELECT 1;');

        $runner = new MigrationRunner($this->tmpDir);

        $this->assertSame(
            ['001_init.sql', '005_middle.sql', '010_zzz.sql'],
            $runner->available()
        );
    }

    public function test_available_returns_empty_for_missing_dir(): void {
        $runner = new MigrationRunner($this->tmpDir . '/does-not-exist');
        $this->assertSame([], $runner->available());
    }
}
