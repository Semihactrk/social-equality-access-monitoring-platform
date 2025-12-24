-- =====================================================
-- SÖZLEŞMELİ ÖZÜR AYDIN - Şehrin Nabzı Database
-- Social Equality Access Monitoring Platform
-- =====================================================

-- Veritabanını oluşturma (eğer yoksa)
CREATE DATABASE IF NOT EXISTS sehrin_nabzi_db;
USE sehrin_nabzi_db;

-- =====================================================
-- TABLE: Kullanıcılar (Users)
-- =====================================================
CREATE TABLE kullanicilar (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kullanici_adi VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    sifre VARCHAR(255) NOT NULL,
    rol ENUM('vatandas', 'yetkili', 'admin') DEFAULT 'vatandas',
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: Raporlar (Reports)
-- =====================================================
CREATE TABLE raporlar (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kullanici_id INT NOT NULL,
    baslik VARCHAR(255) NOT NULL,
    aciklama TEXT NOT NULL,
    enlem DOUBLE NOT NULL,
    boylam DOUBLE NOT NULL,
    durum ENUM('beklemede', 'islemde', 'cozuldu') DEFAULT 'beklemede',
    ustlenen_yetkili_id INT,
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    guncellenme_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (kullanici_id) REFERENCES kullanicilar(id) ON DELETE CASCADE,
    FOREIGN KEY (ustlenen_yetkili_id) REFERENCES kullanicilar(id) ON DELETE SET NULL,
    INDEX idx_durum (durum),
    INDEX idx_olusturma_tarihi (olusturma_tarihi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: Forum Konuları (Forum Topics)
-- =====================================================
CREATE TABLE forum_konulari (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kullanici_id INT NOT NULL,
    baslik VARCHAR(255) NOT NULL,
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    guncellenme_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (kullanici_id) REFERENCES kullanicilar(id) ON DELETE CASCADE,
    INDEX idx_olusturma_tarihi (olusturma_tarihi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: Yorumlar (Comments)
-- =====================================================
CREATE TABLE yorumlar (
    id INT PRIMARY KEY AUTO_INCREMENT,
    konu_id INT NOT NULL,
    kullanici_id INT NOT NULL,
    yorum_metni TEXT NOT NULL,
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    guncellenme_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (konu_id) REFERENCES forum_konulari(id) ON DELETE CASCADE,
    FOREIGN KEY (kullanici_id) REFERENCES kullanicilar(id) ON DELETE CASCADE,
    INDEX idx_konu_id (konu_id),
    INDEX idx_olusturma_tarihi (olusturma_tarihi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SAMPLE DATA - Password hash for all users: "123456"
-- Hash generated with: password_hash("123456", PASSWORD_DEFAULT)
-- =====================================================

INSERT INTO kullanicilar (kullanici_adi, email, sifre, rol) VALUES
('admin123', 'admin@sehrinNabzi.com', '$2y$10$u1cC.vqHpF0uS7HI7J5s/uW8.5e0H6vK.yZ8W1D4e5K3c2D1F0H9E', 'admin'),
('yetkili_user', 'yetkili@sehrinNabzi.com', '$2y$10$u1cC.vqHpF0uS7HI7J5s/uW8.5e0H6vK.yZ8W1D4e5K3c2D1F0H9E', 'yetkili'),
('user1', 'user1@sehrinNabzi.com', '$2y$10$u1cC.vqHpF0uS7HI7J5s/uW8.5e0H6vK.yZ8W1D4e5K3c2D1F0H9E', 'vatandas'),
('user2', 'user2@sehrinNabzi.com', '$2y$10$u1cC.vqHpF0uS7HI7J5s/uW8.5e0H6vK.yZ8W1D4e5K3c2D1F0H9E', 'vatandas'),
('user3', 'user3@sehrinNabzi.com', '$2y$10$u1cC.vqHpF0uS7HI7J5s/uW8.5e0H6vK.yZ8W1D4e5K3c2D1F0H9E', 'vatandas'),
('user4', 'user4@sehrinNabzi.com', '$2y$10$u1cC.vqHpF0uS7HI7J5s/uW8.5e0H6vK.yZ8W1D4e5K3c2D1F0H9E', 'vatandas');

-- Sample Reports (Rapor Örnekleri)
INSERT INTO raporlar (kullanici_id, baslik, aciklama, enlem, boylam, durum, ustlenen_yetkili_id) VALUES
(3, 'Kötü Yol Durumu - Beşiktaş', 'Beşiktaş bölgesinde Barbaros Bulvarı''nda ciddi pothole sorunları var. Hızlı onarım gerekli.', 41.0489, 29.0066, 'islemde', 2),
(3, 'İtfaiye İstasyonu Yakınlarında Koku Problemi', 'Nidervale Caddesi''nde kötü koku sorunu. Çevre sağlığına zarar veriyor.', 41.0431, 29.0144, 'beklemede', NULL),
(4, 'Parkta Aydınlatma Eksikliği', 'Maçka Demokrasi Parkı gece saatlerinde yeterli aydınlatmaya sahip değil. Güvenlik sorunu.', 41.0473, 29.0117, 'cozuldu', 2),
(4, 'Elektrik Kesintileri - Ortaköy', 'Ortaköy mahallesinde sık elektrik kesintileri yaşanıyor. Son 1 haftada 5 kez kesildi.', 41.0451, 29.0288, 'islemde', 2),
(5, 'Su Kaçağı - Besiktas Eki', 'Besiktas sokakta ciddi su kaçağı var. Su israf ediliyordu ve yol hasarı oluşuyor.', 41.0502, 29.0075, 'cozuldu', 2),
(5, 'Çöp Kutusu Taşması', 'Cihangir bölgesinde çöp kutuları düzenli olarak temizlenmiyor, koku sorunu var.', 41.0407, 29.0133, 'beklemede', NULL),
(6, 'Kötü Hava Kalitesi - Kemerburgaz', 'Kemerburgaz bölgesinde çok kötü hava kalitesi. Endüstriyel kirlilik nedeniyle mi?', 41.1066, 29.0436, 'islemde', 2);

-- Sample Forum Topics (Forum Konuları)
INSERT INTO forum_konulari (kullanici_id, baslik) VALUES
(3, 'Şehrin altyapı problemleri hakkında ne düşünüyorsunuz?'),
(4, 'Belediye hizmetlerindeki eksiklikler'),
(5, 'Çevre kirliliğine karşı neler yapabiliriz?'),
(6, 'Toplu taşıma sorunları'),
(3, 'Kent yaşam kalitesini nasıl artırabiliriz?');

-- Sample Comments (Yorumlar)
INSERT INTO yorumlar (konu_id, kullanici_id, yorum_metni) VALUES
(1, 4, 'Sokak aydınlatması gerçekten sorunlu. İlçede gece çok karanlık oluyor.'),
(1, 5, 'Elektrik kesintileri çok sık. Altyapı eski mi bilmiyorum ama sorunlu.'),
(2, 3, 'Belediyeye raporlar gönderilse de çoğu zaman cevap gelmiyor.'),
(2, 6, 'Yollar tam bir felaket durumda. Her yağıştan sonra daha da bozuluyor.'),
(3, 4, 'Ağaç dikmek, yeşil alan artırmak, atık yönetimini iyileştirmek lazım.'),
(3, 5, 'Sektörden gelen kirlilik kontrol edilmeli. Çevre müfettişliği artırılmalı.'),
(4, 3, 'Otobüs hatları yetersiz. Minibüs şoförleri de hızlı gidiyor, güvenlik sorunu.'),
(4, 6, 'Metro genişletme projesinin hızlı biçimde ilerlemesi gerekir.'),
(5, 4, 'Halkı bilgilendirme ve farkındalık artırma çok önemli.'),
(5, 5, 'Gönüllü temizlik kampanyaları organize edebiliriz.');