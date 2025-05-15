<?php
// Helper functions for the application

/**
 * Format date to Thai format
 * 
 * @param string $date Date in Y-m-d format
 * @return string Formatted date in d/m/Y format
 */
function formatDateThai($date) {
    if (empty($date) || $date == '0000-00-00') {
        return '-';
    }
    
    $date_obj = new DateTime($date);
    return $date_obj->format('d/m/Y');
}

/**
 * Format currency in Thai format
 * 
 * @param float $amount Amount to format
 * @return string Formatted amount with Thai baht symbol
 */
function formatCurrency($amount) {
    return number_format($amount, 2) . ' บาท';
}

/**
 * Generate a random password
 * 
 * @param int $length Length of the password
 * @return string Random password
 */
function generateRandomPassword($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
    $password = '';
    
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[rand(0, strlen($chars) - 1)];
    }
    
    return $password;
}

/**
 * Sanitize input to prevent XSS attacks
 * 
 * @param string $input Input to sanitize
 * @return string Sanitized input
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Display alert message with SweetAlert2
 * 
 * @param string $title Title of the alert
 * @param string $message Message of the alert
 * @param string $type Type of the alert (success, error, warning, info)
 * @param string $redirect URL to redirect after alert (optional)
 */
function showAlert($title, $message, $type = 'info', $redirect = '') {
    echo "<script>
        Swal.fire({
            title: '" . addslashes($title) . "',
            text: '" . addslashes($message) . "',
            icon: '" . $type . "',
            confirmButtonText: 'ตกลง'
        })" . ($redirect ? ".then(() => { window.location.href = '" . $redirect . "'; })" : "") . ";
    </script>";
}

/**
 * Log system activity
 * 
 * @param string $action Action performed
 * @param string $details Details of the action
 * @param int $userId User ID who performed the action
 */
function logActivity($action, $details, $userId = null) {
    global $pdo;
    
    if ($userId === null && isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
    }
    
    $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, created_at) VALUES (:user_id, :action, :details, NOW())");
    $stmt->execute([
        'user_id' => $userId,
        'action' => $action,
        'details' => $details
    ]);
}




// includes/functions.php (ส่วนที่เกี่ยวข้องกับการจัดการโปรไฟล์)

/**
 * ตรวจสอบว่าผู้ใช้ล็อกอินหรือไม่
 * @return bool
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * ตรวจสอบว่าผู้ใช้มีสิทธิ์เข้าถึงหน้าหรือไม่
 * @param array $allowed_roles รายการบทบาทที่อนุญาต
 * @return bool
 */
function has_permission($allowed_roles = []) {
    if (!is_logged_in()) {
        return false;
    }
    
    // ถ้าไม่มีการระบุบทบาทที่อนุญาต ให้อนุญาตทุกคนที่ล็อกอิน
    if (empty($allowed_roles)) {
        return true;
    }
    
    // ตรวจสอบว่าบทบาทของผู้ใช้อยู่ในรายการที่อนุญาตหรือไม่
    global $pdo;
    $user_id = $_SESSION['user_id'];
    
    $query = "SELECT r.role_name 
              FROM users u 
              JOIN roles r ON u.role_id = r.id 
              WHERE u.id = :user_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['user_id' => $user_id]);
    $user_role = $stmt->fetchColumn();
    
    return in_array($user_role, $allowed_roles);
}

/**
 * ดึงข้อมูลผู้ใช้ปัจจุบัน
 * @return array|false ข้อมูลผู้ใช้หรือ false หากไม่พบ
 */
// function get_current_user() {
//     if (!is_logged_in()) {
//         return false;
//     }
    
//     global $pdo;
//     $user_id = $_SESSION['user_id'];
    
//     $query = "SELECT u.*, d.name_th as department_name, sd.name_th as sub_department_name, r.role_name
//               FROM users u
//               LEFT JOIN departments d ON u.department_id = d.id
//               LEFT JOIN sub_departments sd ON u.sub_department_id = sd.id
//               LEFT JOIN roles r ON u.role_id = r.id
//               WHERE u.id = :id";
//     $stmt = $pdo->prepare($query);
//     $stmt->execute(['id' => $user_id]);
//     return $stmt->fetch(PDO::FETCH_ASSOC);
// }

/**
 * ดึงข้อมูลแผนกทั้งหมด
 * @return array รายการแผนกทั้งหมด
 */
function get_all_departments() {
    global $pdo;
    $query = "SELECT * FROM departments ORDER BY name_th";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * ดึงข้อมูลหน่วยงานย่อยตามแผนก
 * @param int $department_id รหัสแผนก
 * @return array รายการหน่วยงานย่อย
 */
function get_sub_departments($department_id) {
    global $pdo;
    $query = "SELECT * FROM sub_departments WHERE department_id = :dept_id ORDER BY name_th";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['dept_id' => $department_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * ตรวจสอบความสัมพันธ์ระหว่างแผนกและหน่วยงานย่อย
 * @param int $department_id รหัสแผนก
 * @param int $sub_department_id รหัสหน่วยงานย่อย
 * @return bool
 */
function validate_department_sub_department($department_id, $sub_department_id) {
    if (empty($department_id) || empty($sub_department_id)) {
        return false;
    }
    
    global $pdo;
    $query = "SELECT COUNT(*) FROM sub_departments 
              WHERE id = :sub_id AND department_id = :dept_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        'sub_id' => $sub_department_id,
        'dept_id' => $department_id
    ]);
    
    return $stmt->fetchColumn() > 0;
}

/**
 * เตรียมและแสดงข้อความแจ้งเตือน (Alert)
 * @param string $type ประเภทข้อความ (success, error, info, warning)
 * @param string $message ข้อความที่ต้องการแสดง
 * @return string HTML สำหรับแสดงข้อความแจ้งเตือน
 */
function display_alert($type, $message) {
    $alert_class = '';
    switch ($type) {
        case 'success':
            $alert_class = 'alert-success';
            break;
        case 'error':
            $alert_class = 'alert-danger';
            break;
        case 'info':
            $alert_class = 'alert-info';
            break;
        case 'warning':
            $alert_class = 'alert-warning';
            break;
        default:
            $alert_class = 'alert-info';
    }
    
    return '<div class="alert ' . $alert_class . ' alert-dismissible fade show" role="alert">
                ' . htmlspecialchars($message) . '
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>';
}

/**
 * ประวัติการเบิกของผู้ใช้
 * @param int $user_id รหัสผู้ใช้
 * @param int $limit จำนวนรายการที่ต้องการดึง (0 = ไม่จำกัด)
 * @return array รายการประวัติการเบิก
 */
function get_user_procurement_history($user_id, $limit = 0) {
    global $pdo;
    
    $query = "SELECT pr.*, io.item_name, ps.status_name 
              FROM procurement_requests pr
              JOIN item_options io ON pr.item_option_id = io.id
              LEFT JOIN procurement_statuses ps ON pr.status_id = ps.id
              WHERE pr.user_id = :user_id
              ORDER BY pr.request_date DESC";
    
    if ($limit > 0) {
        $query .= " LIMIT " . (int)$limit;
    }
    
    $stmt = $pdo->prepare($query);
    $stmt->execute(['user_id' => $user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}