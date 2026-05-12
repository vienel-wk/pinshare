<?php
// php/follow.php — Follow / Unfollow User
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) jsonResponse('error', 'Login diperlukan');

$target_id = (int)($_POST['target_id'] ?? 0);
$user_id   = $_SESSION['user_id'];

if ($target_id === 0 || $target_id === $user_id) {
    jsonResponse('error', 'Target tidak valid');
}

// Cek sudah follow?
$stmt = $conn->prepare("SELECT id FROM follows WHERE follower_id=? AND following_id=?");
$stmt->bind_param('ii', $user_id, $target_id);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();

if ($existing) {
    // Unfollow
    $conn->query("DELETE FROM follows WHERE follower_id=$user_id AND following_id=$target_id");
    $action = 'unfollowed';
} else {
    // Follow
    $conn->query("INSERT INTO follows (follower_id, following_id) VALUES ($user_id, $target_id)");
    $action = 'followed';

    // Notifikasi
    $msg = "mulai mengikuti kamu";
    $conn->query("INSERT INTO notifications (user_id, from_user_id, type, message)
                  VALUES ($target_id, $user_id, 'follow', '$msg')");
}

// Hitung total follower target
$count = $conn->query("SELECT COUNT(*) AS c FROM follows WHERE following_id=$target_id")->fetch_assoc()['c'];

jsonResponse('ok', $action, [
    'action'         => $action,
    'follower_count' => $count
]);
