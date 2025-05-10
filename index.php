<?php
require_once 'config.php';
require_once 'functions.php';

// Oturum başlat
session_start();
session_regenerate_id(true); // Oturum sabitleme koruması

// CSRF token oluştur
$csrfToken = generateCSRFToken();

// Klasör dizinini al (varsayılan: FTP_DEFAULT_PATH)
$currentPath = isset($_GET['path']) ? $_GET['path'] : FTP_DEFAULT_PATH;

// Path traversal koruması
$currentPath = sanitizePath($currentPath);

// FTP bağlantısını kur
$ftpConnection = connectToFtp();

// Dosya listesini al
$fileList = listFiles($ftpConnection, $currentPath);

// Sıralama seçenekleri
$sortBy = isset($_GET['sort']) ? sanitizeInput($_GET['sort']) : 'date';
$sortOrder = isset($_GET['order']) ? sanitizeInput($_GET['order']) : 'desc';

// XSS koruması - sort parametrelerini doğrula
if (!in_array($sortBy, ['name', 'size', 'date'])) {
    $sortBy = 'date';
}
if (!in_array($sortOrder, ['asc', 'desc'])) {
    $sortOrder = 'desc';
}

// Arama filtresi
$searchTerm = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

// Dosya istatistikleri hesapla
$totalFiles = 0;
$totalFolders = 0;
$totalSize = 0;

foreach ($fileList as $file) {
    if ($file['is_dir']) {
        $totalFolders++;
    } else {
        $totalFiles++;
        $totalSize += $file['size'];
    }
}

// Filtre uygula
if (!empty($searchTerm)) {
    $fileList = array_filter($fileList, function($file) use ($searchTerm) {
        return stripos($file['name'], $searchTerm) !== false;
    });
}

// Dosyaları sırala
usort($fileList, function($a, $b) use ($sortBy, $sortOrder) {
    if ($sortOrder == 'asc') {
        return $a[$sortBy] <=> $b[$sortBy];
    } else {
        return $b[$sortBy] <=> $a[$sortBy];
    }
});

// Dosyaları tarihe göre gruplandır
$groupedFiles = [];
foreach ($fileList as $file) {
    // Sadece dosyalar için tarihe göre gruplandır, klasörler için ayrı bir kategori
    if ($file['is_dir']) {
        $key = 'Klasörler';
    } else {
        $key = date('Y-m-d', $file['date']);
    }
    
    if (!isset($groupedFiles[$key])) {
        $groupedFiles[$key] = [];
    }
    
    $groupedFiles[$key][] = $file;
}

// Klasörler her zaman en üstte gösterilsin
if (isset($groupedFiles['Klasörler'])) {
    $folders = $groupedFiles['Klasörler'];
    unset($groupedFiles['Klasörler']);
    // Tarihler zaten sıralanmış olacak (en yeni en üstte)
    $groupedFiles = ['Klasörler' => $folders] + $groupedFiles;
}

// Hata ve başarı mesajları
$errors = isset($_SESSION['errors']) ? $_SESSION['errors'] : [];
$success = isset($_SESSION['success']) ? $_SESSION['success'] : [];

// Mesajları temizle
unset($_SESSION['errors']);
unset($_SESSION['success']);

// FTP bağlantısını kapat
closeFtpConnection($ftpConnection);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Farming Simulator 2025 Mod Yönetim Paneli - Sunucuya giriş için gerekli modları indirin">
    <meta name="robots" content="noindex, nofollow">
    <meta name="author" content="Dostlar Konağı">
    <title>Farming Simulator 2025 Mod Yönetim Paneli</title>
    
    <!-- Favicon -->
    <link rel="icon" href="assets/images/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="assets/images/favicon.ico" type="image/x-icon">
    <link rel="apple-touch-icon" href="assets/images/apple-touch-icon.png">
    
    <!-- CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="assets/popup.css">
</head>
<body class="bg-white min-h-screen">
    <!-- Popup Penceresi -->
    <div class="popup-overlay" id="welcomePopup">
        <div class="popup-content">
            <div class="popup-header bg-gray-100 border-b border-gray-200">
                <h3 class="text-base font-medium text-gray-800">
                    <i class="fas fa-tractor mr-2 text-gray-500"></i>Farming Simulator 2025 Mod Yönetim Paneli
                </h3>
                <span class="popup-close" id="closePopup">&times;</span>
            </div>
            <div class="popup-body">
                <div class="mb-3 text-sm text-gray-700 font-medium">
                    Dostlar Konağı Sunucusuna Hoş Geldiniz!
                </div>
                <p class="text-sm text-gray-600 mb-4">
                    Bu panel, Farming Simulator 2025 sunucumuza giriş yapabilmeniz için gerekli olan tüm modları indirmenizi sağlar.
                </p>
                
                <div class="text-xs text-gray-600 space-y-3 mb-4">
                    <div class="feature-item">
                        <i class="fas fa-check-circle feature-icon text-gray-500"></i>
                        <div>Sunucuya giriş sağlayabilmek için bu paneldeki modları indirip kurmanız gerekmektedir.</div>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-check-circle feature-icon text-gray-500"></i>
                        <div>İndirilen tüm modları oyunun "mods" klasörüne kaydetmeyi unutmayınız.</div>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-exclamation-triangle feature-icon text-gray-500"></i>
                        <div>Boyutu büyük olan dosyaları tek tek indirmeye özen gösteriniz. Böylece indirme işlemi daha sağlıklı gerçekleşecektir.</div>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-info-circle feature-icon text-gray-500"></i>
                        <div>Sorun yaşamanız durumunda sağ üst köşedeki e-posta adresi üzerinden destek alabilirsiniz.</div>
                    </div>
                    <div class="feature-item">
                        <i class="fab fa-discord feature-icon text-gray-500"></i>
                        <div>Discord sunucumuza katılarak diğer çiftçilerle sohbet edebilir ve yardım alabilirsiniz: <a href="https://discord.gg/QKN5Ycp68N" target="_blank" class="text-gray-700 hover:underline">discord.gg/QKN5Ycp68N</a></div>
                    </div>
                </div>
                
                <div class="text-sm text-gray-700 font-medium">
                    İyi oyunlar dileriz!
                </div>
            </div>
            <div class="popup-footer bg-gray-100 border-t border-gray-200">
                <button id="closePopupBtn" class="px-4 py-1 bg-gray-200 text-gray-700 text-xs rounded hover:bg-gray-300 transition">
                    Anladım, Devam Et
                </button>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-2 py-4 max-w-5xl">
        <header class="border-b pb-4 mb-4">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <h1 class="text-lg font-medium text-gray-800">Mod Dosya Yönetimi</h1>
                    <p class="text-xs text-gray-500 mt-1 break-all">
                        <?php echo htmlspecialchars($currentPath); ?>
                    </p>
                </div>
                <div class="text-xs text-gray-500 flex flex-col items-end">
                    <a href="mailto:tkmnumut@gmail.com" class="hover:text-gray-700 flex items-center mb-1">
                        <i class="fas fa-envelope mr-1"></i> Destek: tkmnumut@gmail.com
                    </a>
                    <a href="https://discord.gg/QKN5Ycp68N" target="_blank" class="hover:text-gray-700 flex items-center">
                        <i class="fab fa-discord mr-1"></i> Discord Sunucumuza Katılın
                    </a>
                </div>
            </div>
            
            <!-- Arama Formu -->
            <form action="" method="GET" class="mt-2">
                <input type="hidden" name="path" value="<?php echo htmlspecialchars($currentPath); ?>">
                <div class="relative">
                    <input type="text" name="search" placeholder="Dosya ara..." value="<?php echo htmlspecialchars($searchTerm); ?>" class="w-full p-2 pl-8 border text-xs rounded focus:outline-none focus:ring-1 focus:ring-gray-400">
                    <i class="fas fa-search absolute left-3 top-2 text-gray-400 text-xs"></i>
                </div>
            </form>
        </header>

        <!-- Dosya İstatistikleri -->
        <div class="flex justify-between items-center mb-4">
            <div class="flex flex-wrap gap-2 text-xs">
                <div class="bg-gray-100 rounded py-1 px-3 flex items-center">
                    <i class="fas fa-file text-gray-400 mr-1"></i>
                    <span class="text-gray-700">Dosya: <b><?php echo $totalFiles; ?></b></span>
                </div>
                
                <div class="bg-gray-100 rounded py-1 px-3 flex items-center">
                    <i class="fas fa-folder text-gray-400 mr-1"></i>
                    <span class="text-gray-700">Klasör: <b><?php echo $totalFolders; ?></b></span>
                </div>
                
                <div class="bg-gray-100 rounded py-1 px-3 flex items-center">
                    <i class="fas fa-database text-gray-400 mr-1"></i>
                    <span class="text-gray-700">Toplam Boyut: <b><?php echo formatFileSize($totalSize); ?></b></span>
                </div>
                
                <div class="bg-gray-100 rounded py-1 px-3 flex items-center">
                    <i class="fas fa-calendar-alt text-gray-400 mr-1"></i>
                    <span class="text-gray-700">Tarih Grupları: <b><?php echo count($groupedFiles) - (isset($groupedFiles['Klasörler']) ? 1 : 0); ?></b></span>
                </div>
            </div>
            
            <!-- Toplu İndirme Butonu -->
            <div class="bg-gray-100 rounded py-1 px-3">
                <button type="submit" form="fileListForm" id="downloadSelected" class="text-gray-700 text-xs hover:text-gray-900 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center">
                    <i class="fas fa-download mr-1"></i>Seçilenleri İndir
                </button>
            </div>
        </div>

        <!-- Bildirimler -->
        <?php if (!empty($errors)): ?>
        <div class="bg-gray-50 border-l-2 border-red-400 text-red-600 p-2 mb-3 text-xs">
            <ul class="list-disc list-inside">
                <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
        <div class="bg-gray-50 border-l-2 border-green-400 text-green-600 p-2 mb-3 text-xs">
            <ul class="list-disc list-inside">
                <?php foreach ($success as $message): ?>
                <li><?php echo htmlspecialchars($message); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <!-- Üst dizin bağlantısı -->
        <?php if ($currentPath != '/' && $currentPath != FTP_DEFAULT_PATH): ?>
        <div class="mb-3">
            <a href="?path=<?php echo urlencode(dirname($currentPath)); ?>" class="inline-flex items-center px-2 py-1 bg-gray-100 rounded text-xs text-gray-600 hover:bg-gray-200">
                <i class="fas fa-level-up-alt mr-1"></i>Üst Dizin
            </a>
        </div>
        <?php endif; ?>

        <!-- Dosya Listesi -->
        <form id="fileListForm" action="download.php" method="POST">
            <input type="hidden" name="path" value="<?php echo htmlspecialchars($currentPath); ?>">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            
            <?php if (empty($groupedFiles)): ?>
            <div class="border border-gray-200 rounded p-4 text-center text-xs text-gray-500">
                Dosya veya klasör bulunamadı.
            </div>
            <?php else: ?>
            
            <div class="border border-gray-200 rounded overflow-hidden shadow-sm">
                <div class="p-2 bg-gray-100 flex items-center sticky top-0 z-20">
                    <input type="checkbox" id="selectAll" class="mr-2 h-3 w-3">
                    <label for="selectAll" class="text-xs text-gray-600">Tümünü Seç</label>
                </div>
                
                <!-- Tablo Başlıkları -->
                <div class="p-2 bg-gray-50 border-t border-gray-200 hidden sm:flex sticky top-8 z-20">
                    <div class="w-checkbox"></div>
                    <div class="flex-1">Dosya Adı</div>
                    <div class="w-size text-right">Boyut</div>
                    <div class="w-time text-center">Saat</div>
                    <div class="w-actions text-right">İşlem</div>
                </div>
                
                <?php foreach ($groupedFiles as $date => $files): ?>
                <div class="date-group p-2 text-xs font-medium text-gray-600 border-t border-gray-200 flex items-center justify-between">
                    <div class="flex items-center">
                        <i class="fas fa-calendar-day mr-1 text-gray-400"></i>
                        <?php echo $date === 'Klasörler' ? 'Klasörler' : date('d F Y', strtotime($date)); ?>
                        <span class="ml-1 text-xs bg-gray-200 text-gray-600 px-1 rounded"><?php echo count($files); ?></span>
                    </div>
                    
                    <?php if ($date !== 'Klasörler'): ?>
                    <div class="text-xs text-gray-500">
                        <?php
                            $dateFilesSize = 0;
                            foreach ($files as $file) {
                                if (!$file['is_dir']) {
                                    $dateFilesSize += $file['size'];
                                }
                            }
                            echo formatFileSize($dateFilesSize);
                        ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-xs table-fixed">
                        <tbody>
                            <?php foreach ($files as $file): ?>
                            <tr class="border-t border-gray-200 hover:bg-gray-50 transition-colors">
                                <td class="p-2 w-checkbox align-top">
                                    <?php if (!$file['is_dir']): ?>
                                    <input type="checkbox" name="files[]" value="<?php echo htmlspecialchars($file['name']); ?>" class="fileCheckbox h-3 w-3">
                                    <?php endif; ?>
                                </td>
                                
                                <!-- Mobil için tek sütunda gösterim -->
                                <td class="p-2 sm:hidden mobile-row">
                                    <?php if ($file['is_dir']): ?>
                                    <a href="?path=<?php echo urlencode($currentPath . '/' . $file['name']); ?>" class="flex items-center text-gray-700 hover:text-gray-900">
                                        <i class="fas fa-folder text-gray-400 mr-2 text-xs"></i>
                                        <span class="truncate"><?php echo htmlspecialchars($file['name']); ?></span>
                                    </a>
                                    <?php else: ?>
                                    <div class="flex items-center">
                                        <i class="fas fa-file text-gray-400 mr-2 text-xs"></i>
                                        <span class="truncate"><?php echo htmlspecialchars($file['name']); ?></span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="mobile-info">
                                        <span>
                                            <?php if (!$file['is_dir']): ?>
                                            <?php echo formatFileSize($file['size']); ?>
                                            <?php else: ?>
                                            Klasör
                                            <?php endif; ?>
                                        </span>
                                        <span><?php echo date('H:i', $file['date']); ?></span>
                                        <span>
                                            <?php if (!$file['is_dir']): ?>
                                            <a href="download.php?file=<?php echo urlencode($currentPath . '/' . $file['name']); ?>" class="text-gray-500 hover:text-gray-700 mr-1">
                                                <i class="fas fa-download"></i>
                                            </a>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                </td>
                                
                                <!-- Masaüstü için standart görünüm -->
                                <td class="p-2 hidden sm:table-cell">
                                    <?php if ($file['is_dir']): ?>
                                    <a href="?path=<?php echo urlencode($currentPath . '/' . $file['name']); ?>" class="flex items-center text-gray-700 hover:text-gray-900">
                                        <i class="fas fa-folder text-gray-400 mr-2 text-xs"></i>
                                        <span class="truncate"><?php echo htmlspecialchars($file['name']); ?></span>
                                    </a>
                                    <?php else: ?>
                                    <div class="flex items-center">
                                        <i class="fas fa-file text-gray-400 mr-2 text-xs"></i>
                                        <span class="truncate"><?php echo htmlspecialchars($file['name']); ?></span>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td class="p-2 w-size text-right hidden sm:table-cell">
                                    <?php if (!$file['is_dir']): ?>
                                    <?php echo formatFileSize($file['size']); ?>
                                    <?php else: ?>
                                    -
                                    <?php endif; ?>
                                </td>
                                <td class="p-2 w-time text-center hidden sm:table-cell">
                                    <?php echo date('H:i', $file['date']); ?>
                                </td>
                                <td class="p-2 w-actions text-right hidden sm:table-cell">
                                    <?php if (!$file['is_dir']): ?>
                                    <a href="download.php?file=<?php echo urlencode($currentPath . '/' . $file['name']); ?>" class="text-gray-500 hover:text-gray-700">
                                        <i class="fas fa-download"></i>
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </form>
    </div>

    <!-- Footer -->
    <footer class="mb-4 text-center text-xs text-gray-500">
        <p>&copy; <?php echo date('Y'); ?> Mod Dosya Yönetim Paneli</p>
    </footer>

    <script>
        // Dosya seçme işlemleri
        document.addEventListener('DOMContentLoaded', function() {
            const selectAll = document.getElementById('selectAll');
            const fileCheckboxes = document.querySelectorAll('.fileCheckbox');
            const downloadButton = document.getElementById('downloadSelected');
            
            if (selectAll && fileCheckboxes.length > 0) {
                // Tümünü seç/kaldır fonksiyonu
                selectAll.addEventListener('change', function() {
                    const isChecked = this.checked;
                    
                    fileCheckboxes.forEach(function(checkbox) {
                        checkbox.checked = isChecked;
                    });
                    
                    // İndirme butonu durumunu güncelle
                    updateDownloadButtonState();
                });
                
                // Her dosya seçildiğinde indirme butonunu güncelle
                fileCheckboxes.forEach(function(checkbox) {
                    checkbox.addEventListener('change', function() {
                        updateDownloadButtonState();
                        
                        // Tümünü seç durumunu güncelle
                        const allChecked = Array.from(fileCheckboxes).every(function(cb) {
                            return cb.checked;
                        });
                        
                        const someChecked = Array.from(fileCheckboxes).some(function(cb) {
                            return cb.checked;
                        });
                        
                        selectAll.checked = allChecked;
                        selectAll.indeterminate = !allChecked && someChecked;
                    });
                });
                
                // İndirme butonu durumunu güncelle
                function updateDownloadButtonState() {
                    const hasChecked = Array.from(fileCheckboxes).some(function(checkbox) {
                        return checkbox.checked;
                    });
                    
                    downloadButton.disabled = !hasChecked;
                    
                    if (hasChecked) {
                        downloadButton.classList.add('text-gray-900');
                        downloadButton.classList.remove('text-gray-400');
                    } else {
                        downloadButton.classList.remove('text-gray-900');
                        downloadButton.classList.add('text-gray-400');
                    }
                }
                
                // Sayfa yüklendiğinde buton durumunu ayarla
                updateDownloadButtonState();
            }
            
            // Popup işlemleri
            const popup = document.getElementById('welcomePopup');
            const closePopupBtn = document.getElementById('closePopupBtn');
            const closePopupX = document.getElementById('closePopup');
            
            // Popup'ı göster
            setTimeout(function() {
                popup.classList.add('active');
            }, 500);
            
            // Kapatma butonları için olay dinleyicileri
            if (closePopupBtn) {
                closePopupBtn.addEventListener('click', function() {
                    popup.classList.remove('active');
                });
            }
            
            if (closePopupX) {
                closePopupX.addEventListener('click', function() {
                    popup.classList.remove('active');
                });
            }
            
            // Popup dışına tıklama ile kapatma
            popup.addEventListener('click', function(e) {
                if (e.target === popup) {
                    popup.classList.remove('active');
                }
            });
            
            // Popupı yerel depolamada saklama (24 saat boyunca tekrar gösterme)
            const hasSeenPopup = localStorage.getItem('hasSeenPopup');
            const currentTime = new Date().getTime();
            
            if (hasSeenPopup) {
                const lastPopupTime = parseInt(hasSeenPopup);
                const hoursPassed = (currentTime - lastPopupTime) / (1000 * 60 * 60);
                
                if (hoursPassed < 24) {
                    popup.classList.remove('active');
                } else {
                    localStorage.setItem('hasSeenPopup', currentTime.toString());
                }
            } else {
                localStorage.setItem('hasSeenPopup', currentTime.toString());
            }
        });
    </script>
</body>
</html>