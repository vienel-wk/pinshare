<?php
// following.php — Feed pin dari user yang diikuti
session_start();
require_once 'php/config.php';
requireLogin();

$user_id = $_SESSION['user_id'];

// Ambil pin dari user yang diikuti
$pins = $conn->query("
    SELECT pins.*, users.username, users.avatar, categories.name AS cat_name,
           (SELECT COUNT(*) FROM likes WHERE pin_id = pins.id) AS like_count,
           (SELECT COUNT(*) FROM comments WHERE pin_id = pins.id) AS comment_count
    FROM pins
    JOIN follows ON follows.following_id = pins.user_id
    JOIN users ON pins.user_id = users.id
    LEFT JOIN categories ON pins.category_id = categories.id
    WHERE follows.follower_id = $user_id
    ORDER BY pins.created_at DESC
    LIMIT 100
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Following - PinShare</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/feed.css">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body data-logged-in="1">
<?php include 'includes/navbar.php'; ?>

<div style="padding:20px;border-bottom:1px solid var(--border);background:var(--surface);">
  <h2 style="font-family:var(--font-display);font-size:22px;">🏠 Feed Following</h2>
  <p class="text-muted">Pin terbaru dari orang-orang yang kamu ikuti</p>
</div>

<main class="main-content">
  <div class="masonry-grid">
    <?php if ($pins->num_rows === 0): ?>
      <div class="empty-state">
        <div class="empty-icon">👥</div>
        <h3>Belum ada konten</h3>
        <p>Ikuti lebih banyak orang untuk melihat pin mereka di sini!</p>
        <a href="index.php" class="btn-primary">Jelajahi Pin</a>
      </div>
    <?php else: ?>
      <?php while ($pin = $pins->fetch_assoc()): ?>
        <div class="pin-card">
          <div class="pin-image-wrap">
            <img src="uploads/<?= htmlspecialchars($pin['image']) ?>"
                 alt="<?= htmlspecialchars($pin['title']) ?>" loading="lazy">
            <div class="pin-overlay">
              <button class="btn-save" onclick="savePin(<?= $pin['id'] ?>)">💾 Simpan</button>
              <div class="overlay-actions">
                <button class="icon-btn like-btn" onclick="likePin(<?= $pin['id'] ?>, this)">
                  ❤️ <span class="like-count"><?= $pin['like_count'] ?></span>
                </button>
                <a href="pin.php?id=<?= $pin['id'] ?>" class="icon-btn">💬 <?= $pin['comment_count'] ?></a>
              </div>
            </div>
          </div>
          <div class="pin-info">
            <a href="pin.php?id=<?= $pin['id'] ?>" class="pin-title">
              <?= htmlspecialchars($pin['title']) ?>
            </a>
            <div class="pin-user">
              <a href="profile.php?user=<?= urlencode($pin['username']) ?>" class="user-link">
                <div class="user-av" style="background:<?= stringToColor($pin['username']) ?>">
                  <?= strtoupper(substr($pin['username'], 0, 2)) ?>
                </div>
                <span class="user-name">@<?= htmlspecialchars($pin['username']) ?></span>
              </a>
            </div>
          </div>
        </div>
      <?php endwhile; ?>
    <?php endif; ?>
  </div>
</main>

<a href="upload.php" class="fab">+</a>

<div id="modalSave" class="modal hidden">
  <div class="modal-box">
    <div class="modal-header">
      <h3>Simpan ke Board</h3>
      <button onclick="closeModal('modalSave')" class="modal-close">✕</button>
    </div>
    <div id="boardList" class="board-list"></div>
  </div>
</div>
<div id="toast" class="toast hidden"></div>
<script src="js/main.js"></script>
</body>
</html>
