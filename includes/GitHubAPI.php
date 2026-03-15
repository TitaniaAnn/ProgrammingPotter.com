<?php
// includes/GitHubAPI.php — GitHub Issues API helper

class GitHubAPI {

    private static function get(string $url, string $token = ''): array {
        $headers = ['User-Agent: PotteryPortfolio/1.0', 'Accept: application/vnd.github+json'];
        if ($token) {
            $headers[] = "Authorization: Bearer $token";
        }
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => 10,
        ]);
        $response = curl_exec($ch);
        $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($status !== 200 || !$response) {
            return [];
        }
        return json_decode($response, true) ?? [];
    }

    private static function post(string $url, string $token, array $data): array {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_HTTPHEADER     => [
                'User-Agent: PotteryPortfolio/1.0',
                'Accept: application/vnd.github+json',
                "Authorization: Bearer $token",
                'Content-Type: application/json',
            ],
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true) ?? [];
    }

    /**
     * Fetch issues from a public (or private, with token) GitHub repo.
     * Returns an array of issue objects, or empty array on failure.
     *
     * @param string $repo  e.g. "owner/repo-name"
     * @param string $state "open" | "closed" | "all"
     * @param string $token Optional PAT for private repos
     */
    public static function getIssues(string $repo, string $state = 'all', string $token = ''): array {
        if (!$repo) return [];

        $url = "https://api.github.com/repos/{$repo}/issues?" . http_build_query([
            'state'    => $state,
            'per_page' => 100,
            'sort'     => 'created',
            'direction'=> 'desc',
        ]);

        $issues = self::get($url, $token);

        // Filter out pull requests (GitHub returns PRs in /issues endpoint)
        return array_values(array_filter($issues, fn($i) => empty($i['pull_request'])));
    }

    /**
     * Create a new issue on GitHub.
     * Returns the created issue array (includes html_url and number), or empty on failure.
     *
     * @param string   $repo   e.g. "owner/repo-name"
     * @param string   $token  PAT with repo scope
     * @param string   $title
     * @param string   $body
     * @param string[] $labels e.g. ["bug"] or ["enhancement"]
     */
    public static function createIssue(string $repo, string $token, string $title, string $body, array $labels = []): array {
        if (!$repo || !$token) return [];

        $url = "https://api.github.com/repos/{$repo}/issues";
        return self::post($url, $token, compact('title', 'body', 'labels'));
    }
}
