# FTP Dosya Yönetim Paneli

Bu proje, PHP ve Tailwind CSS kullanarak geliştirilmiş kullanıcı dostu bir FTP dosya yönetim panelidir. FTP sunucunuzdaki dosyaları web arayüzü üzerinden güvenli bir şekilde yönetmenizi sağlar. Özellikle Farming Simulator 2025 mod yönetimi için özelleştirilmiştir.

## Özellikler

- Dosya ve klasörleri listeleme
- FTP sunucusuna dosya yükleme
- Dosya indirme (tekli veya çoklu)
- "mods" klasörü için optimize edilmiş indirme sistemi
- Dosya/klasör silme (CSRF korumalı)
- Arama ve filtreleme
- Tarih, boyut ve isim bazlı sıralama
- Duyarlı (responsive) tasarım
- Discord entegrasyonu
- Otomatik hata günlüğü (logging)
- Gelişmiş güvenlik önlemleri

## Gereksinimler

- PHP 7.4 veya üzeri
- PHP FTP Uzantısı
- PHP FileInfo Uzantısı
- Web sunucusu (Apache, Nginx vb.)
- FTP sunucusu

## Kurulum

1. Dosyaları web sunucunuza yükleyin
2. `.env.php.example` dosyasını `.env.php` olarak kopyalayın ve FTP bağlantı bilgilerinizi güncelleyin:

```php
define('FTP_HOST', 'your-ftp-host');
define('FTP_USER', 'your-username');
define('FTP_PASS', 'your-password');
define('FTP_DEFAULT_PATH', '/your/default/path');
```

3. `temp` ve `logs` klasörlerine yazma izni verin:

```bash
chmod 755 temp
chmod 755 logs
```

4. Web tarayıcınızdan projeyi açın

## Güvenlik Özellikleri

- CSRF (Cross-Site Request Forgery) koruması
- XSS (Cross-Site Scripting) koruması
- Dosya yolu doğrulama (Path Traversal koruması)
- Güvenli dosya adı doğrulama
- Güvenli oturum yönetimi (Cookie koruma)
- Dosya boyutu ve türü kontrolü
- Hata günlüğü (activity log) sistemi

## .env.php Kullanımı

Hassas FTP bilgilerinizi korumak için `.env.php` dosyası kullanmanız önerilir:

1. `.env.php.example` dosyasını `.env.php` olarak kopyalayın
2. FTP sunucunuzun bilgilerini güncelleyin
3. `.env.php` dosyasını güvenli bir şekilde saklayın ve versiyonlama sistemlerine (GitHub vb.) asla yüklemeyin

## Dosya Yapısı

```
/
├── index.php        # Ana sayfa ve dosya listesi
├── download.php     # Dosya indirme işlemleri
├── download_list.php # Çoklu dosya indirme arayüzü
├── delete.php       # Dosya silme işlemleri
├── functions.php    # Yardımcı fonksiyonlar ve güvenlik
├── config.php       # Yapılandırma ayarları
├── .env.php         # FTP bağlantı bilgileri (özel)
├── temp/            # Geçici dosyalar için klasör
└── logs/            # Log dosyaları
```

## Yaygın Sorunlar ve Çözümleri

### FTP Bağlantı Hatası
- FTP sunucu adresini, kullanıcı adını ve şifreyi kontrol edin
- FTP sunucunuzun uzaktan erişime izin verdiğinden emin olun
- Firewall veya ağ kısıtlamalarını kontrol edin

### Dosya İndirme Sorunları
- `temp` klasörünün yazılabilir olduğundan emin olun
- PHP'nin file_get_contents ve readfile fonksiyonlarının etkin olduğunu kontrol edin
- Büyük dosyalar için PHP zaman aşımı limitini artırın

### Güvenlik Uyarıları
- Her zaman güncel bir PHP sürümü kullanın
- FTP şifrelerinizi günlü tutun ve düzenli olarak değiştirin
- .env.php dosyasını public erişimden koruyun

## İletişim ve Destek

Sorun yaşarsanız veya öneriniz varsa:

- Discord: [Dostlar Konağı](https://discord.gg/QKN5Ycp68N)
- E-posta: tkmnumut@gmail.com

## Lisans

Bu proje özel kullanım içindir ve Farming Simulator 2025 Mod Yönetim Paneli olarak kullanılmak üzere geliştirilmiştir.
