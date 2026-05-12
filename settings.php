<?php
session_start();
require_once 'php/config.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$error   = '';
$success = '';

// Ambil data user saat ini
$stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// =============================================
// PROSES UPDATE PROFIL
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_profile'])) {
    $full_name = sanitize($_POST['full_name'] ?? '');
    $bio       = sanitize($_POST['bio']       ?? '');
    $website   = sanitize($_POST['website']   ?? '');

    // Upload avatar baru jika ada
    $avatar = $user['avatar'];
    if (!empty($_FILES['avatar']['name'])) {
        $newAvatar = uploadImage($_FILES['avatar'], 'avatars/');
        if ($newAvatar) {
            // Hapus avatar lama
            if ($avatar) @unlink(UPLOAD_PATH . 'avatars/' . $avatar);
            $avatar = basename($newAvatar); // simpan hanya nama file
        } else {
            $error = 'Gagal upload foto profil.';
        }
    }

    if (!$error) {
        $stmt = $conn->prepare("UPDATE users SET full_name=?, bio=?, website=?, avatar=? WHERE id=?");
        $stmt->bind_param('ssssi', $full_name, $bio, $website, $avatar, $user_id);
        if ($stmt->execute()) {
            $_SESSION['avatar'] = $avatar;
            $success = 'Profil berhasil diperbarui!';
            // Reload data user
            $stmt2 = $conn->prepare("SELECT * FROM users WHERE id=?");
            $stmt2->bind_param('i', $user_id);
            $stmt2->execute();
            $user = $stmt2->get_result()->fetch_assoc();
        } else {
            $error = 'Gagal menyimpan perubahan.';
        }
    }
}

// =============================================
// PROSES GANTI PASSWORD
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_password'])) {
    $old_pass  = $_POST['old_password']  ?? '';
    $new_pass  = $_POST['new_password']  ?? '';
    $new_pass2 = $_POST['new_password2'] ?? '';

    if (!password_verify($old_pass, $user['password'])) {
        $error = 'Password lama tidak cocok.';
    } elseif (strlen($new_pass) < 6) {
        $error = 'Password baru minimal 6 karakter.';
    } elseif ($new_pass !== $new_pass2) {
        $error = 'Konfirmasi password baru tidak cocok.';
    } else {
        $hashed = password_hash($new_pass, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
        $stmt->bind_param('si', $hashed, $user_id);
        $stmt->execute();
        $success = 'Password berhasil diubah!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pengaturan - PinShare</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/feed.css">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body data-logged-in="1">

<?php include 'includes/navbar.php'; ?>

<div class="settings-page">
  <h2>⚙️ Pengaturan Akun</h2>

  <?php if ($error):   ?><div class="alert alert-error">❌ <?= $error ?></div><?php endif; ?>
  <?php if ($success): ?><div class="alert alert-success">✅ <?= $success ?></div><?php endif; ?>

  <!-- EDIT PROFIL -->
  <div class="settings-section">
    <h3>👤 Informasi Profil</h3>
    <form action="settings.php" method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action_profile" value="1">

      <!-- Upload Avatar -->
      <div class="avatar-upload-wrap">
        <?php if ($user['avatar']): ?>
          <img src="uploads/avatars/<?= htmlspecialchars($user['avatar']) ?>"
               id="avatarPreview" class="avatar-upload-preview" alt="Avatar">
        <?php else: ?>
          <div id="avatarDefault" class="avatar-upload-preview-default"
               style="background:<?= stringToColor($user['username']) ?>">
            <?= strtoupper(substr($user['username'], 0, 2)) ?>
          </div>
          <img id="avatarPreview" src="" class="avatar-upload-preview" alt="" style="display:none;">
        <?php endif; ?>
        <div>
          <label for="avatarFileInput" class="btn-outline" style="cursor:pointer;display:inline-flex;">
            📷 Ganti Foto
          </label>
          <input type="file" id="avatarFileInput" name="avatar" accept="image/*" style="display:none;">
          <p class="text-muted" style="margin-top:6px;font-size:12px;">JPG, PNG (maks. 2MB)</p>
        </div>
      </div>

      <div class="form-group">
        <label>Username</label>
        <input type="text" class="form-control" value="@<?= htmlspecialchars($user['username']) ?>" 
               disabled style="background:var(--pill-bg);color:var(--text-muted);">
        <div style="font-size:12px;color:var(--text-muted);margin-top:4px;">Username tidak bisa diubah</div>
      </div>

      <div class="form-group">
        <label for="fullName">Nama Lengkap</label>
        <input type="text" id="fullName" name="full_name" class="form-control"
               placeholder="Nama lengkap kamu" maxlength="100"
               value="<?= htmlspecialchars($user['full_name'] ?? '') ?>">
      </div>

      <div class="form-group">
        <label for="bio">Bio</label>
        <textarea id="bio" name="bio" class="form-control"
                  placeholder="Ceritakan sedikit tentang dirimu..."
                  rows="3" maxlength="300"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
        <div style="font-size:12px;color:var(--text-muted);margin-top:4px;">Maksimal 300 karakter</div>
      </div>

      <div class="form-group">
        <label for="website">Website / Link</label>
        <input type="url" id="website" name="website" class="form-control"
               placeholder="https://instagram.com/username"
               value="<?= htmlspecialchars($user['website'] ?? '') ?>">
      </div>

      <div style="display:flex;gap:12px;">
        <a href="profile.php?user=<?= urlencode($user['username']) ?>" class="btn-outline">Batal</a>
        <button type="submit" class="btn-primary">💾 Simpan Perubahan</button>
      </div>
    </form>
  </div>

  <!-- GANTI PASSWORD -->
  <div class="settings-section">
    <h3>🔒 Ganti Password</h3>
    <form action="settings.php" method="POST">
      <input type="hidden" name="action_password" value="1">

      <div class="form-group">
        <label for="oldPass">Password Saat Ini</label>
        <input type="password" id="oldPass" name="old_password" class="form-control"
               placeholder="Masukkan password lama" required>
      </div>

      <div class="form-group">
        <label for="newPass">Password Baru</label>
        <input type="password" id="newPass" name="new_password" class="form-control"
               placeholder="Minimal 6 karakter" required>
      </div>

      <div class="form-group">
        <label for="newPass2">Konfirmasi Password Baru</label>
        <input type="password" id="newPass2" name="new_password2" class="form-control"
               placeholder="Ulangi password baru" required>
      </div>

      <button type="submit" class="btn-primary">🔒 Ganti Password</button>
    </form>
  </div>

  <!-- HAPUS AKUN -->
  <div class="settings-section" style="border-color:#ffcdd2;">
    <h3 style="color:var(--accent);">⚠️ Zona Berbahaya</h3>
    <p class="text-muted" style="margin-bottom:16px;">
      Menghapus akun akan menghapus semua pin, komentar, dan data kamu secara permanen.
    </p>
    <button class="btn-outline" style="border-color:var(--accent);color:var(--accent);"
            onclick="confirmDeleteAccount()">
      🗑️ Hapus Akun Saya
    </button>
  </div>

</div>

<div id="toast" class="toast hidden"></div>
<script src="js/main.js"></script>
<script src="js/feed.js"></script>
<script>
function confirmDeleteAccount() {
  const confirm1 = confirm('Yakin ingin menghapus akun?\nSemua data akan hilang permanen!');
  if (!confirm1) return;
  const confirm2 = prompt('Ketik "HAPUS" untuk konfirmasi:');
  if (confirm2 === 'HAPUS') {
    window.location.href = 'php/delete_account.php';
  } else {
    showToast('Penghapusan dibatalkan', 'info');
  }
}
</script>
</body>
</html>
