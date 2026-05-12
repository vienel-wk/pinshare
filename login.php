<?php
session_start();
require_once 'php/config.php';

// Redirect jika sudah login
if (isset($_SESSION['user_id'])) {
    redirect(SITE_URL . '/index.php');
}

$error   = '';
$success = '';
$tab     = $_GET['tab'] ?? 'login';

// =============================================
// PROSES LOGIN
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_login'])) {
    $email    = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Email dan password tidak boleh kosong.';
    } else {
        $stmt = $conn->prepare("SELECT id, username, password, avatar FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['avatar']   = $user['avatar'];

            $redirect = $_GET['redirect'] ?? SITE_URL . '/index.php';
            redirect($redirect);
        } else {
            $error = 'Email atau password salah.';
        }
    }
    $tab = 'login';
}

// =============================================
// PROSES REGISTER
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_register'])) {
    $username  = sanitize($_POST['username'] ?? '');
    $email     = sanitize($_POST['email']    ?? '');
    $password  = $_POST['password']  ?? '';
    $password2 = $_POST['password2'] ?? '';

    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Semua kolom wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== $password2) {
        $error = 'Konfirmasi password tidak cocok.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
        $error = 'Username hanya boleh huruf, angka, underscore (3-30 karakter).';
    } else {
        // Cek duplikat
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt->bind_param('ss', $email, $username);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = 'Email atau username sudah digunakan.';
        } else {
            // Simpan user baru
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param('sss', $username, $email, $hashed);

            if ($stmt->execute()) {
                $success = 'Akun berhasil dibuat! Silakan login.';
                $tab = 'login';
            } else {
                $error = 'Gagal membuat akun. Coba lagi.';
            }
        }
    }
    if (!$success) $tab = 'register';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login / Daftar - PinShare</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/feed.css">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>
<div class="auth-page">
  <div class="auth-card">

    <div class="auth-logo">
      <h1>● PinShare</h1>
      <p>Temukan dan bagikan inspirasi terbaikmu</p>
    </div>

    <div class="auth-tabs">
      <button class="auth-tab <?= $tab === 'login'    ? 'active' : '' ?>" 
              onclick="switchTab('login')">Masuk</button>
      <button class="auth-tab <?= $tab === 'register' ? 'active' : '' ?>" 
              onclick="switchTab('register')">Daftar</button>
    </div>

    <?php if ($error):   ?><div class="alert alert-error">❌ <?= $error ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success">✅ <?= $success ?></div><?php endif; ?>

    <!-- FORM LOGIN -->
    <form id="formLogin" action="login.php" method="POST" 
          style="display:<?= $tab === 'login' ? 'block' : 'none' ?>">
      <input type="hidden" name="action_login" value="1">

      <div class="form-group">
        <label for="loginEmail">Email</label>
        <input type="email" id="loginEmail" name="email" class="form-control"
               placeholder="nama@email.com" required
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      </div>

      <div class="form-group">
        <label for="loginPassword">Password</label>
        <input type="password" id="loginPassword" name="password" class="form-control"
               placeholder="Masukkan password" required>
      </div>

      <button type="submit" class="btn-primary" style="width:100%;justify-content:center;">
        Masuk →
      </button>
    </form>

    <!-- FORM REGISTER -->
    <form id="formRegister" action="login.php?tab=register" method="POST"
          style="display:<?= $tab === 'register' ? 'block' : 'none' ?>">
      <input type="hidden" name="action_register" value="1">

      <div class="form-group">
        <label for="regUsername">Username</label>
        <input type="text" id="regUsername" name="username" class="form-control"
               placeholder="contoh: budi_123" required
               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
      </div>

      <div class="form-group">
        <label for="regEmail">Email</label>
        <input type="email" id="regEmail" name="email" class="form-control"
               placeholder="nama@email.com" required
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      </div>

      <div class="form-group">
        <label for="regPassword">Password</label>
        <input type="password" id="regPassword" name="password" class="form-control"
               placeholder="Minimal 6 karakter" required>
      </div>

      <div class="form-group">
        <label for="regPassword2">Konfirmasi Password</label>
        <input type="password" id="regPassword2" name="password2" class="form-control"
               placeholder="Ulangi password" required>
      </div>

      <button type="submit" class="btn-primary" style="width:100%;justify-content:center;">
        Buat Akun 🎉
      </button>

      <p class="auth-footer">
        Dengan mendaftar, kamu menyetujui 
        <a href="#">Syarat & Ketentuan</a> kami.
      </p>
    </form>

  </div>
</div>

<div id="toast" class="toast hidden"></div>

<script>
function switchTab(tab) {
  document.getElementById('formLogin').style.display    = tab === 'login'    ? 'block' : 'none';
  document.getElementById('formRegister').style.display = tab === 'register' ? 'block' : 'none';
  document.querySelectorAll('.auth-tab').forEach((t, i) => {
    t.classList.toggle('active', (i === 0 && tab === 'login') || (i === 1 && tab === 'register'));
  });
}
</script>
<script src="js/main.js"></script>
</body>
</html>
