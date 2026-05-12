<?php
// php/delete_pin.php — Hapus pin
session_start();
require_once 'config.php';
requireLogin();

$pin_id  = (int)($_GET['id'] ?? 0);
$user_id = $_SESSION['user_id'];

if ($pin_id === 0) redirect(SITE_URL . '/index.php');

$stmt = $conn->prepare("SELECT image, user_id FROM pins WHERE id=?");
$stmt->bind_param('i', $pin_id);
$stmt->execute();
$pin = $stmt->get_result()->fetch_assoc();

if (!$pin || $pin['user_id'] != $user_id) {
    redirect(SITE_URL . '/index.php');
}

// Hapus file gambar
@unlink(UPLOAD_PATH . $pin['image']);

// Hapus dari database (CASCADE akan hapus likes, komentar, saved_pins otomatis)
$conn->query("DELETE FROM pins WHERE id=$pin_id");

redirect(SITE_URL . '/index.php?deleted=1');
