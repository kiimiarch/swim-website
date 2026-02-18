<?php
session_start();
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بازیابی رمز عبور</title>
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

        /* استایل کادر فرم شیشه‌ای */
        .forget-card {
            position: relative;
            z-index: 5; 
            width: 100%;
            max-width: 450px; /* عرض مناسب برای این فرم */
            padding: 40px;
            border-radius: 25px;
            overflow: hidden;
            background: rgba(255, 255, 255, 0.08); /* شفافیت شیشه‌ای */
            backdrop-filter: blur(20px); /* افکت مات */
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.18);
            box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
            text-align: center;
            animation: slideUp 0.6s cubic-bezier(0.2, 0.8, 0.2, 1);
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .forget-card h2 {
            font-size: 28px;
            text-align: center;
            color: #fff;
            margin-bottom: 10px;
            font-weight: 600;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .forget-card p {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 25px;
        }

        /* استایل ورودی‌ها */
        .field-wrapper {
            position: relative;
            width: 100%;
            height: 60px;
            margin-top: 20px;
            text-align: right;
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

        /* پیام خطا */
        #error-message {
            background: rgba(0, 0, 0, 0.3);
            color: #ff6b6b;
            padding: 10px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 15px;
            display: none; /* مخفی تا زمانی که پیامی وجود داشته باشد */
            text-align: center;
        }
        
        #error-message:not(:empty) {
            display: block;
        }

        /* دکمه ارسال */
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
            margin-top: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .submit-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(58, 95, 140, 0.4);
        }

        /* لینک بازگشت */
        .back-link {
            margin-top: 25px;
            font-size: 15px;
            color: rgba(255, 255, 255, 0.8);
        }

        .back-link a {
            color: #fff;
            font-weight: 700;
            text-decoration: none;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
            transition: .3s;
        }

        .back-link a:hover {
            text-decoration: underline;
            color: #a5b4fc;
        }

        /* ریسپانسیو */
        @media (max-width: 480px) {
            .forget-card { padding: 30px 20px; }
            .forget-card h2 { font-size: 24px; }
        }
    </style>
</head>

<body>

<!-- کد ۳بعدی مشابه صفحه اصلی -->
<script type="module" src="https://unpkg.com/@splinetool/viewer@1.12.46/build/spline-viewer.js"></script>
<spline-viewer url="https://prod.spline.design/O89dg5dSFzUuUBoO/scene.splinecode"></spline-viewer>
<div class="bg-overlay"></div>

<div class="forget-card">
    <h2>بازیابی رمز عبور</h2>
    <p>شماره موبایل خود را وارد کنید تا کد تایید ارسال شود</p>

    <!-- پیام خطا از PHP -->
    <div id="error-message">
        <?php
        if (isset($_SESSION['status']) && $_SESSION['status'] == 'error' && isset($_SESSION['message'])) {
            echo $_SESSION['message'];
            // پاک کردن پیام بعد از نمایش
            unset($_SESSION['status'], $_SESSION['message']);
        }
        ?>
    </div>

    <form action="send_code.php" method="POST">
        <div class="field-wrapper">
            <input type="tel" name="phone" id="phone" required pattern="09[0-9]{9}">
            <label>شماره موبایل</label>
            <i class="fas fa-phone"></i>
        </div>

        <button type="submit" class="submit-button">ارسال کد تایید</button>
    </form>

    <div class="back-link">
        <p><a href="index.php">بازگشت به صفحه ورود <i class="fas fa-arrow-left"></i></a></p>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const phoneInput = document.getElementById('phone');

        // فرمت خودکار شماره موبایل (09xxxxxxxxx)
        if(phoneInput) {
            phoneInput.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, ''); // حذف غیر اعداد
                
                if (value.length > 0) {
                    if (value[0] !== '0') {
                        value = '0' + value;
                    }
                    if (value.length > 1 && value.substring(0, 2) !== '09') {
                        value = '09' + value.substring(2);
                    }
                    if (value.length > 11) {
                        value = value.substring(0, 11);
                    }
                }
                e.target.value = value;
            });
        }
    });
</script>

</body>
</html>