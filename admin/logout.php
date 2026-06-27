<?php
define('BASE_URL', ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/\\') . '/');

require_once dirname(__DIR__) . '/includes/auth.php';
logoutAdmin();
header('Location: ' . BASE_URL . 'login.php');
exit;
