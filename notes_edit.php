<?php
// ============================================================
// notes_edit.php - ฟอร์มแก้ไขโน้ต (เช็คเจ้าของข้อมูลทุกขั้นตอน)
// ความปลอดภัย: Ownership Check (WHERE id AND user_id) / CSRF / Prepared Statements
// ============================================================

require_once 'includes/csrf.php';
require_once 'includes/auth.php';
require_once 'config/database.php';

// ต้องล็อกอินก่อน
require_login();

$id = (int) ($_GET['id'] ?? 0);

// --- ดึงโน้ตที่ต้องการแก้ พร้อมเช็คเจ้าของในคำสั่ง SQL เดียว ---
// ถ้าโน้ตไม่ใช่ของผู้ใช้คนนี้ (หรือไม่มีอยู่จริง) จะได้ false ทันที
$stmt = $pdo->prepare('SELECT id, title, category, content
                       FROM notes
                       WHERE id = :id AND user_id = :user_id');
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->execute();
$note = $stmt->fetch();

// ไม่ใช่ของตัวเอง → ส่งกลับหน้าแรกทันที (ห้ามเปิดฟอร์มให้แก้)
if (!$note) {
    header('Location: index.php?error=1');
    exit;
}

$categories = ['การเขียนโปรแกรมเว็บ', 'PHP', 'MySQL', 'HTML & CSS', 'Git & GitHub', 'อื่น ๆ'];

$errors   = [];
$title    = $note['title'];
$category = $note['category'];
$content  = $note['content'];

// --- ประมวลผลเมื่อกดปุ่ม "บันทึกการแก้ไข" ---
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

    // 3) แก้ไขข้อมูล — เช็คเจ้าของซ้ำอีกครั้งในคำสั่ง UPDATE (Defense in Depth)
    if (empty($errors)) {
        $stmt = $pdo->prepare('UPDATE notes
                               SET title = :title, category = :category, content = :content
                               WHERE id = :id AND user_id = :user_id');
        $stmt->bindValue(':title', $title, PDO::PARAM_STR);
        $stmt->bindValue(':category', $category, PDO::PARAM_STR);
        $stmt->bindValue(':content', $content, PDO::PARAM_STR);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt->execute();

        header('Location: index.php?updated=1');
        exit;
    }
}

$pageTitle = 'แก้ไขโน้ต';
include 'includes/header.php';
?>

<section class="form-box form-box-wide">
    <h1 class="section-title">แก้ไขโน้ต</h1>
    <p class="section-subtitle">แก้ไขข้อมูลโน้ตของคุณเอง</p>

    <?php if (!empty($errors)) : ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $msg) : ?>
                <div><?php echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" action="notes_edit.php?id=<?php echo (int) $id; ?>">
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

        <button type="submit" class="btn btn-primary btn-block">บันทึกการแก้ไข</button>
    </form>

    <p class="form-footer"><a href="index.php">กลับหน้าแรก</a></p>
</section>

<?php include 'includes/footer.php'; ?>
