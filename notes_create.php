<?php
// ============================================================
// notes_create.php - ฟอร์มรับข้อมูลชุดที่ 1: เพิ่มโน้ตใหม่
// ความปลอดภัย: CSRF Token / Prepared Statements / htmlspecialchars
// ============================================================

require_once 'includes/csrf.php';
require_once 'includes/auth.php';
require_once 'config/database.php';

// ต้องล็อกอินก่อน จึงจะเพิ่มโน้ตได้
require_login();

// รายการหมวดหมู่ให้เลือก
$categories = ['การเขียนโปรแกรมเว็บ', 'PHP', 'MySQL', 'HTML & CSS', 'Git & GitHub', 'อื่น ๆ'];

$errors   = [];
$title    = '';
$category = $categories[0];
$content  = '';

// --- ประมวลผลเมื่อกดปุ่ม "บันทึกโน้ต" ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title    = trim($_POST['title'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $content  = trim($_POST['content'] ?? '');

    // 1) ตรวจสอบ CSRF Token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'เซสชันหมดอายุ กรุณาโหลดหน้านี้ใหม่อีกครั้ง';
    }

    // 2) ตรวจสอบข้อมูลที่กรอก
    if ($title === '') {
        $errors[] = 'กรุณากรอกหัวข้อโน้ต';
    } elseif (mb_strlen($title) > 200) {
        $errors[] = 'หัวข้อต้องไม่เกิน 200 ตัวอักษร';
    }

    if (!in_array($category, $categories, true)) {
        $errors[] = 'กรุณาเลือกหมวดหมู่ที่ถูกต้อง';
    }

    if ($content === '') {
        $errors[] = 'กรุณากรอกเนื้อหาโน้ต';
    }

    // 3) บันทึกลงฐานข้อมูล — user_id เอาจาก Session ผู้ใช้ปัจจุบันเสมอ
    if (empty($errors)) {
        $stmt = $pdo->prepare('INSERT INTO notes (user_id, title, category, content)
                               VALUES (:user_id, :title, :category, :content)');
        $stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt->bindValue(':title', $title, PDO::PARAM_STR);
        $stmt->bindValue(':category', $category, PDO::PARAM_STR);
        $stmt->bindValue(':content', $content, PDO::PARAM_STR);
        $stmt->execute();

        // ส่งกลับหน้าแรก (กัน submit ซ้ำเมื่อ refresh)
        header('Location: index.php?created=1');
        exit;
    }
}

$pageTitle = 'เพิ่มโน้ตใหม่';
include 'includes/header.php';
?>

<section class="form-box form-box-wide">
    <h1 class="section-title">เพิ่มโน้ตใหม่</h1>
    <p class="section-subtitle">บันทึกความรู้หรือโค้ดที่น่าสนใจเก็บไว้อ่านภายหลัง</p>

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
            <label for="title">หัวข้อ</label>
            <input type="text" id="title" name="title" maxlength="200" required
                   value="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>">
        </div>

        <div class="form-group">
            <label for="category">หมวดหมู่</label>
            <select id="category" name="category" required>
                <?php foreach ($categories as $cat) : ?>
                    <option value="<?php echo htmlspecialchars($cat, ENT_QUOTES, 'UTF-8'); ?>"
                        <?php echo $cat === $category ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat, ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="content">เนื้อหา</label>
            <textarea id="content" name="content" required><?php echo htmlspecialchars($content, ENT_QUOTES, 'UTF-8'); ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary btn-block">บันทึกโน้ต</button>
    </form>

    <p class="form-footer"><a href="index.php">กลับหน้าแรก</a></p>
</section>

<?php include 'includes/footer.php'; ?>
