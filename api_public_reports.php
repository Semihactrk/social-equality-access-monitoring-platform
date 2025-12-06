<?php
// Bu dosya giriş yapmayı gerektirmez, herkese açıktır (Open Data)
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once 'includes/db_connect.php';

// Sadece onaylanmış ve çözülmemiş (aktif) sorunları çek
$sql = "SELECT title, category, description, created_at FROM reports WHERE status = 'Bekliyor' ORDER BY created_at DESC";
$result = $conn->query($sql);

$reports = array();

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $report_item = array(
            "baslik" => $row['title'],
            "kategori" => $row['category'],
            "aciklama" => html_entity_decode($row['description']),
            "tarih" => $row['created_at']
        );
        array_push($reports, $report_item);
    }
    // Veriyi JSON formatında bas
    echo json_encode(array("status" => "success", "data" => $reports));
} else {
    echo json_encode(array("status" => "empty", "message" => "Şu an aktif bir sorun yok."));
}
$conn->close();
?>