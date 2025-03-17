<?php
session_start();

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 20, '/');
}

// Destroy the session
session_destroy();

// Redirect to login page
header('Location: page_connexion.php');
exit();