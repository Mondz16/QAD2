<?php
require_once 'vendor/autoload.php';

session_start();

// Initialize Google Client
$client = new Google_Client();
$client->setClientId('CLIENT_ID');
$client->setClientSecret('CLIENT_SECRET');
$client->setRedirectUri('http://localhost/qad2/oauth2callback_sso.php'); // Ensure this matches your Google API Console configuration
$client->addScope("email");
$client->addScope("profile");

// Add a parameter to indicate login
$login_url = $client->createAuthUrl() . "&state=login";
header('Location: ' . filter_var($login_url, FILTER_SANITIZE_URL));
exit();
?>
