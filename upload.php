<?php
session_start();
require_once 'php/config.php';
requireLogin();

$error   = '';
$success = '';

// =============================================
// PROSES UPLOAD PIN
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = sanitize($_POST['title']       ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $category_id = (int)($_POST['category_id']    ?? 0);
    $source_url  = sanitize($_POST['source_url']  ?? '');

    if (empty($title)) {
        $error = 'Judul pin tidak boleh kosong.';
    } elseif (empty($_FILES['image']['name'])) {
        $error = 'Pilih gambar untuk pin.';
    } else {
        $imagePath = uploadImage($_FILES['image']);

        if ($imagePath === false) {
            $error = 'Gagal upload gambar. Pastikan format JPG/PNG/GIF/WEBP dan ukuran max 5MB.';
        } else {
            $user_id    = $_SESSION['user_id'];
            $cat_param  = $category_id > 0 ? $category_id : null;

            $stmt = $conn->prepare(
                "INSERT INTO pins (user_id, category_id, title, description, image, source_url)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param('iissss', $user_id, $cat_param, $title, $description, $imagePath, $source_url);

            if ($stmt->execute()) {
                $pin_id = $conn->insert_id;
                redirect(SITE_URL . "/pin.php?id=$pin_id&uploaded=1");
            } else {
                $error = 'Gagal menyimpan pin ke database.';
                // Hapus file yang sudah terupload
                @unlink(UPLOAD_PATH . $imagePath);
            }
        }
    }
}

// Ambil daftar kategori
$categories = $conn->query("SELECT * FROM categories ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Upload Pin - PinShare</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/feed.css">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body data-logged-in="1">

<?php include 'includes/navbar.php'; ?>

<div class="upload-page">
  <h2>📌 Buat Pin Baru</h2>

  <?php if ($error): ?>
    <div class="alert alert-error">❌ <?= $error ?></div>
  <?php endif; ?>

  <form id="uploadForm" action="upload.php" method="POST" enctype="multipart/form-data">
    <div class="upload-layout">

      <!-- AREA UPLOAD GAMBAR -->
      <div>
        <div class="upload-preview-area" id="uploadArea">
          <div class="upload-placeholder" id="uploadPlaceholder">
            <div class="upload-icon">🖼️</div>
            <h4>Seret gambar ke sini</h4>
            <p>atau klik untuk memilih file</p>
            <p class="text-muted" style="margin-top:8px;">JPG, PNG, GIF, WEBP (maks. 5MB)</p>
          </div>
          <img id="previewImg" src="" alt="Preview" style="display:none;">
        </div>
        <input type="file" id="fileInput" name="image" accept="image/*">
        <p class="text-muted" style="margin-top:8px;font-size:12px;text-align:center;">
          Resolusi disarankan: minimal 600px lebar
        </p>
      </div>

      <!-- FORM DATA PIN -->
      <div class="upload-form-card">

        <div class="form-group">
          <label for="pinTitle">Judul Pin <span style="color:var(--accent)">*</span></label>
          <input type="text" id="pinTitle" name="title" class="form-control"
                 placeholder="Tulis judul yang menarik..." required
                 data-maxlength="100"
                 value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
          <small id="pinTitleCounter" style="color:var(--text-muted);font-size:12px;">0/100</small>
        </div>

        <div class="form-group">
          <label for="pinDesc">Deskripsi</label>
          <textarea id="pinDesc" name="description" class="form-control"
                    placeholder="Ceritakan tentang pin ini..."
                    rows="4" data-maxlength="500"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
          <small id="pinDescCounter" style="color:var(--text-muted);font-size:12px;">0/500</small>
        </div>

        <div class="form-group">
          <label for="pinCategory">Kategori</label>
          <select id="pinCategory" name="category_id" class="form-control">
            <option value="">-- Pilih Kategori --</option>
            <?php while ($cat = $categories->fetch_assoc()): ?>
              <option value="<?= $cat['id'] ?>" 
                      <?= (($_POST['category_id'] ?? '') == $cat['id']) ? 'selected' : '' ?>>
                <?= $cat['icon'] ?> <?= htmlspecialchars($cat['name']) ?>
              </option>
            <?php endwhile; ?>
          </select>
        </div>

        <div class="form-group">
          <label for="pinSource">Link Sumber (opsional)</label>
          <input type="url" id="pinSource" name="source_url" class="form-control"
                 placeholder="https://contoh.com/sumber"
                 value="<?= htmlspecialchars($_POST['source_url'] ?? '') ?>">
        </div>

        <div style="display:flex;gap:12px;margin-top:24px;">
          <a href="index.php" class="btn-outline">Batal</a>
          <button type="submit" class="btn-primary" style="flex:1;justify-content:center;">
            📌 Publish Pin
          </button>
        </div>

      </div>
    </div>
  </form>
</div>

<div id="toast" class="toast hidden"></div>
<script src="js/main.js"></script>
<script src="js/feed.js"></script>
</body>
</html>
