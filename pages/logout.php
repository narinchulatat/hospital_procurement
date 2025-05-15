<?php
// เมื่อผู้ใช้ออกจากระบบ ให้ล้างค่า session และ redirect ไปยังหน้า login
session_unset();
session_destroy();

// ใช้ JavaScript เพื่อแสดงข้อความการออกจากระบบสำเร็จ
echo "<script>
    Swal.fire({
        title: 'ออกจากระบบสำเร็จ',
        text: 'ขอบคุณที่ใช้บริการ',
        icon: 'success',
        timer: 2000,
        showConfirmButton: false
    }).then(function() {
        window.location.href = 'index.php?page=login';
    });
</script>";
exit();
