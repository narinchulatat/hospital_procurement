<?php
// api/get_sub_departments.php
session_start();
require_once '../includes/db.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'กรุณาเข้าสู่ระบบ']);
    exit;
}

// ตรวจสอบว่ามีการส่งพารามิเตอร์ dept_id
if (!isset($_GET['dept_id']) || empty($_GET['dept_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'กรุณาระบุแผนก']);
    exit;
}

$dept_id = $_GET['dept_id'];

try {
    // ดึงข้อมูลหน่วยงานย่อยตามแผนก
    $query = "SELECT * FROM sub_departments WHERE department_id = :dept_id ORDER BY name_th";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['dept_id' => $dept_id]);
    $sub_departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ส่งข้อมูลกลับในรูปแบบ JSON
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'sub_departments' => $sub_departments
    ]);
} catch (PDOException $e) {
    // กรณีเกิดข้อผิดพลาด
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage()
    ]);
}