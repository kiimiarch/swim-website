<?php
/**
 * Registration Process Handler
 * Handles user registration and account creation
 */

session_start();
require_once "config.php";
require_once "includes/utils.php";

// Rate limiting: prevent spam registrations
if (!isset($_SESSION['registration_attempts'])) {
    $_SESSION['registration_attempts'] = 0;
    $_SESSION['last_registration_attempt'] = time();
}

// Reset attempts after 15 minutes
if (time() - $_SESSION['last_registration_attempt'] > 900) {
    $_SESSION['registration_attempts'] = 0;
}

// Block if too many attempts
if ($_SESSION['registration_attempts'] >= 10) {
    redirect_with_message('index.php', 'error', 'تعداد تلاش‌های ثبت نام بیش از حد مجاز است. لطفاً ۱۵ دقیقه صبر کنید.');
}

if (!isset($_POST['fullname'], $_POST['phone'], $_POST['password'], $_POST['confirm_password'])) {
    $_SESSION['registration_attempts']++;
    redirect_with_message('index.php', 'error', 'اطلاعات ناقص است');
}

$fullname = trim($_POST['fullname']);
$phone = trim($_POST['phone']);
$password = $_POST['password'];
$confirm_password = $_POST['confirm_password'];

// Validate inputs
if (empty($fullname) || strlen($fullname) < 2) {
    $_SESSION['registration_attempts']++;
    redirect_with_message('index.php', 'error', 'نام و نام خانوادگی باید حداقل ۲ کاراکتر باشد');
}

if (!validate_phone($phone)) {
    $_SESSION['registration_attempts']++;
    redirect_with_message('index.php', 'error', 'شماره موبایل نامعتبر است');
}

if (!validate_password($password)) {
    $_SESSION['registration_attempts']++;
    redirect_with_message('index.php', 'error', 'رمز عبور باید حداقل ۶ کاراکتر باشد');
}

// Validate password match
if ($password !== $confirm_password) {
    $_SESSION['registration_attempts']++;
    redirect_with_message('index.php', 'error', 'رمز عبور و تکرار آن مطابقت ندارند');
}

// Check if phone number already exists
if (user_exists($phone)) {
    $_SESSION['registration_attempts']++;
    redirect_with_message('index.php', 'error', 'این شماره قبلاً ثبت شده است');
}

// Create user
if (create_user($fullname, $phone, $password)) {
    $_SESSION['registration_attempts'] = 0; // Reset attempts on success
    redirect_with_message('index.php', 'success', 'ثبت‌نام موفق بود، لطفاً وارد شوید');
} else {
    $_SESSION['registration_attempts']++;
    redirect_with_message('index.php', 'error', 'خطا در ثبت‌نام');
}