<?php
require_once "config.php";

// Define base URL
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$base_url = $protocol . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);
$base_url = rtrim($base_url, '/') . '/';

// LinkedIn OAuth configuration
$linkedin_client_id = 'YOUR_LINKEDIN_CLIENT_ID'; // Replace with your actual LinkedIn Client ID
$linkedin_scope = 'r_emailaddress r_liteprofile';

$redirect = $base_url . "callback.php?provider=linkedin";

$url = 'https://www.linkedin.com/oauth/v2/authorization?' . http_build_query([
    "response_type" => "code",
    "client_id" => trim($linkedin_client_id),
    "redirect_uri" => $redirect,
    "scope" => $linkedin_scope
]);

header("Location: $url");
exit;