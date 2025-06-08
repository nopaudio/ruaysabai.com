<?php
// admin_articles.php
require_once 'config.php';

// --- การตรวจสอบสิทธิ์ Admin ---
if (!isUserLoggedIn()) { header('Location: login.php?redirect=admin_articles.php'); exit; }
$user = getCurrentUser();
if (!$user || $user['user_type'] !== 'admin') { header('Location: index.php'); exit; }

$db = Database::getInstance();
$action = $_POST['action'] ?? $_GET['action'] ?? 'list';
$article_id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;

// --- ส่วนจัดการข้อมูล (เพิ่ม, แก้ไข, ลบ) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        switch ($action) {
            case 'save':
                $title = trim($_POST['title']);
                $slug = create_slug($title);
                $data = [
                    'title' => $title,
                    'slug' => $slug,
                    'content' => $_POST['content'],
                    'excerpt' => trim($_POST['excerpt']),
                    'status' => $_POST['status'],
                    'icon' => trim($_POST['icon']),
                    'user_id' => $user['id']
                ];

                if ($article_id) { // Update
                    // Check if slug exists for another article
                    $existing = $db->select("SELECT id FROM articles WHERE slug = ? AND id != ?", [$slug, $article_id]);
                    if ($existing) { $slug .= '-' . $article_id; } // Append id to make it unique
                    $data['slug'] = $slug;
                    $db->update('articles', $data, 'id = ?', [$article_id]);
                    $message = "อัปเดตบทความสำเร็จ";
                } else { // Insert
                    $existing = $db->select("SELECT id FROM articles WHERE slug = ?", [$slug]);
                    if ($existing) { $slug .= '-' . uniqid(); }
                    $data['slug'] = $slug;
                    $db->insert('articles', $data);
                    $message = "สร้างบทความใหม่สำเร็จ";
                }
                header('Location: admin_articles.php?message=' . urlencode($message));
                exit;

            case 'delete':
                if ($article_id) {
                    $db->execute("DELETE FROM articles WHERE id = ?", [$article_id]);
                    header('Location: admin_articles.php?message=' . urlencode("ลบบทความสำเร็จ"));
                    exit;
                }
                break;
        }
    } catch (Exception $e) {
        $error_message = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
}

// --- ส่วนแสดงผล ---
$article = null;
if ($action === 'edit' && $article_id) {
    $article = $db->select("SELECT * FROM articles WHERE id = ?", [$article_id]);
    $article = $article ? $article[0] : null;
}
$articles = $db->select("SELECT id, title, status, created_at, updated_at FROM articles ORDER BY id DESC");

function create_slug($string){
   $slug = preg_replace('/[^A-Za-z0-9ก-๙-]+/', '-', strtolower(trim($string)));
   return $slug;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จัดการบทความ</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="admin_style.css"> <script src="https://cdn.tiny.cloud/1/ylt10gbenxdc43naro4ww9mi9tzvahrmbr3qei233oz7mmkk/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
      tinymce.init({
        selector: 'textarea#content',
        plugins: 'code table lists image link media',
        toolbar: 'undo redo | blocks | bold italic | alignleft aligncenter alignright | indent outdent | bullist numlist | code | table | image link media'
      });
    </script>
</head>
<body>
<div class="admin-container">
    <div class="admin-header">
        <h1><i class="fas fa-newspaper"></i> จัดการบทความ</h1>
        <a href="admin.php" class="logout-btn" style="top:25px; right: 150px;"><i class="fas fa-arrow-left"></i> กลับหน้าแอดมิน</a>
    </div>

    <?php if (isset($_GET['message'])): ?>
        <div class="alert success" style="display:block; position:relative; top:0; right:0; min-width:100%; margin-bottom: 20px;"><?php echo htmlspecialchars($_GET['message']); ?></div>
    <?php endif; ?>
    <?php if (isset($error_message)): ?>
        <div class="alert error" style="display:block; position:relative; top:0; right:0; min-width:100%; margin-bottom: 20px;"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <div id="edit-article-section" class="admin-section active">
        <div class="section-title"><span><i class="fas fa-edit"></i> <?php echo $article_id ? 'แก้ไขบทความ' : 'สร้างบทความใหม่'; ?></span></div>
        <form action="admin_articles.php" method="post">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?php echo $article_id; ?>">
            <div class="form-group">
                <label for="title">หัวข้อบทความ</label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($article['title'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="content">เนื้อหาบทความ</label>
                <textarea id="content" name="content" rows="15"><?php echo htmlspecialchars($article['content'] ?? ''); ?></textarea>
            </div>
             <div class="form-group">
                <label for="excerpt">ข้อความเกริ่นนำ (แสดงในหน้าสรุป)</label>
                <textarea id="excerpt" name="excerpt" rows="3"><?php echo htmlspecialchars($article['excerpt'] ?? ''); ?></textarea>
            </div>
            <div class="form-group">
                <label for="icon">ไอคอน (Font Awesome Class)</label>
                <input type="text" id="icon" name="icon" value="<?php echo htmlspecialchars($article['icon'] ?? 'fas fa-newspaper'); ?>" placeholder="เช่น fas fa-lightbulb">
            </div>
            <div class="form-group">
                <label for="status">สถานะ</label>
                <select name="status" id="status">
                    <option value="published" <?php echo (($article['status'] ?? '') === 'published') ? 'selected' : ''; ?>>เผยแพร่</option>
                    <option value="draft" <?php echo (($article['status'] ?? 'draft') === 'draft') ? 'selected' : ''; ?>>ฉบับร่าง</option>
                </select>
            </div>
            <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> บันทึกบทความ</button>
             <?php if ($article_id): ?>
                <a href="admin_articles.php" class="btn">ยกเลิกการแก้ไข</a>
            <?php endif; ?>
        </form>
    </div>

    <div id="list-articles-section" class="admin-section active" style="margin-top: 30px;">
        <div class="section-title"><span><i class="fas fa-list"></i> รายการบทความทั้งหมด</span></div>
        <table class="users-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>หัวข้อ</th>
                    <th>สถานะ</th>
                    <th>สร้างเมื่อ</th>
                    <th>แก้ไขล่าสุด</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($articles)): ?>
                    <tr><td colspan="6" style="text-align:center;">ยังไม่มีบทความ</td></tr>
                <?php else: ?>
                    <?php foreach ($articles as $art): ?>
                    <tr>
                        <td><?php echo $art['id']; ?></td>
                        <td><?php echo htmlspecialchars($art['title']); ?></td>
                        <td><span class="status-badge <?php echo $art['status'] === 'published' ? 'active' : 'suspended'; ?>"><?php echo $art['status']; ?></span></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($art['created_at'])); ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($art['updated_at'])); ?></td>
                        <td>
                            <a href="admin_articles.php?action=edit&id=<?php echo $art['id']; ?>" class="btn btn-sm"><i class="fas fa-edit"></i></a>
                            <form action="admin_articles.php" method="post" style="display:inline;" onSubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการลบบทความนี้?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $art['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>