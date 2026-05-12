<?php
// php/add_comment.php — Tambah komentar / reply
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) jsonResponse('error', 'Login diperlukan');

$pin_id    = (int)($_POST['pin_id']    ?? 0);
$content   = trim($_POST['content']    ?? '');
$parent_id = (int)($_POST['parent_id'] ?? 0) ?: null;
$user_id   = $_SESSION['user_id'];

if ($pin_id === 0)      jsonResponse('error', 'Pin tidak valid');
if (empty($content))    jsonResponse('error', 'Komentar tidak boleh kosong');
if (strlen($content) > 500) jsonResponse('error', 'Komentar terlalu panjang (maks. 500 karakter)');

// Simpan komentar
$stmt = $conn->prepare("INSERT INTO comments (user_id, pin_id, parent_id, content) VALUES (?,?,?,?)");
$stmt->bind_param('iiis', $user_id, $pin_id, $parent_id, $content);

if (!$stmt->execute()) {
    jsonResponse('error', 'Gagal menyimpan komentar');
}

$comment_id = $conn->insert_id;

// Kirim notifikasi ke pembuat pin
$pin = $conn->query("SELECT user_id FROM pins WHERE id=$pin_id")->fetch_assoc();
if ($pin && $pin['user_id'] != $user_id) {
    $owner = $pin['user_id'];
    $msg   = "mengomentari pin kamu";
    $conn->query("INSERT INTO notifications (user_id, from_user_id, type, pin_id, message)
                  VALUES ($owner, $user_id, 'comment', $pin_id, '$msg')");
}

// Jika reply, notifikasi juga ke pembuat komentar asli
if ($parent_id) {
    $parent = $conn->query("SELECT user_id FROM comments WHERE id=$parent_id")->fetch_assoc();
    if ($parent && $parent['user_id'] != $user_id) {
        $parent_user = $parent['user_id'];
        $msg = "membalas komentar kamu";
        $conn->query("INSERT INTO notifications (user_id, from_user_id, type, pin_id, message)
                      VALUES ($parent_user, $user_id, 'comment', $pin_id, '$msg')");
    }
}

// Kembalikan data komentar untuk ditampilkan di DOM
$user = $conn->query("SELECT username, avatar FROM users WHERE id=$user_id")->fetch_assoc();

jsonResponse('ok', 'Komentar berhasil', [
    'comment' => [
        'id'       => $comment_id,
        'username' => $user['username'],
        'avatar'   => $user['avatar'],
        'content'  => $content,
        'color'    => stringToColor($user['username'])
    ]
]);
