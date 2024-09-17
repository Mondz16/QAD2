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

        // Debug: Print token for troubleshooting
        echo '<pre>';
        print_r($token);
        echo '</pre>';

        if (isset($token['access_token'])) {
            $client->setAccessToken($token['access_token']);

            // Get user info from Google
            $oauth2 = new Google_Service_Oauth2($client);
            $userInfo = $oauth2->userinfo->get();
            $email = $userInfo->email;

            // Include your database connection
            require 'connection.php';

            // Define a function to handle user logic based on the table
            function handleUserLogic($stmt, $email, $redirectPath, $otpRedirectPath) {
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();

                // If multiple entries found for this email
                if ($result->num_rows > 1) {
                    $statuses = [];
                    while ($row = $result->fetch_assoc()) {
                        $statuses[] = $row['status'];
                    }

                    // Check for active and pending statuses
                    if (in_array('active', $statuses) && in_array('pending', $statuses)) {
                        echo "This account with Email: $email is currently applying for college transfer.<br>
                              Please wait for the admin to approve.";
                    }
                    // Check for active and inactive statuses, allow login for the active one
                    elseif (in_array('active', $statuses) && in_array('inactive', $statuses)) {
                        foreach ($result as $user) {
                            if ($user['status'] === 'active') {
                                $_SESSION['user_id'] = $user['user_id'];
                                $_SESSION['email'] = $email;

                                // Check OTP
                                if ($user['otp'] != 'verified') {
                                    header("Location: $otpRedirectPath?email=" . urlencode($email) . "&type=internal");
                                    exit();
                                }

                                header("Location: $redirectPath");
                                exit();
                            }
                        }
                    }
                }
                // If one entry found, handle its status
                elseif ($result->num_rows === 1) {
                    $user = $result->fetch_assoc();
                    $status = $user['status'];

                    // Handle OTP verification for single user
                    if ($user['otp'] != 'verified') {
                        header("Location: $otpRedirectPath?email=" . urlencode($email) . "&type=internal");
                        exit();
                    }

                    if ($status === 'inactive') {
                        echo "This account with Email: $email is inactive.<br>Would you like to apply again?";
                    } elseif ($status === 'pending') {
                        echo "This account with Email: $email is pending.<br>Please wait for the admin to approve.";
                    } elseif ($status === 'active') {
                        // Log in the active user
                        $_SESSION['user_id'] = $user['user_id'];
                        $_SESSION['email'] = $email;
                        header("Location: $redirectPath");
                        exit();
                    }
                } else {
                    // No user found
                    echo "No user with this email exists in the system.";
                }
            }

            // Check if the email is from @usep.edu.ph
            if (strpos($email, '@usep.edu.ph') !== false) {
                // Check the admin table first
                $stmt = $conn->prepare("SELECT * FROM admin WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $adminResult = $stmt->get_result();

                if ($adminResult->num_rows === 1) {
                    $admin = $adminResult->fetch_assoc();
                    // Log in admin user
                    $_SESSION['user_id'] = $admin['user_id'];
                    $_SESSION['email'] = $email;
                    header("Location: dashboard.php");
                    exit();
                }

                // If not an admin, check the internal_users table
                $stmt = $conn->prepare("SELECT * FROM internal_users WHERE email = ?");
                handleUserLogic($stmt, $email, 'internal.php', 'verify_otp.php');
            } else {
                // Check the external_users table for non-usep.edu.ph email
                $stmt = $conn->prepare("SELECT * FROM external_users WHERE email = ?");
                handleUserLogic($stmt, $email, 'external.php', 'verify_otp.php');
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
