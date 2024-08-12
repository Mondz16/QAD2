<?php
require 'vendor/autoload.php';
include 'token_storage.php'; // Include token storage functions

use Google\Client;

session_start();

$client = new Client();
$client->setAuthConfig('./secure/credentials.json'); // Path to your credentials.json file
$client->setRedirectUri('http://localhost/QAD2/oauth2callback.php'); // Your redirect URI
$client->addScope(Google\Service\Calendar::CALENDAR);

if (!isset($_GET['code'])) {
    $authUrl = $client->createAuthUrl();
    header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
    exit();
} else {
    $client->authenticate($_GET['code']);
    $accessToken = $client->getAccessToken();
    storeToken($accessToken); // Store the token
    $_SESSION['access_token'] = $accessToken; // Store it in the session as well

    // Redirect back to schedule_approve_process.php
    header('Location: schedule_approve_process.php');
    exit();
}

?>