<?php
// change_password.php
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
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // ตรวจสอบความถูกต้องของข้อมูล
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $_SESSION['profile_error'] = 'กรุณากรอกข้อมูลให้ครบทุกช่อง';
        header('Location: profile.php');
        exit;
    }
    
    // ตรวจสอบว่ารหัสผ่านใหม่และการยืนยันรหัสผ่านตรงกัน
    if ($new_password !== $confirm_password) {
        $_SESSION['profile_error'] = 'รหัสผ่านใหม่และการยืนยันรหัสผ่านไม่ตรงกัน';
        header('Location: profile.php');
        exit;
    }
    
    // ตรวจสอบความยาวของรหัสผ่านใหม่
    if (strlen($new_password) < 8) {
        $_SESSION['profile_error'] = 'รหัสผ่านใหม่ต้องมีความยาวอย่างน้อย 8 ตัวอักษร';
        header('Location: profile.php');
        exit;
    }
    
    try {
        // ดึงรหัสผ่านปัจจุบันของผู้ใช้
        $query = "SELECT password FROM users WHERE id = :user_id";
        $stmt = $pdo->prepare($query);
        $stmt->execute(['user_id' => $user_id]);
        $user = $stmt->fetch();
        
        // ตรวจสอบรหัสผ่านปัจจุบัน
        if (!$user || !password_verify($current_password, $user['password'])) {
            $_SESSION['profile_error'] = 'รหัสผ่านปัจจุบันไม่ถูกต้อง';
            header('Location: profile.php');
            exit;
        }
        
        // เข้ารหัสรหัสผ่านใหม่
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // อัปเดตรหัสผ่านใหม่
        $update_query = "UPDATE users SET password = :password, updated_at = CURRENT_TIMESTAMP WHERE id = :user_id";
        $update_stmt = $pdo->prepare($update_query);
        $result = $update_stmt->execute([
            'password' => $hashed_password,
            'user_id' => $user_id
        ]);
        
        if ($result) {
            $_SESSION['profile_success'] = 'เปลี่ยนรหัสผ่านสำเร็จ';
        } else {
            $_SESSION['profile_error'] = 'เกิดข้อผิดพลาดในการเปลี่ยนรหัสผ่าน กรุณาลองใหม่อีกครั้ง';
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