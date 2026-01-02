<?php
$report_error = false;

if ($report_error) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}

$db_hostname = 'localhost';
$db_name = 'ai_linktree';
$db_username = 'root';
$db_password = '';

$access_token_hf = 'YOUR_HUGGINGFACE_API_TOKEN_HERE';
$access_token_deepseek = 'YOUR_DEEPSEEK_API_TOKEN_HERE';
