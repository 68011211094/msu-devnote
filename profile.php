<?php
// ============================================================
// profile.php - ฟอร์มรับข้อมูลชุดที่ 2
//   ส่วนที่ 1: แก้ไขชื่อโปรไฟล์ส่วนตัว
//   ส่วนที่ 2: ส่งข้อความสอบถาม/ข้อเสนอแนะ (บันทึกลงตาราง feedback)
// ความปลอดภัย: CSRF / Prepared Statements / Validation ฝั่ง Server / htmlspecialchars
// ============================================================

require_once 'includes/csrf.php';
require_once 'includes/auth.php';
require_once 'config/database.php';

// ต้องล็อกอินก่อน
require_login();

$userId = $_SESSION['user_id'];

// --- ดึงข้อมูลโปรไฟล์ปัจจุบันจากฐานข้อมูล ---
$stmt = $pdo->prepare('SELECT fullname, email, created_at FROM users WHERE id = :id');
$stmt->bindValue(':id', $userId, PDO::PARAM_INT);
$stmt->execute();
$profile = $stmt->fetch();

$errors        = [];
$fullname      = $profile['fullname'];
$subject       = '';
$message       = '';

// --- ประมวลผลเมื่อกดปุ่มส่งฟอร์ม (แยกฟอร์มด้วย form_type) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formType = $_POST['form_type'] ?? '';

    // ตรวจสอบ CSRF Token ก่อนเป็นอันดับแรก (ใช้ร่วมกันทั้ง 2 ฟอร์ม)
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'เซสชันหมดอายุ กรุณาโหลดหน้านี้ใหม่อีกครั้ง';
    } else {

        // ======== ฟอร์มที่ 1: แก้ไขชื่อโปรไฟล์ ========
        if ($formType === 'profile') {
            $fullname = trim($_POST['fullname'] ?? '');

            if ($fullname === '') {
                $errors[] = 'กรุณากรอกชื่อ-นามสกุล';
            } elseif (mb_strlen($fullname) > 100) {
                $errors[] = 'ชื่อ-นามสกุลต้องไม่เกิน 100 ตัวอักษร';
            }

            if (empty($errors)) {
                // เช็คเจ้าของด้วย id จาก Session เท่านั้น (ไม่รับค่าจาก POST)
                $stmt = $pdo->prepare('UPDATE users SET fullname = :fullname WHERE id = :id');
                $stmt->bindValue(':fullname', $fullname, PDO::PARAM_STR);
                $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
                $stmt->execute();

                // อัปเดตชื่อใน Session ด้วย ไม่งั้น Navbar จะยังแสดงชื่อเก่า
                $_SESSION['fullname'] = $fullname;

                header('Location: profile.php?updated=1');
                exit;
            }
        }

        // ======== ฟอร์มที่ 2: ส่งข้อความสอบถาม/ข้อเสนอแนะ ========
        elseif ($formType === 'feedback') {
            $subject = trim($_POST['subject'] ?? '');
            $message = trim($_POST['message'] ?? '');

            if ($subject === '') {
                $errors[] = 'กรุณากรอกหัวข้อข้อความ';
            } elseif (mb_strlen($subject) > 150) {
                $errors[] = 'หัวข้อต้องไม่เกิน 150 ตัวอักษร';
            }

            if ($message === '') {
                $errors[] = 'กรุณากรอกข้อความสอบถาม/ข้อเสนอแนะ';
            }

            if (empty($errors)) {
                $stmt = $pdo->prepare('INSERT INTO feedback (user_id, subject, message)
                                       VALUES (:user_id, :subject, :message)');
                $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
                $stmt->bindValue(':subject', $subject, PDO::PARAM_STR);
                $stmt->bindValue(':message', $message, PDO::PARAM_STR);
                $stmt->execute();

                header('Location: profile.php?feedback_sent=1');
                exit;
            }
        }
    }
}

$pageTitle = 'โปรไฟล์';
include 'includes/header.php';
?>

<h1 class="section-title">โปรไฟล์ของฉัน</h1>
<p class="section-subtitle">จัดการข้อมูลส่วนตัวและส่งข้อความถึงผู้ดูแลระบบ</p>

<?php if (isset($_GET['updated'])) : ?>
    <div class="alert alert-success">แก้ไขชื่อโปรไฟล์เรียบร้อยแล้ว</div>
<?php endif; ?>
<?php if (isset($_GET['feedback_sent'])) : ?>
    <div class="alert alert-success">ส่งข้อความเรียบร้อยแล้ว ขอบคุณสำหรับข้อเสนอแนะ</div>
<?php endif; ?>
<?php if (!empty($errors)) : ?>
    <div class="alert alert-error">
        <?php foreach ($errors as $msg) : ?>
            <div><?php echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="notes-grid">

    <!-- ========== ฟอร์มที่ 1: แก้ไขชื่อโปรไฟล์ ========== -->
    <section class="card">
        <h2 class="card-title">แก้ไขชื่อโปรไฟล์</h2>

        <p class="form-hint mb-3">
            อีเมล: <?php echo htmlspecialchars($profile['email'], ENT_QUOTES, 'UTF-8'); ?><br>
            สมาชิกตั้งแต่: <?php echo date('d M Y', strtotime($profile['created_at'])); ?>
        </p>

        <form method="post" action="profile.php">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="form_type" value="profile">

            <div class="form-group">
                <label for="fullname">ชื่อ-นามสกุล</label>
                <input type="text" id="fullname" name="fullname" maxlength="100" required
                       value="<?php echo htmlspecialchars($fullname, ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <button type="submit" class="btn btn-primary btn-block">บันทึกชื่อ</button>
        </form>
    </section>

    <!-- ========== ฟอร์มที่ 2: ส่งข้อความสอบถาม/ข้อเสนอแนะ ========== -->
    <section class="card">
        <h2 class="card-title">สอบถาม / ข้อเสนอแนะ</h2>
        <p class="form-hint mb-3">ส่งข้อความถึงผู้ดูแลระบบ เราช่วยตอบกลับโดยเร็วที่สุด</p>

        <form method="post" action="profile.php">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="form_type" value="feedback">

            <div class="form-group">
                <label for="subject">หัวข้อ</label>
                <input type="text" id="subject" name="subject" maxlength="150" required
                       value="<?php echo htmlspecialchars($subject, ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <div class="form-group">
                <label for="message">ข้อความ</label>
                <textarea id="message" name="message" required><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>

            <button type="submit" class="btn btn-success btn-block">ส่งข้อความ</button>
        </form>
    </section>

</div>

<?php include 'includes/footer.php'; ?>
