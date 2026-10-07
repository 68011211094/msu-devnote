<?php
// ============================================================
// index.php - Dashboard แสดงรายการโน้ตของผู้ใช้ที่ล็อกอินอยู่
// ความปลอดภัย: require_login() + WHERE user_id (แสดงเฉพาะของตัวเอง)
// ============================================================

require_once 'includes/csrf.php';
require_once 'includes/auth.php';
require_once 'config/database.php';

// บังคับให้ล็อกอินก่อนเข้าหน้านี้
require_login();

// ดึงเฉพาะโน้ตของตัวเองเท่านั้น (Data Ownership)
$stmt = $pdo->prepare('SELECT id, title, category, content, created_at
                       FROM notes
                       WHERE user_id = :user_id
                       ORDER BY created_at DESC');
$stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->execute();
$notes = $stmt->fetchAll();

$pageTitle = 'หน้าแรก';
include 'includes/header.php';
?>

<h1 class="section-title">โน้ตของ <?php echo htmlspecialchars($_SESSION['fullname'], ENT_QUOTES, 'UTF-8'); ?></h1>
<p class="section-subtitle">สมุดบันทึกวิชาเรียนและคลังโค้ดส่วนตัวของคุณ</p>

<?php if (isset($_GET['created'])) : ?>
    <div class="alert alert-success">บันทึกโน้ตเรียบร้อยแล้ว</div>
<?php endif; ?>
<?php if (isset($_GET['updated'])) : ?>
    <div class="alert alert-success">แก้ไขโน้ตเรียบร้อยแล้ว</div>
<?php endif; ?>
<?php if (isset($_GET['deleted'])) : ?>
    <div class="alert alert-success">ลบโน้ตเรียบร้อยแล้ว</div>
<?php endif; ?>
<?php if (isset($_GET['error'])) : ?>
    <div class="alert alert-error">ไม่สามารถทำรายการได้ คุณไม่มีสิทธิ์แก้ไขข้อมูลนี้</div>
<?php endif; ?>

<a href="notes_create.php" class="btn btn-primary mb-3">+ เพิ่มโน้ตใหม่</a>

<?php if (empty($notes)) : ?>
    <div class="empty-state">
        <p>ยังไม่มีโน้ต เริ่มสร้างโน้ตแรกของคุณได้เลย</p>
        <a href="notes_create.php" class="btn btn-primary">สร้างโน้ตแรก</a>
    </div>
<?php else : ?>
    <div class="notes-grid">
        <?php foreach ($notes as $note) :
            // ตัดเนื้อหาให้สั้นลงสำหรับแสดงเป็นตัวอย่าง
            $excerpt = mb_strlen($note['content']) > 150
                ? mb_substr($note['content'], 0, 150) . '...'
                : $note['content'];
        ?>
            <article class="card note-card">
                <h3><?php echo htmlspecialchars($note['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                <span class="badge"><?php echo htmlspecialchars($note['category'], ENT_QUOTES, 'UTF-8'); ?></span>
                <p><?php echo htmlspecialchars($excerpt, ENT_QUOTES, 'UTF-8'); ?></p>
                <div class="note-meta">
                    <span class="note-date"><?php echo date('d M Y', strtotime($note['created_at'])); ?></span>
                    <span class="note-actions">
                        <a href="notes_edit.php?id=<?php echo (int) $note['id']; ?>" class="btn btn-secondary btn-sm">แก้ไข</a>
                        <form method="post" action="notes_delete.php" class="note-form"
                              onsubmit="return confirm('ต้องการลบโน้ดนี้จริงหรือไม่?');">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="id" value="<?php echo (int) $note['id']; ?>">
                            <button type="submit" class="btn btn-danger btn-sm">ลบ</button>
                        </form>
                    </span>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
