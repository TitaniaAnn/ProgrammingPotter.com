<?php
/**
 * AnnouncementSocialMedia.php
 * 
 * Handles posting announcements to Instagram and TikTok via their Graph APIs.
 * Posts are tracked in the announcement_social_posts table for audit trail.
 */

class AnnouncementSocialMedia {
    
    /**
     * Post announcement to Instagram
     * 
     * @param int $announcementId
     * @param string $imagePath - Full server path to image file
     * @param string $caption - Caption text (max 2200 chars for carousel, 300 for single image captions)
     * @return array ['post_id' => string, 'url' => string]
     * @throws Exception
     */
    public static function postToInstagram($announcementId, $imagePath, $caption) {
        $businessAccountId = defined('INSTAGRAM_BUSINESS_ACCOUNT_ID') ? INSTAGRAM_BUSINESS_ACCOUNT_ID : null;
        $accessToken       = self::getInstagramAccessToken();

        if (empty($businessAccountId) || empty($accessToken)) {
            throw new Exception('Instagram credentials not configured. Set INSTAGRAM_BUSINESS_ACCOUNT_ID and INSTAGRAM_ACCESS_TOKEN in .env');
        }
        
        if (!file_exists($imagePath)) {
            throw new Exception('Image file not found: ' . $imagePath);
        }
        
        // Truncate caption to Instagram's limit (301 chars for single image)
        $caption = substr($caption, 0, 300);
        
        try {
            // Step 1: Upload image as IgMediaObject (Create)
            $uploadUrl = "https://graph.instagram.com/{$businessAccountId}/media";
            
            $postData = [
                'image_url' => self::getPublicImageUrl($imagePath),
                'caption' => $caption,
                'access_token' => $accessToken,
            ];
            
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => 'Content-Type: application/x-www-form-urlencoded',
                    'content' => http_build_query($postData),
                    'timeout' => 30,
                ],
                'https' => [
                    'verify_peer' => true,
                    'verify_peer_name' => true,
                ],
            ]);
            
            $response = @file_get_contents($uploadUrl, false, $context);
            if ($response === false) {
                throw new Exception('Instagram API request failed');
            }
            
            $result = json_decode($response, true);
            
            if (!isset($result['id'])) {
                $error = $result['error']['message'] ?? 'Unknown error from Instagram API';
                throw new Exception('Instagram API error: ' . $error);
            }
            
            $mediaId = $result['id'];
            
            // Step 2: Publish the media
            $publishUrl = "https://graph.instagram.com/{$businessAccountId}/media_publish";
            $publishData = [
                'creation_id' => $mediaId,
                'access_token' => $accessToken,
            ];
            
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => 'Content-Type: application/x-www-form-urlencoded',
                    'content' => http_build_query($publishData),
                    'timeout' => 30,
                ],
                'https' => [
                    'verify_peer' => true,
                ],
            ]);
            
            $publishResponse = @file_get_contents($publishUrl, false, $context);
            if ($publishResponse === false) {
                throw new Exception('Instagram publish API request failed');
            }
            
            $publishResult = json_decode($publishResponse, true);
            
            if (!isset($publishResult['id'])) {
                $error = $publishResult['error']['message'] ?? 'Unknown error from Instagram publish API';
                throw new Exception('Instagram publish error: ' . $error);
            }
            
            $igPostId = $publishResult['id'];
            
            // Record post in database
            Database::insert('announcement_social_posts', [
                'announcement_id' => $announcementId,
                'platform' => 'instagram',
                'platform_post_id' => $igPostId,
                'status' => 'success',
            ]);
            
            return [
                'post_id' => $igPostId,
                'platform' => 'instagram',
                'url' => "https://instagram.com/p/{$igPostId}",
            ];
            
        } catch (Exception $e) {
            // Record failure in database
            Database::insert('announcement_social_posts', [
                'announcement_id' => $announcementId,
                'platform' => 'instagram',
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
    
    /**
     * Post announcement to TikTok
     * 
     * @param int $announcementId
     * @param string $imagePath - Full server path to image file
     * @param string $caption - Caption text (max 150 chars for TikTok)
     * @return array ['post_id' => string, 'url' => string]
     * @throws Exception
     */
    public static function postToTikTok($announcementId, $imagePath, $caption) {
        // TikTok's Content Posting API requires video uploads; the image flow
        // requires Creative Center integration that isn't wired up here. The
        // previous implementation called the init endpoint and then recorded
        // 'success' even though no image ever published, so disable the path
        // explicitly until proper video/image support is added.
        $message = 'TikTok image posting is not supported. Use Instagram, or post manually until the TikTok video flow is implemented.';

        Database::insert('announcement_social_posts', [
            'announcement_id' => $announcementId,
            'platform'        => 'tiktok',
            'status'          => 'failed',
            'error_message'   => $message,
        ]);

        throw new Exception($message);
    }
    
    /**
     * Validate which social media platforms are configured
     * 
     * @return array ['instagram' => bool, 'tiktok' => bool]
     */
    public static function validateTokens() {
        $igAccount = defined('INSTAGRAM_BUSINESS_ACCOUNT_ID') ? INSTAGRAM_BUSINESS_ACCOUNT_ID : null;
        $igToken   = self::getInstagramAccessToken();
        $igExpiry  = self::getInstagramTokenExpiry();
        $igExpired = $igExpiry !== null && $igExpiry < new DateTimeImmutable();

        return [
            'instagram' => !empty($igAccount) && !empty($igToken) && !$igExpired,
            // TikTok image posting is not supported; the API requires video.
            // Hide TikTok from platform selection UIs until the flow is built.
            'tiktok'    => false,
        ];
    }

    // -----------------------------------------------------------
    //  Token lifecycle
    //
    //  Instagram long-lived tokens expire ~60 days from issuance and can be
    //  refreshed (extended another 60 days) once they are >24h old. We store
    //  the live token + expiry in the `settings` table so a refresh persists
    //  without needing to redeploy the .env file.
    // -----------------------------------------------------------

    public static function getInstagramAccessToken(): ?string {
        $stored = setting('instagram_access_token', '');
        if ($stored !== '') {
            return $stored;
        }
        return defined('INSTAGRAM_ACCESS_TOKEN') ? (INSTAGRAM_ACCESS_TOKEN ?: null) : null;
    }

    public static function getInstagramTokenExpiry(): ?DateTimeImmutable {
        $iso = setting('instagram_token_expires_at', '');
        if ($iso === '') {
            return null;
        }
        try {
            return new DateTimeImmutable($iso);
        } catch (Exception $e) {
            return null;
        }
    }

    public static function getInstagramTokenLastRefreshed(): ?DateTimeImmutable {
        $iso = setting('instagram_token_refreshed_at', '');
        if ($iso === '') {
            return null;
        }
        try {
            return new DateTimeImmutable($iso);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Call Instagram's refresh_access_token endpoint to extend the current
     * long-lived token. Persists the new token + expiry to settings.
     *
     * @return array{access_token:string, expires_in:int, expires_at:string}
     * @throws Exception
     */
    public static function refreshInstagramToken(): array {
        $current = self::getInstagramAccessToken();
        if (empty($current)) {
            throw new Exception('No Instagram access token available to refresh. Set INSTAGRAM_ACCESS_TOKEN in .env first.');
        }

        $url = 'https://graph.instagram.com/refresh_access_token?' . http_build_query([
            'grant_type'   => 'ig_refresh_token',
            'access_token' => $current,
        ]);

        $context = stream_context_create([
            'http'  => ['method' => 'GET', 'timeout' => 30],
            'https' => ['verify_peer' => true, 'verify_peer_name' => true],
        ]);

        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            throw new Exception('Instagram refresh request failed (network error).');
        }

        $data = json_decode($response, true);
        if (!isset($data['access_token'], $data['expires_in'])) {
            $err = $data['error']['message'] ?? 'Unknown response from Instagram refresh endpoint.';
            throw new Exception('Instagram refresh error: ' . $err);
        }

        $expiresIn = (int) $data['expires_in'];
        $expiresAt = (new DateTimeImmutable())->modify("+{$expiresIn} seconds")->format('Y-m-d H:i:s');
        $now       = (new DateTimeImmutable())->format('Y-m-d H:i:s');

        self::storeSetting('instagram_access_token',       $data['access_token']);
        self::storeSetting('instagram_token_expires_at',   $expiresAt);
        self::storeSetting('instagram_token_refreshed_at', $now);

        return [
            'access_token' => $data['access_token'],
            'expires_in'   => $expiresIn,
            'expires_at'   => $expiresAt,
        ];
    }

    private static function storeSetting(string $key, string $value): void {
        Database::query(
            "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)",
            [$key, $value]
        );
    }

    /**
     * Get public URL for an image file
     * Used for passing to social APIs that need HTTP(S) accessible URLs
     *
     * @param string $imagePath - Full server path
     * @return string - Public HTTP(S) URL
     */
    private static function getPublicImageUrl($imagePath) {
        // Convert absolute path to URL
        $uploadDir = UPLOAD_PATH;
        $relPath = str_replace($uploadDir, '', $imagePath);
        return UPLOAD_URL . $relPath;
    }
}
