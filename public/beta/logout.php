<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once ROOT_PATH . '/includes/BetaAuth.php';

BetaAuth::logout();
redirect('/beta/login.php');
