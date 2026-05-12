<?php
// php/save_pin.php — Simpan Pin ke Board
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) jsonResponse('error', 'Login diperlukan');

$pin_id   = (int)($_POST['pin_id']   ?? 0);
$board_id = (int)($_POST['board_id'] ?? 0) ?: null;
$user_id  = $_SESSION['user_id'];

if ($pin_id === 0) jsonResponse('error', 'Pin tidak valid');

// Cek sudah disimpan ke board ini?
$bid_check = $board_id ?? 'NULL';
$existing  = $conn->query("SELECT id FROM saved_pins 
                            WHERE user_id=$user_id AND pin_id=$pin_id 
                            AND board_id " . ($board_id ? "= $board_id" : "IS NULL"));

if ($existing->num_rows > 0) {
    // Hapus dari board (unsave)
    $conn->query("DELETE FROM saved_pins 
                  WHERE user_id=$user_id AND pin_id=$pin_id 
                  AND board_id " . ($board_id ? "= $board_id" : "IS NULL"));
    jsonResponse('ok', 'unsaved', ['action' => 'unsaved']);
} else {
    // Simpan
    $stmt = $conn->prepare("INSERT INTO saved_pins (user_id, pin_id, board_id) VALUES (?,?,?)");
    $stmt->bind_param('iii', $user_id, $pin_id, $board_id);
    if ($stmt->execute()) {
        // Notifikasi ke pembuat pin
        $pin = $conn->query("SELECT user_id FROM pins WHERE id=$pin_id")->fetch_assoc();
        if ($pin && $pin['user_id'] != $user_id) {
            $owner = $pin['user_id'];
            $msg   = "menyimpan pin kamu";
            $conn->query("INSERT INTO notifications (user_id, from_user_id, type, pin_id, message)
                          VALUES ($owner, $user_id, 'save', $pin_id, '$msg')");
        }
        jsonResponse('ok', 'saved', ['action' => 'saved']);
    } else {
        jsonResponse('error', 'Gagal menyimpan');
    }
}
