<?php
/**
 * FTP Bağlantı Ayarları
 */

// Hata ayıklama modunu kapalı tut (production ortamında)
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Hata günlüğü ayarları
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php_errors.log');

// Oturum güvenliği
ini_set('session.cookie_httponly', 1); // JavaScript ile erişimi engeller
ini_set('session.use_only_cookies', 1); // Sadece çerezler ile oturum yönetimi
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0); // HTTPS varsa güvenli çerez
ini_set('session.cookie_samesite', 'Strict'); // CSRF koruması için SameSite

// Zaman dilimi ayarları
date_default_timezone_set('Europe/Istanbul');

// Geçici klasör yolu
define('TEMP_UPLOAD_DIR', __DIR__ . '/temp');

// FTP Bağlantı Bilgileri - Üretim ortamında .env dosyasından alınmalı veya daha güvenli bir şekilde saklanmalı
if (file_exists(__DIR__ . '/.env.php')) {
    // .env.php dosyası varsa oradan oku
    require_once __DIR__ . '/.env.php';
}

/**
 * CSRF Token oluştur ve oturuma kaydet
 * Bu fonksiyon index.php ve form işlemlerinde kullanılmalıdır
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}