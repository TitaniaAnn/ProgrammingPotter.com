<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
Auth::logout();
redirect(SITE_URL . '/admin/login.php');
