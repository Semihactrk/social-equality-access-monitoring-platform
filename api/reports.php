<?php
// Bu dosya giriş yapmayı gerektirmez, herkese açıktır (Open Data)
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once 'includes/db_connect.php';

// Senin veritabanı yapına göre güncellenmiş sorgu:
// Tablo: raporlar
// Sütunlar: baslik, sdg_kategori, aciklama, olusturma_tarihi
// Durum: 'beklemede'
$sql = "SELECT baslik, sdg_kategori, aciklama, olusturma_tarihi FROM raporlar WHERE durum = 'beklemede' ORDER BY olusturma_tarihi DESC";
$result = $conn->query($sql);

$reports = array();

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $report_item = array(
            "baslik" => $row['baslik'],
            // Eğer sdg_kategori boşsa 'Belirtilmemiş' yazsın
            "kategori" => $row['sdg_kategori'] ? $row['sdg_kategori'] : "Belirtilmemiş", 
            "aciklama" => html_entity_decode($row['aciklama']),
            "tarih" => $row['olusturma_tarihi']
        );
        array_push($reports, $report_item);
    }
    // Veriyi JSON formatında bas
    echo json_encode(array("status" => "success", "data" => $reports), JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(array("status" => "empty", "message" => "Şu an aktif bir sorun yok."), JSON_UNESCAPED_UNICODE);
}
$conn->close();
?>