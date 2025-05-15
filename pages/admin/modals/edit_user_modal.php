<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editUserModalLabel">แก้ไขผู้ใช้</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="users.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" id="edit_user_id" name="id">

                    <div class="mb-3">
                        <label for="edit_username" class="form-label">ชื่อผู้ใช้</label>
                        <input type="text" class="form-control" id="edit_username" name="username" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit_full_name" class="form-label">ชื่อเต็ม</label>
                        <input type="text" class="form-control" id="edit_full_name" name="full_name" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit_email" class="form-label">อีเมล</label>
                        <input type="email" class="form-control" id="edit_email" name="email" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit_role_id" class="form-label">บทบาท</label>
                        <select class="form-select" id="edit_role_id" name="role_id" required>
                            <option value="">-- เลือกบทบาท --</option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?= $role['id'] ?>"><?= htmlspecialchars($role['role_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="edit_department_id" class="form-label">แผนกหลัก</label>
                        <select class="form-select" id="edit_department_id" name="department_id" required>
                            <option value="">-- เลือกแผนกหลัก --</option>
                            <?php foreach ($departments as $department): ?>
                                <option value="<?= $department['id'] ?>"><?= htmlspecialchars($department['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="edit_sub_department_id" class="form-label">แผนกย่อย</label>
                        <select class="form-select" id="edit_sub_department_id" name="sub_department_id" required disabled>
                            <option value="">-- เลือกแผนกย่อย --</option>
                        </select>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                    <button type="submit" class="btn btn-primary">บันทึกการเปลี่ยนแปลง</button>
                </div>
            </form>
        </div>
    </div>
</div>
