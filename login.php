<?php
// ============================================================
// login.php - หน้าเข้าสู่ระบบ
// ความปลอดภัย: CSRF Token / password_verify / session_regenerate_id
// ============================================================

require_once 'includes/csrf.php';
require_once 'config/database.php';

// ล็อกอินอยู่แล้วไม่ต้องเข้าซ้ำ
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$errors = [];
$email  = '';
// แสดงข้อความเมื่อเพิ่งสมัครสมาชิกสำเร็จ
$justRegistered = isset($_GET['registered']);

// --- ประมวลผลเมื่อกดปุ่ม "เข้าสู่ระบบ" ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // 1) ตรวจสอบ CSRF Token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'เซสชันหมดอายุ กรุณาโหลดหน้านี้ใหม่อีกครั้ง';
    }

    // 2) ตรวจสอบข้อมูลที่กรอก
    if ($email === '' || $password === '') {
        $errors[] = 'กรุณากรอกอีเมลและรหัสผ่าน';
    }

    // 3) ดึงข้อมูลผู้ใช้จากฐานข้อมูล (Prepared Statement)
    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT id, fullname, password, role FROM users WHERE email = :email');
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch();

        // 4) ตรวจรหัสด้วย password_verify (คืนค่า false ถ้าไม่ตรง)
        //    ใช้ข้อความเดียวกันทั้ง "อีเมลไม่มีในระบบ" และ "รหัสผ่านผิด"
        //    เพื่อไม่ให้คนแปลกหน้ารู้ว่าอีเมลไหนเคยสมัครแล้ว
        if (!$user || !password_verify($password, $user['password'])) {
            $errors[] = 'อีเมลหรือรหัสผ่านไม่ถูกต้อง';
        }
    }

    // 5) ล็อกอินสำเร็จ: สร้าง Session ID ใหม่ (กัน Session Fixation)
    if (empty($errors)) {
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['fullname'] = $user['fullname'];
        $_SESSION['role']     = $user['role'];

        header('Location: index.php');
        exit;
    }
}

$pageTitle = 'เข้าสู่ระบบ';
include 'includes/header.php';
?>

<section class="form-box">
    <h1 class="section-title">เข้าสู่ระบบ</h1>
    <p class="section-subtitle">ยินดีต้อนรับกลับสู่ MSU DevNote</p>

    <?php if ($justRegistered) : ?>
        <div class="alert alert-success">สมัครสมาชิกสำเร็จ กรุณาเข้าสู่ระบบด้วยอีเมลของคุณ</div>
    <?php endif; ?>

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
            <label for="email">อีเมล</label>
            <input type="email" id="email" name="email" maxlength="100" required
                   value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <div class="form-group">
            <label for="password">รหัสผ่าน</label>
            <input type="password" id="password" name="password" required>
        </div>

        <button type="submit" class="btn btn-primary btn-block">เข้าสู่ระบบ</button>
    </form>

    <p class="form-footer">ยังไม่มีบัญชี? <a href="register.php">สมัครสมาชิก</a></p>
</section>

<?php include 'includes/footer.php'; ?>
