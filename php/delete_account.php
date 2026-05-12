<?php
// php/delete_account.php
session_start();
require_once 'config.php';
requireLogin();

$user_id = $_SESSION['user_id'];

// Hapus semua gambar pin user
$pins = $conn->query("SELECT image FROM pins WHERE user_id=$user_id");
while ($p = $pins->fetch_assoc()) {
    @unlink(UPLOAD_PATH . $p['image']);
}

// Hapus avatar
$user = $conn->query("SELECT avatar FROM users WHERE id=$user_id")->fetch_assoc();
if ($user['avatar']) @unlink(UPLOAD_PATH . 'avatars/' . $user['avatar']);

// Hapus dari database (CASCADE akan hapus semua data terkait)
$conn->query("DELETE FROM users WHERE id=$user_id");

session_destroy();
header('Location: ' . SITE_URL . '/login.php?deleted=1');
exit();
