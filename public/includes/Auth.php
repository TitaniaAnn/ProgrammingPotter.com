<?php
// includes/Auth.php — GitHub OAuth

class Auth {

    public static function start(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_name(SESSION_NAME);
            session_set_cookie_params([
                'lifetime' => SESSION_LIFETIME,
                'httponly' => true,
                'secure'   => isset($_SERVER['HTTPS']),
                'samesite' => 'Lax',
            ]);
            session_start();
        }
    }

    public static function isLoggedIn(): bool {
        self::start();
        return !empty($_SESSION['admin_id']);
    }

    public static function requireLogin(): void {
        if (!self::isLoggedIn()) {
            header('Location: ' . SITE_URL . '/admin/login.php');
            exit;
        }
    }

    public static function getUser(): ?array {
        if (!self::isLoggedIn()) return null;
        return $_SESSION['admin_user'] ?? null;
    }

    public static function logout(): void {
        self::start();
        $_SESSION = [];
        session_destroy();
    }

    // ---- GitHub OAuth ----

    public static function getGitHubAuthUrl(): string {
        $state = bin2hex(random_bytes(16));
        $_SESSION['oauth_state'] = $state;

        return 'https://github.com/login/oauth/authorize?' . http_build_query([
            'client_id'    => GITHUB_CLIENT_ID,
            'redirect_uri' => GITHUB_REDIRECT_URI,
            'scope'        => 'read:user',
            'state'        => $state,
        ]);
    }

    public static function handleGitHubCallback(string $code, string $state): bool {
        if ($state !== ($_SESSION['oauth_state'] ?? '')) {
            return false;
        }
        unset($_SESSION['oauth_state']);

        // Exchange code for access token
        $tokenData = self::httpPost('https://github.com/login/oauth/access_token', [
            'client_id'     => GITHUB_CLIENT_ID,
            'client_secret' => GITHUB_CLIENT_SECRET,
            'code'          => $code,
            'redirect_uri'  => GITHUB_REDIRECT_URI,
        ], ['Accept: application/json']);

        if (empty($tokenData['access_token'])) {
            return false;
        }

        // Get GitHub user profile
        $githubUser = self::httpGet(
            'https://api.github.com/user',
            $tokenData['access_token']
        );

        if (empty($githubUser['login'])) {
            return false;
        }

        return self::login($githubUser);
    }

    private static function login(array $githubUser): bool {
        $allowedUsers = array_map('trim', explode(',', ALLOWED_GITHUB_USERS));

        if (!in_array($githubUser['login'], $allowedUsers)) {
            return false;
        }

        // Upsert user in DB — reusing google_id column for GitHub numeric user ID
        $existing = Database::fetchOne(
            "SELECT id FROM admin_users WHERE google_id = ?",
            [$githubUser['id']]
        );

        $name   = $githubUser['name'] ?? $githubUser['login'];
        $avatar = $githubUser['avatar_url'] ?? null;
        $email  = $githubUser['email'] ?? ($githubUser['login'] . '@github');

        if ($existing) {
            Database::update('admin_users', [
                'name'       => $name,
                'avatar_url' => $avatar,
                'last_login' => date('Y-m-d H:i:s'),
            ], 'google_id = :google_id', ['google_id' => $githubUser['id']]);
            $userId = $existing['id'];
        } else {
            $userId = Database::insert('admin_users', [
                'google_id'  => $githubUser['id'],
                'email'      => $email,
                'name'       => $name,
                'avatar_url' => $avatar,
            ]);
        }

        self::start();
        $_SESSION['admin_id']   = $userId;
        $_SESSION['admin_user'] = [
            'id'     => $userId,
            'login'  => $githubUser['login'],
            'name'   => $name,
            'email'  => $email,
            'avatar' => $avatar,
        ];
        return true;
    }

    private static function httpPost(string $url, array $data, array $extraHeaders = []): array {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => array_merge(
                ['Content-Type: application/x-www-form-urlencoded', 'User-Agent: PotteryPortfolio/1.0'],
                $extraHeaders
            ),
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true) ?? [];
    }

    private static function httpGet(string $url, string $accessToken): array {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                "Authorization: Bearer $accessToken",
                'User-Agent: PotteryPortfolio/1.0',
                'Accept: application/json',
            ],
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true) ?? [];
    }
}
