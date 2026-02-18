<?php
require_once "config.php";

// Define base URL
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$base_url = $protocol . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);
$base_url = rtrim($base_url, '/') . '/';

// GitHub OAuth configuration
$github_client_id = 'YOUR_GITHUB_CLIENT_ID'; // Replace with your actual GitHub Client ID
$github_scope = 'user:email';

$redirect = $base_url . "callback.php?provider=github";

$url = 'https://github.com/login/oauth/authorize?' . http_build_query([
    "client_id" => trim($github_client_id),
    "redirect_uri" => $redirect,
    "scope" => $github_scope
]);

header("Location: $url");
exit;