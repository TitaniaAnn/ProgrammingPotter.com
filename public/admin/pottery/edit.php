<?php
require_once __DIR__ . '/../../../includes/bootstrap.php';
redirect(SITE_URL . '/admin/pottery/add.php?id=' . (int)($_GET['id'] ?? 0));
