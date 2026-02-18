<?php
session_start();
require "db.php";

$status = $_SESSION['status'] ?? null;
$message = $_SESSION['message'] ?? null;
unset($_SESSION['status'], $_SESSION['message']);

$show_reset_form = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['new_password'], $_POST['re_password']) && ($_SESSION['verified'] ?? false)) {
        $new = trim($_POST['new_password']);
        $re  = trim($_POST['re_password']);
        $phone = $_SESSION['phone'] ?? null;

        if (!$phone) {
            $status = "error"; $message = "درخواست نامعتبر";
        } elseif ($new !== $re) {
            $status = "error"; $message = "رمزها یکسان نیستند";
        } elseif (strlen($new) < 6) {
            $status = "error"; $message = "حداقل ۶ کاراکتر";
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password=? WHERE phone=?");
            if ($stmt->execute([$hash, $phone])) {
                $status = "success";
                $message = "رمز عبور با موفقیت تغییر کرد";
                session_destroy();
                echo '<script>setTimeout(()=>location.href="index.php",2000)</script>';
            }
        }
    }

    elseif (isset($_POST['code'])) {
        $code = trim($_POST['code']);

        if (!isset($_SESSION['verify_code'])) {
            $status = "error"; $message = "درخواست نامعتبر";
        } elseif (time() - $_SESSION['verify_time'] > 120) {
            session_destroy();
            $status = "error"; $message = "کد منقضی شده";
        } elseif ($code == $_SESSION['verify_code']) {
            $_SESSION['verified'] = true;
            $_SESSION['status'] = 'success';
            $_SESSION['message'] = 'کد صحیح است، رمز جدید را وارد کنید';
            header("Location: verify.php"); exit;
        } else {
            $status = "error"; $message = "کد اشتباه است";
        }
    }
}

if ($_SESSION['verified'] ?? false) $show_reset_form = true;
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<title>تایید کد</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&family=Vazirmatn:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:Poppins,Vazirmatn}
body{
    min-height:100vh;display:flex;justify-content:center;align-items:center;
    background:#0f172a;overflow:hidden
}
spline-viewer{
    position:fixed;top:0;left:0;width:100%;height:100%;z-index:0
}
.overlay{
    position:fixed;inset:0;background:rgba(0,0,0,.15);z-index:1
}
.card{
    z-index:5;width:100%;max-width:420px;
    padding:40px;border-radius:25px;
    background:rgba(255,255,255,.08);
    backdrop-filter:blur(20px);
    border:1px solid rgba(255,255,255,.2);
    box-shadow:0 8px 32px rgba(0,0,0,.4);
    animation:up .6s ease
}
@keyframes up{from{opacity:0;transform:translateY(30px)}to{opacity:1}}

h2{color:#fff;text-align:center;margin-bottom:10px}
p{color:rgba(255,255,255,.8);text-align:center;margin-bottom:20px;font-size:14px}

.field{
    position:relative;height:60px;margin-top:20px
}
.field input{
    width:100%;height:100%;
    background:rgba(255,255,255,.15);
    border:1px solid rgba(255,255,255,.3);
    border-radius:12px;
    padding:0 50px 0 20px;
    color:#fff;font-size:16px
}
.field label{
    position:absolute;top:50%;right:20px;
    transform:translateY(-50%);
    color:rgba(255,255,255,.7);
    transition:.3s
}
.field input:focus+label,
.field input:valid+label{
    top:5px;font-size:12px;color:#fff
}
.field i{
    position:absolute;left:20px;top:50%;
    transform:translateY(-50%);color:#fff
}

button{
    width:100%;height:50px;margin-top:25px;
    border:none;border-radius:12px;
    background:linear-gradient(135deg,#3a5f8c,#2a4a70);
    color:#fff;font-size:16px;font-weight:600;
    cursor:pointer
}
button:hover{transform:translateY(-2px)}

.msg{
    padding:10px;border-radius:8px;margin-bottom:15px;
    font-size:14px;text-align:center
}
.error{background:rgba(0,0,0,.3);color:#ff6b6b}
.success{background:rgba(0,0,0,.3);color:#4ade80}

a{color:#fff;text-decoration:none;font-weight:600}
.back{text-align:center;margin-top:20px}
</style>
</head>

<body>

<script type="module" src="https://unpkg.com/@splinetool/viewer/build/spline-viewer.js"></script>
<spline-viewer url="https://prod.spline.design/O89dg5dSFzUuUBoO/scene.splinecode"></spline-viewer>
<div class="overlay"></div>

<div class="card">
<h2><?php echo $show_reset_form?'تغییر رمز عبور':'تایید کد'; ?></h2>

<p>
<?php
echo $show_reset_form
?'رمز جدید را وارد کنید'
:'کد ارسال‌شده به شماره شما';
?>
</p>

<?php if($message): ?>
<div class="msg <?php echo $status; ?>"><?php echo $message; ?></div>
<?php endif; ?>

<form method="POST">
<?php if($show_reset_form): ?>
<div class="field">
<input type="password" name="new_password" required minlength="6">
<label>رمز عبور جدید</label><i class="fas fa-lock"></i>
</div>
<div class="field">
<input type="password" name="re_password" required minlength="6">
<label>تکرار رمز عبور</label><i class="fas fa-lock"></i>
</div>
<button>تغییر رمز</button>
<?php else: ?>
<div class="field">
<input type="text" name="code" maxlength="4" required pattern="\d{4}">
<label>کد تایید</label><i class="fas fa-key"></i>
</div>
<button>تایید کد</button>
<?php endif; ?>
</form>

<div class="back"><a href="index.php">بازگشت به ورود</a></div>
</div>

</body>
</html>