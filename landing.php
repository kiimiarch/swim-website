<?php
session_start();
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>آکادمی شنا حرفه‌ای | تجربه لوکس</title>
    
    <!-- فونت‌های لوکس -->
    <link href="https://cdn.fontcdn.ir/Font/Peykan/Peykan.woff2" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        @font-face {
            font-family: 'Peykan';
            src: url('https://cdn.fontcdn.ir/Font/Peykan/Peykan.woff2') format('woff2');
            font-weight: normal;
            font-style: normal;
        }

        :root {
            --primary: #0e7490;
            --accent: #fbbf24;
            --text-dark: #1e293b;
            --text-light: #64748b;
            --glass-bg: rgba(255,255, 255, 0.6); 
            --glass-hover: rgba(255,255,255, 0.8);
            --glass-border: rgba(255,255,255, 0.6);
            --shadow-luxury: 0 20px 60px -10px rgba(14, 116, 144, 0.15);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', 'Peykan', sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #e0f2fe 100%);
            color: var(--text-dark);
            overflow-x: hidden;
            line-height: 1.7;
        }

        spline-viewer {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
            opacity: 0.4;
            pointer-events: auto;
        }

        .texture-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background-image: radial-gradient(#cbd5e1 1px, transparent 1px);
            background-size: 40px 40px;
            opacity: 0.2;
            pointer-events: none;
        }

        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            padding: 20px 50px;
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            transition: all 0.5s cubic-bezier(0.19, 1, 0.22, 1);
            border-bottom: 1px solid transparent;
        }

        .navbar.scrolled {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            padding: 15px 50px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            border-bottom: 1px solid rgba(255,255,255,0.5);
        }

        .logo {
            font-family: 'Peykan', sans-serif;
            font-size: 2.2rem;
            font-weight: normal;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn {
            padding: 14px 36px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: bold;
            font-family: 'Peykan', sans-serif;
            font-size: 1.1rem;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }

        .btn::after {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 0%; height: 100%;
            background: rgba(255,255,255,0.2);
            transition: width 0.3s;
            pointer-events: none;
        }

        .btn:hover::after { width: 100%; }

        .btn-primary {
            background: var(--primary);
            color: white;
            box-shadow: 0 8px 20px rgba(14, 116, 144, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-4px);
            box-shadow: 0 15px 30px rgba(14, 116, 144, 0.4);
        }

        .btn-outline {
            background: transparent;
            border: 1px solid var(--text-dark);
            color: var(--text-dark);
        }

        .btn-outline:hover {
            background: var(--text-dark);
            color: white;
        }

        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .hero {
            padding: 200px 0 120px;
            text-align: center;
            position: relative;
        }

        .hero h1 {
            font-family: 'Peykan', sans-serif;
            font-size: 5rem;
            color: var(--text-dark);
            line-height: 1.4;
            margin-bottom: 30px;
            opacity: 0;
            animation: slideUpFade 1.2s cubic-bezier(0.2, 0.8, 0.2, 1) forwards 0.2s;
        }

        .hero h1 span {
            color: var(--primary);
            display: inline-block;
            position: relative;
        }
        
        .hero h1 span::after {
            content: '';
            position: absolute;
            width: 0;
            height: 3px;
            bottom: 5px;
            left: 0;
            background: var(--accent);
            animation: lineExpand 1.5s ease forwards 1s;
        }

        .hero p {
            font-family: 'Vazirmatn', sans-serif;
            font-size: 1.3rem;
            color: var(--text-light);
            max-width: 750px;
            margin: 0 auto 50px;
            opacity: 0;
            animation: slideUpFade 1.2s cubic-bezier(0.2, 0.8, 0.2, 1) forwards 0.4s;
        }

        .hero-btns {
            opacity: 0;
            animation: slideUpFade 1.2s cubic-bezier(0.2, 0.8, 0.2, 1) forwards 0.6s;
            display: flex;
            justify-content: center;
            gap: 25px;
        }

        .card {
            background: var(--glass-bg);
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            border: 1px solid var(--glass-border);
            padding: 40px; /* پدینگ کمی کاهش یافت */
            border-radius: 30px;
            box-shadow: var(--shadow-luxury);
            transition: all 0.5s cubic-bezier(0.19, 1, 0.22, 1);
            position: relative;
            overflow: hidden;
            opacity: 0;
            animation: cardEntrance 0.8s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
            display: flex;
            flex-direction: column; /* قرارگیری عمودی محتوا */
        }

        .card:hover {
            transform: translateY(-15px);
            background: var(--glass-hover);
            box-shadow: 0 30px 70px -15px rgba(14, 116, 144, 0.25);
            border-color: rgba(255,255,255, 1);
        }
        
        .card::before {
            content: '';
            position: absolute;
            top: 0; left: -100%; width: 50%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            transform: skewX(-25deg);
            transition: 0.7s;
            pointer-events: none;
        }
        .card:hover::before {
            left: 150%;
        }

        .grid-3 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 40px;
            /* --- اضافه شده: ارتفاع کارت‌ها یکسان شود --- */
            align-items: stretch; 
        }

        .features { padding: 100px 0; }
        .section-header {
            text-align: center;
            margin-bottom: 80px;
        }
        .section-header h2 {
            font-family: 'Peykan', sans-serif;
            font-size: 3.5rem;
            color: var(--text-dark);
            margin-bottom: 20px;
        }
        .section-header .divider {
            width: 80px;
            height: 4px;
            background: linear-gradient(to right, transparent, var(--primary), transparent);
            margin: 0 auto;
        }

        .card-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #fff 0%, #f1f5f9 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 30px;
            box-shadow: 0 10px 25px rgba(14, 116, 144, 0.1);
            transition: transform 0.5s ease;
        }
        .card:hover .card-icon { transform: scale(1.1) rotate(5deg); }

        .card h3 {
            font-family: 'Peykan', sans-serif;
            font-size: 2rem;
            margin-bottom: 20px;
            color: var(--text-dark);
        }
        .card ul { 
            list-style: none; 
            flex-grow: 1; /* --- اضافه شده: لیست فضا را پر کند --- */
            display: flex;
            flex-direction: column;
            justify-content: center; /* --- اضافه شده: لیست وسط چین شود --- */
        }
        .card li {
            margin-bottom: 15px;
            color: var(--text-light);
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1rem;
            font-family: 'Vazirmatn', sans-serif;
        }
        .card li i { color: var(--accent); font-size: 0.9rem; transition: 0.3s; }
        .card:hover li i { transform: scale(1.2); }

        .pricing { padding: 120px 0; background: rgba(255,255,255,0.2); }
        .pricing-card { text-align: center; display: flex; flex-direction: column; }

        .pricing-card.featured {
            background: #fff;
            border: 2px solid var(--primary);
            transform: scale(1.05);
            z-index: 2;
            box-shadow: 0 30px 60px rgba(14, 116, 144, 0.2);
        }
        .pricing-card.featured:hover { transform: scale(1.08) translateY(-10px); }

        .price-tag {
            font-family: 'Peykan', sans-serif;
            font-size: 4rem;
            font-weight: normal;
            color: var(--text-dark);
            margin: 20px 0; /* کمی کاهش مارجین */
        }
        .price-tag span {
            font-family: 'Vazirmatn', sans-serif;
            font-size: 1rem;
            color: var(--text-light);
            font-weight: 400;
        }
        
        .badge {
            position: absolute;
            top: 30px; left: 50%;
            transform: translateX(-50%);
            background: var(--accent);
            color: #fff;
            padding: 8px 25px;
            border-radius: 30px;
            font-size: 0.9rem;
            font-weight: 700;
            box-shadow: 0 5px 15px rgba(251, 191, 36, 0.4);
            font-family: 'Vazirmatn', sans-serif;
        }

        footer {
            background: var(--text-dark);
            color: white;
            padding: 80px 0 30px;
            text-align: center;
        }

        @keyframes slideUpFade {
            0% { opacity: 0; transform: translateY(40px); }
            100% { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes cardEntrance {
            0% { opacity: 0; transform: translateY(50px) scale(0.95); }
            100% { opacity: 1; transform: translateY(0) scale(1); }
        }
        
        @keyframes lineExpand {
            0% { width: 0; }
            100% { width: 100%; }
        }

        .card:nth-child(1) { animation-delay: 0.1s; }
        .card:nth-child(2) { animation-delay: 0.3s; }
        .card:nth-child(3) { animation-delay: 0.5s; }

        @media (max-width: 768px) {
            .hero h1 { font-size: 3rem; }
            .navbar { padding: 15px 20px; }
            .pricing-card.featured { transform: none; }
            .pricing-card.featured:hover { transform: translateY(-5px); }
            .section-header h2 { font-size: 2.5rem; }
        }
    </style>
</head>

<body>

    <script type="module" src="https://unpkg.com/@splinetool/viewer@1.12.46/build/spline-viewer.js"></script>
    <spline-viewer url="https://prod.spline.design/PdvlUbR1B-rlBPUk/scene.splinecode"></spline-viewer>
    <div class="texture-overlay"></div>

    <!-- هدر -->
    <nav class="navbar" id="navbar">
        <div class="logo">
            <i class="fas fa-water"></i> آکادمی شنا
        </div>
    </nav>

    <section class="container hero">
        <h1>هنر شنا را<br><span>با ما تجربه کنید</span></h1>
        <p>آکادمی شنا با استانداردهای جهانی. مربیان حرفه‌ای، محیط لوکس و متدهای نوین آموزش برای کودکان و بزرگسالان.</p>
        <div class="hero-btns">
            <a href="#pricing" class="btn btn-primary">مشاهده پلن‌ها</a>
            <a href="#features" class="btn btn-outline">چرا ما؟</a>
        </div>
    </section>

    <section class="container features" id="features">
        <div class="section-header">
            <h2>خدمات ویژه</h2>
            <div class="divider"></div>
        </div>

        <div class="grid-3">
            <div class="card">
                <div class="card-icon"><i class="fas fa-baby-carriage"></i></div>
                <h3>کلاس‌های کودک و نوجوان</h3>
                <ul>
                    <li><i class="fas fa-check"></i> آموزش با بازی و سرگرمی</li>
                    <li><i class="fas fa-check"></i> مربیان مخصوص کودک</li>
                    <li><i class="fas fa-check"></i> استخر کودکان مجزا</li>
                    <li><i class="fas fa-check"></i> اب درمانی برای نوزادان</li>
                </ul>
            </div>

            <div class="card">
                <div class="card-icon"><i class="fas fa-swimmer"></i></div>
                <h3>کلاس‌های تخصصی بزرگسال</h3>
                <ul>
                    <li><i class="fas fa-check"></i> اصلاح تکنیک شنا</li>
                    <li><i class="fas fa-check"></i> بدنسازی در آب</li>
                    <li><i class="fas fa-check"></i> شنا درمانی</li>
                    <li><i class="fas fa-check"></i> آمادگی جسمانی ویژه</li>
                </ul>
            </div>

            <div class="card">
                <div class="card-icon"><i class="fas fa-star"></i></div>
                <h3>مزایای آکادمی</h3>
                <ul>
                    <li><i class="fas fa-check"></i> گواهینامه معتبر جهانی</li>
                    <li><i class="fas fa-check"></i> محیط کاملاً استریل و لوکس</li>
                    <li><i class="fas fa-check"></i> آزمایش آب کیفیت استخر</li>
                    <li><i class="fas fa-check"></i> مشاوره تغذیه رایگان</li>
                </ul>
            </div>
        </div>
    </section>

    <section class="pricing" id="pricing">
        <div class="container">
            <div class="section-header">
                <h2>تعرفه‌های آموزشی</h2>
                <div class="divider"></div>
            </div>

            <div class="grid-3">
                <!-- پلن پایه -->
                <div class="card pricing-card">
                    <h3>پایه</h3>
                    <div class="price-tag">1,500,000 <span>تومان</span></div>
                    <ul style="margin-bottom: 30px; list-style: none;">
                        <li style="padding: 12px 0; border-bottom: 1px solid #eee;">کلاس‌های عمومی</li>
                        <li style="padding: 12px 0; border-bottom: 1px solid #eee;">۲۰ جلسه</li>
                        <li style="padding: 12px 0; border-bottom: 1px solid #eee;">ساعت‌های محدود</li>
                        <li style="padding: 12px 0; border-bottom: 1px solid #eee;">گواهی پایان دوره</li>
                    </ul>
                    <a href="index.php?from=landing" class="btn btn-outline" style="margin-top: auto;">انتخاب</a>
                </div>

                <!-- پلن نیمه خصوصی -->
                <div class="card pricing-card featured">
                    <div class="badge"></div>
                    <h3>نیمه خصوصی</h3>
                    <div class="price-tag" style="color: var(--primary);">3,800,000 <span>تومان</span></div>
                    <ul style="margin-bottom: 30px; list-style: none;">
                        <li style="padding: 12px 0; border-bottom: 1px solid #eee;"><strong>۲ تا ۴ نفر</strong></li>
                        <li style="padding: 12px 0; border-bottom: 1px solid #eee;">تمرین ۳۰ دقیقه اضافه</li>
                        <li style="padding: 12px 0; border-bottom: 1px solid #eee;">برنامه هفتگی منظم</li>
                        <li style="padding: 12px 0; border-bottom: 1px solid #eee;">گواهینامه تخصصی</li>
                        <li style="padding: 12px 0; border-bottom: 1px solid #eee;">تضمین یادگیری</li>
                    </ul>
                    <a href="index.php?from=landing" class="btn btn-primary" style="margin-top: auto;">ثبت نام ویژه</a>
                </div>

                <!-- پلن خصوصی -->
                <div class="card pricing-card">
                    <h3>خصوصی</h3>
                    <div class="price-tag">5,000,000 <span>تومان</span></div>
                    <ul style="margin-bottom: 30px; list-style: none;">
                        <li style="padding: 12px 0; border-bottom: 1px solid #eee;"><strong>یک نفر</strong></li>
                        <li style="padding: 12px 0; border-bottom: 1px solid #eee;">زمان‌بندی انعطاف‌پذیر</li>
                        <li style="padding: 12px 0; border-bottom: 1px solid #eee;">تمرین با مربی اختصاصی</li>
                        <li style="padding: 12px 0; border-bottom: 1px solid #eee;">حمایت تغذیه‌ای</li>
                        <li style="padding: 12px 0; border-bottom: 1px solid #eee;">گواهینامه حرفه‌ای</li>
                    </ul>
                    <a href="index.php?from=landing" class="btn btn-outline" style="margin-top: auto;">انتخاب</a>
                </div>
            </div>
        </div>
    </section>

    <section class="container" style="text-align: center; padding: 100px 20px;">
        <div class="card" style="max-width: 900px; margin: 0 auto; padding: 80px;">
            <h2 style="font-family: 'Peykan', sans-serif; font-size: 3rem; margin-bottom: 20px;">آماده شروع هستید؟</h2>
            <p style="color: var(--text-light); font-size: 1.2rem; margin-bottom: 40px;">
                اولین قدم را همین امروز بردارید. ما در آکادمی منتظر شما هستیم تا بهترین تجربه شنا را رقم بزنیم.
            </p>
            <a href="index.php?from=landing" class="btn btn-primary" style="padding: 18px 60px; font-size: 1.2rem;">
                همین حالا ثبت نام کنید
            </a>
        </div>
    </section>

    <footer>
        <div class="container">
            <h3 style="font-family: 'Peykan', sans-serif; margin-bottom: 10px; font-size: 1.8rem;">آکادمی شنا</h3>
            <p style="color: rgba(255,255,255,0.5); font-size: 0.9rem; font-family: 'Vazirmatn', sans-serif;">تمامی حقوق برای آکادمی شنا محفوظ است © ۲۰۲۳</p>
        </div>
    </footer>

    <script>
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({ behavior: 'smooth' });
            });
        });

        const navbar = document.getElementById('navbar');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    </script>
</body>
</html>