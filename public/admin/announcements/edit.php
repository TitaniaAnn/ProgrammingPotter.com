<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';
// Redirect to add.php which handles both add and edit modes based on ?id parameter
redirect(SITE_URL . '/admin/announcements/add.php' . (!empty($_GET['id']) ? '?id=' . urlencode($_GET['id']) : ''));
