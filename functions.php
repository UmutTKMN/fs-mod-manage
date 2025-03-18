<?php
/**
 * FTP İşlemleri ve Yardımcı Fonksiyonlar
 */

/**
 * FTP bağlantısını oluşturur
 * 
 * @return resource|false FTP bağlantısı veya false
 */
function connectToFtp() {
    // Bağlantı hatalarını kaydet
    try {
        // SSL bağlantısı kullanılacaksa
        if (FTP_SSL) {
            $conn = @ftp_ssl_connect(FTP_HOST, FTP_PORT, FTP_TIMEOUT);
        } else {
            $conn = @ftp_connect(FTP_HOST, FTP_PORT, FTP_TIMEOUT);
        }
        
        if (!$conn) {
            logActivity('ERROR', 'FTP bağlantısı başarısız: ' . FTP_HOST . ':' . FTP_PORT);
            die("FTP sunucusuna bağlanılamadı: " . FTP_HOST);
        }
        
        // Giriş yap
        if (!@ftp_login($conn, FTP_USER, FTP_PASS)) {
            logActivity('ERROR', 'FTP giriş başarısız: Kullanıcı: ' . FTP_USER);
            ftp_close($conn);
            die("FTP sunucusuna giriş yapılamadı. Kullanıcı adı veya şifre hatalı.");
        }
        
        // Pasif mod
        ftp_pasv($conn, true);
        
        // Bağlantı başarılı
        logActivity('INFO', 'FTP bağlantısı başarılı: ' . FTP_HOST);
        return $conn;
    } catch (Exception $e) {
        logActivity('ERROR', 'FTP bağlantı hatası: ' . $e->getMessage());
        die("FTP bağlantısı sırasında bir hata oluştu.");
    }
}

/**
 * FTP bağlantısını kapatır
 * 
 * @param resource $conn FTP bağlantısı
 * @return void
 */
function closeFtpConnection($conn) {
    if ($conn) {
        ftp_close($conn);
    }
}

/**
 * Belirtilen dizindeki dosya ve klasörleri listeler
 * 
 * @param resource $conn FTP bağlantısı
 * @param string $path Dizin yolu
 * @return array Dosya ve klasörlerin listesi
 */
function listFiles($conn, $path = '/') {
    // Path'i güvenli hale getir
    $path = sanitizePath($path);
    
    $path = rtrim($path, '/');
    if (empty($path)) {
        $path = '/';
    }
    
    $items = [];
    
    try {
        // Raw dosya listesini al
        $rawlist = @ftp_rawlist($conn, $path);
        
        if (!$rawlist) {
            logActivity('WARNING', 'Dizin listelenemiyor: ' . $path);
            return $items;
        }
        
        foreach ($rawlist as $item) {
            $info = parseRawlistItem($item);
            if ($info) {
                // "." ve ".." klasörlerini atla
                if ($info['name'] != '.' && $info['name'] != '..') {
                    // XSS koruması için dosya adlarını temizle
                    $info['name'] = sanitizeInput($info['name']);
                    $items[] = $info;
                }
            }
        }
        
        logActivity('INFO', 'Dosya listesi alındı: ' . $path . ' (' . count($items) . ' öğe)');
        return $items;
    } catch (Exception $e) {
        logActivity('ERROR', 'Dosya listeleme hatası: ' . $path . ' - ' . $e->getMessage());
        return [];
    }
}

/**
 * FTP raw list çıktısını parse eder
 * 
 * @param string $rawItem FTP raw list öğesi
 * @return array|false Öğe bilgileri veya false
 */
function parseRawlistItem($rawItem) {
    $parsedItem = preg_split("/\s+/", $rawItem, 9);
    
    if (count($parsedItem) < 9) {
        return false;
    }
    
    $permissions = $parsedItem[0];
    $is_dir = ($permissions[0] === 'd');
    
    // Windows FTP sunucuları için farklı format kontrolü
    if (preg_match('/^\d{2}-\d{2}-\d{2}\s+\d{2}:\d{2}(AM|PM)/', $parsedItem[0])) {
        return parseWindowsRawlistItem($rawItem);
    }
    
    $item = [
        'name' => $parsedItem[8],
        'size' => (int)$parsedItem[4],
        'permissions' => $permissions,
        'is_dir' => $is_dir,
        'date' => strtotime($parsedItem[5] . ' ' . $parsedItem[6] . ' ' . $parsedItem[7])
    ];
    
    return $item;
}

/**
 * Windows FTP sunucuları için raw list çıktısını parse eder
 * 
 * @param string $rawItem FTP raw list öğesi
 * @return array|false Öğe bilgileri veya false
 */
function parseWindowsRawlistItem($rawItem) {
    if (preg_match('/(\d{2}-\d{2}-\d{2}\s+\d{2}:\d{2}(?:AM|PM))\s+(<DIR>|\d+)\s+(.+)/', $rawItem, $matches)) {
        $dateStr = $matches[1];
        $sizeOrDir = $matches[2];
        $name = $matches[3];
        
        $is_dir = ($sizeOrDir === '<DIR>');
        $size = $is_dir ? 0 : (int)$sizeOrDir;
        
        // Windows tarih formatını dönüştür
        $date = DateTime::createFromFormat('m-d-y h:iA', $dateStr);
        $timestamp = $date ? $date->getTimestamp() : time();
        
        return [
            'name' => $name,
            'size' => $size,
            'permissions' => $is_dir ? 'd' : '-',
            'is_dir' => $is_dir,
            'date' => $timestamp
        ];
    }
    
    return false;
}

/**
 * Dosya boyutunu okunabilir formata dönüştürür
 * 
 * @param int $bytes Bayt cinsinden boyut
 * @return string Okunabilir boyut
 */
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, 2) . ' ' . $units[$pow];
}

/**
 * Dosya uzantısını döndürür
 * 
 * @param string $filename Dosya adı
 * @return string Dosya uzantısı
 */
function getFileExtension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

/**
 * Dosya uzantısının izin verilen uzantılar listesinde olup olmadığını kontrol eder
 * 
 * @param string $extension Dosya uzantısı
 * @return bool İzin veriliyorsa true
 */
function isAllowedExtension($extension) {
    global $allowedExtensions;
    
    // Eğer izin verilen uzantılar listesi boşsa tüm uzantılara izin ver
    if (empty($allowedExtensions)) {
        return true;
    }
    
    return in_array(strtolower($extension), $allowedExtensions);
}

/**
 * Geçici klasörü oluşturur
 * 
 * @return void
 */
function createTempDirectory() {
    if (!file_exists(TEMP_UPLOAD_DIR)) {
        mkdir(TEMP_UPLOAD_DIR, 0755, true);
    }
}

/**
 * Geçici klasörü temizler
 * 
 * @return void
 */
function cleanTempDirectory() {
    $files = glob(TEMP_UPLOAD_DIR . '/*');
    
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
}

/**
 * Dosya yolundaki dizinleri oluşturur
 *
 * @param resource $conn FTP bağlantısı
 * @param string $path Dosya yolu
 * @return bool Başarılıysa true
 */
function createDirectories($conn, $path) {
    $path = rtrim(dirname($path), '/');
    if (empty($path)) {
        return true;
    }
    
    $parts = explode('/', $path);
    $currentPath = '';
    
    foreach ($parts as $part) {
        if (empty($part)) {
            continue;
        }
        
        $currentPath .= '/' . $part;
        
        // Dizin var mı kontrol et
        if (@ftp_chdir($conn, $currentPath)) {
            continue;
        }
        
        // Dizin yoksa oluştur
        if (!@ftp_mkdir($conn, $currentPath)) {
            return false;
        }
    }
    
    return true;
}

// Metin girişlerini temizlemek için güvenli fonksiyon
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Dosya yolunu güvenli hale getirir (path traversal koruması)
 * 
 * @param string $path Temizlenecek yol
 * @return string Temizlenmiş yol
 */
function sanitizePath($path) {
    // Başlangıç slash'ını koru
    $startsWithSlash = (substr($path, 0, 1) === '/');
    
    // Path traversal ataklarına karşı koruma
    $path = str_replace('..', '', $path);
    $path = preg_replace('~/+~', '/', $path); // Çoklu slash'ları temizle
    
    // Güvenli olmayan karakterleri temizle
    $path = preg_replace('/[^a-zA-Z0-9\/_\-\.\s]/', '', $path);
    
    // Başlangıç slash'ını geri ekle
    if ($startsWithSlash && substr($path, 0, 1) !== '/') {
        $path = '/' . $path;
    }
    
    return $path;
}

/**
 * Dosya adını güvenli hale getirir
 * 
 * @param string $filename Temizlenecek dosya adı
 * @return string Temizlenmiş dosya adı
 */
function sanitizeFilename($filename) {
    // Dosya adındaki tehlikeli karakterleri temizle
    $filename = preg_replace('/[^a-zA-Z0-9_\-\.\s]/', '', $filename);
    $filename = str_replace('..', '', $filename);
    
    return $filename;
}

/**
 * Dosya yükleme sırasında hata kontrolü yapar
 * 
 * @param array $file $_FILES array'inden dosya bilgisi
 * @return array Hata ve durum bilgisi
 */
function validateUpload($file) {
    $response = [
        'success' => false,
        'error' => ''
    ];
    
    // Hata kodu kontrolü
    if ($file['error'] !== UPLOAD_ERR_OK) {
        switch ($file['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $response['error'] = 'Dosya boyutu çok büyük.';
                break;
            case UPLOAD_ERR_PARTIAL:
                $response['error'] = 'Dosya tam olarak yüklenemedi.';
                break;
            case UPLOAD_ERR_NO_FILE:
                $response['error'] = 'Dosya seçilmedi.';
                break;
            default:
                $response['error'] = 'Bilinmeyen bir hata oluştu.';
        }
        return $response;
    }
    
    // Boyut kontrolü
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        $response['error'] = 'Dosya boyutu sınırı aşıldı. En fazla ' . formatFileSize(MAX_UPLOAD_SIZE) . ' olmalıdır.';
        return $response;
    }
    
    // Dosya uzantısı kontrolü
    if (!empty($allowedExtensions)) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExtensions)) {
            $response['error'] = 'Bu dosya türüne izin verilmiyor.';
            return $response;
        }
    }
    
    // Dosya adı kontrolü
    $filename = sanitizeFilename($file['name']);
    if ($filename !== $file['name']) {
        $response['error'] = 'Dosya adı geçersiz karakterler içeriyor.';
        return $response;
    }
    
    $response['success'] = true;
    return $response;
}

/**
 * İstemcinin IP adresini döndürür
 * 
 * @return string IP adresi
 */
function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

/**
 * Aktiviteyi log dosyasına kaydeder
 * 
 * @param string $action Yapılan işlem
 * @param string $details İşlem detayları
 * @return void
 */
function logActivity($action, $details = '') {
    $logFile = __DIR__ . '/logs/activity.log';
    $logDir = dirname($logFile);
    
    // Log dizini yoksa oluştur
    if (!file_exists($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $ip = getClientIP();
    $logMessage = "[$timestamp] [$ip] [$action] $details" . PHP_EOL;
    
    file_put_contents($logFile, $logMessage, FILE_APPEND);
} 