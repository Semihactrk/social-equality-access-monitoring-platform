-- =====================================================
-- SÖZLEŞMELİ ÖZÜR AYDIN - Şehrin Nabzı Database
-- Social Equality Access Monitoring Platform
-- =====================================================

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
    fotograf_yolu VARCHAR(255),
    sdg_kategori VARCHAR(100),
    durum ENUM('beklemede', 'islemde', 'isleme_alindi', 'cozuldu') DEFAULT 'beklemede',
    ustlenen_yetkili_id INT,
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    guncellenme_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (kullanici_id) REFERENCES kullanicilar(id) ON DELETE CASCADE,
    FOREIGN KEY (ustlenen_yetkili_id) REFERENCES kullanicilar(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: Forum Konuları (Forum Topics)
-- =====================================================
CREATE TABLE forum_konulari (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kullanici_id INT NOT NULL,
    baslik VARCHAR(255) NOT NULL,
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kullanici_id) REFERENCES kullanicilar(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: Yorumlar (Comments)
-- =====================================================
CREATE TABLE yorumlar (
    id INT PRIMARY KEY AUTO_INCREMENT,
    konu_id INT DEFAULT NULL,
    rapor_id INT DEFAULT NULL,
    kullanici_id INT NOT NULL,
    yorum_metni TEXT NOT NULL,
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (konu_id) REFERENCES forum_konulari(id) ON DELETE CASCADE,
    FOREIGN KEY (rapor_id) REFERENCES raporlar(id) ON DELETE CASCADE,
    FOREIGN KEY (kullanici_id) REFERENCES kullanicilar(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: Yardım Kaynakları (Social Resources) -- [YENİ EKLENDİ]
-- =====================================================
CREATE TABLE IF NOT EXISTS yardim_kaynaklari (
    id INT PRIMARY KEY AUTO_INCREMENT,
    kaynak_adi VARCHAR(50) NOT NULL,
    stok INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SAMPLE DATA (Örnek Veriler)
-- =====================================================

-- 1. Kullanıcılar (Şifre: 123456)
INSERT INTO kullanicilar (kullanici_adi, email, sifre, rol) VALUES
('admin123', 'admin@sehrinNabzi.com', '$2y$10$u1cC.vqHpF0uS7HI7J5s/uW8.5e0H6vK.yZ8W1D4e5K3c2D1F0H9E', 'admin'),
('yetkili_user', 'yetkili@sehrinNabzi.com', '$2y$10$u1cC.vqHpF0uS7HI7J5s/uW8.5e0H6vK.yZ8W1D4e5K3c2D1F0H9E', 'yetkili'),
('user1', 'user1@sehrinNabzi.com', '$2y$10$u1cC.vqHpF0uS7HI7J5s/uW8.5e0H6vK.yZ8W1D4e5K3c2D1F0H9E', 'vatandas');

-- 2. Yardım Kaynağı (Erzak Paketi) -- [YENİ EKLENDİ]
INSERT INTO yardim_kaynaklari (kaynak_adi, stok) VALUES ('Food Package', 50);

-- 3. Raporlar
INSERT INTO raporlar (kullanici_id, baslik, aciklama, enlem, boylam, durum, sdg_kategori) VALUES
(3, 'Broken Street Light', 'Street lights are not working in the park area.', 41.0473, 29.0117, 'beklemede', 'SDG-11: Sustainable Cities');
