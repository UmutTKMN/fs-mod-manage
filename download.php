<?php
require_once 'config.php';
require_once 'functions.php';

// Oturum başlat
session_start();
session_regenerate_id(true); // Oturum sabitleme koruması

// Geçici klasörü oluştur
createTempDirectory();

// Güvenlik zamanlaması - DDoS koruma
sleep(1);

// FTP bağlantısını kur
$ftpConnection = connectToFtp();

// Tek dosya indirme
if (isset($_GET['file']) && !empty($_GET['file'])) {
    $filePath = $_GET['file'];
    
    // Güvenlik kontrolleri - path traversal ve zararlı karakter önleme
    $filePath = sanitizePath($filePath);
    
    // Dosya adını temizle
    $fileName = sanitizeFilename(basename($filePath));
    
    if (empty($fileName)) {
        $_SESSION['errors'] = ['Geçersiz dosya adı.'];
        header('Location: index.php');
        exit();
    }
    
    // İşlemi logla
    logActivity('DOWNLOAD', 'Dosya indirme: ' . $filePath);
    
    // Geçici dosya yolu
    $tempFilePath = TEMP_UPLOAD_DIR . '/' . $fileName;
    
    // FTP'den dosyayı indir
    if (@ftp_get($ftpConnection, $tempFilePath, $filePath, FTP_BINARY)) {
        // Dosya bilgilerini al
        $fileSize = filesize($tempFilePath);
        
        // Dosya boyutu sıfır kontrolü
        if ($fileSize <= 0) {
            unlink($tempFilePath);
            $_SESSION['errors'] = ['Dosya boş veya indirme sırasında bir hata oluştu: ' . $fileName];
            header('Location: index.php');
            exit();
        }
        
        // Dosya MIME türünü belirle
        $fileType = mime_content_type($tempFilePath) ?: 'application/octet-stream';
        
        // Güvenli MIME türleri - özel kısıtlama istiyorsanız burayı düzenleyin
        $allowedMimeTypes = [
            'application/octet-stream', 
            'application/zip',
            'text/plain',
            'text/xml',
            'application/xml',
            'image/jpeg',
            'image/png',
            'image/gif',
            'application/pdf'
        ];
        
        // MIME türü kısıtlamasını istiyorsanız açın
        if (!in_array($fileType, $allowedMimeTypes)) {
            unlink($tempFilePath);
            logActivity('SECURITY', 'İzin verilmeyen dosya türü: ' . $fileType);
            $_SESSION['errors'] = ['Bu dosya türü indirmeye izin verilmiyor.'];
            header('Location: index.php');
            exit();
        }
        
        // Dosya indirme başlıkları
        header('Content-Description: File Transfer');
        header('Content-Type: ' . $fileType);
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Content-Length: ' . $fileSize);
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Expires: 0');
        
        // Çıktı tamponlamasını kapat ve tampon içindeki veriyi temizle
        ob_end_clean();
        
        // Dosyayı oku ve gönder
        readfile($tempFilePath);
        
        // Geçici dosyayı sil
        unlink($tempFilePath);
        
        // FTP bağlantısını kapat
        closeFtpConnection($ftpConnection);
        exit();
    } else {
        logActivity('ERROR', 'Dosya indirilemedi: ' . $filePath);
        $_SESSION['errors'] = ['Dosya indirilirken bir hata oluştu: ' . $fileName];
    }
}
// Çoklu dosya indirme
elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['files']) && is_array($_POST['files']) && !empty($_POST['files'])) {
    // CSRF koruma
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        logActivity('SECURITY', 'CSRF saldırısı girişimi yapıldı');
        $_SESSION['errors'] = ['Güvenlik doğrulaması başarısız oldu. Lütfen tekrar deneyin.'];
        header('Location: index.php');
        exit();
    }
    
    // Bir dosya seçildiğinde normal indirme yap
    if (count($_POST['files']) == 1) {
        $fileName = sanitizeFilename($_POST['files'][0]);
        $currentPath = isset($_POST['path']) ? sanitizePath($_POST['path']) : '/';
        $currentPath = rtrim($currentPath, '/') . '/';
        $filePath = $currentPath . $fileName;
        
        // Yönlendir
        header('Location: download.php?file=' . urlencode($filePath));
        exit();
    } 
    // Birden fazla dosya seçildiğinde
    else {
        // Dosya sayısı sınırı
        if (count($_POST['files']) > 50) {
            $_SESSION['errors'] = ['En fazla 50 dosya seçebilirsiniz.'];
            header('Location: index.php');
            exit();
        }
        
        $files = array_map('sanitizeFilename', $_POST['files']);
        $currentPath = isset($_POST['path']) ? sanitizePath($_POST['path']) : '/';
        $currentPath = rtrim($currentPath, '/') . '/';
        
        logActivity('DOWNLOAD_BULK', 'Toplu indirme: ' . count($files) . ' dosya');
        
        // İndirme bağlantılarını sakla
        $downloadUrls = [];
        
        foreach ($files as $fileName) {
            if (empty($fileName)) continue;
            
            $filePath = $currentPath . $fileName;
            $downloadUrls[] = [
                'url' => 'download.php?file=' . urlencode($filePath),
                'name' => $fileName
            ];
        }
        
        // İndirme listesi boşsa ana sayfaya dön
        if (empty($downloadUrls)) {
            $_SESSION['errors'] = ['Geçerli dosya seçilmedi.'];
            header('Location: index.php');
            exit();
        }
        
        // İndirme listesini session'a sakla
        $_SESSION['download_urls'] = $downloadUrls;
        $_SESSION['mods_folder'] = true; // mods klasörü bilgisini tut
        
        // İndirme sayfasına yönlendir
        header('Location: download_list.php');
        exit();
    }
}

// FTP bağlantısını kapat
closeFtpConnection($ftpConnection);

// Hiçbir şey indirilmediyse ana sayfaya yönlendir
header('Location: index.php');
exit();