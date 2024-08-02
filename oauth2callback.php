<?php
require 'vendor/autoload.php';
include 'token_storage.php'; // Include token storage functions

use Google\Client;

session_start();

$client = new Client();
$client->setAuthConfig('C:/xampp/htdocs/QAD2/secure/credentials.json'); // Path to your credentials.json file
$client->setRedirectUri('http://localhost/QAD2/oauth2callback.php'); // Your redirect URI
$client->addScope(Google\Service\Calendar::CALENDAR);

if (!isset($_GET['code'])) {
    $authUrl = $client->createAuthUrl();
    header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
} else {
    $client->authenticate($_GET['code']);
    $accessToken = $client->getAccessToken();
    storeToken($accessToken); // Store the token
    $_SESSION['access_token'] = $accessToken; // Store it in the session as well
    $redirectUri = 'http://localhost/QAD2/'; // Redirect to your main application page
    header('Location: ' . filter_var($redirectUri, FILTER_SANITIZE_URL));
}
?>
