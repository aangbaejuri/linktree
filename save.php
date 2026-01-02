<?php
ob_start();
require_once 'setting/connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

$deskripsi = trim($_POST['deskripsi'] ?? '');
$custom_url = trim($_POST['custom_url'] ?? '');
$url_logo = trim($_POST['url_logo'] ?? '');
$html_content = $_POST['html_content'] ?? '';
$urls = $_POST['urls'] ?? [];

if (empty($deskripsi) || empty($custom_url) || empty($html_content)) {
    echo json_encode(['success' => false, 'error' => 'Data tidak lengkap']);
    exit;
}

if (!preg_match('/^[a-zA-Z0-9_-]+$/', $custom_url)) {
    echo json_encode(['success' => false, 'error' => 'Custom URL tidak valid']);
    exit;
}

if (!is_array($urls) || count($urls) === 0) {
    echo json_encode(['success' => false, 'error' => 'Minimal harus ada 1 URL']);
    exit;
}

$urls = array_filter($urls, function ($url) {
    return !empty(trim($url));
});

mysqli_begin_transaction($conn);

try {
    $check_sql = "SELECT id FROM linktrees WHERE custom_url = ?";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, 's', $custom_url);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    $existing = mysqli_fetch_assoc($check_result);
    mysqli_stmt_close($check_stmt);

    if ($existing) {
        $update_sql = "UPDATE linktrees SET url_logo = ?, deskripsi = ?, html_content = ?, updated_at = ? WHERE custom_url = ?";
        $update_stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($update_stmt, 'sssss', $url_logo, $deskripsi, $html_content, $datetime, $custom_url);
        mysqli_stmt_execute($update_stmt);
        mysqli_stmt_close($update_stmt);

        $linktree_id = $existing['id'];

        $delete_links_sql = "DELETE FROM linktree_links WHERE linktree_id = ?";
        $delete_stmt = mysqli_prepare($conn, $delete_links_sql);
        mysqli_stmt_bind_param($delete_stmt, 'i', $linktree_id);
        mysqli_stmt_execute($delete_stmt);
        mysqli_stmt_close($delete_stmt);
    } else {
        $insert_sql = "INSERT INTO linktrees (custom_url, url_logo, deskripsi, html_content, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)";
        $insert_stmt = mysqli_prepare($conn, $insert_sql);
        mysqli_stmt_bind_param($insert_stmt, 'ssssss', $custom_url, $url_logo, $deskripsi, $html_content, $datetime, $datetime);
        mysqli_stmt_execute($insert_stmt);
        $linktree_id = mysqli_insert_id($conn);
        mysqli_stmt_close($insert_stmt);
    }

    $insert_link_sql = "INSERT INTO linktree_links (linktree_id, url, urutan, created_at) VALUES (?, ?, ?, ?)";
    $link_stmt = mysqli_prepare($conn, $insert_link_sql);

    foreach ($urls as $index => $url) {
        $url = trim($url);
        if (!empty($url)) {
            $urutan = $index + 1;
            mysqli_stmt_bind_param($link_stmt, 'isis', $linktree_id, $url, $urutan, $datetime);
            mysqli_stmt_execute($link_stmt);
        }
    }
    mysqli_stmt_close($link_stmt);

    mysqli_commit($conn);

    echo json_encode([
        'success' => true,
        'url' => $link_url . 'lt/' . $custom_url
    ]);
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(['success' => false, 'error' => 'Gagal menyimpan data: ' . $e->getMessage()]);
}
