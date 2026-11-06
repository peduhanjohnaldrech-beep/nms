<?php
// ONE-TIME device token reset — DELETE THIS FILE after use
define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/config/config.php';

$host = $_ENV['DB_HOST'] ?? 'localhost';
$name = $_ENV['DB_NAME'] ?? 'nms';
$user = $_ENV['DB_USER'] ?? 'root';
$pass = $_ENV['DB_PASS'] ?? '';

$pdo = new PDO("mysql:host={$host};dbname={$name}", $user, $pass);
$pdo->exec("UPDATE users SET device_token = NULL WHERE role = 'admin'");
setcookie('nms_device_token', '', time() - 3600, '/');
echo 'Done. Device token cleared — your next login will register this device automatically.<br>';
echo '<a href="' . (defined('APP_URL') ? APP_URL : '/nms/public') . '/login">Go to login</a>';
echo '<br><br><strong>Delete this file (reset_device.php) now!</strong>';
