<?php
// php/get_boards.php — Ambil daftar board milik user
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) jsonResponse('error', 'Login diperlukan');

$user_id = $_SESSION['user_id'];

$result = $conn->query("
    SELECT boards.*, 
           (SELECT COUNT(*) FROM saved_pins WHERE board_id = boards.id) AS pin_count,
           (SELECT image FROM pins 
            JOIN saved_pins ON saved_pins.pin_id = pins.id 
            WHERE saved_pins.board_id = boards.id 
            LIMIT 1) AS cover
    FROM boards
    WHERE boards.user_id = $user_id
    ORDER BY boards.created_at DESC
");

$boards = [];
while ($b = $result->fetch_assoc()) {
    $boards[] = $b;
}

jsonResponse('ok', 'success', ['boards' => $boards]);
