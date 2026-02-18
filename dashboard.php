<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

require "db.php";

// Get user information
 $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
 $stmt->execute([$_SESSION['user_id']]);
 $user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: index.php");
    exit;
}

// --- Logic Section ---

// Check if required tables exist
 $tablesExist = [
    'sessions' => false,
    'scheduled_classes' => false,
    'orders' => false
];

try { $stmt = $pdo->query("SELECT 1 FROM sessions LIMIT 1"); $tablesExist['sessions'] = true; } catch (PDOException $e) {}
try { $stmt = $pdo->query("SELECT 1 FROM scheduled_classes LIMIT 1"); $tablesExist['scheduled_classes'] = true; } catch (PDOException $e) {}
try { $stmt = $pdo->query("SELECT 1 FROM orders LIMIT 1"); $tablesExist['orders'] = true; } catch (PDOException $e) {}

// Get sessions stats
 $attended_sessions = 0;
 $remaining_sessions = 0;
 $scheduled_classes = [];

if ($tablesExist['sessions']) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM sessions WHERE user_id = ? AND status = 'attended'");
        $stmt->execute([$_SESSION['user_id']]);
        $attended_sessions = $stmt->fetchColumn();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM sessions WHERE user_id = ? AND status != 'attended'");
        $stmt->execute([$_SESSION['user_id']]);
        $remaining_sessions = $stmt->fetchColumn();
    } catch (Exception $e) {}
} else {
    $attended_sessions = 0;
    $remaining_sessions = 10;
}

if ($tablesExist['scheduled_classes']) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM scheduled_classes WHERE user_id = ? ORDER BY date_time ASC LIMIT 5");
        $stmt->execute([$_SESSION['user_id']]);
        $scheduled_classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $date_of_birth = $_POST['date_of_birth_gregorian'] ?? '';
    $national_id = trim($_POST['national_id'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if (!empty($phone)) {
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE phone = ? AND id != ?");
        $checkStmt->execute([$phone, $_SESSION['user_id']]);
        if ($checkStmt->fetch()) {
            $_SESSION['status'] = 'error'; $_SESSION['message'] = 'شماره تلفن تکراری است.';
            header("Location: dashboard.php"); exit;
        }
    }
    if (!empty($email)) {
        $checkEmailStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $checkEmailStmt->execute([$email, $_SESSION['user_id']]);
        if ($checkEmailStmt->fetch()) {
            $_SESSION['status'] = 'error'; $_SESSION['message'] = 'ایمیل تکراری است.';
            header("Location: dashboard.php"); exit;
        }
    }

    $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, date_of_birth = ?, national_id = ?, phone = ?, email = ?, address = ? WHERE id = ?");
    $stmt->execute([$first_name, $last_name, $date_of_birth, $national_id, $phone, $email, $address, $_SESSION['user_id']]);

    $_SESSION['status'] = 'success'; $_SESSION['message'] = 'پروفایل به‌روز شد.';
    header("Location: dashboard.php"); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_new_password = $_POST['confirm_new_password'] ?? '';

    if (empty($current_password) || empty($new_password) || empty($confirm_new_password)) {
        $_SESSION['status'] = 'error'; $_SESSION['message'] = 'لطفاً تمامی فیلدها را پر کنید';
    } elseif ($new_password !== $confirm_new_password) {
        $_SESSION['status'] = 'error'; $_SESSION['message'] = 'رمز عبور جدید و تکرار آن مطابقت ندارند';
    } elseif (strlen($new_password) < 6) {
        $_SESSION['status'] = 'error'; $_SESSION['message'] = 'رمز عبور باید حداقل ۶ کاراکتر باشد';
    } else {
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user_check = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user_check && password_verify($current_password, $user_check['password'])) {
            $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed_new_password, $_SESSION['user_id']]);
            $_SESSION['status'] = 'success'; $_SESSION['message'] = 'رمز عبور تغییر یافت';
        } else {
            $_SESSION['status'] = 'error'; $_SESSION['message'] = 'رمز عبور فعلی اشتباه است';
        }
    }
    header("Location: dashboard.php"); exit;
}

// Course Registration Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_course'])) {
    $course_id = $_POST['course_id'] ?? '';
    try {
        $stmt = $pdo->prepare("SELECT id FROM user_courses WHERE user_id = ? AND course_id = ?");
        $stmt->execute([$_SESSION['user_id'], $course_id]);
        if ($stmt->fetch()) {
            $_SESSION['status'] = 'error'; $_SESSION['message'] = 'شما قبلاً در این دوره ثبت نام کرده‌اید.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO user_courses (user_id, course_id, status) VALUES (?, ?, 'pending')");
            $stmt->execute([$_SESSION['user_id'], $course_id]);
            $_SESSION['status'] = 'success'; $_SESSION['message'] = 'درخواست ثبت نام ارسال شد.';
        }
    } catch (Exception $e) {
        $_SESSION['status'] = 'error'; $_SESSION['message'] = 'خطا در ثبت نام';
    }
    header("Location: dashboard.php"); exit;
}

// Medical Records Logic
 $medical_record = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM medical_records WHERE user_id = ? ORDER BY uploaded_at DESC LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $medical_record = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS medical_records (
            id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL,
            medical_conditions TEXT, certificate_path VARCHAR(500),
            uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )");
    } catch (Exception $e2) {}
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_medical_records'])) {
    $medical_conditions = $_POST['medical_conditions'] ?? '';
    $certificate_path = $medical_record['certificate_path'] ?? null;

    if (isset($_FILES['medical_certificate']) && $_FILES['medical_certificate']['error'] == 0) {
        $allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png'];
        $file_extension = strtolower(pathinfo($_FILES['medical_certificate']['name'], PATHINFO_EXTENSION));
        if (in_array($file_extension, $allowed_extensions)) {
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            $filename = 'medical_cert_' . $_SESSION['user_id'] . '_' . time() . '.' . $file_extension;
            if (move_uploaded_file($_FILES['medical_certificate']['tmp_name'], $upload_dir . $filename)) {
                $certificate_path = $upload_dir . $filename;
            }
        }
    }

    try {
        if ($medical_record) {
            $stmt = $pdo->prepare("UPDATE medical_records SET medical_conditions = ?, certificate_path = ? WHERE user_id = ?");
            $stmt->execute([$medical_conditions, $certificate_path, $_SESSION['user_id']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO medical_records (user_id, medical_conditions, certificate_path) VALUES (?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $medical_conditions, $certificate_path]);
        }
        $_SESSION['status'] = 'success'; $_SESSION['message'] = 'سوابق پزشکی به‌روز شد.';
    } catch (Exception $e) {
        $_SESSION['status'] = 'error'; $_SESSION['message'] = 'خطا در ذخیره سوابق.';
    }
    header("Location: dashboard.php"); exit;
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>داشبورد کاربری | آکادمی شنا</title>

    <!-- فونت‌ها -->
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
            --primary-dark: #155e75;
            --accent: #fbbf24;
            --text-dark: #1e293b;
            --text-light: #64748b;
            --glass-bg: rgba(255,255, 255, 0.7);
            --glass-hover: rgba(255,255,255, 0.9);
            --glass-border: rgba(255,255,255, 0.5);
            --shadow-soft: 0 10px 40px -10px rgba(14, 116, 144, 0.1);
            --shadow-luxury: 0 20px 60px -15px rgba(14, 116, 144, 0.2);
            --radius: 20px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            outline: none;
        }

        body {
            font-family: 'Plus Jakarta Sans', 'Vazirmatn', sans-serif;
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            color: var(--text-dark);
            min-height: 100vh;
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* Background Effects */
        .bg-orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            z-index: -1;
            opacity: 0.4;
            animation: float 10s infinite ease-in-out;
        }
        .orb-1 { top: -10%; right: -10%; width: 500px; height: 500px; background: #bae6fd; }
        .orb-2 { bottom: -10%; left: -10%; width: 600px; height: 600px; background: #e0f2fe; animation-delay: 2s; }

        @keyframes float {
            0%, 100% { transform: translate(0, 0); }
            50% { transform: translate(20px, 40px); }
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 280px;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-left: 1px solid rgba(255,255,255,0.6);
            padding: 30px 0;
            position: fixed;
            right: 0;
            top: 0;
            height: 100vh;
            z-index: 100;
            transition: transform 0.3s ease;
        }

        .sidebar-header {
            text-align: center;
            margin-bottom: 40px;
            padding: 0 20px;
        }

        .logo {
            font-family: 'Peykan', sans-serif;
            font-size: 2rem;
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .nav-links {
            list-style: none;
            padding: 0 10px;
        }

        .nav-item {
            margin-bottom: 8px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 14px 20px;
            color: var(--text-light);
            text-decoration: none;
            border-radius: 12px;
            font-weight: 500;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            gap: 15px;
            cursor: pointer;
        }

        .nav-link i {
            font-size: 1.1rem;
            width: 25px;
            text-align: center;
            transition: transform 0.3s;
        }

        .nav-link:hover {
            background: rgba(14, 116, 144, 0.05);
            color: var(--primary);
            transform: translateX(-5px);
        }

        .nav-link:hover i {
            transform: scale(1.1);
        }

        .nav-link.active {
            background: var(--primary);
            color: white;
            box-shadow: 0 10px 20px rgba(14, 116, 144, 0.25);
        }

        .nav-link.active i {
            color: white;
        }

        /* Main Content */
        .main-content {
            margin-right: 280px;
            flex: 1;
            padding: 40px;
            width: calc(100% - 280px);
            transition: margin 0.3s;
        }

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }

        .welcome-box {
            display: flex;
            align-items: center;
            gap: 20px;
            background: var(--glass-bg);
            padding: 15px 30px;
            border-radius: 50px;
            border: 1px solid var(--glass-border);
            backdrop-filter: blur(10px);
            box-shadow: var(--shadow-soft);
        }

        .avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), #38bdf8);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Peykan', sans-serif;
            font-size: 1.5rem;
            border: 3px solid white;
            box-shadow: 0 5px 15px rgba(14,116,144,0.2);
        }

        .user-details h2 {
            font-family: 'Peykan', sans-serif;
            font-size: 1.8rem;
            color: var(--text-dark);
        }

        .user-details p {
            color: var(--text-light);
            font-size: 0.9rem;
        }

        .logout-btn {
            background: white;
            color: #ef4444;
            border: none;
            padding: 12px 25px;
            border-radius: 12px;
            cursor: pointer;
            font-family: 'Vazirmatn', sans-serif;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: var(--shadow-soft);
            transition: all 0.3s;
        }

        .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(239, 68, 68, 0.2);
        }

        /* Notifications */
        .notification {
            padding: 15px 25px;
            border-radius: 12px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 15px;
            animation: slideDown 0.5s ease;
        }

        .notification.success {
            background: #ecfdf5;
            color: #047857;
            border: 1px solid #d1fae5;
        }

        .notification.error {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fee2e2;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Sections */
        .content-section {
            display: none;
            animation: fadeIn 0.5s ease;
        }

        .content-section.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius);
            padding: 30px;
            box-shadow: var(--shadow-soft);
            transition: transform 0.3s, box-shadow 0.3s;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-luxury);
            background: var(--glass-hover);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            background: rgba(14, 116, 144, 0.1);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--primary);
            margin-bottom: 20px;
        }

        .stat-value {
            font-family: 'Peykan', sans-serif;
            font-size: 2.5rem;
            color: var(--text-dark);
            margin-bottom: 5px;
        }

        .stat-label {
            color: var(--text-light);
            font-size: 0.95rem;
        }

        /* Cards & Forms */
        .card {
            background: white;
            border-radius: var(--radius);
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-soft);
            border: 1px solid rgba(14, 116, 144, 0.05);
        }

        .card-header {
            margin-bottom: 25px;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 15px;
        }

        .card-title {
            font-family: 'Peykan', sans-serif;
            font-size: 1.6rem;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Forms */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-dark);
        }

        .form-control {
            width: 100%;
            padding: 12px 18px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            font-family: inherit;
            font-size: 1rem;
            transition: all 0.3s;
            background: #f8fafc;
        }

        .form-control:focus {
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 4px rgba(14, 116, 144, 0.1);
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        .btn {
            padding: 12px 30px;
            border-radius: 12px;
            border: none;
            cursor: pointer;
            font-family: 'Vazirmatn', sans-serif;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
            box-shadow: 0 4px 15px rgba(14, 116, 144, 0.3);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-outline {
            background: transparent;
            border: 1px solid var(--primary);
            color: var(--primary);
        }

        .btn-outline:hover {
            background: rgba(14, 116, 144, 0.05);
        }

        /* AI Assistant */
        .ai-chat {
            background: var(--glass-bg);
            border-radius: var(--radius);
            padding: 25px;
            border: 1px solid var(--glass-border);
            height: 500px;
            display: flex;
            flex-direction: column;
        }

        .chat-messages {
            flex: 1;
            overflow-y: auto;
            margin-bottom: 20px;
            padding-right: 10px;
        }

        .message {
            margin-bottom: 15px;
            max-width: 80%;
            padding: 12px 18px;
            border-radius: 15px;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .msg-ai {
            background: white;
            color: var(--text-dark);
            border-bottom-right-radius: 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .msg-user {
            background: var(--primary);
            color: white;
            margin-right: auto;
            border-bottom-left-radius: 0;
        }

        .chat-input-area {
            display: flex;
            gap: 10px;
        }

        /* Course List */
        .course-item {
            background: #f8fafc;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            border: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s;
        }

        .course-item:hover {
            border-color: var(--primary);
            box-shadow: 0 5px 15px rgba(14, 116, 144, 0.1);
        }

        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .badge-pending { background: #fff7ed; color: #c2410c; }
        .badge-approved { background: #f0fdf4; color: #15803d; }
        .badge-rejected { background: #fef2f2; color: #b91c1c; }

        /* Responsive */
        @media (max-width: 992px) {
            .sidebar { transform: translateX(100%); }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-right: 0; width: 100%; padding: 20px; }
            .toggle-menu { display: block !important; position: fixed; top: 20px; right: 20px; z-index: 200; background: white; padding: 10px; border-radius: 8px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        }

        .toggle-menu { display: none; cursor: pointer; }
    </style>
</head>
<body>
    <div class="bg-orb orb-1"></div>
    <div class="bg-orb orb-2"></div>

    <div class="dashboard-container">
        <!-- Sidebar -->
        <nav class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <i class="fas fa-water"></i> آکادمی شنا
                </div>
            </div>
            <ul class="nav-links">
                <li class="nav-item">
                    <a href="#" class="nav-link active" data-target="dashboard-section">
                        <i class="fas fa-home"></i> داشبورد
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" data-target="profile-section">
                        <i class="fas fa-user-edit"></i> پروفایل
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" data-target="ongoing-courses-section">
                        <i class="fas fa-book-open"></i> دوره‌های جاری
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" data-target="medical-records-section">
                        <i class="fas fa-file-medical"></i> پرونده پزشکی
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" data-target="courses-section">
                        <i class="fas fa-graduation-cap"></i> دوره‌های من
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" data-target="settings-section">
                        <i class="fas fa-cog"></i> تنظیمات
                    </a>
                </li>
                <li class="nav-item" style="margin-top: 20px;">
                    <a href="index.php?from=landing" class="nav-link" style="color: #ef4444;">
                        <i class="fas fa-sign-out-alt"></i> خروج
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <div class="toggle-menu" id="toggle-menu"><i class="fas fa-bars"></i></div>

            <!-- Header -->
            <div class="header">
                <div class="welcome-box">
                    <div class="avatar">
                        <?php echo strtoupper(substr($user['first_name'] ?? 'U', 0, 1)); ?>
                    </div>
                    <div class="user-details">
                        <h2>خوش آمدید، <?php echo htmlspecialchars($user['first_name'] ?? 'کاربر'); ?></h2>
                        <p>عضو ویژه آکادمی شنا</p>
                    </div>
                </div>
            </div>

            <?php if (isset($_SESSION['status']) && isset($_SESSION['message'])): ?>
                <div class="notification <?php echo $_SESSION['status']; ?>">
                    <i class="fas fa-<?php echo ($_SESSION['status'] === 'success') ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <span><?php echo htmlspecialchars($_SESSION['message']); ?></span>
                </div>
                <?php unset($_SESSION['status'], $_SESSION['message']); ?>
            <?php endif; ?>

            <!-- Dashboard Home -->
            <div id="dashboard-section" class="content-section active">
                <!-- Stats -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-graduation-cap"></i></div>
                        <div class="stat-value"><?php echo $attended_sessions; ?></div>
                        <div class="stat-label">جلسات برگزار شده</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-clock"></i></div>
                        <div class="stat-value"><?php echo $remaining_sessions; ?></div>
                        <div class="stat-label">جلسات باقی‌مانده</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-certificate"></i></div>
                        <div class="stat-value">فعال</div>
                        <div class="stat-label">وضعیت اکانت</div>
                    </div>
                </div>

                <div class="form-grid">
                    <!-- AI Assistant -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-robot"></i> دستیار هوشمند</h3>
                        </div>
                        <div class="ai-chat">
                            <div class="chat-messages" id="chat-messages">
                                <div class="message msg-ai">سلام! چطور می‌توانم در انتخاب دوره یا برنامه تمرینی به شما کمک کنم؟</div>
                            </div>
                            <div class="chat-input-area">
                                <input type="text" class="form-control" id="ai-input" placeholder="سوال خود را بپرسید...">
                                <button class="btn btn-primary" id="send-ai"><i class="fas fa-paper-plane"></i></button>
                            </div>
                        </div>
                    </div>

                    <!-- Upcoming Classes -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-calendar-alt"></i> برنامه هفته آینده</h3>
                        </div>
                        <?php if (!empty($scheduled_classes)): ?>
                            <?php foreach ($scheduled_classes as $class): ?>
                                <div class="course-item">
                                    <div>
                                        <h4 style="margin-bottom: 5px;"><?php echo htmlspecialchars($class['title'] ?? 'کلاس شنا'); ?></h4>
                                        <small style="color: var(--text-light);"><i class="far fa-clock"></i> <?php echo $class['date_time'] ?? ''; ?></small>
                                    </div>
                                    <span class="badge badge-approved">تأیید شده</span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="text-align: center; color: var(--text-light);">کلاسی ثبت نشده است.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Profile Section -->
            <div id="profile-section" class="content-section">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-user-edit"></i> ویرایش اطلاعات</h3>
                    </div>
                    <form method="POST" class="form-grid">
                        <div class="form-group">
                            <label>نام</label>
                            <input type="text" name="first_name" class="form-control" value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>نام خانوادگی</label>
                            <input type="text" name="last_name" class="form-control" value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>شماره ملی</label>
                            <input type="text" name="national_id" class="form-control" value="<?php echo htmlspecialchars($user['national_id'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>شماره تلفن</label>
                            <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>ایمیل</label>
                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>تاریخ تولد</label>
                            <input type="date" name="date_of_birth_gregorian" class="form-control" value="<?php echo htmlspecialchars($user['date_of_birth'] ?? ''); ?>">
                        </div>
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label>آدرس</label>
                            <textarea name="address" class="form-control"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                        </div>
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <button type="submit" name="update_profile" class="btn btn-primary"><i class="fas fa-save"></i> ذخیره تغییرات</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Medical Records Section -->
            <div id="medical-records-section" class="content-section">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-file-medical"></i> سوابق پزشکی</h3>
                    </div>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label>شرایط یا حساسیت‌ها</label>
                            <textarea name="medical_conditions" class="form-control"><?php echo htmlspecialchars($medical_record['medical_conditions'] ?? ''); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>آپلود گواهی سلامت</label>
                            <input type="file" name="medical_certificate" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                            <?php if (!empty($medical_record['certificate_path'])): ?>
                                <small style="display:block; margin-top:10px;"><a href="<?php echo $medical_record['certificate_path']; ?>" target="_blank" class="btn-outline" style="padding: 5px 10px; border-radius: 6px; font-size: 0.8rem; text-decoration: none;">مشاهده فایل قبلی</a></small>
                            <?php endif; ?>
                        </div>
                        <button type="submit" name="update_medical_records" class="btn btn-primary">ذخیره سوابق</button>
                    </form>
                </div>
            </div>

            <!-- Settings (Password) Section -->
            <div id="settings-section" class="content-section">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-key"></i> تغییر رمز عبور</h3>
                    </div>
                    <form method="POST">
                        <div class="form-group">
                            <label>رمز عبور فعلی</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>رمز عبور جدید</label>
                            <input type="password" name="new_password" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>تکرار رمز عبور جدید</label>
                            <input type="password" name="confirm_new_password" class="form-control" required>
                        </div>
                        <button type="submit" name="change_password" class="btn btn-primary">تغییر رمز</button>
                    </form>
                </div>
            </div>

            <!-- Ongoing & Available Courses (Structure) -->
            <div id="ongoing-courses-section" class="content-section">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-book-reader"></i> دوره‌های جاری</h3>
                    </div>
                    <div class="form-grid">
                         <!-- Example item (Logic to be filled) -->
                         <div class="course-item">
                            <div>
                                <h4>شنا مقدماتی</h4>
                                <small>شنبه‌ها ساعت ۱۶</small>
                            </div>
                            <span class="badge badge-approved">تأیید شده</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- My Courses (Structure) -->
            <div id="courses-section" class="content-section">
                 <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-graduation-cap"></i> دوره‌های من</h3>
                    </div>
                    <p style="color: var(--text-light); text-align: center;">لیست دوره‌های شما در اینجا نمایش داده می‌شود.</p>
                </div>
            </div>

        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const navLinks = document.querySelectorAll('.nav-link');
            const sections = document.querySelectorAll('.content-section');
            const sidebar = document.getElementById('sidebar');
            const toggleBtn = document.getElementById('toggle-menu');

            // Function to switch tabs
            function activateSection(targetId) {
                if (!targetId || !document.getElementById(targetId)) return;

                // Update Links
                navLinks.forEach(link => link.classList.remove('active'));
                const activeLink = document.querySelector(`.nav-link[data-target="${targetId}"]`);
                if (activeLink) activeLink.classList.add('active');

                // Update Sections
                sections.forEach(section => section.classList.remove('active'));
                document.getElementById(targetId).classList.add('active');

                // Close sidebar on mobile
                if (window.innerWidth <= 992) {
                    sidebar.classList.remove('open');
                }
            }

            // Add Click Events to Links
            navLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const targetId = this.getAttribute('data-target');
                    activateSection(targetId);
                });
            });

            // Mobile Menu Toggle
            if (toggleBtn) {
                toggleBtn.addEventListener('click', function(e) {
                    e.stopPropagation(); // Prevent click from immediately closing
                    sidebar.classList.toggle('open');
                });

                // Close sidebar when clicking outside
                document.addEventListener('click', function(event) {
                    const isClickInsideSidebar = sidebar.contains(event.target);
                    const isClickOnToggle = toggleBtn.contains(event.target);

                    if (!isClickInsideSidebar && !isClickOnToggle && sidebar.classList.contains('open')) {
                        sidebar.classList.remove('open');
                    }
                });
            }

            // AI Chat Logic
            const chatInput = document.getElementById('ai-input');
            const sendBtn = document.getElementById('send-ai');
            const chatMessages = document.getElementById('chat-messages');

            function addMsg(text, type) {
                const div = document.createElement('div');
                div.className = `message ${type === 'user' ? 'msg-user' : 'msg-ai'}`;
                div.textContent = text;
                chatMessages.appendChild(div);
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }

            if (sendBtn && chatInput) {
                sendBtn.addEventListener('click', () => {
                    const text = chatInput.value.trim();
                    if(!text) return;
                    addMsg(text, 'user');
                    chatInput.value = '';

                    setTimeout(() => {
                        addMsg('درخواست شما ثبت شد. کارشناسان ما به زودی پاسخ خواهند داد.', 'ai');
                    }, 1000);
                });

                // Send on Enter key
                chatInput.addEventListener('keypress', (e) => {
                    if(e.key === 'Enter') sendBtn.click();
                });
            }
        });
    </script>
</body>
</html>