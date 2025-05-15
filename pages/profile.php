<?php
// profile.php
// session_start();
// require_once '../includes/db.php';  // เชื่อมต่อฐานข้อมูล
// require_once '../includes/functions.php';  // ฟังก์ชันที่ใช้งานในระบบ

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// ดึงข้อมูลผู้ใช้ที่ล็อกอิน
$user_id = $_SESSION['user_id'];
$query = "SELECT u.*, d.name_th as department_name, sd.name_th as sub_department_name, r.role_name
          FROM users u
          LEFT JOIN departments d ON u.department_id = d.id
          LEFT JOIN sub_departments sd ON u.sub_department_id = sd.id
          LEFT JOIN roles r ON u.role_id = r.id
          WHERE u.id = :id";
$stmt = $pdo->prepare($query);
$stmt->execute(['id' => $user_id]);
$user = $stmt->fetch();

// ดึงข้อมูลแผนกทั้งหมดสำหรับการแก้ไข
$dept_query = "SELECT * FROM departments ORDER BY name_th";
$dept_stmt = $pdo->prepare($dept_query);
$dept_stmt->execute();
$departments = $dept_stmt->fetchAll();

// สร้างฟังก์ชันสำหรับดึงหน่วยงานย่อยตามแผนก
function getSubDepartments($pdo, $dept_id) {
    $query = "SELECT * FROM sub_departments WHERE department_id = :dept_id ORDER BY name_th";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['dept_id' => $dept_id]);
    return $stmt->fetchAll();
}

// ดึงหน่วยงานย่อยของแผนกปัจจุบัน
$sub_departments = [];
if (!empty($user['department_id'])) {
    $sub_departments = getSubDepartments($pdo, $user['department_id']);
}

// ดึงข้อมูลบทบาททั้งหมด
$role_query = "SELECT * FROM roles ORDER BY role_name";
$role_stmt = $pdo->prepare($role_query);
$role_stmt->execute();
$roles = $role_stmt->fetchAll();

// ดึงประวัติการเบิกของผู้ใช้
$request_query = "SELECT pr.*, io.item_name, ps.status_name 
                  FROM procurement_requests pr
                  JOIN item_options io ON pr.item_option_id = io.id
                  JOIN procurement_statuses ps ON pr.status_id = ps.id
                  WHERE pr.user_id = :user_id
                  ORDER BY pr.request_date DESC
                  LIMIT 10";
$request_stmt = $pdo->prepare($request_query);
$request_stmt->execute(['user_id' => $user_id]);
$requests = $request_stmt->fetchAll();

// ฟังก์ชันตรวจสอบหากมีข้อผิดพลาดจากการอัปเดต
$error_message = '';
$success_message = '';
if (isset($_SESSION['profile_error'])) {
    $error_message = $_SESSION['profile_error'];
    unset($_SESSION['profile_error']);
}
if (isset($_SESSION['profile_success'])) {
    $success_message = $_SESSION['profile_success'];
    unset($_SESSION['profile_success']);
}

// Include header
// include_once '../includes/header.php';
?>

<div class="container content-wrapper my-4">
    <!-- แสดงข้อความแจ้งเตือน -->
    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success" role="alert">
            <?php echo htmlspecialchars($success_message); ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- ข้อมูลส่วนตัว -->
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h3 class="card-title mb-0">ข้อมูลส่วนตัว</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <tbody>
                                <tr>
                                    <th>ชื่อผู้ใช้:</th>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                </tr>
                                <tr>
                                    <th>ชื่อเต็ม:</th>
                                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                </tr>
                                <tr>
                                    <th>อีเมล:</th>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                </tr>
                                <tr>
                                    <th>แผนก:</th>
                                    <td><?php echo htmlspecialchars($user['department_name'] ?? 'ไม่ระบุ'); ?></td>
                                </tr>
                                <tr>
                                    <th>หน่วยงานย่อย:</th>
                                    <td><?php echo htmlspecialchars($user['sub_department_name'] ?? 'ไม่ระบุ'); ?></td>
                                </tr>
                                <tr>
                                    <th>บทบาท:</th>
                                    <td><?php echo htmlspecialchars($user['role_name'] ?? 'ไม่ระบุ'); ?></td>
                                </tr>
                                <tr>
                                    <th>สถานะ:</th>
                                    <td>
                                        <?php if ($user['status'] == 'active'): ?>
                                            <span class="badge bg-success">ใช้งาน</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">ไม่ใช้งาน</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                            <i class="bi bi-pencil-square"></i> แก้ไขข้อมูล
                        </button>
                        <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                            <i class="bi bi-key"></i> เปลี่ยนรหัสผ่าน
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ประวัติการเบิก -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h3 class="card-title mb-0">ประวัติการเบิกล่าสุด</h3>
                </div>
                <div class="card-body">
                    <?php if (count($requests) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-striped">
                                <thead>
                                    <tr>
                                        <th>วันที่</th>
                                        <th>รายการ</th>
                                        <th>จำนวน</th>
                                        <th>สถานะ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($requests as $request): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y', strtotime($request['request_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($request['item_name']); ?></td>
                                            <td><?php echo htmlspecialchars($request['quantity']); ?></td>
                                            <td>
                                                <?php if ($request['status_name'] == 'pending' || $request['status'] == 'pending'): ?>
                                                    <span class="badge bg-warning">รอดำเนินการ</span>
                                                <?php elseif ($request['status_name'] == 'approved' || $request['status'] == 'approved'): ?>
                                                    <span class="badge bg-success">อนุมัติแล้ว</span>
                                                <?php elseif ($request['status_name'] == 'rejected' || $request['status'] == 'rejected'): ?>
                                                    <span class="badge bg-danger">ปฏิเสธ</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($request['status_name'] ?? $request['status']); ?></span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            <a href="../procurement/history.php" class="btn btn-outline-primary">ดูประวัติทั้งหมด</a>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">ไม่พบประวัติการเบิก</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal สำหรับการแก้ไขข้อมูล -->
<div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="editProfileModalLabel">แก้ไขข้อมูลส่วนตัว</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- ฟอร์มแก้ไขข้อมูล -->
                <form action="pages/update_profile.php" method="POST">
                    <div class="mb-3">
                        <label for="full_name" class="form-label">ชื่อเต็ม <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">อีเมล <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="department_id" class="form-label">แผนก</label>
                        <select class="form-select" id="department_id" name="department_id">
                            <option value="">-- เลือกแผนก --</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>" <?php echo ($user['department_id'] == $dept['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept['name_th']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="sub_department_id" class="form-label">หน่วยงานย่อย</label>
                        <select class="form-select" id="sub_department_id" name="sub_department_id">
                            <option value="">-- เลือกหน่วยงานย่อย --</option>
                            <?php foreach ($sub_departments as $sub_dept): ?>
                                <option value="<?php echo $sub_dept['id']; ?>" <?php echo ($user['sub_department_id'] == $sub_dept['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($sub_dept['name_th']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                        <button type="submit" class="btn btn-primary">บันทึกการแก้ไข</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal สำหรับการเปลี่ยนรหัสผ่าน -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title" id="changePasswordModalLabel">เปลี่ยนรหัสผ่าน</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- ฟอร์มเปลี่ยนรหัสผ่าน -->
                <form action="change_password.php" method="POST">
                    <div class="mb-3">
                        <label for="current_password" class="form-label">รหัสผ่านปัจจุบัน <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">รหัสผ่านใหม่ <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required minlength="8">
                        <div class="form-text">รหัสผ่านต้องมีความยาวอย่างน้อย 8 ตัวอักษร</div>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">ยืนยันรหัสผ่านใหม่ <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="8">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                        <button type="submit" class="btn btn-warning">บันทึกรหัสผ่านใหม่</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Function to load sub-departments based on selected department
    function loadSubDepartments(departmentId) {
        // Clear existing options
        const subDeptSelect = document.getElementById('sub_department_id');
        subDeptSelect.innerHTML = '<option value="">-- เลือกหน่วยงานย่อย --</option>';
        
        if (!departmentId) return;
        
        // AJAX request to get sub departments
        fetch(`pages/get_sub_departments.php?dept_id=${departmentId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    data.sub_departments.forEach(subDept => {
                        const option = document.createElement('option');
                        option.value = subDept.id;
                        option.textContent = subDept.name_th;
                        
                        // Set selected if matches current user's sub department
                        if (subDept.id == <?php echo json_encode($user['sub_department_id']); ?>) {
                            option.selected = true;
                        }
                        
                        subDeptSelect.appendChild(option);
                    });
                }
            })
            .catch(error => console.error('Error loading sub departments:', error));
    }
    
    // Add event listener to department select
    const deptSelect = document.getElementById('department_id');
    if (deptSelect) {
        deptSelect.addEventListener('change', function() {
            loadSubDepartments(this.value);
        });
    }
    
    // Form validation for password change
    const passwordForm = document.querySelector('#changePasswordModal form');
    if (passwordForm) {
        passwordForm.addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('รหัสผ่านใหม่และการยืนยันรหัสผ่านไม่ตรงกัน กรุณาตรวจสอบอีกครั้ง');
            }
        });
    }
});
</script>

<?php
// Include footer
// include_once '../includes/footer.php';
?>