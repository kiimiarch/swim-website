<?php
session_start();
require_once "db.php";

// Define base URL
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$base_url = $protocol . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);
$base_url = rtrim($base_url, '/') . '/';

// OAuth configurations
$oauth_configs = [
    'google' => [
        'token_url' => 'https://oauth2.googleapis.com/token',
        'user_url' => 'https://www.googleapis.com/oauth2/v2/userinfo',
        'client_id' => 'YOUR_GOOGLE_CLIENT_ID', // Replace with your actual Google Client ID
        'client_secret' => 'YOUR_GOOGLE_CLIENT_SECRET' // Replace with your actual Google Client Secret
    ],
    'github' => [
        'token_url' => 'https://github.com/login/oauth/access_token',
        'user_url' => 'https://api.github.com/user',
        'client_id' => 'YOUR_GITHUB_CLIENT_ID', // Replace with your actual GitHub Client ID
        'client_secret' => 'YOUR_GITHUB_CLIENT_SECRET' // Replace with your actual GitHub Client Secret
    ],
    'linkedin' => [
        'token_url' => 'https://www.linkedin.com/oauth/v2/accessToken',
        'user_url' => 'https://api.linkedin.com/v2/me',
        'client_id' => 'YOUR_LINKEDIN_CLIENT_ID', // Replace with your actual LinkedIn Client ID
        'client_secret' => 'YOUR_LINKEDIN_CLIENT_SECRET' // Replace with your actual LinkedIn Client Secret
    ]
];

$provider = $_GET['provider'] ?? null;
$code     = $_GET['code'] ?? null;

if (!$provider || !$code) {
    die("Invalid OAuth callback");
}

if (!isset($oauth_configs[$provider])) {
    die("Unknown provider");
}

$p = $oauth_configs[$provider];
$redirect_uri = $base_url . "callback.php?provider=" . $provider;

/* ================================
   1️⃣ دریافت access_token
================================ */
$token_data = http_build_query([
    "client_id"     => $p['client_id'],
    "client_secret" => $p['client_secret'],
    "redirect_uri"  => $redirect_uri,
    "grant_type"    => "authorization_code",
    "code"          => $code
]);

$ch = curl_init($p['token_url']);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $token_data,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => [
        "Accept: application/json"
    ]
]);

$response = curl_exec($ch);
curl_close($ch);

$token = json_decode($response, true);

if (!isset($token['access_token'])) {
    die("Token error");
}

$access_token = $token['access_token'];

/* ================================
   2️⃣ دریافت اطلاعات کاربر
================================ */
$ch = curl_init($p['user_url']);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer " . $access_token,
        "User-Agent: mywebsite"
    ]
]);

$user_response = curl_exec($ch);
curl_close($ch);

$user = json_decode($user_response, true);

/* ================================
   3️⃣ استخراج داده‌ها
================================ */
switch ($provider) {
    case "google":
        $provider_id = $user['id'] ?? null;
        $email       = $user['email'] ?? null;
        $fullname    = $user['name'] ?? null;
        break;

    case "github":
        $provider_id = $user['id'] ?? null;
        $email       = $user['email'] ?? null;
        $fullname    = $user['name'] ?? $user['login'] ?? null;
        break;

    case "linkedin":
        $provider_id = $user['id'] ?? null;
        $fullname    = trim(($user['firstName']['localized']['en_US'] ?? '') . ' ' . ($user['lastName']['localized']['en_US'] ?? ''));

        // Get email separately for LinkedIn
        $email_url = 'https://api.linkedin.com/v2/emailAddress?q=members&projection=(elements*(handle~))';
        $ch = curl_init($email_url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer " . $access_token,
                "User-Agent: mywebsite"
            ]
        ]);

        $email_response = curl_exec($ch);
        curl_close($ch);

        $email_data = json_decode($email_response, true);
        $email = null;
        if (isset($email_data['elements'][0]['handle~']['emailAddress'])) {
            $email = $email_data['elements'][0]['handle~']['emailAddress'];
        }
        break;

    default:
        die("Provider not supported");
}

if (!$provider_id) {
    die("Provider ID missing");
}

/* =================================================
   4️⃣ اگر قبلاً وصل شده → لاگین
================================================= */
$stmt = $pdo->prepare(
    "SELECT id FROM users
     WHERE provider = ? AND provider_id = ?"
);
$stmt->execute([$provider, $provider_id]);
$user_db = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user_db) {
    $_SESSION['user_id'] = $user_db['id'];
    header("Location: dashboard.php");
    exit;
}

/* =================================================
   5️⃣ ادغام با اکانت موبایلی موجود (بر اساس ایمیل)
================================================= */
if ($email) {
    $stmt = $pdo->prepare(
        "SELECT id FROM users WHERE email = ?"
    );
    $stmt->execute([$email]);
    $user_db = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user_db) {
        $stmt = $pdo->prepare(
            "UPDATE users
             SET provider = ?, provider_id = ?
             WHERE id = ?"
        );
        $stmt->execute([$provider, $provider_id, $user_db['id']]);

        $_SESSION['user_id'] = $user_db['id'];
        header("Location: dashboard.php");
        exit;
    }
}

/* =================================================
   6️⃣ ساخت اکانت جدید
================================================= */
$stmt = $pdo->prepare(
    "INSERT INTO users (fullname, email, provider, provider_id)
     VALUES (?, ?, ?, ?)"
);
$stmt->execute([$fullname, $email, $provider, $provider_id]);

$_SESSION['user_id'] = $pdo->lastInsertId();

/* ورود موفق کاربر جدید */
header("Location: dashboard.php");
exit;