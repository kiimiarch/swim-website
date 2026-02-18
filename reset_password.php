<?php
session_start();
require "db.php";

// Check if user is verified
if (!isset($_SESSION['verified']) || !$_SESSION['verified']) {
    $_SESSION['status'] = "error";
    $_SESSION['message'] = "ابتدا باید کد تایید را وارد کنید";
    header("Location: forget.php");
    exit;
}

$phone = $_SESSION['phone'] ?? null;

if (!$phone) {
    $_SESSION['status'] = "error";
    $_SESSION['message'] = "شماره موبایل یافت نشد";
    header("Location: forget.php");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'] ?? '';
    $re_password = $_POST['re_password'] ?? '';

    if ($new_password !== $re_password) {
        $status = "error";
        $message = "رمز عبور و تکرار آن مطابقت ندارند";
    } elseif (strlen($new_password) < 4) {
        $status = "error";
        $message = "رمز عبور باید حداقل ۴ کاراکتر باشد";
    } else {
        // Hash the new password
        $hash = password_hash($new_password, PASSWORD_DEFAULT);

        // Update the password in database
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE phone = ?");
        if ($stmt->execute([$hash, $phone])) {
            // Clear session data
            unset($_SESSION['verified'], $_SESSION['verify_code'], $_SESSION['verify_time'], $_SESSION['phone']);

            $status = "success";
            $message = "رمز عبور با موفقیت تغییر کرد. <a href='index.php' style='color:#155724; text-decoration:underline;'>برای ورود کلیک کنید</a>";

            // Redirect after 2 seconds
            echo "<meta http-equiv='refresh' content='2;url=index.php'>";
        } else {
            $status = "error";
            $message = "خطا در تغییر رمز عبور";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تغییر رمز عبور</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>

<div class="login-container">
    <div class="login-box">

        <h2>تغییر رمز عبور</h2>

        <?php if (isset($message)): ?>
            <div class="message <?= $status; ?>" style="color:<?php echo $status === 'error' ? 'red' : 'green'; ?>; margin-bottom:15px;">
                <?= $message; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="input-group">
                <label>رمز عبور جدید</label>
                <input type="password" name="new_password" placeholder="رمز عبور جدید" required>
            </div>

            <div class="input-group">
                <label>تکرار رمز عبور جدید</label>
                <input type="password" name="re_password" placeholder="تکرار رمز عبور" required>
            </div>

            <button type="submit" class="login-button">تغییر رمز</button>
        </form>

        <div class="register-link">
            <p><a href="index.php">بازگشت به صفحه ورود</a></p>
        </div>
    </div>
</div>

</body>
</html>