<?php
session_start();
require_once 'php/config.php';

// Ambil kategori
$categories = $conn->query("SELECT * FROM categories ORDER BY name ASC");

// Ambil pin berdasarkan filter kategori
$cat_filter = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;
$search = isset($_GET['q']) ? $conn->real_escape_string($_GET['q']) : '';

$sql = "SELECT pins.*, users.username, users.avatar, categories.name AS cat_name,
        (SELECT COUNT(*) FROM likes WHERE likes.pin_id = pins.id) AS like_count,
        (SELECT COUNT(*) FROM comments WHERE comments.pin_id = pins.id) AS comment_count
        FROM pins
        JOIN users ON pins.user_id = users.id
        LEFT JOIN categories ON pins.category_id = categories.id
        WHERE 1=1";

if ($cat_filter > 0) $sql .= " AND pins.category_id = $cat_filter";
if ($search)         $sql .= " AND (pins.title LIKE '%$search%' OR pins.description LIKE '%$search%')";

$sql .= " ORDER BY pins.created_at DESC";
$pins = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PinShare - Berbagi Inspirasi</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/feed.css">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<!-- KATEGORI PILLS -->
<div class="categories-bar">
  <a href="index.php" class="pill <?= $cat_filter == 0 ? 'active' : '' ?>">Semua</a>
  <?php
    $categories->data_seek(0);
    while ($cat = $categories->fetch_assoc()):
  ?>
    <a href="index.php?cat=<?= $cat['id'] ?>" class="pill <?= $cat_filter == $cat['id'] ? 'active' : '' ?>">
      <?= htmlspecialchars($cat['name']) ?>
    </a>
  <?php endwhile; ?>
</div>

<!-- SEARCH RESULT INFO -->
<?php if ($search): ?>
  <div class="search-info">
    Hasil pencarian untuk: <strong>"<?= htmlspecialchars($search) ?>"</strong>
    <a href="index.php">Hapus filter</a>
  </div>
<?php endif; ?>

<!-- MASONRY GRID -->
<main class="main-content">
  <div class="masonry-grid" id="masonryGrid">
    <?php if ($pins->num_rows === 0): ?>
      <div class="empty-state">
        <div class="empty-icon">📭</div>
        <h3>Belum ada pin</h3>
        <p>Jadilah yang pertama berbagi inspirasi!</p>
        <?php if (isset($_SESSION['user_id'])): ?>
          <a href="upload.php" class="btn-primary">Upload Pin</a>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <?php while ($pin = $pins->fetch_assoc()): ?>
        <div class="pin-card" data-id="<?= $pin['id'] ?>">
          <div class="pin-image-wrap">
            <img src="uploads/<?= htmlspecialchars($pin['image']) ?>" 
                 alt="<?= htmlspecialchars($pin['title']) ?>"
                 loading="lazy">
            <div class="pin-overlay">
              <?php if (isset($_SESSION['user_id'])): ?>
                <button class="btn-save" onclick="savePin(<?= $pin['id'] ?>)">
                  💾 Simpan
                </button>
              <?php endif; ?>
              <div class="overlay-actions">
                <button class="icon-btn like-btn <?= isset($_SESSION['user_id']) ? '' : 'disabled' ?>"
                        onclick="likePin(<?= $pin['id'] ?>, this)"
                        title="Suka">
                  ❤️ <span class="like-count"><?= $pin['like_count'] ?></span>
                </button>
                <a href="pin.php?id=<?= $pin['id'] ?>" class="icon-btn" title="Komentar">
                  💬 <span><?= $pin['comment_count'] ?></span>
                </a>
                <button class="icon-btn" onclick="sharePin(<?= $pin['id'] ?>)" title="Bagikan">
                  🔗
                </button>
              </div>
            </div>
          </div>
          <div class="pin-info">
            <a href="pin.php?id=<?= $pin['id'] ?>" class="pin-title">
              <?= htmlspecialchars($pin['title']) ?>
            </a>
            <?php if ($pin['cat_name']): ?>
              <span class="pin-tag"><?= htmlspecialchars($pin['cat_name']) ?></span>
            <?php endif; ?>
            <div class="pin-user">
              <a href="profile.php?user=<?= urlencode($pin['username']) ?>" class="user-link">
                <?php if ($pin['avatar']): ?>
                  <img src="uploads/avatars/<?= htmlspecialchars($pin['avatar']) ?>" 
                       class="user-av-img" alt="<?= $pin['username'] ?>">
                <?php else: ?>
                  <div class="user-av" style="background:<?= stringToColor($pin['username']) ?>">
                    <?= strtoupper(substr($pin['username'], 0, 2)) ?>
                  </div>
                <?php endif; ?>
                <span class="user-name">@<?= htmlspecialchars($pin['username']) ?></span>
              </a>
            </div>
          </div>
        </div>
      <?php endwhile; ?>
    <?php endif; ?>
  </div>
</main>

<!-- FLOATING BUTTON UPLOAD -->
<?php if (isset($_SESSION['user_id'])): ?>
  <a href="upload.php" class="fab" title="Upload Pin">+</a>
<?php endif; ?>

<!-- MODAL SIMPAN KE BOARD -->
<div id="modalSave" class="modal hidden">
  <div class="modal-box">
    <div class="modal-header">
      <h3>Simpan ke Board</h3>
      <button onclick="closeModal('modalSave')" class="modal-close">✕</button>
    </div>
    <div id="boardList" class="board-list">
      <!-- Diisi via JS/AJAX -->
    </div>
    <button onclick="createBoard()" class="btn-outline mt-2">+ Buat Board Baru</button>
  </div>
</div>

<!-- MODAL SHARE -->
<div id="modalShare" class="modal hidden">
  <div class="modal-box">
    <div class="modal-header">
      <h3>Bagikan Pin</h3>
      <button onclick="closeModal('modalShare')" class="modal-close">✕</button>
    </div>
    <div class="share-url-box">
      <input type="text" id="shareUrl" readonly>
      <button onclick="copyUrl()" class="btn-primary">Salin</button>
    </div>
  </div>
</div>

<div id="toast" class="toast hidden"></div>

<script src="js/main.js"></script>
<script src="js/feed.js"></script>
</body>
</html>
