<?php
session_start();
require_once 'php/config.php';
requireLogin();

$user_id = $_SESSION['user_id'];

// Tandai semua notifikasi sebagai sudah dibaca
$conn->query("UPDATE notifications SET is_read=1 WHERE user_id=$user_id");

// Ambil notifikasi
$notifs = $conn->query("
    SELECT notifications.*,
           users.username AS from_username,
           users.avatar   AS from_avatar,
           pins.image     AS pin_image,
           pins.title     AS pin_title
    FROM notifications
    LEFT JOIN users ON notifications.from_user_id = users.id
    LEFT JOIN pins  ON notifications.pin_id = pins.id
    WHERE notifications.user_id = $user_id
    ORDER BY notifications.created_at DESC
    LIMIT 50
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Notifikasi - PinShare</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/feed.css">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body data-logged-in="1">

<?php include 'includes/navbar.php'; ?>

<div class="notif-page">
  <h2>🔔 Notifikasi</h2>

  <?php if ($notifs->num_rows === 0): ?>
    <div class="empty-state" style="background:var(--surface);border-radius:16px;padding:60px;">
      <div class="empty-icon">🔔</div>
      <h3>Belum ada notifikasi</h3>
      <p>Ketika seseorang menyukai atau mengomentari pinmu, akan muncul di sini.</p>
    </div>
  <?php else: ?>
    <div class="notif-list">
      <?php while ($n = $notifs->fetch_assoc()):
        // Tentukan pesan berdasarkan tipe
        $icons = [
          'like'    => '❤️',
          'comment' => '💬',
          'follow'  => '👤',
          'save'    => '💾',
        ];
        $icon = $icons[$n['type']] ?? '🔔';

        // URL tujuan klik
        $link = '#';
        if ($n['pin_id']) $link = "pin.php?id={$n['pin_id']}";
        elseif ($n['from_username']) $link = "profile.php?user={$n['from_username']}";
      ?>
        <a href="<?= $link ?>" class="notif-item <?= $n['is_read'] ? '' : 'unread' ?>">
          <!-- Avatar pengirim -->
          <?php if ($n['from_avatar']): ?>
            <img src="uploads/avatars/<?= htmlspecialchars($n['from_avatar']) ?>"
                 class="notif-av" alt="<?= $n['from_username'] ?>">
          <?php else: ?>
            <div class="notif-av-default"
                 style="background:<?= $n['from_username'] ? stringToColor($n['from_username']) : '#ccc' ?>">
              <?= $n['from_username'] ? strtoupper(substr($n['from_username'], 0, 2)) : '?' ?>
            </div>
          <?php endif; ?>

          <!-- Teks notifikasi -->
          <div class="notif-text">
            <?= $icon ?>
            <strong><?= $n['from_username'] ? '@' . htmlspecialchars($n['from_username']) : 'Seseorang' ?></strong>
            <?= htmlspecialchars($n['message']) ?>
            <?php if ($n['pin_title']): ?>
              — "<em><?= htmlspecialchars(substr($n['pin_title'], 0, 40)) ?></em>"
            <?php endif; ?>
          </div>

          <span class="notif-time"><?= formatDate($n['created_at']) ?></span>

          <!-- Thumbnail pin jika ada -->
          <?php if ($n['pin_image']): ?>
            <img src="uploads/<?= htmlspecialchars($n['pin_image']) ?>"
                 class="notif-pin-thumb" alt="Pin">
          <?php endif; ?>
        </a>
      <?php endwhile; ?>
    </div>
  <?php endif; ?>
</div>

<div id="toast" class="toast hidden"></div>
<script src="js/main.js"></script>
</body>
</html>
