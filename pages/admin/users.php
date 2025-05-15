<?php
// หน้าจัดการผู้ใช้งาน (สำหรับ Admin เท่านั้น)

// ตรวจสอบสิทธิ์การเข้าถึง
checkRole('admin');

// การจัดการกับการส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ตรวจสอบการดำเนินการ (เพิ่ม/แก้ไข/ลบ)
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        // เพิ่มผู้ใช้ใหม่
        $username = sanitizeInput($_POST['username']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $full_name = sanitizeInput($_POST['full_name']);
        $email = sanitizeInput($_POST['email']);
        $role_id = (int)$_POST['role_id'];
        $department_id = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null;
        $sub_department_id = !empty($_POST['sub_department_id']) ? (int)$_POST['sub_department_id'] : null;
        
        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, email, role_id, department_id, sub_department_id, status, created_at) 
                                VALUES (:username, :password, :full_name, :email, :role_id, :department_id, :sub_department_id, 'active', NOW())");
            $stmt->execute([
                'username' => $username,
                'password' => $password,
                'full_name' => $full_name,
                'email' => $email,
                'role_id' => $role_id,
                'department_id' => $department_id,
                'sub_department_id' => $sub_department_id
            ]);
            
            // บันทึกกิจกรรม
            logActivity('เพิ่มผู้ใช้', 'เพิ่มผู้ใช้ใหม่: ' . $username);
            
            // แสดงข้อความสำเร็จ
            showAlert('สำเร็จ', 'เพิ่มผู้ใช้งานเรียบร้อยแล้ว', 'success', 'index.php?page=users');
        } catch (PDOException $e) {
            // แสดงข้อความผิดพลาด
            showAlert('ผิดพลาด', 'ไม่สามารถเพิ่มผู้ใช้งาน: ' . $e->getMessage(), 'error');
        }
    } elseif ($action === 'edit') {
        // แก้ไขผู้ใช้
        $user_id = (int)$_POST['user_id'];
        $full_name = sanitizeInput($_POST['full_name']);
        $email = sanitizeInput($_POST['email']);
        $role_id = (int)$_POST['role_id'];
        $status = sanitizeInput($_POST['status']);
        $department_id = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null;
        $sub_department_id = !empty($_POST['sub_department_id']) ? (int)$_POST['sub_department_id'] : null;
        
        try {
            // ตรวจสอบว่ามีการเปลี่ยนรหัสผ่านหรือไม่
            if (!empty($_POST['password'])) {
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET full_name = :full_name, email = :email, 
                                    role_id = :role_id, status = :status, password = :password,
                                    department_id = :department_id, sub_department_id = :sub_department_id, 
                                    updated_at = NOW() WHERE id = :id");
                $params = [
                    'full_name' => $full_name,
                    'email' => $email,
                    'role_id' => $role_id,
                    'status' => $status,
                    'password' => $password,
                    'department_id' => $department_id,
                    'sub_department_id' => $sub_department_id,
                    'id' => $user_id
                ];
            } else {
                $stmt = $pdo->prepare("UPDATE users SET full_name = :full_name, email = :email, 
                                    role_id = :role_id, status = :status,
                                    department_id = :department_id, sub_department_id = :sub_department_id, 
                                    updated_at = NOW() 
                                    WHERE id = :id");
                $params = [
                    'full_name' => $full_name,
                    'email' => $email,
                    'role_id' => $role_id,
                    'status' => $status,
                    'department_id' => $department_id,
                    'sub_department_id' => $sub_department_id,
                    'id' => $user_id
                ];
            }
            
            $stmt->execute($params);
            
            // บันทึกกิจกรรม
            logActivity('แก้ไขผู้ใช้', 'แก้ไขข้อมูลผู้ใช้ ID: ' . $user_id);
            
            // แสดงข้อความสำเร็จ
            showAlert('สำเร็จ', 'แก้ไขข้อมูลผู้ใช้งานเรียบร้อยแล้ว', 'success', 'index.php?page=users');
        } catch (PDOException $e) {
            // แสดงข้อความผิดพลาด
            showAlert('ผิดพลาด', 'ไม่สามารถแก้ไขข้อมูลผู้ใช้งาน: ' . $e->getMessage(), 'error');
        }
    } elseif ($action === 'delete') {
        // ลบผู้ใช้
        $user_id = (int)$_POST['user_id'];
        
        // ป้องกันการลบบัญชีที่กำลังใช้งานอยู่
        if ($user_id === $_SESSION['user_id']) {
            showAlert('ผิดพลาด', 'ไม่สามารถลบบัญชีที่กำลังใช้งานอยู่', 'error');
            exit();
        }
        
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
            $stmt->execute(['id' => $user_id]);
            
            // บันทึกกิจกรรม
            logActivity('ลบผู้ใช้', 'ลบผู้ใช้ ID: ' . $user_id);
            
            // แสดงข้อความสำเร็จ
            showAlert('สำเร็จ', 'ลบผู้ใช้งานเรียบร้อยแล้ว', 'success', 'index.php?page=users');
        } catch (PDOException $e) {
            // แสดงข้อความผิดพลาด
            showAlert('ผิดพลาด', 'ไม่สามารถลบผู้ใช้งาน: ' . $e->getMessage(), 'error');
        }
    }
}

// ดึงข้อมูลผู้ใช้ทั้งหมด
$stmt = $pdo->query("SELECT u.*, r.role_name, d.name_th AS department_name, sd.name_th AS sub_department_name 
                    FROM users u 
                    LEFT JOIN roles r ON u.role_id = r.id 
                    LEFT JOIN departments d ON u.department_id = d.id
                    LEFT JOIN sub_departments sd ON u.sub_department_id = sd.id
                    ORDER BY u.id ASC");
$users = $stmt->fetchAll();

// ดึงข้อมูลบทบาททั้งหมด
$stmt = $pdo->query("SELECT * FROM roles ORDER BY id ASC");
$roles = $stmt->fetchAll();

// ดึงข้อมูลแผนกหลักทั้งหมด
$stmt = $pdo->query("SELECT * FROM departments ORDER BY name_th ASC");
$departments = $stmt->fetchAll();

// ดึงข้อมูลแผนกรองทั้งหมด
$stmt = $pdo->query("SELECT * FROM sub_departments ORDER BY name_th ASC");
$all_sub_departments = $stmt->fetchAll();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">จัดการผู้ใช้งาน</h5>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                        <i class="bx bx-plus"></i> เพิ่มผู้ใช้งาน
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover datatable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>ชื่อผู้ใช้</th>
                                    <th>ชื่อ-นามสกุล</th>
                                    <th>อีเมล</th>
                                    <th>แผนก</th>
                                    <th>หน่วยงาน</th>
                                    <th>บทบาท</th>
                                    <th>สถานะ</th>
                                    <th>สร้างเมื่อ</th>
                                    <th>การจัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['department_name'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($user['sub_department_name'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($user['role_name']); ?></td>
                                    <td>
                                        <?php if ($user['status'] == 'active'): ?>
                                            <span class="badge bg-success">เปิดใช้งาน</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">ปิดใช้งาน</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo formatDateThai($user['created_at']); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-info edit-user" 
                                                data-id="<?php echo $user['id']; ?>"
                                                data-username="<?php echo htmlspecialchars($user['username']); ?>"
                                                data-full_name="<?php echo htmlspecialchars($user['full_name']); ?>"
                                                data-email="<?php echo htmlspecialchars($user['email']); ?>"
                                                data-role="<?php echo $user['role_id']; ?>"
                                                data-department="<?php echo $user['department_id'] ?? ''; ?>"
                                                data-sub-department="<?php echo $user['sub_department_id'] ?? ''; ?>"
                                                data-status="<?php echo $user['status']; ?>"
                                                data-bs-toggle="modal" data-bs-target="#editUserModal">
                                            <i class="bx bx-edit"></i> แก้ไข
                                        </button>
                                        
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <button type="button" class="btn btn-sm btn-danger delete-user" 
                                                data-id="<?php echo $user['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($user['username']); ?>">
                                            <i class="bx bx-trash"></i> ลบ
                                        </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal เพิ่มผู้ใช้งาน -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">เพิ่มผู้ใช้งานใหม่</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="index.php?page=users">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="mb-3">
                        <label class="form-label">ชื่อผู้ใช้</label>
                        <input type="text" class="form-control" name="username" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">รหัสผ่าน</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">ชื่อ-นามสกุล</label>
                        <input type="text" class="form-control" name="full_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">อีเมล</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">แผนก</label>
                        <select class="form-select" name="department_id" id="add_department">
                            <option value="">-- เลือกแผนก --</option>
                            <?php foreach ($departments as $department): ?>
                            <option value="<?php echo $department['id']; ?>"><?php echo htmlspecialchars($department['name_th']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">หน่วยงาน</label>
                        <select class="form-select" name="sub_department_id" id="add_sub_department">
                            <option value="">-- เลือกหน่วยงาน --</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">บทบาท</label>
                        <select class="form-select" name="role_id" required>
                            <?php foreach ($roles as $role): ?>
                            <option value="<?php echo $role['id']; ?>"><?php echo htmlspecialchars($role['role_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">บันทึก</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal แก้ไขผู้ใช้งาน -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">แก้ไขข้อมูลผู้ใช้งาน</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="index.php?page=users">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    
                    <div class="mb-3">
                        <label class="form-label">ชื่อผู้ใช้</label>
                        <input type="text" class="form-control" id="edit_username" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">รหัสผ่านใหม่ (เว้นว่างหากไม่ต้องการเปลี่ยน)</label>
                        <input type="password" class="form-control" name="password">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">ชื่อ-นามสกุล</label>
                        <input type="text" class="form-control" name="full_name" id="edit_full_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">อีเมล</label>
                        <input type="email" class="form-control" name="email" id="edit_email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">แผนก</label>
                        <select class="form-select" name="department_id" id="edit_department">
                            <option value="">-- เลือกแผนก --</option>
                            <?php foreach ($departments as $department): ?>
                            <option value="<?php echo $department['id']; ?>"><?php echo htmlspecialchars($department['name_th']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">หน่วยงาน</label>
                        <select class="form-select" name="sub_department_id" id="edit_sub_department">
                            <option value="">-- เลือกหน่วยงาน --</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">บทบาท</label>
                        <select class="form-select" name="role_id" id="edit_role" required>
                            <?php foreach ($roles as $role): ?>
                            <option value="<?php echo $role['id']; ?>"><?php echo htmlspecialchars($role['role_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">สถานะ</label>
                        <select class="form-select" name="status" id="edit_status" required>
                            <option value="active">เปิดใช้งาน</option>
                            <option value="inactive">ปิดใช้งาน</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">บันทึก</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal ยืนยันการลบ -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">ยืนยันการลบ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>คุณต้องการลบผู้ใช้ <span id="delete_user_name" class="fw-bold"></span> ใช่หรือไม่?</p>
                <p class="text-danger">หมายเหตุ: การดำเนินการนี้ไม่สามารถเรียกคืนได้</p>
            </div>
            <div class="modal-footer">
                <form method="post" action="index.php?page=users">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="user_id" id="delete_user_id">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-danger">ลบ</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// เตรียมข้อมูลแผนกรองสำหรับ JavaScript
const subDepartments = <?php echo json_encode($all_sub_departments); ?>;

$(document).ready(function() {
    // เมื่อคลิกปุ่มแก้ไข
    $('.edit-user').click(function() {
        var id = $(this).data('id');
        var username = $(this).data('username');
        var full_name = $(this).data('full_name');
        var email = $(this).data('email');
        var role = $(this).data('role');
        var department = $(this).data('department');
        var subDepartment = $(this).data('sub-department');
        var status = $(this).data('status');
        
        $('#edit_user_id').val(id);
        $('#edit_username').val(username);
        $('#edit_full_name').val(full_name);
        $('#edit_email').val(email);
        $('#edit_role').val(role);
        $('#edit_department').val(department);
        $('#edit_status').val(status);
        
        // โหลดข้อมูลแผนกรองตามแผนกหลัก
        loadSubDepartments('edit', department, subDepartment);
    });
    
    // เมื่อคลิกปุ่มลบ
    $('.delete-user').click(function() {
        var id = $(this).data('id');
        var name = $(this).data('name');
        
        $('#delete_user_id').val(id);
        $('#delete_user_name').text(name);
        
        $('#deleteUserModal').modal('show');
    });
    
    // เมื่อเลือกแผนก (เพิ่มผู้ใช้)
    $('#add_department').change(function() {
        var departmentId = $(this).val();
        loadSubDepartments('add', departmentId);
    });
    
    // เมื่อเลือกแผนก (แก้ไขผู้ใช้)
    $('#edit_department').change(function() {
        var departmentId = $(this).val();
        loadSubDepartments('edit', departmentId);
    });
    
    // ฟังก์ชันโหลดข้อมูลแผนกรอง
    function loadSubDepartments(modalType, departmentId, selectedSubDept = '') {
        // เคลียร์ตัวเลือกเดิม
        var selectElement = modalType === 'add' ? $('#add_sub_department') : $('#edit_sub_department');
        selectElement.empty();
        selectElement.append('<option value="">-- เลือกหน่วยงาน --</option>');
        
        if (departmentId) {
            // กรองแผนกรองตามแผนกหลัก
            var filteredSubDepts = subDepartments.filter(function(subDept) {
                return subDept.department_id == departmentId;
            });
            
            // เพิ่มตัวเลือกแผนกรอง
            $.each(filteredSubDepts, function(index, subDept) {
                var option = $('<option></option>')
                    .attr('value', subDept.id)
                    .text(subDept.name_th);
                
                if (selectedSubDept && selectedSubDept == subDept.id) {
                    option.attr('selected', 'selected');
                }
                
                selectElement.append(option);
            });
        }
    }
});
</script>