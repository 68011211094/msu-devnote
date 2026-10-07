<?php
// ============================================================
// admin/index.php - แผงควบคุมผู้ดูแลระบบ
// ความปลอดภัย: require_admin() / POST + CSRF ทุกปุ่ม / Prepared Statements / htmlspecialchars
// ============================================================

// ไฟล์นี้อยู่ในโฟลเดอร์ admin จึงต้องอ้างพาธย้อนกลับหนึ่งชั้นด้วย __DIR__
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

// ป้องกันผู้ใช้ทั่วไปแอบเข้าหน้านี้ (ยังไม่ล็อกอิน → ไป login / ไม่ใช่ admin → กลับหน้าแรก)
require_admin();

// --- จัดการการกดปุ่ม (ต้องเป็น POST + ผ่าน CSRF เท่านั้น) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        header('Location: index.php?error=csrf');
        exit;
    }

    $action   = $_POST['action'] ?? '';
    $targetId = (int) ($_POST['user_id'] ?? 0);

    // ห้ามแก้ไขหรือลบบัญชีของตัวเอง (ป้องกันผู้ดูแลระบบลบตัวเองจนเข้าระบบไม่ได้)
    if ($targetId === (int) $_SESSION['user_id']) {
        header('Location: index.php?error=self');
        exit;
    }

    if ($action === 'toggle_role') {
        // ดูสิทธิ์ปัจจุบันก่อน แล้วค่อยสลับ
        $stmt = $pdo->prepare('SELECT role FROM users WHERE id = :id');
        $stmt->bindValue(':id', $targetId, PDO::PARAM_INT);
        $stmt->execute();
        $currentRole = $stmt->fetchColumn();

        if (!$currentRole) {
            header('Location: index.php?error=notfound');
            exit;
        }

        $newRole = ($currentRole === 'admin') ? 'user' : 'admin';

        $stmt = $pdo->prepare('UPDATE users SET role = :role WHERE id = :id');
        $stmt->bindValue(':role', $newRole, PDO::PARAM_STR);
        $stmt->bindValue(':id', $targetId, PDO::PARAM_INT);
        $stmt->execute();

        header('Location: index.php?updated=1');
        exit;
    }

    if ($action === 'delete_user') {
        // โน้ตและ feedback ของผู้ใช้คนนี้จะถูกลบอัตโนมัติ (ON DELETE CASCADE)
        $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
        $stmt->bindValue(':id', $targetId, PDO::PARAM_INT);
        $stmt->execute();

        header('Location: index.php?deleted=1');
        exit;
    }

    header('Location: index.php');
    exit;
}

// --- สถิติรวมสำหรับแสดงบนการ์ด ---
$totalUsers     = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$totalNotes     = (int) $pdo->query('SELECT COUNT(*) FROM notes')->fetchColumn();
$totalFeedback  = (int) $pdo->query('SELECT COUNT(*) FROM feedback')->fetchColumn();

// --- รายชื่อผู้ใช้ทั้งหมด ---
$users = $pdo->query('SELECT id, fullname, email, role, created_at
                      FROM users ORDER BY id')->fetchAll();

// --- ข้อความ feedback ทั้งหมด พร้อมชื่อผู้ส่ง ---
$feedbackList = $pdo->query('SELECT f.subject, f.message, f.created_at, u.fullname
                             FROM feedback f
                             INNER JOIN users u ON u.id = f.user_id
                             ORDER BY f.created_at DESC')->fetchAll();

$pageTitle = 'แผงผู้ดูแลระบบ';
include __DIR__ . '/../includes/header.php';
?>

<h1 class="section-title">แผงควบคุมผู้ดูแลระบบ</h1>
<p class="section-subtitle">ภาพรวมระบบและจัดการบัญชีผู้ใช้งาน</p>

<?php if (isset($_GET['updated'])) : ?>
    <div class="alert alert-success">สลับสิทธิ์ผู้ใช้เรียบร้อยแล้ว</div>
<?php endif; ?>
<?php if (isset($_GET['deleted'])) : ?>
    <div class="alert alert-success">ลบผู้ใช้พร้อมข้อมูลที่เกี่ยวข้องเรียบร้อยแล้ว</div>
<?php endif; ?>
<?php if (isset($_GET['error'])) : ?>
    <div class="alert alert-error">
        <?php
        // แสดงข้อความตามประเภทข้อผิดพลาด (ข้อความคงที่ ไม่รับค่าจากผู้ใช้)
        $errorMessages = [
            'csrf'     => 'เซสชันหมดอายุ กรุณาโหลดหน้านี้ใหม่อีกครั้ง',
            'self'     => 'ไม่สามารถแก้ไขหรือลบบัญชีของตนเองได้',
            'notfound' => 'ไม่พบผู้ใช้ที่ต้องการ',
        ];
        echo htmlspecialchars($errorMessages[$_GET['error']] ?? 'ไม่สามารถทำรายการได้', ENT_QUOTES, 'UTF-8');
        ?>
    </div>
<?php endif; ?>

<!-- ========== การ์ดสถิติรวม ========== -->
<div class="notes-grid mb-3">
    <section class="card text-center">
        <h2 class="card-title">ผู้ใช้ทั้งหมด</h2>
        <p class="section-title"><?php echo $totalUsers; ?></p>
    </section>
    <section class="card text-center">
        <h2 class="card-title">โน้ตทั้งหมด</h2>
        <p class="section-title"><?php echo $totalNotes; ?></p>
    </section>
    <section class="card text-center">
        <h2 class="card-title">Feedback ทั้งหมด</h2>
        <p class="section-title"><?php echo $totalFeedback; ?></p>
    </section>
</div>

<!-- ========== ตารางจัดการผู้ใช้ ========== -->
<h2 class="section-title mb-3">จัดการผู้ใช้</h2>
<div class="table-wrap mb-3">
    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>ชื่อ-นามสกุล</th>
                <th>อีเมล</th>
                <th>สิทธิ์</th>
                <th>สมัครเมื่อ</th>
                <th>จัดการ</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user) : ?>
                <tr>
                    <td><?php echo (int) $user['id']; ?></td>
                    <td><?php echo htmlspecialchars($user['fullname'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td>
                        <span class="badge <?php echo $user['role'] === 'admin' ? 'badge-admin' : ''; ?>">
                            <?php echo htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    </td>
                    <td><?php echo date('d M Y', strtotime($user['created_at'])); ?></td>
                    <td>
                        <?php if ((int) $user['id'] === (int) $_SESSION['user_id']) : ?>
                            <span class="note-date">บัญชีของคุณ</span>
                        <?php else : ?>
                            <!-- ปุ่มสลับสิทธิ์ -->
                            <form method="post" action="" class="note-form">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="toggle_role">
                                <input type="hidden" name="user_id" value="<?php echo (int) $user['id']; ?>">
                                <button type="submit" class="btn btn-secondary btn-sm">
                                    <?php echo $user['role'] === 'admin' ? 'ลดเป็น user' : 'ยกเป็น admin'; ?>
                                </button>
                            </form>
                            <!-- ปุ่มลบผู้ใช้ -->
                            <form method="post" action="" class="note-form"
                                  onsubmit="return confirm('ลบผู้ใช้นี้พร้อมโน้ตและ feedback ทั้งหมด?');">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="delete_user">
                                <input type="hidden" name="user_id" value="<?php echo (int) $user['id']; ?>">
                                <button type="submit" class="btn btn-danger btn-sm">ลบ</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- ========== ตาราง Feedback ทั้งหมด ========== -->
<h2 class="section-title mb-3">ข้อความสอบถาม / ข้อเสนอแนะ</h2>
<?php if (empty($feedbackList)) : ?>
    <div class="empty-state"><p>ยังไม่มีข้อความ Feedback</p></div>
<?php else : ?>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>ผู้ส่ง</th>
                    <th>หัวข้อ</th>
                    <th>ข้อความ</th>
                    <th>วันที่</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($feedbackList as $item) : ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['fullname'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($item['subject'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars($item['message'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo date('d M Y H:i', strtotime($item['created_at'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
