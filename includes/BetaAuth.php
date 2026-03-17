<?php
// includes/BetaAuth.php — Email + password auth for beta testers

class BetaAuth {

    public static function isLoggedIn(): bool {
        Auth::start(); // reuse the same session
        return !empty($_SESSION['beta_user_id']);
    }

    public static function requireLogin(): void {
        if (!self::isLoggedIn()) {
            header('Location: /beta/login.php');
            exit;
        }
    }

    public static function getUser(): ?array {
        if (!self::isLoggedIn()) return null;
        return $_SESSION['beta_user'] ?? null;
    }

    public static function logout(): void {
        Auth::start();
        unset($_SESSION['beta_user_id'], $_SESSION['beta_user']);
    }

    /**
     * Register a new beta tester. Returns user id or throws on failure.
     */
    public static function register(string $email, string $password, string $name, string $platform = 'other'): int {
        $email = strtolower(trim($email));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Invalid email address.');
        }
        if (strlen($password) < 8) {
            throw new RuntimeException('Password must be at least 8 characters.');
        }
        if (trim($name) === '') {
            throw new RuntimeException('Name is required.');
        }

        $existing = Database::fetchOne("SELECT id FROM beta_users WHERE email = ?", [$email]);
        if ($existing) {
            throw new RuntimeException('An account with that email already exists.');
        }

        $platform = in_array($platform, ['android', 'ios']) ? $platform : 'android';
        $hash     = password_hash($password, PASSWORD_BCRYPT);

        return Database::insert('beta_users', [
            'email'         => $email,
            'password_hash' => $hash,
            'name'          => trim($name),
            'platform'      => $platform,
            'approved'      => 1,
        ]);
    }

    /**
     * Attempt login. Returns true on success, false on failure.
     */
    public static function login(string $email, string $password): bool {
        $email = strtolower(trim($email));
        $user  = Database::fetchOne("SELECT * FROM beta_users WHERE email = ?", [$email]);

        if (!$user || !$user['approved']) {
            return false;
        }
        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }

        Database::update('beta_users', ['last_login' => date('Y-m-d H:i:s')], 'id = :id', ['id' => $user['id']]);

        Auth::start();
        $_SESSION['beta_user_id'] = $user['id'];
        $_SESSION['beta_user']    = [
            'id'    => $user['id'],
            'email' => $user['email'],
            'name'  => $user['name'],
        ];
        return true;
    }
}
