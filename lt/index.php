<?php
require_once '../setting/connect.php';

$custom_url = $_GET['url'] ?? '';

if (empty($custom_url)) {
    header('Location: ' . $link_url);
    exit;
}

if (!preg_match('/^[a-zA-Z0-9_-]+$/', $custom_url)) {
    header('Location: ' . $link_url);
    exit;
}

$sql = "SELECT html_content FROM linktrees WHERE custom_url = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 's', $custom_url);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$linktree = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$linktree) {
    header('Location: ' . $link_url . '#notfound');
    exit;
}

echo $linktree['html_content'];
