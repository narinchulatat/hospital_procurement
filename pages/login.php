<?php
// require_once 'includes/db.php'; // เชื่อมต่อฐานข้อมูล
require_once '../includes/db.php';  // ✅ ถูกต้อง เพราะ login.php อยู่ใน /pages

// if ($pdo) {
//   echo "เชื่อมต่อฐานข้อมูลสำเร็จ";
// } else {
//   echo "การเชื่อมต่อฐานข้อมูลล้มเหลว";
// }
// echo "<br \>";
if (session_status() == PHP_SESSION_NONE) {
  session_start();
}

// echo password_hash("1234", PASSWORD_DEFAULT);

// $hash = '$2y$10$PyyzMGDDo6x0ydsU9bMG1eKUxYTVLZEDf3LSROEDuBHYKlRA/0n9q';

// // ลองรหัสผ่านหลายค่า
// $test_passwords = ['1234', 'admin123', 'password', 'Admin@123'];

// foreach ($test_passwords as $pw) {
//     echo "Testing with password: $pw => ";
//     if (password_verify($pw, $hash)) {
//         echo "MATCH ✅<br>";
//     } else {
//         echo "❌<br>";
//     }
// }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = $_POST['username'];
  $password = $_POST['password'];

  $sql = "SELECT * FROM users WHERE username = :username AND status = 'active'";
  $stmt = $pdo->prepare($sql);

  $stmt->execute(['username' => $username]);

  // if (!$stmt->execute(['username' => $username])) {
  //   print_r($stmt->errorInfo());
  //   exit;
  // }

  $user = $stmt->fetch(PDO::FETCH_ASSOC);
  // echo "<pre>";
  // var_dump($user);
  // echo "</pre>";

  if ($user && password_verify($password, $user['password'])) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role_id'] = $user['role_id'];

    echo "<script>
      Swal.fire({
        title: 'เข้าสู่ระบบสำเร็จ',
        text: 'ยินดีต้อนรับ, " . htmlspecialchars($user['username']) . "!',
        icon: 'success',
        confirmButtonText: 'ตกลง'
      }).then(() => {
        window.location.href = 'index.php?page=users';
      });
    </script>";
    exit();
  } else {
    echo "<script>
      Swal.fire({
        title: 'เข้าสู่ระบบล้มเหลว',
        text: 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง',
        icon: 'error',
        confirmButtonText: 'ลองอีกครั้ง'
      });
    </script>";
  }
}
?>

<div class="login-container">
  <h2>เข้าสู่ระบบ</h2>
  <form method="post" action="index.php?page=login">
    <div class="form-group">
      <input type="text" name="username" placeholder="ชื่อผู้ใช้" required>
    </div>
    <div class="form-group">
      <input type="password" name="password" placeholder="รหัสผ่าน" required>
    </div>
    <button type="submit">เข้าสู่ระบบ</button>
  </form>
</div>

<style>
  body {
    font-family: 'Sarabun', sans-serif;
    background: #f5f6fa;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    margin: 0;
    padding: 20px;
  }

  .login-container {
    background: #fff;
    padding: 2rem 3rem;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    width: 100%;
    max-width: 400px;
    margin: 0 auto;
  }

  .login-container h2 {
    text-align: center;
    margin-bottom: 1.5rem;
  }

  .form-group {
    margin-bottom: 1rem;
  }

  input[type="text"],
  input[type="password"] {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ccc;
    border-radius: 8px;
    box-sizing: border-box;
  }

  button {
    width: 100%;
    padding: 0.75rem;
    background: #3498db;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    cursor: pointer;
  }

  button:hover {
    background: #2980b9;
  }
</style>

<!-- เพิ่ม Script สำหรับ SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>