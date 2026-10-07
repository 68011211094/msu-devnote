<?php
// ============================================================
// includes/header.php - ส่วนหัวของเว็บ + Navbar ตามสถานะล็อกอิน
// วิธีใช้: เรียกใช้ใต้คำสั่งเปิดไฟล์ PHP ของทุกหน้า หลังจากกำหนด $pageTitle แล้ว
//   $pageTitle = 'ชื่อหน้า';
//   include 'includes/header.php';
// ============================================================

// เริ่ม Session ถ้ายังไม่เริ่ม (ใช้ตรวจสถานะล็อกอิน)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// คำนวณพาธของไฟล์ CSS/ลิงก์ ให้ถูกต้องทั้งหน้ารากและหน้าในโฟลเดอร์ย่อย (เช่น /admin/)
$depth      = substr_count(dirname($_SERVER['SCRIPT_NAME']), '/');
$basePath   = str_repeat('../', max($depth - 1, 0));
if ($basePath === '') {
    $basePath = './';
}

// ชื่อหน้าสำหรับแสดงบนแท็บเบราว์เซอร์ (กำหนดก่อนเรียก include ไฟล์นี้)
$pageTitle = $pageTitle ?? 'MSU DevNote';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?> | MSU DevNote</title>
    <link rel="stylesheet" href="<?php echo $basePath; ?>assets/css/style.css">
</head>
<body>

<header class="site-header">
    <div class="container nav-inner">
        <a href="<?php echo $basePath; ?>index.php" class="logo">MSU DevNote</a>

        <!-- ตัวเปิด/ปิดเมนูบนมือถือ (ใช้ CSS ล้วน ไม่ต้องใช้ JavaScript) -->
        <input type="checkbox" id="nav-toggle" class="nav-toggle-input">
        <label for="nav-toggle" class="nav-toggle-label" title="เปิดเมนู">&#9776;</label>

        <nav class="main-nav">
            <?php if (isset($_SESSION['user_id'])) : ?>
                <!-- เมนูสำหรับสมาชิกที่ล็อกอินแล้ว -->
                <a href="<?php echo $basePath; ?>index.php">หน้าแรก</a>
                <a href="<?php echo $basePath; ?>notes_create.php">เพิ่มโน้ต</a>
                <a href="<?php echo $basePath; ?>profile.php">โปรไฟล์</a>
                <?php if (($_SESSION['role'] ?? 'user') === 'admin') : ?>
                    <a href="<?php echo $basePath; ?>admin/index.php">จัดการระบบ</a>
                <?php endif; ?>
                <a href="<?php echo $basePath; ?>logout.php" class="btn btn-danger btn-sm">ออกจากระบบ</a>
            <?php else : ?>
                <!-- เมนูสำหรับผู้ที่ยังไม่ล็อกอิน -->
                <a href="<?php echo $basePath; ?>login.php">เข้าสู่ระบบ</a>
                <a href="<?php echo $basePath; ?>register.php" class="btn btn-primary btn-sm">สมัครสมาชิก</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main class="page-content">
