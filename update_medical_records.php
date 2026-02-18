<?php
session_start();
require "db.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'ابتدا باید وارد شوید']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $medical_conditions = $_POST['medical_conditions'] ?? '';
    
    // Get existing medical record if any
    $medical_record = null;
    try {
        $stmt = $pdo->prepare("SELECT * FROM medical_records WHERE user_id = ? ORDER BY uploaded_at DESC LIMIT 1");
        $stmt->execute([$user_id]);
        $medical_record = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Table might not exist, try to create it
        try {
            $createTable = "CREATE TABLE IF NOT EXISTS medical_records (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                medical_conditions TEXT,
                certificate_path VARCHAR(500),
                uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )";
            $pdo->exec($createTable);
        } catch (Exception $e2) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'خطا در ایجاد جدول سوابق پزشکی']);
            exit;
        }
    }
    
    // Handle file upload if provided
    $certificate_path = $medical_record['certificate_path'] ?? null; // Keep existing if no new file
    
    if (isset($_FILES['medical_certificate']) && $_FILES['medical_certificate']['error'] == 0) {
        $allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png'];
        $file_extension = strtolower(pathinfo($_FILES['medical_certificate']['name'], PATHINFO_EXTENSION));
        
        if (in_array($file_extension, $allowed_extensions)) {
            // Create uploads directory if it doesn't exist
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Generate unique filename
            $filename = 'medical_cert_' . $user_id . '_' . time() . '.' . $file_extension;
            $target_path = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['medical_certificate']['tmp_name'], $target_path)) {
                $certificate_path = $target_path;
                
                // Delete old file if exists and it's in uploads directory
                if ($medical_record && $medical_record['certificate_path'] && 
                    strpos($medical_record['certificate_path'], 'uploads/') === 0 &&
                    file_exists($medical_record['certificate_path'])) {
                    unlink($medical_record['certificate_path']);
                }
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'آپلود فایل با خطا مواجه شد.']);
                exit;
            }
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'فرمت فایل مجاز نیست. فقط PDF، JPG، JPEG و PNG مجاز هستند.']);
            exit;
        }
    }
    
    try {
        if ($medical_record) {
            // Update existing record
            $stmt = $pdo->prepare("UPDATE medical_records SET medical_conditions = ?, certificate_path = ? WHERE user_id = ?");
            $result = $stmt->execute([$medical_conditions, $certificate_path, $user_id]);
        } else {
            // Insert new record
            $stmt = $pdo->prepare("INSERT INTO medical_records (user_id, medical_conditions, certificate_path) VALUES (?, ?, ?)");
            $result = $stmt->execute([$user_id, $medical_conditions, $certificate_path]);
        }
        
        if ($result) {
            echo json_encode([
                'success' => true, 
                'message' => 'سوابق پزشکی با موفقیت به روز شد!',
                'medical_conditions' => $medical_conditions,
                'certificate_path' => $certificate_path
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'به روزرسانی سوابق پزشکی ناموفق بود.']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'خطا در ذخیره سوابق پزشکی: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'متد درخواست نامعتبر است']);
}
?>