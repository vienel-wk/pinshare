<?php
// php/create_board.php — Buat board baru
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) jsonResponse('error', 'Login diperlukan');

$name    = sanitize($_POST['name'] ?? '');
$user_id = $_SESSION['user_id'];

if (empty($name)) jsonResponse('error', 'Nama board tidak boleh kosong');
if (strlen($name) > 100) jsonResponse('error', 'Nama board terlalu panjang');

// Cek duplikat board dengan nama sama untuk user ini
$stmt = $conn->prepare("SELECT id FROM boards WHERE user_id=? AND name=?");
$stmt->bind_param('is', $user_id, $name);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    jsonResponse('error', 'Kamu sudah punya board dengan nama ini');
}

$stmt = $conn->prepare("INSERT INTO boards (user_id, name) VALUES (?,?)");
$stmt->bind_param('is', $user_id, $name);

if ($stmt->execute()) {
    jsonResponse('ok', 'Board berhasil dibuat', ['board_id' => $conn->insert_id]);
} else {
    jsonResponse('error', 'Gagal membuat board');
}
