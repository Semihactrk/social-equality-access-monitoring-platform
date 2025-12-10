<?php
// Eğer biri yanlışlıkla "user" klasörüne girmeye çalışırsa,
// onu otomatik olarak profil sayfasına yönlendir.
header("Location: profile.php");
exit();
?>