<?php
require_once 'config.php';
require_once 'functions.php';

session_start();
session_regenerate_id(true);

createTempDirectory();

sleep(1);

$ftpConnection = connectToFtp();
if (!$ftpConnection || !($ftpConnection instanceof \FTP\Connection)) {
    $_SESSION['errors'] = ['FTP bağlantısı kurulamadı. Lütfen daha sonra tekrar deneyin.'];
    header('Location: index.php');
    exit();
}

if (isset($_GET['file']) && !empty($_GET['file'])) {
    $filePath = $_GET['file'];
    
    $filePath = sanitizePath($filePath);
    
    $fileName = sanitizeFilename(basename($filePath));
    
    if (empty($fileName)) {
        $_SESSION['errors'] = ['Geçersiz dosya adı.'];
        header('Location: index.php');
        exit();
    }
    
    logActivity('DOWNLOAD', 'Dosya indirme: ' . $filePath);
    
    $tempFilePath = TEMP_UPLOAD_DIR . '/' . $fileName;
    
    if (@ftp_get($ftpConnection, $tempFilePath, $filePath, FTP_BINARY)) {
        $fileSize = filesize($tempFilePath);
        
        if ($fileSize <= 0) {
            unlink($tempFilePath);
            $_SESSION['errors'] = ['Dosya boş veya indirme sırasında bir hata oluştu: ' . $fileName];
            header('Location: index.php');
            exit();
        }
        
        $fileType = mime_content_type($tempFilePath) ?: 'application/octet-stream';
        
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
        
        if (!in_array($fileType, $allowedMimeTypes)) {
            unlink($tempFilePath);
            logActivity('SECURITY', 'İzin verilmeyen dosya türü: ' . $fileType);
            $_SESSION['errors'] = ['Bu dosya türü indirmeye izin verilmiyor.'];
            header('Location: index.php');
            exit();
        }
        
        header('Content-Description: File Transfer');
        header('Content-Type: ' . $fileType);
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Content-Length: ' . $fileSize);
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Expires: 0');
        
        if (ob_get_level()) {
            ob_end_clean();
        }
        readfile($tempFilePath);
        
        unlink($tempFilePath);
        
        closeFtpConnection($ftpConnection);
        exit();
    } else {
        logActivity('ERROR', 'Dosya indirilemedi: ' . $filePath);
        $_SESSION['errors'] = ['Dosya indirilirken bir hata oluştu: ' . $fileName];
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['files']) && is_array($_POST['files']) && !empty($_POST['files'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        logActivity('SECURITY', 'CSRF saldırısı girişimi yapıldı');
        $_SESSION['errors'] = ['Güvenlik doğrulaması başarısız oldu. Lütfen tekrar deneyin.'];
        header('Location: index.php');
        exit();
    }
    
    if (count($_POST['files']) == 1) {
        $fileName = sanitizeFilename($_POST['files'][0]);
        $currentPath = isset($_POST['path']) ? sanitizePath($_POST['path']) : '/';
        $currentPath = rtrim($currentPath, '/') . '/';
        $filePath = $currentPath . $fileName;
        
        header('Location: download.php?file=' . urlencode($filePath));
        exit();
    } else {
        if (count($_POST['files']) > 50) {
            $_SESSION['errors'] = ['En fazla 50 dosya seçebilirsiniz.'];
            header('Location: index.php');
            exit();
        }
        
        $files = array_map('sanitizeFilename', $_POST['files']);
        $currentPath = isset($_POST['path']) ? sanitizePath($_POST['path']) : '/';
        $currentPath = rtrim($currentPath, '/') . '/';
        
        logActivity('DOWNLOAD_BULK', 'Toplu indirme: ' . count($files) . ' dosya');
        
        $downloadUrls = [];
        
        foreach ($files as $fileName) {
            if (empty($fileName)) continue;
            
            $filePath = $currentPath . $fileName;
            $downloadUrls[] = [
                'url' => 'download.php?file=' . urlencode($filePath),
                'name' => $fileName
            ];
        }
        
        if (empty($downloadUrls)) {
            $_SESSION['errors'] = ['Geçerli dosya seçilmedi.'];
            header('Location: index.php');
            exit();
        }
        
        $_SESSION['download_urls'] = $downloadUrls;
        $_SESSION['mods_folder'] = true;
        
        header('Location: download_list.php');
        exit();
    }
}

closeFtpConnection($ftpConnection);

header('Location: index.php');
exit();