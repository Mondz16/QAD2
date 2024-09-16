<?php
require_once 'vendor/autoload.php';

session_start();

// Setup Google Client
$client = new Google_Client();
$client->setClientId('CLIENT_ID');
$client->setClientSecret('CLIENT_SECRET');
$client->setRedirectUri('http://localhost/qad2/oauth2callback_sso.php');

if (isset($_GET['code'])) {
    try {
        // Exchange the auth code for an access token
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

        // Print token to debug
        echo '<pre>';
        print_r($token);
        echo '</pre>';

        if (isset($token['access_token'])) {
            $client->setAccessToken($token['access_token']);

            // Get user info from Google
            $oauth2 = new Google_Service_Oauth2($client);
            $userInfo = $oauth2->userinfo->get();

            // Get the user's email
            $email = $userInfo->email;

            // Check if the email is from the @usep.edu.ph domain
            if (strpos($email, '@usep.edu.ph') !== false) {
                // Include your database connection
                require 'connection.php';
                
                // Check if the email exists in the database
                $stmt = $conn->prepare("SELECT * FROM internal_users WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    // User exists in the database, set session and redirect
                    $user = $result->fetch_assoc();
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['email'] = $email;  // Save email in session
                    header('Location: internal.php');  // Redirect to internal dashboard
                } else {
                    // User not found in database
                    echo "No user with this email exists in the system.";
                }
            } else {
                // Email is not from @usep.edu.ph
                echo "Access restricted to @usep.edu.ph accounts.";
            }
        } else {
            // Access token not found
            echo "Failed to obtain access token. Token response:";
            echo '<pre>';
            print_r($token);
            echo '</pre>';
        }
    } catch (Exception $e) {
        echo 'Error: ' . $e->getMessage();
    }
} else {
    // OAuth 2.0 authentication failed
    echo 'Google login failed.';
}
?>
