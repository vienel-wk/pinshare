<?php
// =============================================
// KONFIGURASI DATABASE
// Sesuaikan dengan pengaturan XAMPP kamu
// =============================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');        // default XAMPP
define('DB_PASS', '');            // default XAMPP (kosong)
define('DB_NAME', 'pinshare_db');

define('SITE_URL', 'http://localhost/pinshare');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

// Koneksi ke database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die(json_encode([
        'status' => 'error',
        'message' => 'Koneksi database gagal: ' . $conn->connect_error
    ]));
}

$conn->set_charset('utf8mb4');

// =============================================
// FUNGSI HELPER
// =============================================

/**
 * Konversi nama jadi warna hex (untuk avatar default)
 */
function stringToColor($str) {
    $colors = [
        '#e91e63', '#9c27b0', '#673ab7', '#3f51b5',
        '#2196f3', '#009688', '#4caf50', '#ff9800',
        '#ff5722', '#795548', '#607d8b', '#00bcd4'
    ];
    $hash = array_sum(array_map('ord', str_split($str)));
    return $colors[$hash % count($colors)];
}

/**
 * Sanitasi input dari user
 */
function sanitize($data) {
    global $conn;
    return htmlspecialchars(strip_tags($conn->real_escape_string(trim($data))));
}

/**
 * Redirect ke URL lain
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * Cek apakah user sudah login, redirect jika belum
 */
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        redirect(SITE_URL . '/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    }
}

/**
 * Format tanggal ke bahasa Indonesia
 */
function formatDate($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;

    if ($diff < 60)        return 'Baru saja';
    if ($diff < 3600)      return floor($diff/60) . ' menit lalu';
    if ($diff < 86400)     return floor($diff/3600) . ' jam lalu';
    if ($diff < 2592000)   return floor($diff/86400) . ' hari lalu';
    if ($diff < 31536000)  return floor($diff/2592000) . ' bulan lalu';
    return floor($diff/31536000) . ' tahun lalu';
}

/**
 * Upload gambar ke server
 * @return string|false nama file jika sukses, false jika gagal
 */
function uploadImage($file, $subfolder = '') {
    if ($file['error'] !== UPLOAD_ERR_OK) return false;
    if ($file['size'] > MAX_FILE_SIZE) return false;
    if (!in_array($file['type'], ALLOWED_TYPES)) return false;

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('img_', true) . '.' . $ext;
    $dest = UPLOAD_PATH . $subfolder . $filename;

    if (!is_dir(UPLOAD_PATH . $subfolder)) {
        mkdir(UPLOAD_PATH . $subfolder, 0755, true);
    }

    if (move_uploaded_file($file['tmp_name'], $dest)) {
        return $subfolder . $filename;
    }
    return false;
}

/**
 * Kirim response JSON (untuk endpoint AJAX)
 */
function jsonResponse($status, $message, $data = []) {
    header('Content-Type: application/json');
    echo json_encode(array_merge([
        'status'  => $status,
        'message' => $message
    ], $data));
    exit();
}
