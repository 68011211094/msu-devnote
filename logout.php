<?php
// ============================================================
// logout.php - หน้าออกจากระบบ (ล้างข้อมูล Session ให้หมดจด)
// ============================================================

// เริ่ม Session ถ้ายังไม่เริ่ม เพื่อล้างข้อมูลที่ค้างอยู่
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1) ลบตัวแปรทั้งหมดใน Session
session_unset();

// 2) ทำลาย Session ทิ้ง (ปลดโยง Session ID กับข้อมูลบนเซิร์ฟเวอร์)
session_destroy();

// 3) ลบ Cookie ของ Session ออกจากเบราว์เซอร์ด้วย (กันค้างไว้)
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 4200,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// 4) ส่งกลับไปหน้าเข้าสู่ระบบ
header('Location: login.php');
exit;
