<?php
// Her zaman olduğu gibi oturumu başlat
session_start();

// Tüm session değişkenlerini temizle (boş bir diziye eşitle)
$_SESSION = array();

// Oturumu tamamen sonlandır
session_destroy();

// Kullanıcıyı giriş sayfasına yönlendir
header("Location: login.php");
exit;
?>