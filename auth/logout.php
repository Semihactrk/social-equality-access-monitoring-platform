<?php
// Start the session as always
session_start();

// Clear all session variables (set to an empty array)
$_SESSION = array();

// Completely destroy the session
session_destroy();

// Redirect user to the login page
header("Location: login.php");
exit;
?>