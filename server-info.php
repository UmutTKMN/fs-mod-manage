<?php
// Oturum başlat
session_start();
session_regenerate_id(true); // Oturum sabitleme koruması

// Yönetici değilse erişimi engelle
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: index.php');
    exit();
}

// Sunucu bilgileri
$serverInfo = [
    'PHP Versiyonu' => phpversion(),
    'İşletim Sistemi' => PHP_OS,
    'Sunucu Yazılımı' => $_SERVER['SERVER_SOFTWARE'] ?? 'Bilinmiyor',
    'Sunucu Adresi' => $_SERVER['SERVER_ADDR'] ?? 'Bilinmiyor',
    'Sunucu Adı' => $_SERVER['SERVER_NAME'] ?? 'Bilinmiyor',
    'Protokol' => $_SERVER['SERVER_PROTOCOL'] ?? 'Bilinmiyor',
    'Doküman Kök Dizini' => $_SERVER['DOCUMENT_ROOT'] ?? 'Bilinmiyor',
    'Güncel Zaman' => date('Y-m-d H:i:s'),
];

// PHP ayarları
$phpSettings = [
    'memory_limit' => ini_get('memory_limit'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'max_execution_time' => ini_get('max_execution_time'),
    'max_input_time' => ini_get('max_input_time'),
    'display_errors' => ini_get('display_errors'),
    'error_reporting' => ini_get('error_reporting'),
];

// Yüklü PHP eklentileri
$phpExtensions = get_loaded_extensions();
sort($phpExtensions);

// Disk kullanımı
$diskTotal = disk_total_space('/');
$diskFree = disk_free_space('/');
$diskUsed = $diskTotal - $diskFree;
$diskPercentUsed = round(($diskUsed / $diskTotal) * 100, 2);

// Log dosyası bilgileri
$logDir = __DIR__ . '/logs';
$logFiles = [];

if (is_dir($logDir)) {
    if ($handle = opendir($logDir)) {
        while (false !== ($file = readdir($handle))) {
            if ($file != "." && $file != "..") {
                $logFile = $logDir . '/' . $file;
                $logFiles[$file] = [
                    'size' => filesize($logFile),
                    'modified' => date('Y-m-d H:i:s', filemtime($logFile)),
                ];
            }
        }
        closedir($handle);
    }
}

// Dosya boyutunu formatla
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, $precision) . ' ' . $units[$pow];
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Sunucu Bilgileri - Farming Simulator 2025</title>
    
    <!-- Favicon -->
    <link rel="icon" href="assets/images/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="assets/images/favicon.ico" type="image/x-icon">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-6 max-w-6xl">
        <header class="bg-white p-4 rounded-lg shadow-sm mb-6">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-medium text-gray-800">Sunucu Bilgileri</h1>
                <div>
                    <a href="index.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded text-sm hover:bg-gray-300 transition flex items-center">
                        <i class="fas fa-arrow-left mr-2"></i> Geri Dön
                    </a>
                </div>
            </div>
        </header>
        
        <!-- Disk Kullanımı -->
        <div class="bg-white p-4 rounded-lg shadow-sm mb-6">
            <h2 class="text-lg font-medium text-gray-800 mb-3">Disk Kullanımı</h2>
            <div class="mb-2 flex justify-between text-sm">
                <span>Kullanılan: <?php echo formatBytes($diskUsed); ?> (<?php echo $diskPercentUsed; ?>%)</span>
                <span>Boş: <?php echo formatBytes($diskFree); ?></span>
                <span>Toplam: <?php echo formatBytes($diskTotal); ?></span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2.5">
                <div class="bg-blue-500 h-2.5 rounded-full" style="width: <?php echo $diskPercentUsed; ?>%"></div>
            </div>
        </div>
        
        <!-- Ana Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Sunucu Bilgileri -->
            <div class="bg-white p-4 rounded-lg shadow-sm">
                <h2 class="text-lg font-medium text-gray-800 mb-3">Sunucu Bilgileri</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <tbody>
                            <?php foreach ($serverInfo as $key => $value): ?>
                            <tr class="border-b border-gray-100">
                                <td class="py-2 font-medium text-gray-700"><?php echo htmlspecialchars($key); ?></td>
                                <td class="py-2 text-gray-600"><?php echo htmlspecialchars($value); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- PHP Ayarları -->
            <div class="bg-white p-4 rounded-lg shadow-sm">
                <h2 class="text-lg font-medium text-gray-800 mb-3">PHP Ayarları</h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <tbody>
                            <?php foreach ($phpSettings as $key => $value): ?>
                            <tr class="border-b border-gray-100">
                                <td class="py-2 font-medium text-gray-700"><?php echo htmlspecialchars($key); ?></td>
                                <td class="py-2 text-gray-600"><?php echo htmlspecialchars($value); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Log Dosyaları -->
            <div class="bg-white p-4 rounded-lg shadow-sm">
                <h2 class="text-lg font-medium text-gray-800 mb-3">Log Dosyaları</h2>
                <?php if (empty($logFiles)): ?>
                <div class="text-sm text-gray-500">Hiç log dosyası bulunamadı.</div>
                <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 text-left">
                                <th class="py-2 font-medium text-gray-700">Dosya Adı</th>
                                <th class="py-2 font-medium text-gray-700">Boyut</th>
                                <th class="py-2 font-medium text-gray-700">Son Değişiklik</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logFiles as $file => $info): ?>
                            <tr class="border-b border-gray-100">
                                <td class="py-2 text-gray-700"><?php echo htmlspecialchars($file); ?></td>
                                <td class="py-2 text-gray-600"><?php echo formatBytes($info['size']); ?></td>
                                <td class="py-2 text-gray-600"><?php echo htmlspecialchars($info['modified']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- PHP Eklentileri -->
            <div class="bg-white p-4 rounded-lg shadow-sm">
                <h2 class="text-lg font-medium text-gray-800 mb-3">PHP Eklentileri</h2>
                <div class="overflow-x-auto max-h-60">
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-2 text-sm">
                        <?php foreach ($phpExtensions as $extension): ?>
                        <div class="py-1 px-2 bg-gray-50 rounded text-gray-700"><?php echo htmlspecialchars($extension); ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <footer class="mt-6 text-center text-xs text-gray-500">
            <p>&copy; <?php echo date('Y'); ?> Farming Simulator 2025 Mod Yönetim Paneli</p>
        </footer>
    </div>
</body>
</html>