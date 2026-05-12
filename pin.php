<?php
session_start();
require_once 'php/config.php';

$pin_id = (int)($_GET['id'] ?? 0);
if ($pin_id === 0) redirect(SITE_URL . '/index.php');

// Ambil data pin
$stmt = $conn->prepare("
    SELECT pins.*, 
           users.username, users.avatar, users.bio, users.full_name,
           categories.name AS cat_name, categories.icon AS cat_icon,
           (SELECT COUNT(*) FROM likes WHERE pin_id = pins.id) AS like_count,
           (SELECT COUNT(*) FROM comments WHERE pin_id = pins.id) AS comment_count,
           (SELECT COUNT(*) FROM saved_pins WHERE pin_id = pins.id) AS save_count
    FROM pins
    JOIN users ON pins.user_id = users.id
    LEFT JOIN categories ON pins.category_id = categories.id
    WHERE pins.id = ?
");
$stmt->bind_param('i', $pin_id);
$stmt->execute();
$pin = $stmt->get_result()->fetch_assoc();

if (!$pin) {
    echo '<h2 style="text-align:center;margin-top:60px;">Pin tidak ditemukan 😔</h2>';
    exit();
}

// Update jumlah view
$conn->query("UPDATE pins SET views = views + 1 WHERE id = $pin_id");

// Cek apakah user sudah like
$is_liked = false;
$is_saved = false;
$is_following = false;

if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];

    $r = $conn->query("SELECT id FROM likes WHERE user_id=$uid AND pin_id=$pin_id");
    $is_liked = $r->num_rows > 0;

    $r = $conn->query("SELECT id FROM saved_pins WHERE user_id=$uid AND pin_id=$pin_id");
    $is_saved = $r->num_rows > 0;

    $r = $conn->query("SELECT id FROM follows WHERE follower_id=$uid AND following_id={$pin['user_id']}");
    $is_following = $r->num_rows > 0;
}

// Ambil komentar (hanya komentar utama, bukan reply)
$comments = $conn->query("
    SELECT comments.*, users.username, users.avatar
    FROM comments
    JOIN users ON comments.user_id = users.id
    WHERE comments.pin_id = $pin_id AND comments.parent_id IS NULL
    ORDER BY comments.created_at ASC
");

// Fungsi ambil replies
function getReplies($conn, $parent_id) {
    return $conn->query("
        SELECT comments.*, users.username, users.avatar
        FROM comments
        JOIN users ON comments.user_id = users.id
        WHERE comments.parent_id = $parent_id
        ORDER BY comments.created_at ASC
    ");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pin['title']) ?> - PinShare</title>
  <meta name="description" content="<?= htmlspecialchars(substr($pin['description'] ?? '', 0, 160)) ?>">
  <!-- Open Graph untuk preview link -->
  <meta property="og:title"       content="<?= htmlspecialchars($pin['title']) ?>">
  <meta property="og:image"       content="<?= SITE_URL ?>/uploads/<?= htmlspecialchars($pin['image']) ?>">
  <meta property="og:description" content="<?= htmlspecialchars(substr($pin['description'] ?? '', 0, 160)) ?>">
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/feed.css">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body data-logged-in="<?= isset($_SESSION['user_id']) ? '1' : '0' ?>">

<?php include 'includes/navbar.php'; ?>

<?php if (isset($_GET['uploaded'])): ?>
  <div class="alert alert-success" style="margin:16px 20px;max-width:960px;margin-inline:auto;">
    ✅ Pin berhasil dipublish! Selamat berbagi inspirasi 🎉
  </div>
<?php endif; ?>

<div class="pin-detail-page">
  <div class="pin-detail-wrap">

    <!-- GAMBAR PIN -->
    <div class="pin-detail-image">
      <img src="uploads/<?= htmlspecialchars($pin['image']) ?>" 
           alt="<?= htmlspecialchars($pin['title']) ?>">
    </div>

    <!-- INFO & INTERAKSI -->
    <div class="pin-detail-right">

      <!-- Tombol Aksi -->
      <div class="pin-detail-actions">
        <?php if (isset($_SESSION['user_id'])): ?>
          <button class="btn-save" onclick="savePin(<?= $pin_id ?>)" style="border-radius:24px;padding:10px 20px;">
            <?= $is_saved ? '✅ Tersimpan' : '💾 Simpan' ?>
          </button>
        <?php endif; ?>

        <button class="like-btn-large <?= $is_liked ? 'liked' : '' ?>"
                onclick="likePin(<?= $pin_id ?>, this)">
          <?= $is_liked ? '❤️' : '🤍' ?>
          <span class="like-count"><?= $pin['like_count'] ?></span>
        </button>

        <button class="icon-btn" onclick="sharePin(<?= $pin_id ?>)" title="Bagikan">
          🔗
        </button>

        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $pin['user_id']): ?>
          <a href="edit_pin.php?id=<?= $pin_id ?>" class="icon-btn" title="Edit">✏️</a>
          <button class="icon-btn" onclick="confirmDelete(<?= $pin_id ?>)" title="Hapus" style="color:var(--accent)">🗑️</button>
        <?php endif; ?>
      </div>

      <!-- Statistik -->
      <div style="display:flex;gap:16px;font-size:13px;color:var(--text-muted);">
        <span>👁️ <?= number_format($pin['views']) ?> tayangan</span>
        <span>❤️ <span id="likeStatCount"><?= $pin['like_count'] ?></span> suka</span>
        <span>💬 <span id="commentCount"><?= $pin['comment_count'] ?></span> komentar</span>
        <span>💾 <?= $pin['save_count'] ?> disimpan</span>
      </div>

      <!-- Judul & Deskripsi -->
      <div>
        <?php if ($pin['cat_name']): ?>
          <a href="index.php?cat=<?= $pin['category_id'] ?>" class="pin-tag" style="margin-bottom:10px;display:inline-block;">
            <?= $pin['cat_icon'] ?> <?= htmlspecialchars($pin['cat_name']) ?>
          </a>
        <?php endif; ?>

        <h1 class="pin-detail-title"><?= htmlspecialchars($pin['title']) ?></h1>

        <?php if ($pin['description']): ?>
          <p class="pin-detail-desc" style="margin-top:12px;">
            <?= nl2br(htmlspecialchars($pin['description'])) ?>
          </p>
        <?php endif; ?>

        <?php if ($pin['source_url']): ?>
          <div class="pin-detail-source" style="margin-top:10px;">
            <a href="<?= htmlspecialchars($pin['source_url']) ?>" target="_blank" rel="noopener">
              🔗 Lihat sumber asli
            </a>
          </div>
        <?php endif; ?>

        <div class="text-muted" style="margin-top:8px;font-size:13px;">
          📅 Dipublish <?= formatDate($pin['created_at']) ?>
        </div>
      </div>

      <!-- Info Pembuat Pin -->
      <div class="pin-author">
        <a href="profile.php?user=<?= urlencode($pin['username']) ?>">
          <?php if ($pin['avatar']): ?>
            <img src="uploads/avatars/<?= htmlspecialchars($pin['avatar']) ?>"
                 class="pin-author-av" alt="<?= $pin['username'] ?>">
          <?php else: ?>
            <div class="pin-author-av-default" 
                 style="background:<?= stringToColor($pin['username']) ?>">
              <?= strtoupper(substr($pin['username'], 0, 2)) ?>
            </div>
          <?php endif; ?>
        </a>
        <div class="pin-author-info">
          <a href="profile.php?user=<?= urlencode($pin['username']) ?>" class="pin-author-name">
            <?= htmlspecialchars($pin['full_name'] ?: $pin['username']) ?>
          </a>
          <div class="pin-author-bio">
            <?= $pin['bio'] ? htmlspecialchars(substr($pin['bio'], 0, 80)) : '@' . $pin['username'] ?>
          </div>
        </div>
        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $pin['user_id']): ?>
          <button class="<?= $is_following ? 'btn-outline' : 'btn-primary' ?>"
                  onclick="toggleFollow(<?= $pin['user_id'] ?>, this)"
                  style="padding:8px 18px;font-size:13px;">
            <?= $is_following ? 'Mengikuti' : 'Ikuti' ?>
          </button>
        <?php endif; ?>
      </div>

      <!-- SEKSI KOMENTAR -->
      <div class="comments-section">
        <h4>💬 Komentar (<span id="commentCountHeader"><?= $pin['comment_count'] ?></span>)</h4>

        <?php if (isset($_SESSION['user_id'])): ?>
          <form id="commentForm" data-pin-id="<?= $pin_id ?>" data-parent-id="">
            <div class="comment-form">
              <textarea id="commentInput" class="comment-input"
                        placeholder="Tulis komentar..." rows="1"
                        maxlength="500"></textarea>
              <button type="submit" class="comment-send" title="Kirim">➤</button>
            </div>
          </form>
        <?php else: ?>
          <div style="background:var(--pill-bg);border-radius:12px;padding:14px;text-align:center;font-size:14px;color:var(--text-muted);margin-bottom:16px;">
            <a href="login.php" style="color:var(--accent);font-weight:600;">Login</a> untuk meninggalkan komentar
          </div>
        <?php endif; ?>

        <div class="comment-list" id="commentList">
          <?php while ($c = $comments->fetch_assoc()): ?>
            <div class="comment-item" id="comment-<?= $c['id'] ?>">
              <?php if ($c['avatar']): ?>
                <img src="uploads/avatars/<?= htmlspecialchars($c['avatar']) ?>"
                     class="comment-av" alt="<?= $c['username'] ?>">
              <?php else: ?>
                <div class="comment-av-default" 
                     style="background:<?= stringToColor($c['username']) ?>">
                  <?= strtoupper(substr($c['username'], 0, 2)) ?>
                </div>
              <?php endif; ?>
              <div class="comment-bubble">
                <div class="comment-username">
                  <a href="profile.php?user=<?= urlencode($c['username']) ?>">
                    @<?= htmlspecialchars($c['username']) ?>
                  </a>
                </div>
                <div class="comment-text"><?= nl2br(htmlspecialchars($c['content'])) ?></div>
                <div class="comment-meta">
                  <span><?= formatDate($c['created_at']) ?></span>
                  <?php if (isset($_SESSION['user_id'])): ?>
                    <button class="comment-reply-btn" 
                            onclick="startReply(<?= $c['id'] ?>, '<?= htmlspecialchars($c['username']) ?>')">
                      Balas
                    </button>
                  <?php endif; ?>
                  <?php if (isset($_SESSION['user_id']) && ($_SESSION['user_id'] == $c['user_id'] || $_SESSION['user_id'] == $pin['user_id'])): ?>
                    <button class="comment-reply-btn" style="color:var(--accent);"
                            onclick="deleteComment(<?= $c['id'] ?>)">Hapus</button>
                  <?php endif; ?>
                </div>
              </div>
            </div>

            <!-- Replies -->
            <?php $replies = getReplies($conn, $c['id']); ?>
            <?php if ($replies->num_rows > 0): ?>
              <div class="comment-replies" id="replies-<?= $c['id'] ?>">
                <?php while ($r = $replies->fetch_assoc()): ?>
                  <div class="comment-item" id="comment-<?= $r['id'] ?>">
                    <?php if ($r['avatar']): ?>
                      <img src="uploads/avatars/<?= htmlspecialchars($r['avatar']) ?>"
                           class="comment-av" alt="<?= $r['username'] ?>" 
                           style="width:28px;height:28px;">
                    <?php else: ?>
                      <div class="comment-av-default" style="width:28px;height:28px;font-size:10px;background:<?= stringToColor($r['username']) ?>">
                        <?= strtoupper(substr($r['username'], 0, 2)) ?>
                      </div>
                    <?php endif; ?>
                    <div class="comment-bubble">
                      <div class="comment-username">
                        <a href="profile.php?user=<?= urlencode($r['username']) ?>">
                          @<?= htmlspecialchars($r['username']) ?>
                        </a>
                      </div>
                      <div class="comment-text"><?= nl2br(htmlspecialchars($r['content'])) ?></div>
                      <div class="comment-meta">
                        <span><?= formatDate($r['created_at']) ?></span>
                      </div>
                    </div>
                  </div>
                <?php endwhile; ?>
              </div>
            <?php endif; ?>

          <?php endwhile; ?>

          <?php if ($pin['comment_count'] == 0): ?>
            <div style="text-align:center;padding:24px;color:var(--text-muted);font-size:14px;">
              Belum ada komentar. Jadilah yang pertama! 💬
            </div>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- MODAL SIMPAN KE BOARD -->
<div id="modalSave" class="modal hidden">
  <div class="modal-box">
    <div class="modal-header">
      <h3>Simpan ke Board</h3>
      <button onclick="closeModal('modalSave')" class="modal-close">✕</button>
    </div>
    <div id="boardList" class="board-list"></div>
    <div style="padding:12px 24px 16px;">
      <button onclick="createBoard()" class="btn-outline" style="width:100%;justify-content:center;">
        + Buat Board Baru
      </button>
    </div>
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
