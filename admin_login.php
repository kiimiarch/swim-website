<?php
session_start();

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin_login.php");
    exit;
}

// If admin is already logged in, redirect to admin panel
if (isset($_SESSION['admin_id'])) {
    header("Location: admin_panel.php");
    exit;
}

require "db.php";

$error_message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ? AND is_active = 1");
            $stmt->execute([$username]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($admin && password_verify($password, $admin['password'])) {
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_role'] = $admin['role'];

                header("Location: admin_panel.php");
                exit;
            } else {
                $error_message = "نام کاربری یا رمز عبور اشتباه است";
            }
        } catch (Exception $e) {
            $error_message = "خطا در ارتباط با پایگاه داده";
        }
    } else {
        $error_message = "لطفاً نام کاربری و رمز عبور را وارد کنید";
    }
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ورود مدیر - پنل مدیریت</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
            color: #333;
        }

        body {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><rect width="100" height="100" fill="%23f0f9ff"/><circle cx="20" cy="20" r="2" fill="%23c7d2fe" opacity="0.5"/><circle cx="50" cy="50" r="1" fill="%23a5b4fc" opacity="0.3"/><circle cx="80" cy="80" r="1.5" fill="%23c7d2fe" opacity="0.4"/></svg>');
            background-size: 300px;
            background-attachment: fixed;
            padding: 20px;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            padding: 15px;
            font-size: 14px;
            color: #555;
        }

        .footer a {
            color: #3a5f8c;
            text-decoration: none;
            font-weight: 600;
            transition: .3s;
        }

        .footer a:hover {
            text-decoration: underline;
            color: #4a7cb1;
        }

        .auth-wrapper {
            position: relative;
            width: 100%;
            max-width: 75vw; /* 3/4 of viewport width */
            height: 75vh; /* 75% of viewport height */
            min-height: 650px;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 15px 50px rgba(58, 95, 140, 0.3);
            background: #ffffff;
        }

        .auth-wrapper .credentials-panel {
            position: absolute;
            top: 0;
            width: 50%;
            height: 100%;
            display: flex;
            justify-content: center;
            flex-direction: column;
        }

        .credentials-panel.admin-login {
            left: 0;
            padding: 0 40px;
        }

        .credentials-panel.admin-login .slide-element {
            transform: translateX(0%);
            transition: .7s;
            opacity: 1;
        }

        .credentials-panel.admin-login .slide-element:nth-child(1) {
            transition-delay: 2.1s;
        }

        .credentials-panel.admin-login .slide-element:nth-child(2) {
            transition-delay: 2.2s;
        }

        .credentials-panel.admin-login .slide-element:nth-child(3) {
            transition-delay: 2.3s;
        }

        .credentials-panel.admin-login .slide-element:nth-child(4) {
            transition-delay: 2.4s;
        }

        .credentials-panel.admin-login .slide-element:nth-child(5) {
            transition-delay: 2.5s;
        }

        .auth-wrapper.toggled .credentials-panel.admin-login .slide-element {
            transform: translateX(-120%);
            opacity: 0;
        }

        .auth-wrapper.toggled .credentials-panel.admin-login .slide-element:nth-child(1) {
            transition-delay: 0s;
        }

        .auth-wrapper.toggled .credentials-panel.admin-login .slide-element:nth-child(2) {
            transition-delay: 0.1s;
        }

        .auth-wrapper.toggled .credentials-panel.admin-login .slide-element:nth-child(3) {
            transition-delay: 0.2s;
        }

        .auth-wrapper.toggled .credentials-panel.admin-login .slide-element:nth-child(4) {
            transition-delay: 0.3s;
        }

        .auth-wrapper.toggled .credentials-panel.admin-login .slide-element:nth-child(5) {
            transition-delay: 0.4s;
        }

        .admin-welcome-section {
            position: absolute;
            top: 0;
            height: 100%;
            width: 50%;
            display: flex;
            justify-content: center;
            flex-direction: column;
            right: 0;
            text-align: right;
            padding: 0 40px 60px 150px;
            background: linear-gradient(135deg, #f0f4f8 0%, #dfe7f1 100%);
            position: relative;
            overflow: hidden;
        }

        .admin-welcome-section .slide-element {
            transform: translateX(0);
            transition: .7s ease;
            opacity: 1;
            filter: blur(0px);
        }

        .admin-welcome-section .slide-element:nth-child(1) {
            transition-delay: 2.0s;
        }

        .admin-welcome-section .slide-element:nth-child(2) {
            transition-delay: 2.1s;
        }

        .auth-wrapper.toggled .admin-welcome-section .slide-element {
            transform: translateX(120%);
            opacity: 0;
            filter: blur(10px);
        }

        .auth-wrapper.toggled .admin-welcome-section .slide-element:nth-child(1) {
            transition-delay: 0s;
        }

        .auth-wrapper.toggled .admin-welcome-section .slide-element:nth-child(2) {
            transition-delay: 0.1s;
        }

        .credentials-panel h2 {
            font-size: 32px;
            text-align: center;
            color: #3a5f8c;
            margin-bottom: 25px;
            font-weight: 600;
        }

        .credentials-panel .field-wrapper {
            position: relative;
            width: 100%;
            height: 65px;
            margin-top: 25px;
        }

        .field-wrapper input {
            width: 100%;
            height: 100%;
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            outline: none;
            font-size: 16px;
            color: #333;
            font-weight: 500;
            padding: 0 55px 0 20px;
            transition: all 0.3s ease;
        }

        .field-wrapper input:focus,
        .field-wrapper input:valid {
            border-color: #3a5f8c;
            box-shadow: 0 0 0 4px rgba(58, 95, 140, 0.1);
        }

        .field-wrapper label {
            position: absolute;
            top: 50%;
            left: 15px;
            transform: translateY(-50%);
            font-size: 15px;
            color: #64748b;
            transition: all 0.3s ease;
            pointer-events: none;
            font-weight: 500;
        }

        .field-wrapper input:focus~label,
        .field-wrapper input:valid~label {
            top: 5px;
            font-size: 12px;
            color: #3a5f8c;
            background: #fff;
            padding: 0 8px;
            border-radius: 3px;
            font-weight: 600;
        }

        .field-wrapper i {
            position: absolute;
            top: 50%;
            right: 20px;
            transform: translateY(-50%);
            font-size: 18px;
            color: #64748b;
            transition: all 0.3s ease;
        }

        .field-wrapper input:focus~i,
        .field-wrapper input:valid~i {
            color: #3a5f8c;
        }

        .submit-button {
            position: relative;
            width: 100%;
            height: 50px;
            background: linear-gradient(135deg, #3a5f8c 0%, #2a4a70 100%);
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            color: white;
            overflow: hidden;
            z-index: 1;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .submit-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(58, 95, 140, 0.3);
        }

        .switch-link {
            font-size: 15px;
            text-align: center;
            margin: 25px 0 15px;
            font-weight: 500;
        }

        .switch-link a {
            text-decoration: none;
            color: #3a5f8c;
            font-weight: 600;
        }

        .switch-link a:hover {
            text-decoration: underline;
            color: #4a7cb1;
        }

        .admin-welcome-section h2 {
            text-transform: uppercase;
            font-size: 36px;
            line-height: 1.3;
            color: #374151;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .admin-welcome-section p {
            font-size: 16px;
            color: #4b5563;
            line-height: 1.7;
            margin-bottom: 10px;
        }

        .error-message {
            color: #ff6b6b;
            text-align: center;
            margin: 10px 0;
            font-size: 14px;
        }

        .success-message {
            color: #4ade80;
            text-align: center;
            margin: 10px 0;
            font-size: 14px;
        }

        /* Mobile Responsive Styles */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }

            .footer {
                margin-top: 20px;
                font-size: 13px;
            }

            .auth-wrapper {
                height: auto;
                min-height: 500px;
                flex-direction: column;
            }

            .auth-wrapper .credentials-panel,
            .admin-welcome-section {
                width: 100%;
                position: relative;
            }

            .credentials-panel.admin-login {
                padding: 40px 30px;
                left: 0;
                right: 0;
            }

            .credentials-panel.admin-login {
                display: flex;
            }

            .admin-welcome-section {
                display: none;
            }

            .credentials-panel h2 {
                font-size: 28px;
                margin-bottom: 10px;
            }

            .field-wrapper {
                margin-top: 20px;
            }
        }

        @media (max-width: 480px) {

            .credentials-panel.admin-login {
                padding: 30px 20px;
            }

            .credentials-panel h2 {
                font-size: 24px;
            }

            .field-wrapper input,
            .field-wrapper label {
                font-size: 14px;
            }

            .submit-button {
                font-size: 14px;
                height: 40px;
            }

            .switch-link {
                font-size: 13px;
            }
        }
    </style>
    <script>
        function validateForm() {
            const username = document.getElementById('username')?.value.trim() || '';
            const password = document.getElementById('password')?.value.trim() || '';

            // Validate username length
            if (!username || username.length < 3) {
                alert('نام کاربری باید حداقل 3 کاراکتر باشد');
                return false;
            }

            // Validate password length
            if (!password || password.length < 6) {
                alert('رمز عبور باید حداقل 6 کاراکتر باشد');
                return false;
            }

            return true;
        }

        // Initialize the toggle functionality when the DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            const authWrapper = document.querySelector('.auth-wrapper');
            const loginForm = document.querySelector('.admin-login form');

            // Trigger dive animation when login form is submitted
            if(loginForm) {
                loginForm.addEventListener('submit', function(e) {
                    const swimmerContainer = document.querySelector('.swimmer-container');
                    if(swimmerContainer) {
                        swimmerContainer.classList.add('dive-animation');

                        // Reset animation after it completes
                        setTimeout(function() {
                            swimmerContainer.classList.remove('dive-animation');
                        }, 2000);
                    }
                });
            }

            // Username input formatting
            const usernameInput = document.getElementById('username');
            if(usernameInput) {
                usernameInput.addEventListener('input', function(e) {
                    let value = e.target.value.trim();

                    if (value.length > 20) {
                        value = value.substring(0, 20);
                    }

                    e.target.value = value;
                });
            }
        });
    </script>
</head>

<body>
    <div class="auth-wrapper">
        <div class="admin-welcome-section">
            <div class="slide-element">
                <h2>پنل مدیریت</h2>
                <p>به پنل مدیریت سایت خوش آمدید</p>
            </div>
            <div class="slide-element">
                <p>برای دسترسی به امکانات مدیریتی، لطفاً وارد شوید</p>
            </div>
        </div>

        <div class="credentials-panel admin-login">
            <div class="slide-element">
                <h2><i class="fas fa-lock"></i> ورود مدیر</h2>
            </div>

            <form method="POST" onsubmit="return validateForm();" class="slide-element">
                <?php if ($error_message): ?>
                    <div class="slide-element">
                        <div class="error-message">
                            <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="field-wrapper slide-element">
                    <input type="text" name="username" id="username" required>
                    <label>نام کاربری</label>
                    <i class="fas fa-user"></i>
                </div>

                <div class="field-wrapper slide-element">
                    <input type="password" name="password" id="password" required>
                    <label>رمز عبور</label>
                    <i class="fas fa-lock"></i>
                </div>

                <button type="submit" class="submit-button slide-element">ورود</button>

                <div class="switch-link slide-element">
                    <a href="dashboard.php">بازگشت به داشبورد</a>
                </div>

                <div class="slide-element" style="margin-top: 20px; text-align: center; font-size: 14px; color: #666;">
                    کاربر پیش‌فرض: <strong>admin</strong><br>
                    رمز عبور: <strong>admin123</strong>
                </div>
            </form>
        </div>
    </div>
</body>
</html>