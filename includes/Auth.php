<?php
// includes/Auth.php — GitHub OAuth

class Auth {

    public static function start(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_name(SESSION_NAME);
            session_set_cookie_params([
                'lifetime' => SESSION_LIFETIME,
                'httponly' => true,
                'secure'   => self::isSecureRequest(),
                'samesite' => 'Lax',
            ]);
            session_start();
        }
    }

    /**
     * True when the user-facing request is HTTPS, including the case where
     * TLS is terminated at an upstream proxy/load balancer (Bluehost,
     * Cloudflare, etc.) and only X-Forwarded-Proto reflects the real scheme.
     * Without the X-Forwarded-Proto branch the session cookie would be sent
     * without the Secure flag in those environments.
     */
    public static function isSecureRequest(): bool {
        if (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') {
            return true;
        }
        $forwardedProto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
        if (strcasecmp($forwardedProto, 'https') === 0) {
            return true;
        }
        if (($_SERVER['SERVER_PORT'] ?? '') === '443') {
            return true;
        }
        return false;
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

        $existing = Database::fetchOne(
            "SELECT id FROM admin_users WHERE provider_user_id = ?",
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
            ], 'provider_user_id = :provider_user_id', ['provider_user_id' => $githubUser['id']]);
            $userId = $existing['id'];
        } else {
            $userId = Database::insert('admin_users', [
                'provider_user_id' => $githubUser['id'],
                'email'            => $email,
                'name'             => $name,
                'avatar_url'       => $avatar,
            ]);
        }

        self::start();
        session_regenerate_id(true);
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
