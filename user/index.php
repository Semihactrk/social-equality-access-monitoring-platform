<?php
// If someone accidentally tries to enter the "user" folder,
// redirect them automatically to the profile page.
header("Location: profile.php");
exit();
?>