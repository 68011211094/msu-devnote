<?php
// ============================================================
// includes/auth.php - ตรวจสอบสถานะล็อกอินและสิทธิ์ผู้ใช้ (Middleware)
// วิธีใช้: วางใต้ <?php ของหน้าที่ต้องการปกป้อง แล้วเรียก
//   require_login();   // หน้าที่ต้องล็อกอินก่อน
//   require_admin();   // หน้าที่ต้องเป็นผู้ดูแลระบบเท่านั้น
// ============================================================

// เริ่ม Session ถ้ายังไม่เริ่ม (ข้อมูลล็อกอินเก็บอยู่ใน Session)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ตรวจสอบว่าผู้ใช้ล็อกอินอยู่หรือไม่
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// สร้าง URL ที่ถูกต้อง ไม่ว่าหน้านั้นจะอยู่ชั้นไหน (เช่น หน้าราก หรือ /admin/)
function auth_redirect_url($file) {
    $depth = substr_count(dirname($_SERVER['SCRIPT_NAME']), '/');
    return str_repeat('../', max($depth - 1, 0)) . $file;
}

// บังคับให้ล็อกอินก่อนเข้าหน้า ถ้ายังไม่ล็อกให้ไปหน้าเข้าสู่ระบบ
function require_login() {
    if (!is_logged_in()) {
        header('Location: ' . auth_redirect_url('login.php'));
        exit; // หยุดทำงานทันที ห้ามโหลดเนื้อหาหน้าต่อ
    }
}

// บังคับให้เป็นผู้ดูแลระบบเท่านั้น (เช็ค role จาก Session)
function require_admin() {
    // ยังไม่ล็อกอิน → ไปหน้าเข้าสู่ระบบ
    if (!is_logged_in()) {
        header('Location: ' . auth_redirect_url('login.php'));
        exit;
    }
    // ล็อกอินแล้วแต่ไม่ใช่ admin → ส่งกลับหน้าแรก (ไม่ต้องบอกว่าถูกปฏิเสธ)
    if (($_SESSION['role'] ?? 'user') !== 'admin') {
        header('Location: ' . auth_redirect_url('index.php'));
        exit;
    }
}
