<?php
// ============================================================
// includes/csrf.php - สร้างและตรวจสอบ CSRF Token (ป้องกัน CSRF Attack)
// วิธีใช้:
//   ฝังในฟอร์ม HTML    : echo csrf_field();
//   ตรวจตอนรับ POST   : if (!verify_csrf_token($_POST['csrf_token'] ?? '')) { die('Token ไม่ถูกต้อง'); }
//   (หมายเหตุ: ห้ามเขียนแท็กปิด PHP ในคอมเมนต์ เพราะจะปิดโหมด PHP จริง)
// ============================================================

// เริ่ม Session ถ้ายังไม่เริ่ม (เก็บ Token ไว้ใน Session)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// สุ่ม CSRF Token ด้วย bin2hex(random_bytes(32)) => ค่า hex ยาว 64 ตัวอักษร
// ถ้ามี Token แล้วจะไม่สุ่มใหม่ เพื่อให้ฟอร์มที่เปิดค้างอยู่ใช้ token เดิมได้
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// คืนค่า input hidden สำหรับฝังในฟอร์ม (ครอบด้วย htmlspecialchars กัน XSS)
function csrf_field() {
    $token = htmlspecialchars(generate_csrf_token(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

// ตรวจสอบ Token ที่ส่งมาจากฟอร์มด้วย hash_equals() (เทียบค่าแบบป้องกัน Timing Attack)
function verify_csrf_token($token) {
    // ต้องเป็น string และต้องมี Token ใน Session ก่อน
    if (!is_string($token) || !isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}
