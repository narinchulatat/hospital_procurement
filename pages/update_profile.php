<?php
// update_profile.php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// ตรวจสอบว่าเป็นการส่งข้อมูลแบบ POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    
    // รับข้อมูลจากฟอร์ม
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $department_id = !empty($_POST['department_id']) ? $_POST['department_id'] : null;
    $sub_department_id = !empty($_POST['sub_department_id']) ? $_POST['sub_department_id'] : null;
    
    // ตรวจสอบความถูกต้องของข้อมูล
    if (empty($full_name)) {
        $_SESSION['profile_error'] = 'กรุณาระบุชื่อเต็ม';
        header('Location: profile.php');
        exit;
    }
    
    if (empty($email)) {
        $_SESSION['profile_error'] = 'กรุณาระบุอีเมล';
        header('Location: profile.php');
        exit;
    }
    
    // ตรวจสอบรูปแบบอีเมล
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['profile_error'] = 'รูปแบบอีเมลไม่ถูกต้อง';
        header('Location: profile.php');
        exit;
    }
    
    // ตรวจสอบความสัมพันธ์ระหว่างแผนกและหน่วยงานย่อย
    if (!empty($sub_department_id) && !empty($department_id)) {
        $check_query = "SELECT id FROM sub_departments WHERE id = :sub_id AND department_id = :dept_id";
        $check_stmt = $pdo->prepare($check_query);
        $check_stmt->execute([
            'sub_id' => $sub_department_id,
            'dept_id' => $department_id
        ]);
        
        if ($check_stmt->rowCount() == 0) {
            // หน่วยงานย่อยไม่ได้อยู่ภายใต้แผนกที่เลือก
            $sub_department_id = null;
        }
    }
    
    try {
        // อัปเดตข้อมูลผู้ใช้
        $query = "UPDATE users 
                 SET full_name = :full_name, 
                     email = :email, 
                     department_id = :department_id, 
                     sub_department_id = :sub_department_id,
                     updated_at = CURRENT_TIMESTAMP
                 WHERE id = :user_id";
        
        $stmt = $pdo->prepare($query);
        $result = $stmt->execute([
            'full_name' => $full_name,
            'email' => $email,
            'department_id' => $department_id,
            'sub_department_id' => $sub_department_id,
            'user_id' => $user_id
        ]);
        
        if ($result) {
            $_SESSION['profile_success'] = 'อัปเดตข้อมูลสำเร็จ';
        } else {
            $_SESSION['profile_error'] = 'เกิดข้อผิดพลาดในการอัปเดตข้อมูล กรุณาลองใหม่อีกครั้ง';
        }
    } catch (PDOException $e) {
        $_SESSION['profile_error'] = 'เกิดข้อผิดพลาดในระบบ: ' . $e->getMessage();
    }
    
    // กลับไปที่หน้าโปรไฟล์
    header('Location: profile.php');
    exit;
} else {
    // ถ้าไม่ใช่การส่งข้อมูลแบบ POST ให้กลับไปที่หน้าโปรไฟล์
    header('Location: profile.php');
    exit;
}