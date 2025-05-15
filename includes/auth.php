<?php
// Start session (if not already started)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * ตรวจสอบสิทธิ์การเข้าถึงตาม role
 * 
 * @param string $role ชื่อสิทธิ์ที่ต้องการตรวจสอบ
 * @return void
 */
function checkRole($role) {
    // ตรวจสอบว่ามีการล็อกอินหรือไม่
    if (!isset($_SESSION['user_id'])) {
        // ถ้ายังไม่ได้ล็อกอิน ให้ redirect ไปที่หน้า login
        header('Location: index.php?page=login');
        exit();
    }

    // ตรวจสอบสิทธิ์ตาม role
    if ($role == 'admin' && $_SESSION['role_id'] != 1) {
        // ถ้าไม่ใช่ admin (role_id = 1) ให้แสดงข้อความ error
        echo "<div class='container mt-4'>";
        echo "<div class='alert alert-danger'>คุณไม่มีสิทธิ์เข้าถึงหน้านี้</div>";
        echo "</div>";
        include 'includes/footer.php';
        exit();
    }
}

/**
 * ตรวจสอบว่ามีการล็อกอินแล้วหรือไม่
 * 
 * @return boolean true ถ้ามีการล็อกอินแล้ว, false ถ้ายังไม่ได้ล็อกอิน
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * ดึงข้อมูลผู้ใช้ปัจจุบัน
 * 
 * @return array|null ข้อมูลผู้ใช้ หรือ null ถ้ายังไม่ได้ล็อกอิน
 */
function getCurrentUser() {
    global $pdo;
    
    if (!isLoggedIn()) {
        return null;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->execute(['id' => $_SESSION['user_id']]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * ดึงชื่อ role จาก role_id
 * 
 * @param int $roleId รหัส role
 * @return string ชื่อของ role
 */
function getRoleName($roleId) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT role_name FROM roles WHERE id = :id");
    $stmt->execute(['id' => $roleId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result ? $result['role_name'] : 'Unknown Role';
}