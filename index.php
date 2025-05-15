<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Define base path to make includes work properly
define('BASE_PATH', __DIR__);

// Include required files
require_once BASE_PATH . '/includes/db.php';
require_once BASE_PATH . '/includes/auth.php';
require_once BASE_PATH . '/includes/functions.php';

// Get requested page or default to login
$page = isset($_GET['page']) ? $_GET['page'] : 'login';

// Include header
include BASE_PATH . '/includes/header.php';

// Route to appropriate page
switch ($page) {
    case 'login':
        include BASE_PATH . '/pages/login.php';
        break;
    case 'logout':
        include BASE_PATH . '/pages/logout.php';
        break;
    case 'users':
        checkRole('admin');
        include BASE_PATH . '/pages/admin/users.php';
        break;
    case 'profile':  // เพิ่มเคสสำหรับหน้า profile
        checkRole('user');  // สามารถปรับให้เฉพาะผู้ใช้เท่านั้นที่เข้าได้
        include BASE_PATH . '/pages/profile.php';
        break;
    default:
        echo "<div class='container mt-5'><div class='alert alert-danger'>404 - Page not found</div></div>";
}

// Include footer
include BASE_PATH . '/includes/footer.php';
