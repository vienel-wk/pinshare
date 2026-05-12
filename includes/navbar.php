<?php
// includes/navbar.php
// Pastikan session sudah di-start di file utama sebelum include ini

// Hitung notifikasi belum dibaca
$unread_notif = 0;
if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $r = $conn->query("SELECT COUNT(*) AS cnt FROM notifications WHERE user_id=$uid AND is_read=0");
    $unread_notif = $r->fetch_assoc()['cnt'];
}
?>
<nav class="navbar">
  <!-- LOGO -->
  <a href="index.php" class="nav-logo">
    <span class="nav-logo-dot"></span>
    PinShare
  </a>

  <!-- NAVIGASI UTAMA (desktop) -->
  <?php if (isset($_SESSION['user_id'])): ?>
    <a href="index.php"     class="nav-link">Beranda</a>
    <a href="following.php" class="nav-link">Following</a>
  <?php endif; ?>

  <!-- SEARCH BAR -->
  <form id="searchForm" style="flex:1;max-width:560px;">
    <div class="nav-search">
      <span class="nav-search-icon">🔍</span>
      <input type="text" id="searchInput"
             placeholder="Cari pin, kategori, atau orang..."
             value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
    </div>
  </form>

  <!-- TOMBOL AKSI KANAN -->
  <div class="nav-actions">
    <?php if (isset($_SESSION['user_id'])): ?>
      <!-- Tombol Upload -->
      <a href="upload.php" class="btn-primary" style="padding:9px 16px;font-size:13px;">
        + Pin
      </a>

      <!-- Notifikasi -->
      <a href="notifications.php" class="notif-btn" title="Notifikasi">
        🔔
        <?php if ($unread_notif > 0): ?>
          <span class="notif-badge"><?= $unread_notif > 9 ? '9+' : $unread_notif ?></span>
        <?php endif; ?>
      </a>

      <!-- Avatar + Dropdown -->
      <div class="user-menu-wrap">
        <button class="avatar-btn" id="avatarBtn"
                style="background:<?= stringToColor($_SESSION['username']) ?>"
                title="<?= htmlspecialchars($_SESSION['username']) ?>">
          <?php if ($_SESSION['avatar']): ?>
            <img src="uploads/avatars/<?= htmlspecialchars($_SESSION['avatar']) ?>"
                 alt="<?= $_SESSION['username'] ?>">
          <?php else: ?>
            <?= strtoupper(substr($_SESSION['username'], 0, 2)) ?>
          <?php endif; ?>
        </button>

        <div class="user-dropdown" id="userDropdown">
          <a href="profile.php?user=<?= urlencode($_SESSION['username']) ?>" class="dropdown-item">
            👤 Profil Saya
          </a>
          <a href="boards.php" class="dropdown-item">
            📌 Board Saya
          </a>
          <a href="settings.php" class="dropdown-item">
            ⚙️ Pengaturan
          </a>
          <div class="dropdown-divider"></div>
          <a href="logout.php" class="dropdown-item danger">
            🚪 Keluar
          </a>
        </div>
      </div>

    <?php else: ?>
      <!-- Belum login -->
      <a href="login.php" class="btn-outline" style="padding:9px 18px;font-size:14px;">Masuk</a>
      <a href="login.php?tab=register" class="btn-primary">Daftar Gratis</a>
    <?php endif; ?>
  </div>
</nav>
