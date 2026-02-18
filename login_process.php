<?php
/**
 * Login Process Handler
 * Handles user authentication and session management
 */

session_start();
require_once "config.php";
require_once "includes/utils.php";

// Rate limiting: prevent brute force attacks
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt'] = time();
}

// Reset attempts after 15 minutes
if (time() - $_SESSION['last_attempt'] > 900) {
    $_SESSION['login_attempts'] = 0;
}

// Block if too many attempts
if ($_SESSION['login_attempts'] >= 5) {
    redirect_with_message('index.php', 'error', 'تعداد تلاش‌های ناموفق بیش از حد مجاز است. لطفاً ۱۵ دقیقه صبر کنید.');
}

$phone = trim($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';

if (!$phone || !$password) {
    $_SESSION['login_attempts']++;
    redirect_with_message('index.php', 'error', 'شماره موبایل و رمز عبور الزامی است');
}

// Validate phone number format
if (!validate_phone($phone)) {
    $_SESSION['login_attempts']++;
    redirect_with_message('index.php', 'error', 'شماره موبایل نامعتبر است');
}

// Search for user
$stmt = $pdo->prepare("SELECT id, password, fullname, first_name, last_name FROM users WHERE phone = ?");
$stmt->execute([$phone]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    $_SESSION['login_attempts']++;
    $_SESSION['last_attempt'] = time();
    redirect_with_message('index.php', 'error', 'کاربری با این شماره موبایل یافت نشد');
}

// Verify password
if (!password_verify($password, $user['password'])) {
    $_SESSION['login_attempts']++;
    $_SESSION['last_attempt'] = time();
    redirect_with_message('index.php', 'error', 'رمز عبور اشتباه است');
}

// Successful login - reset attempts
$_SESSION['login_attempts'] = 0;
$_SESSION['user_id'] = $user['id'];
$_SESSION['last_login'] = date('Y-m-d H:i:s');

// Update first_name and last_name if they don't exist
if (empty($user['first_name']) || empty($user['last_name'])) {
    $name_parts = explode(' ', $user['fullname'] ?? '', 2);
    $first_name = $name_parts[0] ?? '';
    $last_name = isset($name_parts[1]) ? $name_parts[1] : '';

    $update_stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ? WHERE id = ?");
    $update_stmt->execute([$first_name, $last_name, $user['id']]);
}

// Log successful login
error_log("Successful login for user ID: {$user['id']} at " . date('Y-m-d H:i:s'));

header("Location: dashboard.php");
exit;