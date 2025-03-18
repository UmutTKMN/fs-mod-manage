<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bakımda - Farming Simulator 2025 Mod Yönetim Paneli</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f9fafb;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .maintenance-container {
            max-width: 500px;
            padding: 2rem;
            text-align: center;
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        .icon {
            font-size: 4rem;
            color: #6B7280;
            margin-bottom: 1rem;
        }
        h1 {
            color: #4B5563;
            margin-bottom: 1rem;
        }
        p {
            color: #6B7280;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="maintenance-container">
        <div class="icon">
            <i class="fas fa-tools"></i>
        </div>
        <h1 class="text-2xl font-bold text-gray-800">Sunucu Bakımda</h1>
        <p class="text-gray-600 mb-4">
            Farming Simulator 2025 Mod Yönetim Paneli şu anda bakım çalışmaları nedeniyle geçici olarak kullanılamıyor. 
            Kısa süre içinde tekrar hizmetinizde olacağız. 
        </p>
        <p class="text-gray-600 mb-4">
            Beklediğiniz için teşekkür ederiz.
        </p>
        <div class="mt-6">
            <p class="text-sm text-gray-500">
                <i class="fas fa-clock mr-1"></i> Tahmini hizmet dönüş süresi: <span id="countdown">30:00</span>
            </p>
        </div>
        <div class="mt-8">
            <a href="https://discord.gg/QKN5Ycp68N" target="_blank" class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 rounded-md text-sm">
                <i class="fab fa-discord mr-2"></i> Discord sunucumuza katılın
            </a>
        </div>
    </div>

    <script>
        // Geri sayım fonksiyonu
        function startCountdown(minutes) {
            let totalSeconds = minutes * 60;
            const countdownElement = document.getElementById('countdown');
            
            const timer = setInterval(function() {
                if (totalSeconds <= 0) {
                    clearInterval(timer);
                    countdownElement.textContent = "Bakım tamamlandı, sayfayı yenileyin!";
                    return;
                }
                
                const minutes = Math.floor(totalSeconds / 60);
                const seconds = totalSeconds % 60;
                
                countdownElement.textContent = `${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;
                totalSeconds--;
            }, 1000);
        }
        
        // 30 dakikalık geri sayım başlat
        startCountdown(30);
    </script>
</body>
</html> 