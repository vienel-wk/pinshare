<?php
// php/delete_comment.php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) jsonResponse('error', 'Login diperlukan');

$comment_id = (int)($_POST['comment_id'] ?? 0);
$user_id    = $_SESSION['user_id'];

if ($comment_id === 0) jsonResponse('error', 'Komentar tidak valid');

// Cek kepemilikan: boleh hapus jika milik sendiri atau pemilik pin
$stmt = $conn->prepare("
    SELECT comments.user_id AS commenter_id, pins.user_id AS pin_owner_id
    FROM comments
    JOIN pins ON pins.id = comments.pin_id
    WHERE comments.id = ?
");
$stmt->bind_param('i', $comment_id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) jsonResponse('error', 'Komentar tidak ditemukan');

if ($data['commenter_id'] != $user_id && $data['pin_owner_id'] != $user_id) {
    jsonResponse('error', 'Tidak punya izin menghapus komentar ini');
}

$conn->query("DELETE FROM comments WHERE id=$comment_id");
jsonResponse('ok', 'Komentar dihapus');
