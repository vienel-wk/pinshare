<?php
session_start();
require_once 'php/config.php';

$username = $_GET['user'] ?? '';
if (empty($username)) redirect(SITE_URL . '/index.php');

// Ambil data user
$stmt = $conn->prepare("SELECT * FROM users WHERE username=?");
$stmt->bind_param('s', $username);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    echo '<div style="text-align:center;padding:80px;font-size:18px;">User tidak ditemukan 😔</div>';
    exit();
}

$uid         = $user['id'];
$is_own      = isset($_SESSION['user_id']) && $_SESSION['user_id'] == $uid;
$is_following = false;
$tab          = $_GET['tab'] ?? 'pins';

// Statistik
$pin_count      = $conn->query("SELECT COUNT(*) AS c FROM pins WHERE user_id=$uid")->fetch_assoc()['c'];
$follower_count = $conn->query("SELECT COUNT(*) AS c FROM follows WHERE following_id=$uid")->fetch_assoc()['c'];
$following_count= $conn->query("SELECT COUNT(*) AS c FROM follows WHERE follower_id=$uid")->fetch_assoc()['c'];
$save_count     = $conn->query("SELECT COUNT(*) AS c FROM saved_pins WHERE user_id=$uid")->fetch_assoc()['c'];

if (isset($_SESSION['user_id']) && !$is_own) {
    $viewer = $_SESSION['user_id'];
    $r = $conn->query("SELECT id FROM follows WHERE follower_id=$viewer AND following_id=$uid");
    $is_following = $r->num_rows > 0;
}

// Ambil pin user
$user_pins = $conn->query("
    SELECT pins.*, 
           (SELECT COUNT(*) FROM likes WHERE pin_id = pins.id) AS like_count
    FROM pins WHERE user_id=$uid ORDER BY created_at DESC
");

// Ambil board user
$boards = $conn->query("
    SELECT boards.*,
           (SELECT COUNT(*) FROM saved_pins WHERE board_id = boards.id) AS pin_count,
           (SELECT image FROM pins JOIN saved_pins ON saved_pins.pin_id = pins.id 
            WHERE saved_pins.board_id = boards.id LIMIT 1) AS cover
    FROM boards WHERE user_id=$uid ORDER BY created_at DESC
");

// Pin tersimpan
$saved = $conn->query("
    SELECT pins.*, users.username AS owner_username
    FROM saved_pins
    JOIN pins ON saved_pins.pin_id = pins.id
    JOIN users ON pins.user_id = users.id
    WHERE saved_pins.user_id=$uid
    ORDER BY saved_pins.saved_at DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($user['full_name'] ?: $user['username']) ?> - PinShare</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/feed.css">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body data-logged-in="<?= isset($_SESSION['user_id']) ? '1' : '0' ?>">

<?php include 'includes/navbar.php'; ?>

<!-- HEADER PROFIL -->
<div class="profile-header">
  <?php if ($user['avatar']): ?>
    <img src="uploads/avatars/<?= htmlspecialchars($user['avatar']) ?>"
         class="profile-avatar" alt="<?= $user['username'] ?>">
  <?php else: ?>
    <div class="profile-avatar-default" 
         style="background:<?= stringToColor($user['username']) ?>">
      <?= strtoupper(substr($user['username'], 0, 2)) ?>
    </div>
  <?php endif; ?>

  <h1 class="profile-name">
    <?= htmlspecialchars($user['full_name'] ?: $user['username']) ?>
    <?php if ($user['is_verified']): ?><span title="Terverifikasi">✅</span><?php endif; ?>
  </h1>
  <div class="profile-username">@<?= htmlspecialchars($user['username']) ?></div>

  <?php if ($user['bio']): ?>
    <p class="profile-bio"><?= nl2br(htmlspecialchars($user['bio'])) ?></p>
  <?php endif; ?>

  <?php if ($user['website']): ?>
    <a href="<?= htmlspecialchars($user['website']) ?>" target="_blank" 
       style="color:var(--accent);font-size:14px;">
      🔗 <?= htmlspecialchars(parse_url($user['website'], PHP_URL_HOST) ?: $user['website']) ?>
    </a>
  <?php endif; ?>

  <div class="profile-stats">
    <div class="stat-item">
      <span class="stat-number"><?= number_format($pin_count) ?></span>
      <span class="stat-label">Pin</span>
    </div>
    <div class="stat-item">
      <span class="stat-number" id="followerCount"><?= number_format($follower_count) ?></span>
      <span class="stat-label">Pengikut</span>
    </div>
    <div class="stat-item">
      <span class="stat-number"><?= number_format($following_count) ?></span>
      <span class="stat-label">Mengikuti</span>
    </div>
  </div>

  <div class="profile-actions">
    <?php if ($is_own): ?>
      <a href="settings.php" class="btn-outline">✏️ Edit Profil</a>
      <a href="upload.php"   class="btn-primary">+ Upload Pin</a>
    <?php elseif (isset($_SESSION['user_id'])): ?>
      <button class="<?= $is_following ? 'btn-outline' : 'btn-primary' ?>"
              onclick="toggleFollow(<?= $uid ?>, this)">
        <?= $is_following ? 'Mengikuti' : 'Ikuti' ?>
      </button>
    <?php endif; ?>
  </div>
</div>

<!-- TABS -->
<div class="profile-tabs">
  <button class="profile-tab <?= $tab == 'pins'   ? 'active' : '' ?>" 
          data-tab="pins"   onclick="switchProfileTab('pins')">
    📌 Pin (<?= $pin_count ?>)
  </button>
  <button class="profile-tab <?= $tab == 'boards' ? 'active' : '' ?>"
          data-tab="boards" onclick="switchProfileTab('boards')">
    🗂️ Board
  </button>
  <?php if ($is_own): ?>
    <button class="profile-tab <?= $tab == 'saved' ? 'active' : '' ?>"
            data-tab="saved"  onclick="switchProfileTab('saved')">
      💾 Tersimpan
    </button>
  <?php endif; ?>
</div>

<!-- TAB: PIN -->
<div id="tab-pins" class="tab-content <?= $tab == 'pins' ? '' : 'hidden' ?>"
     style="padding:20px;max-width:1800px;margin:0 auto;">
  <div class="masonry-grid">
    <?php if ($user_pins->num_rows === 0): ?>
      <div class="empty-state">
        <div class="empty-icon">📭</div>
        <p><?= $is_own ? 'Kamu belum upload pin. Yuk mulai!' : 'User ini belum punya pin.' ?></p>
        <?php if ($is_own): ?><a href="upload.php" class="btn-primary">Upload Pin Pertamamu</a><?php endif; ?>
      </div>
    <?php else: ?>
      <?php while ($pin = $user_pins->fetch_assoc()): ?>
        <div class="pin-card">
          <div class="pin-image-wrap">
            <img src="uploads/<?= htmlspecialchars($pin['image']) ?>"
                 alt="<?= htmlspecialchars($pin['title']) ?>" loading="lazy">
            <div class="pin-overlay">
              <div class="overlay-actions">
                <button class="icon-btn like-btn" onclick="likePin(<?= $pin['id'] ?>, this)">
                  ❤️ <span class="like-count"><?= $pin['like_count'] ?></span>
                </button>
                <?php if ($is_own): ?>
                  <a href="edit_pin.php?id=<?= $pin['id'] ?>" class="icon-btn">✏️</a>
                  <button class="icon-btn" onclick="confirmDelete(<?= $pin['id'] ?>)">🗑️</button>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <div class="pin-info">
            <a href="pin.php?id=<?= $pin['id'] ?>" class="pin-title">
              <?= htmlspecialchars($pin['title']) ?>
            </a>
          </div>
        </div>
      <?php endwhile; ?>
    <?php endif; ?>
  </div>
</div>

<!-- TAB: BOARD -->
<div id="tab-boards" class="tab-content <?= $tab == 'boards' ? '' : 'hidden' ?>">
  <div class="boards-grid">
    <?php if ($boards->num_rows === 0): ?>
      <div class="empty-state" style="grid-column:1/-1">
        <div class="empty-icon">🗂️</div>
        <p><?= $is_own ? 'Buat board untuk mengorganisir pin.' : 'Belum ada board publik.' ?></p>
        <?php if ($is_own): ?><a href="boards.php" class="btn-primary">Buat Board</a><?php endif; ?>
      </div>
    <?php else: ?>
      <?php while ($b = $boards->fetch_assoc()): ?>
        <a href="board.php?id=<?= $b['id'] ?>" class="board-card" style="text-decoration:none;color:inherit;">
          <div class="board-cover">
            <div class="board-cover-img">
              <?= $b['cover'] ? "<img src='uploads/{$b['cover']}' alt=''>" : '📌' ?>
            </div>
            <div class="board-cover-img">📷</div>
            <div class="board-cover-img">🎨</div>
          </div>
          <div class="board-card-info">
            <div class="board-card-name"><?= htmlspecialchars($b['name']) ?></div>
            <div class="board-card-count"><?= $b['pin_count'] ?> pin</div>
          </div>
        </a>
      <?php endwhile; ?>
    <?php endif; ?>
  </div>
</div>

<!-- TAB: TERSIMPAN -->
<?php if ($is_own): ?>
<div id="tab-saved" class="tab-content <?= $tab == 'saved' ? '' : 'hidden' ?>"
     style="padding:20px;max-width:1800px;margin:0 auto;">
  <div class="masonry-grid">
    <?php if ($saved->num_rows === 0): ?>
      <div class="empty-state">
        <div class="empty-icon">💾</div>
        <p>Belum ada pin tersimpan. Jelajahi dan simpan pin favoritmu!</p>
        <a href="index.php" class="btn-primary">Jelajahi Pin</a>
      </div>
    <?php else: ?>
      <?php while ($pin = $saved->fetch_assoc()): ?>
        <div class="pin-card">
          <div class="pin-image-wrap">
            <img src="uploads/<?= htmlspecialchars($pin['image']) ?>"
                 alt="<?= htmlspecialchars($pin['title']) ?>" loading="lazy">
          </div>
          <div class="pin-info">
            <a href="pin.php?id=<?= $pin['id'] ?>" class="pin-title">
              <?= htmlspecialchars($pin['title']) ?>
            </a>
            <div class="pin-user">
              <span class="user-name">@<?= htmlspecialchars($pin['owner_username']) ?></span>
            </div>
          </div>
        </div>
      <?php endwhile; ?>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

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
<script src="js/feed.js"></script>
<script>
function switchProfileTab(tab) {
  document.querySelectorAll('.tab-content').forEach(c => c.classList.add('hidden'));
  document.querySelectorAll('.profile-tab').forEach(t => t.classList.remove('active'));
  document.getElementById('tab-' + tab).classList.remove('hidden');
  document.querySelector(`[data-tab="${tab}"]`).classList.add('active');
}
</script>
</body>
</html>
