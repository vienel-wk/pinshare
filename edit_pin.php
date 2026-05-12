<?php
// edit_pin.php — Edit judul, deskripsi, kategori pin
session_start();
require_once 'php/config.php';
requireLogin();

$pin_id  = (int)($_GET['id'] ?? 0);
$user_id = $_SESSION['user_id'];

if ($pin_id === 0) redirect(SITE_URL . '/index.php');

$stmt = $conn->prepare("SELECT * FROM pins WHERE id=? AND user_id=?");
$stmt->bind_param('ii', $pin_id, $user_id);
$stmt->execute();
$pin = $stmt->get_result()->fetch_assoc();

if (!$pin) redirect(SITE_URL . '/index.php');

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = sanitize($_POST['title']       ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $category_id = (int)($_POST['category_id']    ?? 0) ?: null;
    $source_url  = sanitize($_POST['source_url']  ?? '');

    if (empty($title)) {
        $error = 'Judul tidak boleh kosong.';
    } else {
        $stmt = $conn->prepare("UPDATE pins SET title=?, description=?, category_id=?, source_url=? WHERE id=?");
        $stmt->bind_param('ssisi', $title, $description, $category_id, $source_url, $pin_id);
        if ($stmt->execute()) {
            redirect(SITE_URL . "/pin.php?id=$pin_id");
        } else {
            $error = 'Gagal menyimpan perubahan.';
        }
    }
}

$categories = $conn->query("SELECT * FROM categories ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Pin - PinShare</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/feed.css">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body data-logged-in="1">
<?php include 'includes/navbar.php'; ?>
<div class="upload-page">
  <h2>✏️ Edit Pin</h2>
  <?php if ($error): ?><div class="alert alert-error">❌ <?= $error ?></div><?php endif; ?>

  <div class="upload-layout">
    <!-- Preview gambar (tidak bisa diubah) -->
    <div>
      <div class="upload-preview-area" style="cursor:default;">
        <img src="uploads/<?= htmlspecialchars($pin['image']) ?>" 
             alt="Pin" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;">
      </div>
      <p class="text-muted" style="text-align:center;margin-top:8px;font-size:12px;">
        Gambar tidak dapat diubah
      </p>
    </div>

    <!-- Form edit -->
    <div class="upload-form-card">
      <form action="edit_pin.php?id=<?= $pin_id ?>" method="POST">
        <div class="form-group">
          <label>Judul Pin <span style="color:var(--accent)">*</span></label>
          <input type="text" name="title" class="form-control" required maxlength="200"
                 value="<?= htmlspecialchars($_POST['title'] ?? $pin['title']) ?>">
        </div>
        <div class="form-group">
          <label>Deskripsi</label>
          <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($_POST['description'] ?? $pin['description'] ?? '') ?></textarea>
        </div>
        <div class="form-group">
          <label>Kategori</label>
          <select name="category_id" class="form-control">
            <option value="">-- Pilih Kategori --</option>
            <?php while ($cat = $categories->fetch_assoc()): ?>
              <option value="<?= $cat['id'] ?>"
                <?= (($_POST['category_id'] ?? $pin['category_id']) == $cat['id']) ? 'selected' : '' ?>>
                <?= $cat['icon'] ?> <?= htmlspecialchars($cat['name']) ?>
              </option>
            <?php endwhile; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Link Sumber</label>
          <input type="url" name="source_url" class="form-control"
                 value="<?= htmlspecialchars($_POST['source_url'] ?? $pin['source_url'] ?? '') ?>">
        </div>
        <div style="display:flex;gap:12px;">
          <a href="pin.php?id=<?= $pin_id ?>" class="btn-outline">Batal</a>
          <button type="submit" class="btn-primary" style="flex:1;justify-content:center;">
            💾 Simpan
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
<div id="toast" class="toast hidden"></div>
<script src="js/main.js"></script>
</body>
</html>
