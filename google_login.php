<?php
require_once "config.php";

// Define base URL
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$base_url = $protocol . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);
$base_url = rtrim($base_url, '/') . '/';

// Google OAuth configuration
$google_client_id = 'YOUR_GOOGLE_CLIENT_ID'; // Replace with your actual Google Client ID
$google_scope = 'email profile';

$redirect = $base_url . "callback.php?provider=google";

$url = 'https://accounts.google.com/o/oauth2/auth?' . http_build_query([
    "client_id" => trim($google_client_id),
    "redirect_uri" => $redirect,
    "response_type" => "code",
    "scope" => $google_scope,
    "access_type" => "online",
    "prompt" => "select_account"
]);

header("Location: $url");
exit;