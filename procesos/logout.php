<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/security.php';

logout_user();
header('Location: ' . app_url('index.php'));
exit;
