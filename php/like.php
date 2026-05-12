<?php
// php/like.php — Toggle Like Pin
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    jsonResponse('error', 'Login diperlukan');
}

$pin_id  = (int)($_POST['pin_id'] ?? 0);
$user_id = $_SESSION['user_id'];

if ($pin_id === 0) jsonResponse('error', 'Pin tidak valid');

// Cek apakah sudah like
$stmt = $conn->prepare("SELECT id FROM likes WHERE user_id=? AND pin_id=?");
$stmt->bind_param('ii', $user_id, $pin_id);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();

if ($existing) {
    // Unlike
    $conn->query("DELETE FROM likes WHERE user_id=$user_id AND pin_id=$pin_id");
    $action = 'unliked';
} else {
    // Like
    $conn->query("INSERT INTO likes (user_id, pin_id) VALUES ($user_id, $pin_id)");
    $action = 'liked';

    // Kirim notifikasi ke pembuat pin (jika bukan diri sendiri)
    $pin = $conn->query("SELECT user_id FROM pins WHERE id=$pin_id")->fetch_assoc();
    if ($pin && $pin['user_id'] != $user_id) {
        $owner_id = $pin['user_id'];
        $msg = "menyukai pin kamu";
        $conn->query("INSERT INTO notifications (user_id, from_user_id, type, pin_id, message)
                      VALUES ($owner_id, $user_id, 'like', $pin_id, '$msg')");
    }
}

// Hitung total like
$count = $conn->query("SELECT COUNT(*) AS c FROM likes WHERE pin_id=$pin_id")->fetch_assoc()['c'];
jsonResponse('ok', $action, ['action' => $action, 'count' => $count]);
