<?php
if (empty($_SESSION['token'])) {
    $_SESSION['token'] = bin2hex(random_bytes(32));
}

$csrf_token = $_SESSION['token'];

function validate_csrf_token($token)
{
    return isset($_SESSION['token']) && hash_equals($_SESSION['token'], $token);
}
