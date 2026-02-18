<?php
session_start();

// If user is logging out, destroy session and redirect to landing page
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: landing.php");
    exit;
}

// Redirect to landing page if not logged in and not coming from landing page
if (!isset($_SESSION['user_id']) && !isset($_GET['from']) && basename($_SERVER['SCRIPT_NAME']) === 'index.php') {
    header("Location: landing.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php if (isset($_SESSION['user_id'])): ?>
        <title>داشبورد | دیزاین ایرانی</title>
    <?php else: ?>
        <title>صفحه ورود | دیزاین ایرانی</title>
    <?php endif; ?>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', 'Vazirmatn', sans-serif;
            color: #333;
        }

        body {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: #0f172a; /* پس‌زمینه تیره */
            padding: 20px;
            overflow-x: hidden;
        }

        /* --- استایل‌های مربوط به Spline Viewer --- */
        spline-viewer {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
        }

        .bg-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.1); /* لایه تاریک بسیار کم */
            z-index: 1;
            pointer-events: none;
        }

        .auth-wrapper {
            position: relative;
            z-index: 5;
            width: 100%;
            max-width: 75vw;
            height: 75vh;
            min-height: 650px;
            border-radius: 25px;
            overflow: hidden;
            /* باکس اصلی فرم‌ها همچنان شیشه‌ای و مات است */
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.18);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
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

        .credentials-panel.signin {
            left: 0;
            padding: 0 40px;
        }

        /* انیمیشن‌های اسلاید */
        .credentials-panel.signin .slide-element { transform: translateX(0%); transition: .7s; opacity: 1; }
        .credentials-panel.signin .slide-element:nth-child(1) { transition-delay: 2.1s; }
        .credentials-panel.signin .slide-element:nth-child(2) { transition-delay: 2.2s; }
        .credentials-panel.signin .slide-element:nth-child(3) { transition-delay: 2.3s; }
        .credentials-panel.signin .slide-element:nth-child(4) { transition-delay: 2.4s; }
        .credentials-panel.signin .slide-element:nth-child(5) { transition-delay: 2.5s; }

        .auth-wrapper.toggled .credentials-panel.signin .slide-element { transform: translateX(-120%); opacity: 0; transition-delay: 0s; }

        .credentials-panel.signup {
            right: 0;
            padding: 0 60px;
        }

        .credentials-panel.signup .slide-element { transform: translateX(120%); transition: .7s ease; opacity: 0; filter: blur(10px); }
        .credentials-panel.signup .slide-element:nth-child(1) { transition-delay: 0s; }
        .credentials-panel.signup .slide-element:nth-child(2) { transition-delay: 0.1s; }
        .credentials-panel.signup .slide-element:nth-child(3) { transition-delay: 0.2s; }
        .credentials-panel.signup .slide-element:nth-child(4) { transition-delay: 0.3s; }
        .credentials-panel.signup .slide-element:nth-child(5) { transition-delay: 0.4s; }
        .credentials-panel.signup .slide-element:nth-child(6) { transition-delay: 0.5s; }

        .auth-wrapper.toggled .credentials-panel.signup .slide-element { transform: translateX(0%); opacity: 1; filter: blur(0px); }
        .auth-wrapper.toggled .credentials-panel.signup .slide-element:nth-child(1) { transition-delay: 1.7s; }
        .auth-wrapper.toggled .credentials-panel.signup .slide-element:nth-child(2) { transition-delay: 1.8s; }
        .auth-wrapper.toggled .credentials-panel.signup .slide-element:nth-child(3) { transition-delay: 1.9s; }
        .auth-wrapper.toggled .credentials-panel.signup .slide-element:nth-child(4) { transition-delay: 1.9s; }
        .auth-wrapper.toggled .credentials-panel.signup .slide-element:nth-child(5) { transition-delay: 2.0s; }
        .auth-wrapper.toggled .credentials-panel.signup .slide-element:nth-child(6) { transition-delay: 2.1s; }

        .credentials-panel h2 {
            font-size: 32px;
            text-align: center;
            color: #fff;
            margin-bottom: 25px;
            font-weight: 600;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
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
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 12px;
            outline: none;
            font-size: 16px;
            color: #fff;
            font-weight: 500;
            padding: 0 55px 0 20px;
            transition: all 0.3s ease;
        }

        .field-wrapper input:focus,
        .field-wrapper input:valid {
            border-color: rgba(255, 255, 255, 0.8);
            background: rgba(255, 255, 255, 0.25);
            box-shadow: 0 0 0 4px rgba(255, 255, 255, 0.1);
        }

        .field-wrapper label {
            position: absolute;
            top: 50%;
            left: 15px;
            transform: translateY(-50%);
            font-size: 15px;
            color: rgba(255, 255, 255, 0.8);
            transition: all 0.3s ease;
            pointer-events: none;
            font-weight: 500;
        }

        .field-wrapper input:focus~label,
        .field-wrapper input:valid~label {
            top: 5px;
            font-size: 12px;
            color: #fff;
            background: rgba(0,0,0,0.2);
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
            color: rgba(255, 255, 255, 0.7);
            transition: all 0.3s ease;
        }

        .field-wrapper input:focus~i,
        .field-wrapper input:valid~i {
            color: #fff;
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
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .submit-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(58, 95, 140, 0.4);
        }

        .switch-link {
            font-size: 15px;
            text-align: center;
            margin: 25px 0 15px;
            font-weight: 500;
            color: rgba(255, 255, 255, 0.9);
        }

        .switch-link a {
            text-decoration: none;
            color: #fff;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
            transition: .3s;
        }

        .switch-link a:hover {
            text-decoration: underline;
            color: #a5b4fc;
        }

        .welcome-section {
            position: absolute;
            top: 0;
            height: 100%;
            width: 50%;
            display: flex;
            justify-content: center;
            flex-direction: column;
        }

        /* --- تغییرات جدید: شیشه‌ای بدون مات برای دیدن وال --- */
        .welcome-section.signin {
            right: 0;
            text-align: right;
            padding: 0 40px 60px 150px;
            background: rgba(255, 255, 255, 0.02); /* بسیار شفاف */
            /* هیچ backdrop-filterای اینجا نیست */
            position: relative;
            overflow: hidden;
        }

        .welcome-section.signin .slide-element { transform: translateX(0); transition: .7s ease; opacity: 1; filter: blur(0px); }
        .welcome-section.signin .slide-element:nth-child(1) { transition-delay: 2.0s; }
        .welcome-section.signin .slide-element:nth-child(2) { transition-delay: 2.1s; }

        .auth-wrapper.toggled .welcome-section.signin .slide-element { transform: translateX(120%); opacity: 0; filter: blur(10px); transition-delay: 0s; }

        .welcome-section.signup {
            left: 0;
            text-align: left;
            padding: 0 150px 60px 38px;
            background: rgba(255, 255, 255, 0.02); /* بسیار شفاف */
            /* هیچ backdrop-filterای اینجا نیست */
        }

        .welcome-section.signup .slide-element { transform: translateX(-120%); transition: .7s ease; opacity: 0; filter: blur(10PX); }
        .welcome-section.signup .slide-element:nth-child(1) { transition-delay: 0s; }
        .welcome-section.signup .slide-element:nth-child(2) { transition-delay: 0.1s; }

        .auth-wrapper.toggled .welcome-section.signup .slide-element { transform: translateX(0%); opacity: 1; filter: blur(0); }
        .auth-wrapper.toggled .welcome-section.signup .slide-element:nth-child(1) { transition-delay: 1.7s; }
        .auth-wrapper.toggled .welcome-section.signup .slide-element:nth-child(2) { transition-delay: 1.8s; }

        .welcome-section h2 {
            text-transform: uppercase;
            font-size: 36px;
            line-height: 1.3;
            color: #fff;
            font-weight: 700;
            margin-bottom: 15px;
            /* سایه قوی‌تر برای خوانایی روی وال */
            text-shadow: 0 4px 10px rgba(0,0,0,0.5);
        }

        .welcome-section p {
            font-size: 16px;
            color: rgba(255, 255, 255, 0.9);
            line-height: 1.7;
            margin-bottom: 10px;
            text-shadow: 0 2px 5px rgba(0,0,0,0.5);
        }

        .forget-password {
            font-size: 14px;
            color: #a5b4fc !important;
            text-shadow: none !important;
        }

        .remember-me {
            color: rgba(255,255,255,0.8) !important;
        }

        .error-message { color: #ff6b6b; text-align: center; margin: 10px 0; font-size: 14px; background: rgba(0,0,0,0.3); padding: 5px; border-radius: 5px; }
        .success-message { color: #4ade80; text-align: center; margin: 10px 0; font-size: 14px; background: rgba(0,0,0,0.3); padding: 5px; border-radius: 5px; }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            body { padding: 10px; }
            .auth-wrapper { height: auto; min-height: 500px; flex-direction: column; background: rgba(15, 23, 42, 0.85); }
            .auth-wrapper .credentials-panel, .welcome-section { width: 100%; position: relative; }
            .credentials-panel.signin, .credentials-panel.signup { padding: 40px 30px; left: 0; right: 0; }
            .credentials-panel.signin { display: flex; }
            .credentials-panel.signup { display: none; }
            .auth-wrapper.toggled .credentials-panel.signin { display: none; }
            .auth-wrapper.toggled .credentials-panel.signup { display: flex; }
            .welcome-section { display: none; }
            .credentials-panel h2 { font-size: 28px; margin-bottom: 10px; }
            .field-wrapper { margin-top: 20px; }
        }
    </style>
    <script>
        function validateForm() {
            const phone = document.getElementById('phone')?.value.trim() || '';
            const password = document.getElementById('password')?.value.trim() || '';

            if (!phone || !/^(09[0-9]{9})$/.test(phone)) {
                alert('لطفا یک شماره موبایل معتبر وارد کنید (09xxxxxxxxx)');
                return false;
            }
            if (!password || password.length < 6) {
                alert('رمز عبور باید حداقل 6 کاراکتر باشد');
                return false;
            }
            return true;
        }

        document.addEventListener('DOMContentLoaded', function() {
            const authWrapper = document.querySelector('.auth-wrapper');
            const loginTrigger = document.querySelector('.login-trigger');
            const registerTrigger = document.querySelector('.register-trigger');
            const phoneInput = document.getElementById('phone');
            const regPhoneInput = document.getElementById('reg_phone');

            // --- Sound Effect Setup ---
            const soundEffect = new Audio('https://assets.mixkit.co/active_storage/sfx/2579/2579-preview.mp3');

            if(registerTrigger) {
                registerTrigger.addEventListener('click', function(e) {
                    soundEffect.currentTime = 0;
                    soundEffect.play().catch(e => console.log("Audio play failed:", e));

                    e.preventDefault();
                    authWrapper.classList.add('toggled');
                });
            }

            if(loginTrigger) {
                loginTrigger.addEventListener('click', function(e) {
                    e.preventDefault();
                    authWrapper.classList.remove('toggled');
                });
            }

            if(phoneInput) {
                phoneInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length > 0) {
                        if (value[0] !== '0') value = '0' + value;
                        if (value.length > 1 && value.substring(0, 2) !== '09') value = '09' + value.substring(2);
                        if (value.length > 11) value = value.substring(0, 11);
                    }
                    e.target.value = value;
                });
            }

            if(regPhoneInput) {
                regPhoneInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length > 0) {
                        if (value[0] !== '0') value = '0' + value;
                        if (value.length > 1 && value.substring(0, 2) !== '09') value = '09' + value.substring(2);
                        if (value.length > 11) value = value.substring(0, 11);
                    }
                    e.target.value = value;
                });
            }
        });

        function validateRegister() {
            const fullname = document.getElementById('reg_fullname')?.value.trim() || '';
            const phone = document.getElementById('reg_phone')?.value.trim() || '';
            const password = document.getElementById('reg_password')?.value || '';
            const confirmPassword = document.getElementById('confirm_password')?.value || '';
            const errorElement = document.getElementById('error') || document.querySelector('#error') || document.createElement('div');
            errorElement.innerHTML = '';

            if (!fullname || fullname.length < 2) { errorElement.innerHTML = 'لطفاً نام و نام خانوادگی خود را به درستی وارد کنید'; return false; }
            if (!phone || !/^(09[0-9]{9})$/.test(phone)) { errorElement.innerHTML = 'لطفا یک شماره موبایل معتبر وارد کنید (09xxxxxxxxx)'; return false; }
            if (!password || password.length < 6) { errorElement.innerHTML = 'رمز عبور باید حداقل 6 کاراکتر باشد'; return false; }
            if (password !== confirmPassword) { errorElement.innerHTML = 'رمز عبور و تکرار آن مطابقت ندارند'; return false; }
            return true;
        }
    </script>
</head>

<body>

<!-- جایگذاری کد ۳بعدی جدید -->
<script type="module" src="https://unpkg.com/@splinetool/viewer@1.12.46/build/spline-viewer.js"></script>
<spline-viewer url="https://prod.spline.design/O89dg5dSFzUuUBoO/scene.splinecode"></spline-viewer>

<div class="bg-overlay"></div>

<?php if (isset($_SESSION['user_id'])): ?>
    <div class="auth-wrapper" style="text-align: center; display: flex; justify-content: center; align-items: center; background: rgba(255,255,255,0.1);">
        <div>
            <h2 style="color: #fff; margin-bottom: 20px;">شما قبلاً وارد شده‌اید</h2>
            <a href="dashboard.php" class="submit-button" style="display: inline-block; width: auto; padding: 10px 30px; text-decoration: none;">رفتن به داشبورد</a>
            <a href="?logout=1" style="display: block; margin-top: 20px; color: #ffadad; text-decoration: none;">خروج از حساب</a>
        </div>
    </div>
<?php else: ?>
    <div class="auth-wrapper">
        <div class="welcome-section signin">
            <div class="slide-element">
                <h2>خوش آمدید</h2>
                <p>برای دسترسی به حساب کاربری خود، لطفاً وارد شوید</p>
            </div>
            <div class="slide-element">
                <p>اگر حساب کاربری ندارید، می توانید با کلیک روی دکمه زیر ثبت نام کنید</p>
            </div>
        </div>

        <div class="welcome-section signup">
            <div class="slide-element">
                <h2>ثبت نام</h2>
                <p>با ثبت نام در سایت می توانید از تمامی امکانات سایت استفاده کنید</p>
            </div>
            <div class="slide-element">
                <p>پس از ثبت نام، ایمیل خود را برای فعالسازی حساب چک کنید</p>
            </div>
        </div>

        <div class="credentials-panel signin">
            <div class="slide-element">
                <h2>ورود</h2>
            </div>
            <form action="login_process.php" method="POST" onsubmit="return validateForm();" class="slide-element">
                <?php if (isset($_SESSION['status']) && isset($_SESSION['message'])): ?>
                    <div class="slide-element">
                        <div class="<?php echo $_SESSION['status'] == 'success' ? 'success-message' : 'error-message'; ?>">
                            <?php
                            echo $_SESSION['message'];
                            unset($_SESSION['status'], $_SESSION['message']);
                            ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="field-wrapper slide-element">
                    <input type="tel" name="phone" id="phone" required pattern="09[0-9]{9}">
                    <label>شماره موبایل</label>
                    <i class="fas fa-phone"></i>
                </div>

                <div class="field-wrapper slide-element">
                    <input type="password" name="password" id="password" placeholder="••••••••" required minlength="6">
                    <label>رمز عبور</label>
                    <i class="fas fa-lock"></i>
                </div>

                <div class="slide-element" style="display: flex; justify-content: space-between; align-items: center; margin-top: 15px;">
                    <label class="remember-me" style="display: flex; align-items: center; gap: 5px; font-size: 14px;">
                        <input type="checkbox" name="remember" style="width: 16px; height: 16px;"> مرا به خاطر بسپار
                    </label>
                    <a href="forget.php" class="forget-password">رمز عبور را فراموش کرده‌اید؟</a>
                </div>

                <button type="submit" class="submit-button slide-element">ورود</button>

                <div class="switch-link slide-element">
                    حساب کاربری ندارید؟ <a href="#" class="register-trigger">ثبت نام کنید</a>
                </div>

                <div class="social-login slide-element" style="margin-top: 30px; text-align: center;">
                    <div style="display: flex; align-items: center; text-align: center; margin-bottom: 20px;">
                        <div style="flex-grow: 1; height: 1px; background: rgba(255,255,255,0.3);"></div>
                        <span style="color: rgba(255,255,255,0.7); font-size: 14px; padding: 0 15px;">با شبکه های اجتماعی وارد شوید</span>
                        <div style="flex-grow: 1; height: 1px; background: rgba(255,255,255,0.3);"></div>
                    </div>
                    <div class="social-icons" style="display: flex; justify-content: center; gap: 20px; margin-top: 15px;">
                        <a href="google_login.php" class="social-icon" style="width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; text-decoration: none; transition: transform 0.3s; background: rgba(255,255,255,0.2); box-shadow: 0 2px 5px rgba(0,0,0,0.2);">
                            <i class="fab fa-google" style="font-size: 20px;"></i>
                        </a>
                        <a href="github_login.php" class="social-icon" style="width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; text-decoration: none; transition: transform 0.3s; background: rgba(255,255,255,0.2); box-shadow: 0 2px 5px rgba(0,0,0,0.2);">
                            <i class="fab fa-github" style="font-size: 20px;"></i>
                        </a>
                        <a href="linkedin_login.php" class="social-icon" style="width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; text-decoration: none; transition: transform 0.3s; background: rgba(255,255,255,0.2); box-shadow: 0 2px 5px rgba(0,0,0,0.2);">
                            <i class="fab fa-linkedin-in" style="font-size: 20px;"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <div class="credentials-panel signup">
            <div class="slide-element">

            </div>
            <form action="register_process.php" method="post" class="slide-element" id="registerForm" onsubmit="return validateRegister();">
                <div class="field-wrapper slide-element">
                    <input type="text" name="fullname" id="reg_fullname" required>
                    <label>نام و نام خانوادگی</label>
                    <i class="fas fa-user"></i>
                </div>

                <div class="field-wrapper slide-element">
                    <input type="tel" name="phone" id="reg_phone" required pattern="09[0-9]{9}">
                    <label>شماره موبایل</label>
                    <i class="fas fa-phone"></i>
                </div>

                <div class="field-wrapper slide-element">
                    <input type="password" name="password" id="reg_password" required>
                    <label>رمز عبور</label>
                    <i class="fas fa-lock"></i>
                </div>

                <div class="field-wrapper slide-element">
                    <input type="password" name="confirm_password" id="confirm_password" required>
                    <label>تکرار رمز عبور</label>
                    <i class="fas fa-lock"></i>
                </div>

                <button type="submit" class="submit-button slide-element">ثبت نام</button>

                <div class="switch-link slide-element">
                    حساب کاربری دارید؟ <a href="#" class="login-trigger">وارد شوید</a>
                </div>

                <div class="social-login slide-element" style="margin-top: 30px; text-align: center;">
                    <div style="display: flex; align-items: center; text-align: center; margin-bottom: 20px;">
                        <div style="flex-grow: 1; height: 1px; background: rgba(255,255,255,0.3);"></div>
                        <span style="color: rgba(255,255,255,0.7); font-size: 14px; padding: 0 15px;">ثبت نام با شبکه های اجتماعی</span>
                        <div style="flex-grow: 1; height: 1px; background: rgba(255,255,255,0.3);"></div>
                    </div>
                    <div class="social-icons" style="display: flex; justify-content: center; gap: 20px; margin-top: 15px;">
                        <a href="google_login.php" class="social-icon" style="width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; text-decoration: none; transition: transform 0.3s; background: rgba(255,255,255,0.2); box-shadow: 0 2px 5px rgba(0,0,0,0.2);">
                            <i class="fab fa-google" style="font-size: 20px;"></i>
                        </a>
                        <a href="github_login.php" class="social-icon" style="width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; text-decoration: none; transition: transform 0.3s; background: rgba(255,255,255,0.2); box-shadow: 0 2px 5px rgba(0,0,0,0.2);">
                            <i class="fab fa-github" style="font-size: 20px;"></i>
                        </a>
                        <a href="linkedin_login.php" class="social-icon" style="width: 50px; height: 50px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; text-decoration: none; transition: transform 0.3s; background: rgba(255,255,255,0.2); box-shadow: 0 2px 5px rgba(0,0,0,0.2);">
                            <i class="fab fa-linkedin-in" style="font-size: 20px;"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

</body>
</html>