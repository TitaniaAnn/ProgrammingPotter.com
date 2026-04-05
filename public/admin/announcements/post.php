<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';
require_once __DIR__ . '/../../../includes/AnnouncementSocialMedia.php';

Auth::requireLogin();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    flash('error', 'Invalid announcement ID.');
    redirect(SITE_URL . '/admin/announcements/index.php');
}

$announcement = Database::fetchOne("SELECT * FROM announcements WHERE id = ?", [$id]);
if (!$announcement) {
    flash('error', 'Announcement not found.');
    redirect(SITE_URL . '/admin/announcements/index.php');
}

// Check if announcement is ready to post (publish_date <= now)
$now = date('Y-m-d H:i:s');
if ($announcement['publish_date'] > $now) {
    flash('error', 'This announcement is scheduled for the future and cannot be posted yet.');
    redirect(SITE_URL . '/admin/announcements/index.php');
}

// Check if image exists
if (empty($announcement['image_path']) || !file_exists($announcement['image_path'])) {
    flash('error', 'Announcement has no image attached. Cannot post to social media.');
    redirect(SITE_URL . '/admin/announcements/edit.php?id=' . $id);
}

// Build caption from announcement + linked entities
$caption = $announcement['title'];
if (!empty($announcement['description'])) {
    // Include brief description if present
    $desc = substr($announcement['description'], 0, 100);
    if (strlen($announcement['description']) > 100) {
        $desc .= '...';
    }
    $caption .= "\n\n" . $desc;
}

// Add linked entities to caption
$links = Database::fetchAll(
    "SELECT entity_type, entity_id FROM announcement_links WHERE announcement_id = ? ORDER BY sort_order ASC",
    [$id]
);

if (!empty($links)) {
    $caption .= "\n\n";
    foreach ($links as $link) {
        if ($link['entity_type'] === 'event') {
            $event = Database::fetchOne("SELECT name FROM events WHERE id = ?", [$link['entity_id']]);
            if ($event) {
                $caption .= "📅 " . $event['name'] . "\n";
            }
        } elseif ($link['entity_type'] === 'pottery') {
            $pottery = Database::fetchOne("SELECT title FROM pottery WHERE id = ?", [$link['entity_id']]);
            if ($pottery) {
                $caption .= "🏺 " . $pottery['title'] . "\n";
            }
        }
    }
}

$caption .= "\n\nVisit ProgrammingPotter.com for more!";

// Validate available platforms
$validPlatforms = AnnouncementSocialMedia::validateTokens();

// Determine which platforms to post to
$requestPlatforms = (array)($_POST['platforms'] ?? []);
if (empty($requestPlatforms)) {
    // Default to all available platforms
    $requestPlatforms = [];
    if ($validPlatforms['instagram']) $requestPlatforms[] = 'instagram';
    if ($validPlatforms['tiktok']) $requestPlatforms[] = 'tiktok';
}

if (empty($requestPlatforms)) {
    flash('error', 'No social media platforms configured. Please set up Instagram or TikTok credentials.');
    redirect(SITE_URL . '/admin/announcements/edit.php?id=' . $id);
}

$successCount = 0;
$errors = [];

// Post to each selected platform
foreach ($requestPlatforms as $platform) {
    if (!isset($validPlatforms[$platform]) || !$validPlatforms[$platform]) {
        continue;
    }
    
    try {
        if ($platform === 'instagram') {
            $result = AnnouncementSocialMedia::postToInstagram($id, $announcement['image_path'], $caption);
            $successCount++;
            flash('success', "Posted to Instagram! Post ID: " . $result['post_id']);
        } elseif ($platform === 'tiktok') {
            $result = AnnouncementSocialMedia::postToTikTok($id, $announcement['image_path'], $caption);
            $successCount++;
            flash('success', "Posted to TikTok! Post ID: " . $result['post_id']);
        }
    } catch (Exception $e) {
        $errors[] = ucfirst($platform) . ': ' . $e->getMessage();
    }
}

// Show any errors
if (!empty($errors)) {
    foreach ($errors as $error) {
        flash('error', $error);
    }
}

// If no platforms succeeded, show error
if ($successCount === 0) {
    flash('error', 'Failed to post to any social media platform.');
}

redirect(SITE_URL . '/admin/announcements/edit.php?id=' . $id);
