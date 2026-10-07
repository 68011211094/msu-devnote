<?php
// ============================================================
// register.php - หน้าสมัครสมาชิก
// ความปลอดภัย: CSRF Token / ตรวจอีเมลซ้ำ / password_hash
// ============================================================

require_once 'includes/csrf.php';
require_once 'config/database.php';

// ถ้าล็อกอินอยู่แล้ว ไม่ต้องสมัครซ้ำ
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$errors  = [];
$fullname = '';
$email    = '';

// --- ประมวลผลเมื่อกดปุ่ม "สมัครสมาชิก" ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    // 1) ตรวจสอบ CSRF Token ก่อนเป็นอันดับแรก
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'เซสชันหมดอายุ กรุณาโหลดหน้านี้ใหม่อีกครั้ง';
    }

    // 2) ตรวจสอบข้อมูลที่กรอก (ฝั่งเซิร์ฟเวอร์ ห้ามเชื่อแค่ฝั่ง Browser)
    if ($fullname === '') {
        $errors[] = 'กรุณากรอกชื่อ-นามสกุล';
    } elseif (mb_strlen($fullname) > 100) {
        $errors[] = 'ชื่อ-นามสกุลต้องไม่เกิน 100 ตัวอักษร';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'รูปแบบอีเมลไม่ถูกต้อง';
    }

    if (strlen($password) < 8) {
        $errors[] = 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร';
    }

    if ($password !== $confirm) {
        $errors[] = 'รหัสผ่านทั้งสองช่องไม่ตรงกัน';
    }

    // 3) ตรวจอีเมลซ้ำด้วย Prepared Statement
    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email');
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        if ($stmt->fetch()) {
            $errors[] = 'อีเมลนี้ถูกสมัครสมาชิกแล้ว';
        }
    }

    // 4) บันทึกลงฐานข้อมูล (รหัสผ่านถูก Hash ก่อนเสมอ ห้ามเก็บ Plain Text)
    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare('INSERT INTO users (fullname, email, password) VALUES (:fullname, :email, :password)');
        $stmt->bindValue(':fullname', $fullname, PDO::PARAM_STR);
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->bindValue(':password', $hashedPassword, PDO::PARAM_STR);
        $stmt->execute();

        // ส่งไปหน้าเข้าสู่ระบบ (กัน submit ซ้ำเมื่อผู้ใช้กด refresh)
        header('Location: login.php?registered=1');
        exit;
    }
}

$pageTitle = 'สมัครสมาชิก';
include 'includes/header.php';
?>

<section class="form-box">
    <h1 class="section-title">สมัครสมาชิก</h1>
    <p class="section-subtitle">สร้างบัญชี MSU DevNote เพื่อเริ่มบันทึกโน้ตของคุณ</p>

    <?php if (!empty($errors)) : ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $msg) : ?>
                <div><?php echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" action="">
        <?php echo csrf_field(); ?>

        <div class="form-group">
            <label for="fullname">ชื่อ-นามสกุล</label>
            <input type="text" id="fullname" name="fullname" maxlength="100" required
                   value="<?php echo htmlspecialchars($fullname, ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <div class="form-group">
            <label for="email">อีเมล</label>
            <input type="email" id="email" name="email" maxlength="100" required
                   value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <div class="form-group">
            <label for="password">รหัสผ่าน</label>
            <input type="password" id="password" name="password" minlength="8" required>
            <p class="form-hint">อย่างน้อย 8 ตัวอักษร</p>
        </div>

        <div class="form-group">
            <label for="confirm_password">ยืนยันรหัสผ่าน</label>
            <input type="password" id="confirm_password" name="confirm_password" minlength="8" required>
        </div>

        <button type="submit" class="btn btn-primary btn-block">สมัครสมาชิก</button>
    </form>

    <p class="form-footer">มีบัญชีอยู่แล้ว? <a href="login.php">เข้าสู่ระบบ</a></p>
</section>

<?php include 'includes/footer.php'; ?>
