<?php
// Oturum başlat
session_start();

// İndirme listesi yoksa ana sayfaya yönlendir
if (!isset($_SESSION['download_urls']) || empty($_SESSION['download_urls'])) {
    header('Location: index.php');
    exit();
}

// İndirme listesini al
$downloadUrls = $_SESSION['download_urls'];
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Farming Simulator 2025 Mod İndirme Sayfası - Seçtiğiniz modları otomatik olarak indirin">
    <meta name="robots" content="noindex, nofollow">
    <meta name="author" content="Dostlar Konağı">
    <title>Mod İndirme - Farming Simulator 2025</title>
    
    <!-- Favicon -->
    <link rel="icon" href="assets/images/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="assets/images/favicon.ico" type="image/x-icon">
    <link rel="apple-touch-icon" href="assets/images/apple-touch-icon.png">
    
    <!-- CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-white min-h-screen">
    <div class="container mx-auto px-4 py-8 max-w-5xl">
        <header class="border-b pb-4 mb-4">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <h1 class="text-lg font-medium text-gray-800">Dosya İndirme</h1>
                    <p class="text-sm text-gray-500 mt-1">Dosyalar "mods" klasörüne indirilecek</p>
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
        </header>

        <div class="bg-white shadow-sm rounded-lg p-4 mb-4">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-sm font-medium">İndirilecek Dosyalar (<?php echo count($downloadUrls); ?>)</h2>
                <a href="index.php" class="text-xs text-gray-500 hover:text-gray-700">
                    <i class="fas fa-arrow-left mr-1"></i> Geri Dön
                </a>
            </div>
            
            <div class="progress-container mb-4">
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div id="progress-bar" class="bg-blue-500 h-2 rounded-full" style="width: 0%"></div>
                </div>
                <p id="progress-text" class="text-xs text-gray-500 mt-1">İndirme başlatılıyor...</p>
            </div>
            
            <ul id="download-list" class="space-y-2 max-h-80 overflow-y-auto">
                <?php foreach ($downloadUrls as $index => $file): ?>
                <li id="file-<?php echo $index; ?>" class="flex items-center justify-between p-2 border-b border-gray-100">
                    <span class="text-xs truncate max-w-xs"><?php echo htmlspecialchars($file['name']); ?></span>
                    <span id="status-<?php echo $index; ?>" class="text-xs text-gray-500">Bekleniyor</span>
                </li>
                <?php endforeach; ?>
            </ul>
            
            <!-- Bilgi Penceresi -->
            <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                <h3 class="text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-info-circle mr-1"></i> Bilgi
                </h3>
                <div class="text-xs text-gray-500 space-y-2">
                    <p>Bu Dostlar Konağı Farming Simulator 2025 Mod Yönetim Panelidir.</p>
                    <p>Buradan kullanıcılar sunucuya giriş sağlayabilmek adına gerekli modları elde edebilirler.</p>
                    <p>Boyutu büyük olan dosyaları tek indirmeye özen gösteriniz.</p>
                </div>
            </div>
            
            <div class="mt-4 text-xs text-gray-500">
                <p><i class="fas fa-info-circle mr-1"></i> Dosyaları "mods" klasörüne indirmek için onları indirdiğinizde, dosyayı kaydetme penceresi açıldığında "mods" klasörüne kaydedin.</p>
            </div>
        </div>
        
        <footer class="mb-4 text-center text-xs text-gray-500">
            <p>&copy; <?php echo date('Y'); ?> Mod Dosya Yönetim Paneli</p>
        </footer>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // İndirilecek dosya URL'leri
        const downloadUrls = <?php echo json_encode($downloadUrls); ?>;
        let currentIndex = 0;
        let totalFiles = downloadUrls.length;
        
        // Dosya indirme fonksiyonu
        function downloadNext() {
            if (currentIndex >= totalFiles) {
                document.getElementById('progress-text').textContent = 'Tüm dosyalar indirildi!';
                document.getElementById('progress-bar').style.width = '100%';
                return;
            }
            
            // İlerleme bilgisini güncelle
            let progress = Math.round((currentIndex / totalFiles) * 100);
            document.getElementById('progress-bar').style.width = progress + '%';
            document.getElementById('progress-text').textContent = 
                `İndiriliyor: ${currentIndex + 1}/${totalFiles} (${progress}%)`;
            
            // Mevcut dosya durumunu güncelle
            document.getElementById(`status-${currentIndex}`).textContent = 'İndiriliyor...';
            document.getElementById(`status-${currentIndex}`).className = 'text-xs text-blue-500';
            
            // Dosya indirme linki oluştur
            const fileUrl = downloadUrls[currentIndex].url;
            const fileName = downloadUrls[currentIndex].name;
            const link = document.createElement('a');
            link.href = fileUrl;
            link.download = fileName;
            
            // İndirmeyi başlat
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            // Durum güncelle
            document.getElementById(`status-${currentIndex}`).textContent = 'İndirildi';
            document.getElementById(`status-${currentIndex}`).className = 'text-xs text-green-500';
            document.getElementById(`file-${currentIndex}`).classList.add('bg-gray-50');
            
            // Bir sonraki dosya için zaman tanı
            currentIndex++;
            setTimeout(downloadNext, 1500);
        }
        
        // İndirmeyi başlat
        setTimeout(downloadNext, 1000);
    });
    </script>
</body>
</html> 