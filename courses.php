<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

require "db.php";

// Handle course enrollment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enroll_course'])) {
    $course_id = $_POST['course_id'];
    
    try {
        // Check if user is already enrolled in this course
        $stmt = $pdo->prepare("SELECT id FROM user_courses WHERE user_id = ? AND course_id = ?");
        $stmt->execute([$_SESSION['user_id'], $course_id]);
        $existing_enrollment = $stmt->fetch();
        
        if ($existing_enrollment) {
            $error = "شما قبلاً در این دوره ثبت نام کرده‌اید.";
        } else {
            // Enroll user in the course (status will be pending awaiting admin approval)
            $stmt = $pdo->prepare("INSERT INTO user_courses (user_id, course_id, status) VALUES (?, ?, 'pending')");
            $result = $stmt->execute([$_SESSION['user_id'], $course_id]);
            
            if ($result) {
                $success = "درخواست ثبت نام شما ارسال شد. پس از تأیید توسط مدیر، دوره برای شما فعال خواهد شد.";
            } else {
                $error = "خطا در ثبت نام در دوره.";
            }
        }
    } catch (Exception $e) {
        $error = "خطا در ثبت نام: " . $e->getMessage();
    }
}

// Get available courses
$courses = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM courses ORDER BY created_at DESC");
    $stmt->execute();
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = "خطا در بارگذاری دوره‌ها: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ثبت نام در دوره - داشبورد کاربری</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Vazirmatn', Tahoma, sans-serif;
            background-color: #f5f7fa;
            margin: 0;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #007BFF;
        }
        
        .course-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .course-card {
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 20px;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .course-title {
            font-size: 1.2em;
            color: #007BFF;
            margin-bottom: 10px;
        }
        
        .course-info {
            margin: 10px 0;
            color: #666;
        }
        
        .course-description {
            margin: 10px 0;
            color: #555;
        }
        
        .enroll-btn {
            background: #007BFF;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
        }
        
        .enroll-btn:hover {
            background: #0056b3;
        }
        
        .enrolled {
            background: #28a745;
        }
        
        .pending {
            background: #ffc107;
        }
        
        .notification {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .notification.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .notification.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            margin-top: 5px;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-approved {
            background: #d4edda;
            color: #155724;
        }
        
        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-graduation-cap"></i> دوره‌های موجود</h1>
            <p>از بین دوره‌های زیر یکی را انتخاب کرده و در آن ثبت نام کنید</p>
            <a href="dashboard.php" class="btn" style="display:inline-block; margin-top:10px; text-decoration:none; color:white; background:#007BFF; padding:8px 15px; border-radius:5px;"><i class="fas fa-arrow-right"></i> بازگشت به داشبورد</a>
        </div>
        
        <?php if (isset($success)): ?>
            <div class="notification success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="notification error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <div class="course-grid">
            <?php foreach ($courses as $course): ?>
                <div class="course-card">
                    <h3 class="course-title"><?php echo htmlspecialchars($course['title']); ?></h3>
                    
                    <?php if ($course['instructor']): ?>
                        <div class="course-info"><strong>مدرس:</strong> <?php echo htmlspecialchars($course['instructor']); ?></div>
                    <?php endif; ?>
                    
                    <?php if ($course['duration']): ?>
                        <div class="course-info"><strong>مدت زمان:</strong> <?php echo $course['duration']; ?> ساعت</div>
                    <?php endif; ?>
                    
                    <?php if ($course['schedule_info']): ?>
                        <div class="course-info"><strong>زمان‌بندی:</strong> <?php echo htmlspecialchars($course['schedule_info']); ?></div>
                    <?php endif; ?>
                    
                    <?php if ($course['price']): ?>
                        <div class="course-info"><strong>هزینه:</strong> <?php echo number_format($course['price']); ?> تومان</div>
                    <?php endif; ?>
                    
                    <div class="course-description">
                        <?php echo htmlspecialchars($course['description'] ?: 'بدون توضیحات'); ?>
                    </div>
                    
                    <?php
                    // Check if user is already enrolled in this course
                    $stmt = $pdo->prepare("SELECT status FROM user_courses WHERE user_id = ? AND course_id = ?");
                    $stmt->execute([$_SESSION['user_id'], $course['id']]);
                    $enrollment = $stmt->fetch();
                    ?>
                    
                    <?php if ($enrollment): ?>
                        <div class="status-badge status-<?php echo $enrollment['status']; ?>">
                            <?php 
                            switch($enrollment['status']) {
                                case 'pending': echo 'در انتظار تأیید'; break;
                                case 'approved': echo 'تأیید شده'; break;
                                case 'rejected': echo 'رد شده'; break;
                                case 'completed': echo 'تکمیل شده'; break;
                                default: echo $enrollment['status'];
                            }
                            ?>
                        </div>
                        <button class="enroll-btn" disabled>ثبت نام انجام شده</button>
                    <?php else: ?>
                        <form method="POST" style="margin-top: 15px;">
                            <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                            <button type="submit" name="enroll_course" class="enroll-btn">
                                <i class="fas fa-plus-circle"></i> ثبت نام در این دوره
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>