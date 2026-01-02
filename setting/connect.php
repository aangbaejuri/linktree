<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Jakarta');

$author_script = "Aang Baejuri";
$date = date("Y-m-d");
$time = date("H:i:s");
$datetime = date("Y-m-d H:i:s");
$timestamp = (int) (microtime(true) * 1000);

$root_path = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/';

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$link_url = $scheme . '://' . $_SERVER['HTTP_HOST'] . '/';

require_once $root_path . 'setup.php';
require_once $root_path . 'setting/csrf_token.php';

$conn = mysqli_connect($db_hostname, $db_username, $db_password, $db_name);

if (!$conn) {
    error_log(mysqli_connect_error());
    die('Koneksi database gagal.');
}
