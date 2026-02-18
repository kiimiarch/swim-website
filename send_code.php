<?php
session_start();
require "db.php";

$phone = trim($_POST['phone'] ?? '');

if (!$phone) {
    $_SESSION['status'] = "error";
    $_SESSION['message'] = "شماره موبایل وارد نشده";
    header("Location: forget.php");
    exit;
}

// اعتبارسنجی شماره موبایل
if (!preg_match('/^09\d{9}$/', $phone)) {
    $_SESSION['status'] = "error";
    $_SESSION['message'] = "شماره موبایل نامعتبر است";
    header("Location: forget.php");
    exit;
}

// بررسی وجود کاربر
$stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
$stmt->execute([$phone]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    $_SESSION['status'] = "error";
    $_SESSION['message'] = "کاربری با این شماره یافت نشد";
    header("Location: forget.php");
    exit;
}

// تولید کد تصادفی
$code = rand(10000, 99999);

// ذخیره در سشن
$_SESSION['verify_code'] = $code;
$_SESSION['verify_time'] = time();
$_SESSION['phone'] = $phone;
$_SESSION['attempts'] = 0;

// ریدایرکت به صفحه تایید
header("Location: verify.php");
exit;