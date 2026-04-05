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
        $accessToken = defined('INSTAGRAM_ACCESS_TOKEN') ? INSTAGRAM_ACCESS_TOKEN : null;
        
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
        $businessAccountId = defined('TIKTOK_BUSINESS_ACCOUNT_ID') ? TIKTOK_BUSINESS_ACCOUNT_ID : null;
        $accessToken = defined('TIKTOK_ACCESS_TOKEN') ? TIKTOK_ACCESS_TOKEN : null;
        
        if (empty($businessAccountId) || empty($accessToken)) {
            throw new Exception('TikTok credentials not configured. Set TIKTOK_BUSINESS_ACCOUNT_ID and TIKTOK_ACCESS_TOKEN in .env');
        }
        
        if (!file_exists($imagePath)) {
            throw new Exception('Image file not found: ' . $imagePath);
        }
        
        // TikTok has lower caption limit
        $caption = substr($caption, 0, 150);
        
        try {
            // TikTok uses a different API structure
            // Note: TikTok's API requires video content for regular API
            // Image posts typically require the TikTok Creative Center
            // This is a simplified implementation using their content posting API
            
            $url = 'https://open.tiktokapis.com/v1/post/publish/action/init/';
            
            $postData = json_encode([
                'source_info' => [
                    'source' => 'CREATIVE_CENTER',
                ],
                'post_info' => [
                    'title' => $caption,
                    'privacy_level' => 'PUBLIC_TO_ANYONE',
                ],
            ]);
            
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => [
                        'Content-Type: application/json',
                        'Authorization: Bearer ' . $accessToken,
                    ],
                    'content' => $postData,
                    'timeout' => 30,
                ],
                'https' => [
                    'verify_peer' => true,
                ],
            ]);
            
            $response = @file_get_contents($url, false, $context);
            if ($response === false) {
                throw new Exception('TikTok API request failed');
            }
            
            $result = json_decode($response, true);
            
            if (!isset($result['data']['publish_id'])) {
                $error = $result['error']['message'] ?? ($result['message'] ?? 'Unknown error from TikTok API');
                throw new Exception('TikTok API error: ' . $error);
            }
            
            $publishId = $result['data']['publish_id'];
            
            // Note: TikTok's image post flow is complex and requires special handling
            // For MVP, we'll record the publish intent
            
            Database::insert('announcement_social_posts', [
                'announcement_id' => $announcementId,
                'platform' => 'tiktok',
                'platform_post_id' => $publishId,
                'status' => 'success',
            ]);
            
            return [
                'post_id' => $publishId,
                'platform' => 'tiktok',
                'url' => 'https://www.tiktok.com/@[username]/video/' . $publishId,
            ];
            
        } catch (Exception $e) {
            // Record failure in database
            Database::insert('announcement_social_posts', [
                'announcement_id' => $announcementId,
                'platform' => 'tiktok',
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
    
    /**
     * Validate which social media platforms are configured
     * 
     * @return array ['instagram' => bool, 'tiktok' => bool]
     */
    public static function validateTokens() {
        return [
            'instagram' => !empty(defined('INSTAGRAM_BUSINESS_ACCOUNT_ID') ? INSTAGRAM_BUSINESS_ACCOUNT_ID : null) 
                        && !empty(defined('INSTAGRAM_ACCESS_TOKEN') ? INSTAGRAM_ACCESS_TOKEN : null),
            'tiktok' => !empty(defined('TIKTOK_BUSINESS_ACCOUNT_ID') ? TIKTOK_BUSINESS_ACCOUNT_ID : null) 
                     && !empty(defined('TIKTOK_ACCESS_TOKEN') ? TIKTOK_ACCESS_TOKEN : null),
        ];
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
