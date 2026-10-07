<?php
// ============================================================
// notes_delete.php - สคริปต์ลบโน้ต
// ความปลอดภัย: รับเฉพาะ POST / CSRF Token / Ownership Check
// ============================================================

require_once 'includes/csrf.php';
require_once 'includes/auth.php';
require_once 'config/database.php';

// ต้องล็อกอินก่อน
require_login();

// รับเฉพาะ POST เท่านั้น (กันพิมพ์ URL ตรงในเบราว์เซอร์)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// ตรวจสอบ CSRF Token
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    header('Location: index.php?error=1');
    exit;
}

$id = (int) ($_POST['id'] ?? 0);

// ลบด้วยเงื่อนไขเจ้าของข้อมูล — ถ้าไม่ใช่ของตัวเอง จะลบไม่ได้เลย
$stmt = $pdo->prepare('DELETE FROM notes
                       WHERE id = :id AND user_id = :user_id');
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->execute();

// ตรวจว่ามีแถวถูกลบจริงหรือไม่
if ($stmt->rowCount() > 0) {
    header('Location: index.php?deleted=1');
} else {
    header('Location: index.php?error=1');
}
exit;
