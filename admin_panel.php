<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

require "db.php";

// Function to convert Gregorian date to Persian date
function gregorian_to_persian($date) {
    if (empty($date)) return '';
    $timestamp = strtotime($date);
    if ($timestamp === false) return $date;
    $g_y = (int)date('Y', $timestamp);
    $g_m = (int)date('n', $timestamp);
    $g_d = (int)date('j', $timestamp);
    $g_days_in_month = [0, 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
    $j_days_in_month = [0, 31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29];
    if (((($g_y % 4) == 0) && ((($g_y % 100) != 0) || (($g_y % 400) == 0)))) {
        $g_days_in_month[2] = 29;
    }
    $gy = $g_y - 1600;
    $gm = $g_m - 1;
    $gd = $g_d - 1;
    $g_day_no = 365 * $gy + (int)(($gy + 3) / 4) - (int)(($gy + 99) / 100) + (int)(($gy + 399) / 400);
    for ($i = 0; $i < $gm; ++$i) {
        $g_day_no += $g_days_in_month[$i + 1];
    }
    $g_day_no += $gd;
    $j_day_no = $g_day_no - 226899;
    $j_np = 0;
    if ($j_day_no >= 0) {
        $jy = 979 + (int)($j_day_no / 365);
        $j_day_no %= 365;
    } else {
        $jy = 979 - (int)((-$j_day_no - 1) / 365) - 1;
        $j_day_no = 365 - (-$j_day_no - 1) % 365;
    }
    if ($j_day_no >= 186) {
        $jm = 6 + (int)(($j_day_no - 186) / 30);
        $jd = 1 + (($j_day_no - 186) % 30);
    } else {
        $jm = 1 + (int)($j_day_no / 31);
        $jd = 1 + ($j_day_no % 31);
    }
    return sprintf("%04d/%02d/%02d", $jy, $jm, $jd);
}

// Handle active tab preservation
 $active_tab = 'requests';
if (isset($_POST['active_tab'])) {
    $active_tab = $_POST['active_tab'];
}

// Handle AJAX request for getting course registrants
if (isset($_GET['action']) && $_GET['action'] === 'get_registrants' && isset($_GET['course_id'])) {
    header('Content-Type: application/json');
    $course_id = (int)$_GET['course_id'];
    try {
        $stmt = $pdo->prepare("SELECT u.id, u.first_name, u.last_name, u.phone, u.email, u.national_id, uc.status, uc.enrollment_date FROM users u JOIN user_courses uc ON u.id = uc.user_id WHERE uc.course_id = ? ORDER BY uc.enrollment_date DESC");
        $stmt->execute([$course_id]);
        $registrants = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'registrants' => $registrants]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'خطا: ' . $e->getMessage()]);
        exit;
    }
}

// Handle AJAX request for getting user's enrolled courses
if (isset($_GET['get_user_courses'])) {
    $user_id = intval($_GET['get_user_courses']);
    try {
        $stmt = $pdo->prepare("SELECT c.id, c.title FROM courses c JOIN user_courses uc ON c.id = uc.course_id WHERE uc.user_id = ? AND uc.status = 'approved'");
        $stmt->execute([$user_id]);
        header('Content-Type: application/json');
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        exit;
    } catch (Exception $e) {
        echo json_encode([]);
        exit;
    }
}

// Handle course approval/rejection
if (isset($_POST['action']) && isset($_POST['user_course_id'])) {
    $user_course_id = $_POST['user_course_id'];
    $action = $_POST['action'];
    $admin_notes = $_POST['admin_notes'] ?? '';
    
    try {
        $stmt = $pdo->prepare("UPDATE user_courses SET status = ?, admin_notes = ? WHERE id = ?");
        $stmt->execute([$action, $admin_notes, $user_course_id]);

        if ($action === 'rejected') {
            $stmt = $pdo->prepare("UPDATE user_courses SET notification_read = 0 WHERE id = ?");
            $stmt->execute([$user_course_id]);
        }

        $message = "وضعیت دوره با موفقیت به‌روزرسانی شد.";
    } catch (Exception $e) {
        $error = "خطا در به‌روزرسانی وضعیت دوره: " . $e->getMessage();
    }
}

// Handle attendance recording
if (isset($_POST['record_attendance'])) {
    $user_id = $_POST['user_id'] ?? 0;
    $course_id = $_POST['course_id'] ?? 0;
    $session_date = $_POST['session_date'] ?? '';
    $status = $_POST['attendance_status'] ?? '';
    $notes = $_POST['attendance_notes'] ?? '';

    try {
        // Find user_course_id
        $stmt = $pdo->prepare("SELECT id FROM user_courses WHERE user_id = ? AND course_id = ?");
        $stmt->execute([$user_id, $course_id]);
        $user_course = $stmt->fetch();

        if (!$user_course) {
            $error = "کاربر در این دوره ثبت نام نکرده است.";
        } else {
            $user_course_id = $user_course['id'];

            // Check if attendance for this date already exists
            $checkStmt = $pdo->prepare("SELECT id FROM course_attendance WHERE user_course_id = ? AND session_date = ?");
            $checkStmt->execute([$user_course_id, $session_date]);
            $existing_attendance = $checkStmt->fetch();

            if ($existing_attendance) {
                $updateStmt = $pdo->prepare("UPDATE course_attendance SET status = ?, notes = ?, recorded_by = ? WHERE id = ?");
                $result = $updateStmt->execute([$status, $notes, $_SESSION['admin_id'], $existing_attendance['id']]);
            } else {
                $insertStmt = $pdo->prepare("INSERT INTO course_attendance (user_course_id, session_date, status, notes, recorded_by) VALUES (?, ?, ?, ?, ?)");
                $result = $insertStmt->execute([$user_course_id, $session_date, $status, $notes, $_SESSION['admin_id']]);
            }

            if ($result) {
                $message = "حضوریت با موفقیت ثبت شد.";
            } else {
                $error = "خطا در ثبت حضوریت.";
            }
        }
    } catch (Exception $e) {
        $error = "خطا در ثبت حضوریت: " . $e->getMessage();
    }

    // Return JSON response for AJAX
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        if (isset($error)) {
            echo json_encode(['success' => false, 'message' => $error]);
        } else {
            echo json_encode(['success' => true, 'message' => $message]);
        }
        exit;
    }
}

// Get pending course requests
 $pending_requests = [];
try {
    $stmt = $pdo->prepare("
        SELECT uc.id as user_course_id, u.first_name, u.last_name, u.phone, c.title, c.description, uc.enrollment_date, uc.admin_notes
        FROM user_courses uc
        JOIN users u ON uc.user_id = u.id
        JOIN courses c ON uc.course_id = c.id
        WHERE uc.status = 'pending'
        ORDER BY uc.enrollment_date DESC
    ");
    $stmt->execute();
    $pending_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = "خطا در بارگذاری درخواست‌های در انتظار: " . $e->getMessage();
}

// Get all courses with enrolled students
 $all_courses = [];
try {
    $stmt = $pdo->prepare("
        SELECT c.*, u.first_name, u.last_name, uc.status as enrollment_status, uc.id as user_course_id
        FROM courses c
        LEFT JOIN user_courses uc ON c.id = uc.course_id
        LEFT JOIN users u ON uc.user_id = u.id
        ORDER BY c.created_at DESC, u.first_name
    ");
    $stmt->execute();
    $all_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = "خطا در بارگذاری دوره‌ها: " . $e->getMessage();
}

// Helper function to get course ID by title
function getCourseIdByTitle($title, $pdo) {
    try {
        $stmt = $pdo->prepare("SELECT id FROM courses WHERE title = ?");
        $stmt->execute([$title]);
        $result = $stmt->fetch();
        return $result ? $result['id'] : 0;
    } catch (Exception $e) {
        return 0;
    }
}

// --- Course Management Logic ---
if (isset($_POST['create_course'])) {
    $title = $_POST['course_title'] ?? '';
    $description = $_POST['course_description'] ?? '';
    $instructor = $_POST['course_instructor'] ?? '';
    $duration = $_POST['course_duration'] ?? '';
    $schedule_info = $_POST['course_schedule'] ?? '';
    $price = $_POST['course_price'] ?? '';
    $max_students = $_POST['max_students'] ?? 20;

    if (empty($title)) {
        $error = "عنوان دوره الزامی است.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO courses (title, description, instructor, duration, schedule_info, price, max_students) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $result = $stmt->execute([$title, $description, $instructor, $duration, $schedule_info, $price, $max_students]);
            if ($result) {
                $message = "دوره با موفقیت ایجاد شد.";
                // Refresh courses
                $stmt = $pdo->prepare("SELECT c.*, u.first_name, u.last_name, uc.status as enrollment_status, uc.id as user_course_id FROM courses c LEFT JOIN user_courses uc ON c.id = uc.course_id LEFT JOIN users u ON uc.user_id = u.id ORDER BY c.created_at DESC");
                $stmt->execute();
                $all_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $error = "خطا در ایجاد دوره.";
            }
        } catch (Exception $e) {
            $error = "خطا در ایجاد دوره: " . $e->getMessage();
        }
    }
}

if (isset($_POST['update_course'])) {
    $course_id = $_POST['course_id_update'] ?? '';
    $title = $_POST['course_title_update'] ?? '';
    $description = $_POST['course_description_update'] ?? '';
    $instructor = $_POST['course_instructor_update'] ?? '';
    $duration = $_POST['course_duration_update'] ?? '';
    $schedule_info = $_POST['course_schedule_update'] ?? '';
    $price = $_POST['course_price_update'] ?? '';
    $max_students = $_POST['max_students_update'] ?? 20;

    try {
        $stmt = $pdo->prepare("UPDATE courses SET title=?, description=?, instructor=?, duration=?, schedule_info=?, price=?, max_students=? WHERE id=?");
        $result = $stmt->execute([$title, $description, $instructor, $duration, $schedule_info, $price, $max_students, $course_id]);
        if ($result) {
            $message = "دوره با موفقیت به‌روزرسانی شد.";
            $stmt = $pdo->prepare("SELECT c.*, u.first_name, u.last_name, uc.status as enrollment_status, uc.id as user_course_id FROM courses c LEFT JOIN user_courses uc ON c.id = uc.course_id LEFT JOIN users u ON uc.user_id = u.id ORDER BY c.created_at DESC");
            $stmt->execute();
            $all_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $error = "خطا در به‌روزرسانی دوره.";
        }
    } catch (Exception $e) {
        $error = "خطا در به‌روزرسانی دوره: " . $e->getMessage();
    }
}

if (isset($_POST['delete_course'])) {
    $course_id = $_POST['course_id_delete'] ?? '';
    try {
        $stmt = $pdo->prepare("DELETE FROM course_attendance WHERE user_course_id IN (SELECT id FROM user_courses WHERE course_id = ?)");
        $stmt->execute([$course_id]);
        $stmt = $pdo->prepare("DELETE FROM user_courses WHERE course_id = ?");
        $stmt->execute([$course_id]);
        $stmt = $pdo->prepare("DELETE FROM courses WHERE id = ?");
        $result = $stmt->execute([$course_id]);
        if ($result) {
            $message = "دوره با موفقیت حذف شد.";
            $stmt = $pdo->prepare("SELECT c.*, u.first_name, u.last_name, uc.status as enrollment_status, uc.id as user_course_id FROM courses c LEFT JOIN user_courses uc ON c.id = uc.course_id LEFT JOIN users u ON uc.user_id = u.id ORDER BY c.created_at DESC");
            $stmt->execute();
            $all_courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $error = "خطا در حذف دوره.";
        }
    } catch (Exception $e) {
        $error = "خطا در حذف دوره: " . $e->getMessage();
    }
}

// --- About Us & Coach Logic ---
 $about_info = null;
try {
    $stmt = $pdo->query("SELECT * FROM about_us LIMIT 1");
    $about_info = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$about_info) {
        $pdo->exec("INSERT INTO about_us (company_info, address, contact_info) VALUES ('ما مجموعه‌ای حرفه‌ای...', 'آدرس...', 'تماس...')");
        $stmt = $pdo->query("SELECT * FROM about_us LIMIT 1");
        $about_info = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {}

 $coaches = [];
try {
    $stmt = $pdo->query("SELECT * FROM coaches ORDER BY id");
    $coaches = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

if (isset($_POST['update_about_us'])) {
    $company_info = $_POST['company_info'] ?? '';
    $address = $_POST['address'] ?? '';
    $contact_info = $_POST['contact_info'] ?? '';
    try {
        if ($about_info) {
            $stmt = $pdo->prepare("UPDATE about_us SET company_info = ?, address = ?, contact_info = ? WHERE id = ?");
            $result = $stmt->execute([$company_info, $address, $contact_info, $about_info['id']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO about_us (company_info, address, contact_info) VALUES (?, ?, ?)");
            $result = $stmt->execute([$company_info, $address, $contact_info]);
        }
        if ($result) {
            $message = "اطلاعات بخش درباره ما با موفقیت به‌روزرسانی شد.";
            $stmt = $pdo->query("SELECT * FROM about_us LIMIT 1");
            $about_info = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $error = "خطا در به‌روزرسانی اطلاعات.";
        }
    } catch (Exception $e) {
        $error = "خطا در به‌روزرسانی: " . $e->getMessage();
    }
}

if (isset($_POST['add_coach'])) {
    $name = $_POST['coach_name'] ?? '';
    $specialty = $_POST['coach_specialty'] ?? '';
    $bio = $_POST['coach_bio'] ?? '';
    $phone = $_POST['coach_phone'] ?? '';
    $photo_path = null;

    if (isset($_FILES['coach_photo']) && $_FILES['coach_photo']['error'] == 0) {
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        $file_extension = strtolower(pathinfo($_FILES['coach_photo']['name'], PATHINFO_EXTENSION));
        if (in_array($file_extension, $allowed_extensions)) {
            $upload_dir = 'uploads/coaches/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            $filename = 'coach_' . time() . '_' . preg_replace('/[^A-Za-z0-9\-_.]/', '_', $name) . '.' . $file_extension;
            $target_path = $upload_dir . $filename;
            if (move_uploaded_file($_FILES['coach_photo']['tmp_name'], $target_path)) {
                $photo_path = $target_path;
            }
        }
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO coaches (name, specialty, bio, phone, photo_path) VALUES (?, ?, ?, ?, ?)");
        $result = $stmt->execute([$name, $specialty, $bio, $phone, $photo_path]);
        if ($result) {
            $message = "مربی با موفقیت اضافه شد.";
            $stmt = $pdo->query("SELECT * FROM coaches ORDER BY id");
            $coaches = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $error = "خطا در اضافه کردن مربی.";
        }
    } catch (Exception $e) {
        $error = "خطا در اضافه کردن مربی: " . $e->getMessage();
    }
}

if (isset($_POST['update_coach'])) {
    $coach_id = $_POST['coach_id_update'] ?? '';
    $name = $_POST['coach_name_update'] ?? '';
    $specialty = $_POST['coach_specialty_update'] ?? '';
    $bio = $_POST['coach_bio_update'] ?? '';
    $phone = $_POST['coach_phone_update'] ?? '';
    $photo_path = null;

    if (isset($_FILES['coach_photo_update']) && $_FILES['coach_photo_update']['error'] == 0) {
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        $file_extension = strtolower(pathinfo($_FILES['coach_photo_update']['name'], PATHINFO_EXTENSION));
        if (in_array($file_extension, $allowed_extensions)) {
            $upload_dir = 'uploads/coaches/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            $filename = 'coach_' . time() . '_' . preg_replace('/[^A-Za-z0-9\-_.]/', '_', $name) . '.' . $file_extension;
            $target_path = $upload_dir . $filename;
            if (move_uploaded_file($_FILES['coach_photo_update']['tmp_name'], $target_path)) {
                $photo_path = $target_path;
                $stmt = $pdo->prepare("SELECT photo_path FROM coaches WHERE id = ?");
                $stmt->execute([$coach_id]);
                $old_coach = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($old_coach && $old_coach['photo_path'] && file_exists($old_coach['photo_path'])) unlink($old_coach['photo_path']);
            }
        }
    }

    try {
        if ($photo_path) {
            $stmt = $pdo->prepare("UPDATE coaches SET name = ?, specialty = ?, bio = ?, phone = ?, photo_path = ? WHERE id = ?");
            $result = $stmt->execute([$name, $specialty, $bio, $phone, $photo_path, $coach_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE coaches SET name = ?, specialty = ?, bio = ?, phone = ? WHERE id = ?");
            $result = $stmt->execute([$name, $specialty, $bio, $phone, $coach_id]);
        }
        if ($result) {
            $message = "اطلاعات مربی با موفقیت به‌روزرسانی شد.";
            $stmt = $pdo->query("SELECT * FROM coaches ORDER BY id");
            $coaches = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $error = "خطا در به‌روزرسانی مربی.";
        }
    } catch (Exception $e) {
        $error = "خطا در به‌روزرسانی مربی: " . $e->getMessage();
    }
}

if (isset($_POST['delete_coach'])) {
    $coach_id = $_POST['coach_id'] ?? '';
    try {
        $stmt = $pdo->prepare("SELECT photo_path FROM coaches WHERE id = ?");
        $stmt->execute([$coach_id]);
        $coach = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($coach && $coach['photo_path'] && file_exists($coach['photo_path'])) unlink($coach['photo_path']);
        $stmt = $pdo->prepare("DELETE FROM coaches WHERE id = ?");
        $result = $stmt->execute([$coach_id]);
        if ($result) {
            $message = "مربی با موفقیت حذف شد.";
            $stmt = $pdo->query("SELECT * FROM coaches ORDER BY id");
            $coaches = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $error = "خطا در حذف مربی.";
        }
    } catch (Exception $e) {
        $error = "خطا در حذف مربی: " . $e->getMessage();
    }
}

// Function to calculate attendance statistics for a user
function getUserAttendanceStats($user_id, $pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT
                ca.status,
                COUNT(*) as count
            FROM users u
            JOIN user_courses uc ON u.id = uc.user_id
            JOIN course_attendance ca ON uc.id = ca.user_course_id
            WHERE u.id = ?
            GROUP BY ca.status
        ");
        $stmt->execute([$user_id]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stats = [
            'present' => 0,
            'absent' => 0,
            'late' => 0,
            'excused' => 0
        ];

        foreach ($results as $row) {
            $status = $row['status'];
            if (isset($stats[$status])) {
                $stats[$status] = (int)$row['count'];
            }
        }

        return $stats;
    } catch (Exception $e) {
        return ['present' => 0, 'absent' => 0, 'late' => 0, 'excused' => 0];
    }
}

// --- Students Logic ---
 $students = [];
 $search_query = isset($_POST['search_query']) && !isset($_POST['show_all']) ? $_POST['search_query'] : '';

try {
    if (!empty($search_query)) {
        $stmt = $pdo->prepare("
            SELECT u.*, c.title as course_title, uc.status as enrollment_status, uc.enrollment_date
            FROM users u
            LEFT JOIN user_courses uc ON u.id = uc.user_id
            LEFT JOIN courses c ON uc.course_id = c.id
            WHERE (u.first_name LIKE ? OR u.last_name LIKE ? OR u.national_id LIKE ?)
            AND (uc.status IS NULL OR uc.status != 'rejected')
            ORDER BY u.id DESC, uc.enrollment_date DESC
        ");
        $p = '%' . $search_query . '%';
        $stmt->execute([$p, $p, $p]);
    } else {
        $stmt = $pdo->prepare("
            SELECT u.*, c.title as course_title, uc.status as enrollment_status, uc.enrollment_date
            FROM users u
            LEFT JOIN user_courses uc ON u.id = uc.user_id
            LEFT JOIN courses c ON uc.course_id = c.id
            WHERE uc.status IS NULL OR uc.status != 'rejected'
            ORDER BY u.id DESC, uc.enrollment_date DESC
        ");
        $stmt->execute();
    }

    $all_results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group results by user (One user -> multiple courses)
    $grouped_students = [];
    foreach ($all_results as $row) {
        $user_id = $row['id'];
        if (!isset($grouped_students[$user_id])) {
            // Calculate attendance stats for this user
            $attendance_stats = getUserAttendanceStats($user_id, $pdo);

            $grouped_students[$user_id] = [
                'user_info' => $row,
                'courses' => [],
                'attendance_stats' => $attendance_stats
            ];
        }
        if (!empty($row['course_title'])) {
            $grouped_students[$user_id]['courses'][] = [
                'title' => $row['course_title'],
                'status' => $row['enrollment_status'],
                'enrollment_date' => $row['enrollment_date']
            ];
        }
    }
    $students = $grouped_students;

} catch (Exception $e) {
    $error = "خطا در بارگذاری دانشجویان: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پنل مدیریت - آکادمی شنا</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            /* Color Palette - Preserved from original */
            --primary: #0e7490;
            --primary-dark: #0c5a6f;
            --primary-light: #e0f2fe;
            --accent: #fbbf24;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --bg-body: #f0f9ff;

            /* Glassmorphism Variables */
            --glass-bg: rgba(255, 255, 255, 0.9); /* slightly more opaque for better contrast */
            --glass-border: rgba(255, 255, 255, 0.5);
            --glass-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.07);

            /* UI Variables */
            --radius-lg: 20px;
            --radius-md: 12px;
            --radius-sm: 8px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            
            /* Layout Dimensions */
            --sidebar-width: 280px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; outline: none; }

        body {
            font-family: 'Vazirmatn', sans-serif;
            background: var(--bg-body);
            color: var(--text-main);
            min-height: 100vh;
            overflow-x: hidden;
            font-size: 14px;
            display: flex;
        }

        /* Background Decoration */
        .bg-orb {
            position: fixed; width: 500px; height: 500px; border-radius: 50%;
            filter: blur(80px); opacity: 0.4; z-index: -1;
            animation: floatOrb 10s infinite ease-in-out;
        }
        .orb-1 { top: -100px; right: -100px; background: #bae6fd; }
        .orb-2 { bottom: -100px; left: -100px; background: #e0f2fe; animation-delay: 5s; }
        @keyframes floatOrb {
            0%, 100% { transform: translate(0, 0); }
            50% { transform: translate(30px, 30px); }
        }

        /* --- Sidebar --- */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--glass-bg);
            backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
            border-left: 1px solid var(--glass-border);
            padding: 30px 0;
            display: flex;
            flex-direction: column;
            z-index: 100;
            transition: transform 0.3s ease;
            box-shadow: var(--glass-shadow);
            height: 100vh;
            position: sticky; /* Sticky sidebar instead of fixed for easier flex handling */
            top: 0;
            right: 0;
            flex-shrink: 0; /* Important: Prevent sidebar from shrinking */
            overflow-y: auto;
        }

        .logo-area {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(14, 116, 144, 0.1);
            margin-bottom: 15px;
        }
        .logo-text {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 12px;
            letter-spacing: -0.5px;
        }

        .nav-list { list-style: none; padding: 0 15px; }
        .nav-item { margin-bottom: 8px; }
        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 18px;
            color: var(--text-muted);
            text-decoration: none;
            border-radius: var(--radius-md);
            font-weight: 500;
            transition: var(--transition);
            cursor: pointer;
        }
        .nav-link i { width: 24px; text-align: center; transition: transform 0.3s; }
        .nav-link:hover {
            background: rgba(14, 116, 144, 0.05);
            color: var(--primary);
            transform: translateX(-5px);
        }
        .nav-link.active {
            background: var(--primary);
            color: white;
            box-shadow: 0 4px 12px rgba(14, 116, 144, 0.3);
        }
        .nav-link.active i { color: white; transform: scale(1.1); }

        /* --- Main Content --- */
        .main-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0; /* Crucial for Flexbox overflow handling */
            max-width: 100%;
            position: relative;
        }

        .top-bar {
            padding: 18px 30px; /* Increased padding for better desktop feel */
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.6);
            flex-shrink: 0;
            height: 70px;
        }

        .content-area {
            flex: 1;
            padding: 30px; /* Clean consistent padding */
            overflow-y: auto;
            scroll-behavior: smooth;
            min-height: 0;
            max-width: 100%;
        }

        /* --- Cards & Containers --- */
        .card {
            background: white;
            border-radius: var(--radius-lg);
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px -10px rgba(14, 116, 144, 0.08);
            border: 1px solid rgba(14, 116, 144, 0.05);
            animation: slideUp 0.5s ease-out;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            max-width: 100%;
            overflow: hidden; /* Prevents overflow */
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px -10px rgba(14, 116, 144, 0.12);
        }

        @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid #f1f5f9;
            flex-wrap: wrap;
            gap: 12px;
        }
        .section-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--text-main);
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
        }

        /* --- Buttons --- */
        .btn {
            padding: 8px 20px;
            border: none;
            border-radius: var(--radius-md);
            font-family: inherit;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-size: 0.9rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .btn:active { transform: scale(0.98); }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-dark); transform: translateY(-2px); box-shadow: 0 6px 16px rgba(14, 116, 144, 0.4); }
        .btn-danger { background: #ef4444; color: white; }
        .btn-danger:hover { background: #dc2626; transform: translateY(-2px); box-shadow: 0 6px 16px rgba(239, 68, 68, 0.4); }
        .btn-secondary { background: #e2e8f0; color: var(--text-muted); }
        .btn-secondary:hover { background: #cbd5e1; transform: translateY(-2px); box-shadow: 0 6px 16px rgba(203, 213, 225, 0.4); }
        .btn-sm { padding: 5px 10px; font-size: 0.85rem; }

        /* --- Forms --- */
        .form-group { margin-bottom: 16px; }
        .form-label { display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-main); font-size: 0.95rem; }
        .form-control {
            width: 100%;
            padding: 10px 14px;
            border: 2px solid #e2e8f0;
            border-radius: var(--radius-md);
            font-size: 0.95rem;
            background: #f8fafc;
            color: var(--text-main);
            transition: var(--transition);
            font-family: 'Vazirmatn', sans-serif;
        }
        .form-control:focus {
            border-color: var(--primary);
            background: white;
            box-shadow: 0 0 0 4px rgba(14, 116, 144, 0.15);
            outline: none;
        }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }

        /* --- Badges --- */
        .badge {
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            display: inline-block;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
        }
        .badge-pending { background: #fef3c7; color: #d97706; }
        .badge-approved { background: #d1fae5; color: #059669; }
        .badge-rejected { background: #fee2e2; color: #dc2626; }

        /* --- Tables --- */
        .table-container {
            overflow-x: auto;
            border-radius: var(--radius-md);
            border: 1px solid #e2e8f0;
            background: white;
            width: 100%;
            margin: 0 auto;
        }
        table { width: 100%; border-collapse: collapse; min-width: 600px; }
        th {
            background: #f8fafc;
            color: var(--text-muted);
            font-weight: 700;
            text-align: right;
            padding: 12px 14px;
            border-bottom: 2px solid #e2e8f0;
            font-size: 0.9rem;
            white-space: nowrap;
        }
        td {
            padding: 12px 14px;
            border-bottom: 1px solid #f1f5f9;
            color: var(--text-main);
            font-size: 0.95rem;
            vertical-align: top;
            white-space: nowrap;
        }
        tr:hover td { background: #fdfdfd; }

        /* --- Modals --- */
        .modal-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.4);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            z-index: 2000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .modal-overlay.active { display: flex; animation: fadeIn 0.3s; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

        .modal-box {
            background: white;
            padding: 25px;
            border-radius: var(--radius-lg);
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            position: relative;
            margin: 0 auto;
        }
        #registrants-modal .modal-box { max-width: 900px; }

        .close-modal {
            position: absolute;
            top: 20px; left: 20px;
            font-size: 1.5rem;
            color: #94a3b8;
            cursor: pointer;
            transition: 0.2s;
            z-index: 1001;
        }
        .close-modal:hover { color: var(--primary); }

        /* --- Notifications --- */
        .alert {
            padding: 14px 18px;
            border-radius: var(--radius-md);
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1px solid transparent;
        }
        .alert-success { background: #d1fae5; color: #065f46; border-right: 4px solid #059669; }
        .alert-error { background: #fee2e2; color: #991b1b; border-right: 4px solid #dc2626; }

        /* --- Helpers --- */
        .tab-pane { display: none; }
        .tab-pane.active { display: block; }
        
        .coach-card {
            display: flex;
            gap: 12px;
            background: #f8fafc;
            padding: 12px;
            border-radius: var(--radius-md);
            margin-bottom: 12px;
            border: 1px solid #e2e8f0;
            align-items: flex-start;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .coach-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        .coach-avatar {
            width: 60px; height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid white;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            flex-shrink: 0;
        }

        /* --- RESPONSIVE MEDIA QUERIES --- */

        /* Mobile and Tablet (Vertical layout) */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(100%);
                position: fixed;
                height: 100vh;
                right: 0;
                top: 0;
                z-index: 1000;
                box-shadow: -5px 0 15px rgba(0,0,0,0.1);
            }
            .sidebar.open { transform: translateX(0); }
            .main-wrapper { margin-right: 0; width: 100%; }
            
            .content-area { padding: 20px; }
            .form-row { grid-template-columns: 1fr; }
            .section-header { flex-direction: column; align-items: flex-start; gap: 10px; }
            .card { padding: 20px; margin-bottom: 20px; }
            
            .menu-toggle { display: block !important; cursor: pointer; font-size: 1.5rem; color: var(--primary); }
        }
        
        /* Desktop Layout */
        @media (min-width: 993px) {
            .menu-toggle { display: none; }
        }

        .menu-toggle { display: none; }
    </style>
</head>
<body>
    <div class="bg-orb orb-1"></div>
    <div class="bg-orb orb-2"></div>

    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">
        <div class="logo-area">
            <div class="logo-text"><i class="fas fa-water"></i> پنل مدیریت</div>
        </div>
        <ul class="nav-list">
            <li class="nav-item"><a class="nav-link active" onclick="switchTab('requests')"><i class="fas fa-clock"></i> درخواست‌ها</a></li>
            <li class="nav-item"><a class="nav-link" onclick="switchTab('manage-courses')"><i class="fas fa-book"></i> مدیریت دوره‌ها</a></li>
            <li class="nav-item"><a class="nav-link" onclick="switchTab('create-course')"><i class="fas fa-plus-circle"></i> ایجاد دوره</a></li>
            <li class="nav-item"><a class="nav-link" onclick="switchTab('students')"><i class="fas fa-users"></i> دانشجویان</a></li>
            <li class="nav-item"><a class="nav-link" onclick="switchTab('about-us')"><i class="fas fa-info-circle"></i> درباره ما</a></li>
            <li class="nav-item" style="margin-top: 20px;">
                <a class="nav-link" href="dashboard.php" style="color: #64748b;"><i class="fas fa-arrow-right"></i> بازگشت به سایت</a>
            </li>
            <li class="nav-item" style="margin-top: 10px; border-top: 1px solid rgba(14, 116, 144, 0.1); padding-top: 15px;">
                <a class="nav-link" href="admin_login.php?logout=1" style="color: #ef4444;"><i class="fas fa-sign-out-alt"></i> خروج از حساب</a>
            </li>
        </ul>
    </nav>

    <!-- Main Wrapper -->
    <div class="main-wrapper">
        <!-- Top Bar -->
        <header class="top-bar">
            <div class="menu-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></div>
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 40px; height: 40px; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; box-shadow: 0 4px 10px rgba(14, 116, 144, 0.2);">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div>
                    <div style="font-weight: 700; font-size: 1rem; color: var(--text-main);">مدیر سیستم</div>
                    <div style="font-size: 0.8rem; color: var(--text-muted);">آکادمی شنا</div>
                </div>
            </div>
        </header>

        <!-- Content Area -->
        <main class="content-area">
            <?php if (isset($message)): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $message; ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <!-- TAB: REQUESTS -->
            <div id="requests" class="tab-pane active">
                <div class="card">
                    <div class="section-header">
                        <h2 class="section-title"><i class="fas fa-clock" style="color: var(--primary);"></i> درخواست‌های در انتظار</h2>
                        <span class="badge badge-pending" style="font-size: 0.9rem; padding: 6px 14px;"><?php echo count($pending_requests); ?> درخواست</span>
                    </div>
                    
                    <?php if (empty($pending_requests)): ?>
                        <div style="text-align: center; padding: 50px 0; color: var(--text-muted);">
                            <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 20px; opacity: 0.2;"></i>
                            <p>در حال حاضر هیچ درخواست تاییدیه‌ای وجود ندارد.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($pending_requests as $req): ?>
                            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 15px; padding: 25px; margin-bottom: 20px;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px;">
                                    <div>
                                        <h3 style="font-size: 1.1rem; margin-bottom: 5px;"><?php echo htmlspecialchars($req['first_name'] . ' ' . $req['last_name']); ?></h3>
                                        <div style="color: var(--text-muted); font-size: 0.9rem;"><i class="fas fa-mobile-alt" style="margin-left: 5px;"></i> <?php echo htmlspecialchars($req['phone']); ?></div>
                                    </div>
                                    <span class="badge badge-pending">در انتظار تایید</span>
                                </div>
                                <div style="background: white; padding: 12px 15px; border-radius: 8px; font-size: 0.95rem; margin-bottom: 20px; border-right: 3px solid var(--primary);">
                                    <strong>دوره درخواستی:</strong> <?php echo htmlspecialchars($req['title']); ?>
                                </div>
                                <form method="POST" onsubmit="preserveTab(this)">
                                    <input type="hidden" name="user_course_id" value="<?php echo $req['user_course_id']; ?>">
                                    <input type="hidden" name="active_tab" value="requests">
                                    <div class="form-group">
                                        <textarea name="admin_notes" class="form-control" placeholder="یادداشت برای دانشجو (اختیاری)..." rows="3" style="resize: none;"></textarea>
                                    </div>
                                    <div style="display: flex; gap: 12px;">
                                        <button type="submit" name="action" value="approved" class="btn btn-primary"><i class="fas fa-check"></i> تایید درخواست</button>
                                        <button type="submit" name="action" value="rejected" class="btn btn-danger"><i class="fas fa-times"></i> رد درخواست</button>
                                    </div>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- TAB: MANAGE COURSES -->
            <div id="manage-courses" class="tab-pane">
                <div class="card">
                    <div class="section-header">
                        <h2 class="section-title"><i class="fas fa-book" style="color: var(--primary);"></i> مدیریت دوره‌ها</h2>
                    </div>
                    <div class="table-container" style="max-height: 600px; overflow-y: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>عنوان دوره</th>
                                    <th>مدرس</th>
                                    <th>ظرفیت</th>
                                    <th>عملیات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                // Unique courses loop
                                $displayed_courses = [];
                                foreach ($all_courses as $course): 
                                    if (in_array($course['id'], $displayed_courses)) continue;
                                    $displayed_courses[] = $course['id'];
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($course['title']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($course['instructor'] ?? '-'); ?></td>
                                    <td><?php echo $course['max_students'] ?? '20'; ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" onclick="openEditModal(<?php echo $course['id']; ?>, '<?php echo addslashes(htmlspecialchars($course['title'])); ?>', '<?php echo addslashes(htmlspecialchars($course['description'])); ?>', '<?php echo addslashes(htmlspecialchars($course['instructor'])); ?>', '<?php echo $course['duration']; ?>', '<?php echo addslashes(htmlspecialchars($course['schedule_info'])); ?>', '<?php echo $course['price']; ?>', '<?php echo $course['max_students']; ?>')">ویرایش</button>
                                        <button class="btn btn-sm btn-danger" style="margin-right: 5px;" onclick="viewCourseRegistrants(<?php echo $course['id']; ?>, '<?php echo addslashes(htmlspecialchars($course['title'])); ?>')">مشاهده</button>
                                        <form method="POST" style="display: inline;" onsubmit="preserveTab(this); return confirm('آیا از حذف این دوره اطمینان دارید؟');">
                                            <input type="hidden" name="course_id_delete" value="<?php echo $course['id']; ?>">
                                            <input type="hidden" name="delete_course" value="1">
                                            <input type="hidden" name="active_tab" value="manage-courses">
                                            <button type="submit" class="btn btn-sm btn-secondary" style="background: #fee2e2; color: #b91c1c;"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB: CREATE COURSE -->
            <div id="create-course" class="tab-pane">
                <div class="card">
                    <div class="section-header">
                        <h2 class="section-title"><i class="fas fa-plus-circle" style="color: var(--primary);"></i> ایجاد دوره جدید</h2>
                    </div>
                    <form method="POST" onsubmit="preserveTab(this)">
                        <input type="hidden" name="active_tab" value="create-course">
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">عنوان دوره *</label>
                                <input type="text" name="course_title" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">نام مدرس</label>
                                <input type="text" name="course_instructor" class="form-control">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">مدت زمان (ساعت)</label>
                                <input type="number" name="course_duration" class="form-control" placeholder="مثلا 20">
                            </div>
                            <div class="form-group">
                                <label class="form-label">هزینه (تومان)</label>
                                <input type="number" name="course_price" class="form-control">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">زمان‌بندی برگزاری</label>
                            <textarea name="course_schedule" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">توضیحات دوره</label>
                            <textarea name="course_description" class="form-control" rows="3"></textarea>
                        </div>
                        <div style="text-align: left;">
                            <button type="submit" name="create_course" class="btn btn-primary"><i class="fas fa-plus"></i> ایجاد دوره</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- TAB: STUDENTS -->
            <div id="students" class="tab-pane">
                <div class="card">
                    <div class="section-header">
                        <h2 class="section-title"><i class="fas fa-users" style="color: var(--primary);"></i> دانشجویان</h2>
                    </div>
                    
                    <form method="POST" class="form-group" style="margin-bottom: 25px; display: flex; gap: 10px; align-items: center;">
                        <div style="position: relative; flex: 1;">
                            <i class="fas fa-search" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: #94a3b8;"></i>
                            <input type="text" name="search_query" class="form-control" placeholder="جستجو بر اساس نام یا کد ملی..." style="padding-right: 45px;">
                        </div>
                        <button type="submit" name="active_tab" value="students" class="btn btn-primary">جستجو</button>
                        <button type="submit" name="show_all" value="1" class="btn btn-secondary"><i class="fas fa-sync"></i> همه</button>
                    </form>

                    <?php if (empty($students)): ?>
                        <div style="text-align: center; padding: 40px; color: var(--text-muted);">دانشجویی یافت نشد.</div>
                    <?php else: ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th style="width: 200px;">نام و نام خانوادگی</th>
                                        <th style="width: 150px;">شماره موبایل</th>
                                        <th style="width: 120px;">کد ملی</th>
                                        <th style="width: 200px;">دوره‌ها</th>
                                        <th style="width: 150px;">آمار حضوریت</th>
                                        <th style="width: 150px;">عملیات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $student_data): ?>
                                        <?php $user = $student_data['user_info']; ?>
                                        <?php $attendance_stats = $student_data['attendance_stats']; ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($user['phone'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($user['national_id'] ?? ''); ?></td>
                                            <td>
                                                <?php if (!empty($student_data['courses'])): ?>
                                                    <div style="display: flex; flex-direction: column; gap: 5px;">
                                                        <?php foreach ($student_data['courses'] as $course): ?>
                                                            <span class="badge badge-<?php echo $course['status']; ?>"><?php echo htmlspecialchars($course['title']); ?></span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span style="color: var(--text-muted); font-size: 0.85rem;">بدون دوره</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div style="display: flex; flex-direction: column; gap: 5px;">
                                                    <span class="badge badge-approved" style="background: #dbeafe; color: #1e40af;">حاضر: <?php echo $attendance_stats['present']; ?></span>
                                                    <span class="badge badge-rejected" style="background: #fee2e2; color: #dc2626;">غایب: <?php echo $attendance_stats['absent']; ?></span>
                                                    <span class="badge" style="background: #fef3c7; color: #d97706;">تاخیر: <?php echo $attendance_stats['late']; ?></span>
                                                    <span class="badge" style="background: #ddd; color: #666;">معذور: <?php echo $attendance_stats['excused']; ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-primary" onclick="openAttendanceModal(<?php echo $user['id']; ?>, '<?php echo addslashes($user['first_name'] . ' ' . $user['last_name']); ?>')">ثبت حضوریت</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- TAB: ABOUT US -->
            <div id="about-us" class="tab-pane">
                <div class="card">
                    <div class="section-header">
                        <h2 class="section-title"><i class="fas fa-building" style="color: var(--primary);"></i> ویرایش اطلاعات مجموعه</h2>
                    </div>
                    <form method="POST" onsubmit="preserveTab(this)">
                        <input type="hidden" name="active_tab" value="about-us">
                        <div class="form-group">
                            <label class="form-label">معرفی مجموعه</label>
                            <textarea name="company_info" class="form-control" rows="4"><?php echo htmlspecialchars($about_info['company_info']??''); ?></textarea>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">آدرس</label>
                                <textarea name="address" class="form-control" rows="3"><?php echo htmlspecialchars($about_info['address']??''); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label class="form-label">اطلاعات تماس</label>
                                <textarea name="contact_info" class="form-control" rows="3"><?php echo htmlspecialchars($about_info['contact_info']??''); ?></textarea>
                            </div>
                        </div>
                        <button type="submit" name="update_about_us" class="btn btn-primary"><i class="fas fa-save"></i> ذخیره اطلاعات</button>
                    </form>
                </div>
                
                <!-- Coach Management (Simplified into About Us tab for this clean design) -->
                <div class="card">
                    <div class="section-header">
                        <h2 class="section-title"><i class="fas fa-chalkboard-teacher" style="color: var(--primary);"></i> مدیریت مربیان</h2>
                    </div>
                    
                    <form method="POST" enctype="multipart/form-data" style="background: #f8fafc; padding: 20px; border-radius: var(--radius-md); margin-bottom: 30px;" onsubmit="preserveTab(this)">
                        <input type="hidden" name="active_tab" value="about-us">
                        <h4 style="margin: 0 0 15px 0; font-size: 1rem;">افزودن مربی جدید</h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">نام</label>
                                <input type="text" name="coach_name" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">تخصص</label>
                                <input type="text" name="coach_specialty" class="form-control">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">توضیحات</label>
                            <textarea name="coach_bio" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">تلفن</label>
                                <input type="text" name="coach_phone" class="form-control">
                            </div>
                            <div class="form-group">
                                <label class="form-label">عکس</label>
                                <input type="file" name="coach_photo" class="form-control">
                            </div>
                        </div>
                        <button type="submit" name="add_coach" class="btn btn-primary">افزودن مربی</button>
                    </form>

                    <!-- Existing Coaches -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
                        <?php foreach ($coaches as $coach): ?>
                            <div class="coach-card">
                                <?php if ($coach['photo_path']): ?>
                                    <img src="<?php echo htmlspecialchars($coach['photo_path']); ?>" class="coach-avatar">
                                <?php else: ?>
                                    <div class="coach-avatar" style="background: #e2e8f0; display: flex; align-items: center; justify-content: center; color: var(--text-muted);"><i class="fas fa-user"></i></div>
                                <?php endif; ?>
                                <div style="flex: 1;">
                                    <h4 style="font-size: 1.1rem; margin-bottom: 5px;"><?php echo htmlspecialchars($coach['name']); ?></h4>
                                    <p style="font-size: 0.9rem; color: var(--text-muted); margin-bottom: 10px;"><?php echo htmlspecialchars($coach['specialty'] ?? ''); ?></p>
                                    <div style="display: flex; gap: 8px;">
                                        <form method="POST" onsubmit="preserveTab(this)">
                                            <input type="hidden" name="coach_id" value="<?php echo $coach['id']; ?>">
                                            <input type="hidden" name="active_tab" value="about-us">
                                            <button type="submit" name="delete_coach" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <!-- ATTENDANCE MODAL -->
    <div id="attendance-modal" class="modal-overlay">
        <div class="modal-box">
            <i class="fas fa-times close-modal" onclick="closeModal('attendance-modal')"></i>
            <h2 style="margin-bottom: 25px; border-bottom: 1px solid #eee; padding-bottom: 15px;">ثبت حضوریت</h2>
            <form id="attendance-form" method="POST">
                <input type="hidden" id="modal-user-id" name="user_id">
                
                <div class="form-group">
                    <label class="form-label">انتخاب دوره</label>
                    <select id="modal-course-select" name="course_id" class="form-control" required>
                        <option value="">در حال بارگذاری...</option>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">تاریخ جلسه</label>
                        <input type="date" name="session_date" value="<?php echo date('Y-m-d'); ?>" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">وضعیت</label>
                        <select name="attendance_status" class="form-control">
                            <option value="present">حاضر</option>
                            <option value="absent">غایب</option>
                            <option value="late">تاخیر</option>
                            <option value="excused">معذور</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">یادداشت</label>
                    <textarea name="attendance_notes" class="form-control" rows="3" placeholder="توضیحات اختیاری..."></textarea>
                </div>

                <button type="submit" name="record_attendance" class="btn btn-primary" style="width: 100%; padding: 14px;">ثبت حضوریت</button>
            </form>
        </div>
    </div>

    <!-- EDIT COURSE MODAL -->
    <div id="edit-course-modal" class="modal-overlay">
        <div class="modal-box">
            <i class="fas fa-times close-modal" onclick="closeModal('edit-course-modal')"></i>
            <h2 style="margin-bottom: 25px; border-bottom: 1px solid #eee; padding-bottom: 15px;">ویرایش دوره</h2>
            <form id="edit-course-form" method="POST">
                <input type="hidden" id="edit_course_id" name="course_id_update">
                <input type="hidden" name="active_tab" value="manage-courses">

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">عنوان دوره</label>
                        <input type="text" id="edit_course_title" name="course_title_update" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">مدرس</label>
                        <input type="text" id="edit_course_instructor" name="course_instructor_update" class="form-control">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">مدت زمان</label>
                        <input type="number" id="edit_course_duration" name="course_duration_update" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">هزینه</label>
                        <input type="number" id="edit_course_price" name="course_price_update" class="form-control">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">زمان‌بندی</label>
                    <textarea id="edit_course_schedule" name="course_schedule_update" class="form-control" rows="2"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">توضیحات</label>
                    <textarea id="edit_course_description" name="course_description_update" class="form-control" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">ظرفیت</label>
                    <input type="number" id="edit_course_max_students" name="max_students_update" class="form-control">
                </div>

                <button type="submit" name="update_course" class="btn btn-primary" style="width: 100%;">ذخیره تغییرات</button>
            </form>
        </div>
    </div>
    
    <!-- REGISTRANTS MODAL -->
    <div id="registrants-modal" class="modal-overlay">
        <div class="modal-box" style="max-width: 800px;">
            <i class="fas fa-times close-modal" onclick="closeModal('registrants-modal')"></i>
            <h2 id="registrants-modal-title" style="margin-bottom: 25px; border-bottom: 1px solid #eee; padding-bottom: 15px;">ثبت نامی ها</h2>
            <div id="registrants-list" style="max-height: 400px; overflow-y: auto;">
                <!-- Content loaded via AJAX -->
            </div>
        </div>
    </div>

    <script>
        // Navigation Logic
        function switchTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-pane').forEach(tab => tab.classList.remove('active'));
            // Remove active class from nav
            document.querySelectorAll('.nav-link').forEach(link => link.classList.remove('active'));
            
            // Show target
            const targetTab = document.getElementById(tabName);
            if(targetTab) targetTab.classList.add('active');
            
            // Activate nav item
            const targetNav = document.querySelector(`.nav-link[onclick="switchTab('${tabName}')"]`);
            if(targetNav) targetNav.classList.add('active');
            
            // Mobile sidebar close
            if (window.innerWidth < 992) toggleSidebar();
        }

        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('open');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        function preserveTab(form) {
            const activeTab = document.querySelector('.nav-link.active');
            const tabName = activeTab.getAttribute('onclick').match(/'([^']+)'/)[1];
            const input = form.querySelector('input[name="active_tab"]');
            if(input) input.value = tabName;
            else {
                const newInput = document.createElement('input');
                newInput.type = 'hidden'; newInput.name = 'active_tab'; newInput.value = tabName;
                form.appendChild(newInput);
            }
        }

        // Edit Course Modal
        function openEditModal(id, title, desc, inst, dur, sched, price, max) {
            document.getElementById('edit_course_id').value = id;
            document.getElementById('edit_course_title').value = title;
            document.getElementById('edit_course_description').value = desc;
            document.getElementById('edit_course_instructor').value = inst;
            document.getElementById('edit_course_duration').value = dur;
            document.getElementById('edit_course_schedule').value = sched;
            document.getElementById('edit_course_price').value = price;
            document.getElementById('edit_course_max_students').value = max;
            document.getElementById('edit-course-modal').classList.add('active');
        }

        // Attendance Modal
        function openAttendanceModal(userId, userName) {
            document.getElementById('modal-user-id').value = userId;
            document.querySelector('#attendance-modal h2').textContent = `ثبت حضوریت: ${userName}`;
            document.getElementById('attendance-modal').classList.add('active');
            
            // Load Courses
            const select = document.getElementById('modal-course-select');
            select.innerHTML = '<option value="">در حال بارگذاری...</option>';
            fetch(window.location.href.split('?')[0] + '?get_user_courses=' + userId, {
                headers: {'X-Requested-With': 'XMLHttpRequest'}
            })
            .then(r => r.json())
            .then(courses => {
                if(courses && courses.length > 0) {
                    select.innerHTML = courses.map(c => `<option value="${c.id}">${c.title}</option>`).join('');
                } else {
                    select.innerHTML = '<option>دوره‌ای یافت نشد</option>';
                }
            });
        }

        // Registrants Modal
        function viewCourseRegistrants(courseId, title) {
            document.getElementById('registrants-modal-title').textContent = `ثبت نامی های: ${title}`;
            document.getElementById('registrants-modal').classList.add('active');
            
            fetch(`?action=get_registrants&course_id=${courseId}`)
            .then(r => r.json())
            .then(data => {
                if(data.success && data.registrants.length > 0) {
                    let html = '<table class="table"><thead><tr><th>نام</th><th>تلفن</th><th>ایمیل</th><th>وضعیت</th></tr></thead><tbody>';
                    data.registrants.forEach(r => {
                        html += `<tr><td>${r.first_name} ${r.last_name}</td><td>${r.phone}</td><td>${r.email}</td><td><span class="badge badge-${r.status}">${r.status}</span></td></tr>`;
                    });
                    html += '</tbody></table>';
                    document.getElementById('registrants-list').innerHTML = html;
                } else {
                    document.getElementById('registrants-list').innerHTML = '<p style="text-align:center; padding:20px;">ثبت نامی وجود ندارد.</p>';
                }
            });
        }

        // Close modal on outside click
        window.onclick = function(e) {
            if (e.target.classList.contains('modal-overlay')) {
                e.target.classList.remove('active');
            }
        }

        // Handle Form Submit with AJAX for Attendance (to avoid reload)
        document.getElementById('attendance-form').addEventListener('submit', function(e) {
            e.preventDefault();

            // Validate form fields
            const userId = document.getElementById('modal-user-id').value;
            const courseId = document.getElementById('modal-course-select').value;

            if (!userId || !courseId) {
                alert('لطفاً کاربر و دوره را انتخاب کنید.');
                return;
            }

            const formData = new FormData(this);
            console.log('Sending attendance data:', Object.fromEntries(formData)); // Debug log

            fetch(window.location.href, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                console.log('Server response:', data); // Debug log
                if(data.success) {
                    alert('حضوریت با موفقیت ثبت شد');
                    closeModal('attendance-modal');
                    location.reload();
                } else {
                    alert(data.message || 'خطا در ثبت حضوریت');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('خطا در اتصال به سرور: ' + error.message);
            });
        });

        // Initial Tab State (PHP set active_tab)
        document.addEventListener('DOMContentLoaded', () => {
            const activeTab = "<?php echo $active_tab; ?>";
            if(activeTab && document.getElementById(activeTab)) {
                switchTab(activeTab);
            }
        });
    </script>
</body>
</html>