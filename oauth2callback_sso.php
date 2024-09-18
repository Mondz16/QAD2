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
                        echo "<div class='popup-content'>
                                <div class='popup-header'>
                                    <img class='Error' src='images/Error.png' height='100'>
                                    <p class='error'>This account with Email: $email is currently applying for college transfer.<br>
                                    Please wait for the admin to approve.</p>
                                </div>
                                <a href='college.php' class='btn-hover'>OKAY</a>
                              </div>";
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
                        echo "<!DOCTYPE html>
                        <html lang=\"en\">
                        <head>
                            <meta charset=\"UTF-8\">
                            <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
                            <title>Login Form</title>
                            <link rel=\"stylesheet\" href=\"https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600&display=swap\">
                            <link rel=\"stylesheet\" href=\"index.css\">
                        </head>
                        <body>
                            <div id=\"errorPopup\" class=\"popup\" style=\"display: block;\">
                                <div class=\"popup-content\">
                                    <div style=\"height: 50px; width: 0px;\"></div>
                                    <img class=\"Error\" src=\"images/Error.png\" height=\"100\">
                                    <div style=\"height: 20px; width: 0px;\"></div>
                                    <p class=\"popup-text\">This account with Email: $email is inactive.<br>Would you like to apply again?</p>
                                    <div style=\"height: 50px; width: 0px;\"></div>
                                    <button class=\"cancel\" onclick=\"window.location.href='login.php'\">CANCEL</button>
                                    <a href=\"login_process_reactivation.php?type=internal&user_id=" . urlencode($user['user_id']) . "\" class=\"apply\">Apply</a>
                                    <div style=\"height: 100px; width: 0px;\"></div>
                                    <div class=\"hairpop-up\"></div>
                                </div>
                            </div>
                        </body>
                        </html>";
                    } elseif ($status === 'pending') {
                        echo "<!DOCTYPE html>
                        <html lang=\"en\">
                        <head>
                            <meta charset=\"UTF-8\">
                            <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
                            <title>Login Form</title>
                            <link rel=\"stylesheet\" href=\"https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600&display=swap\">
                            <link rel=\"stylesheet\" href=\"index.css\">
                        </head>
                        <body>
                            <div id=\"errorPopup\" class=\"popup\" style=\"display: block;\">
                                <div class=\"popup-content\">
                                    <div style=\"height: 50px; width: 0px;\"></div>
                                    <img class=\"Error\" src=\"images/Error.png\" height=\"100\">
                                    <div style=\"height: 20px; width: 0px;\"></div>
                                    <p class=\"popup-text\">This account with Email: $email is pending.<br>Please wait for the admin to approve.</p>
                                    <div style=\"height: 50px; width: 0px;\"></div>
                                    <button class=\"cancel\" onclick=\"window.location.href='login.php'\">OKAY</button>
                                    <div style=\"height: 100px; width: 0px;\"></div>
                                    <div class=\"hairpop-up\"></div>
                                </div>
                            </div>
                        </body>
                        </html>";
                    } elseif ($status === 'active') {
                        // Log in the active user
                        $_SESSION['user_id'] = $user['user_id'];
                        $_SESSION['email'] = $email;
                        header("Location: $redirectPath");
                        exit();
                    }
                } else {
                    echo "<!DOCTYPE html>
                    <html lang=\"en\">
                    <head>
                        <meta charset=\"UTF-8\">
                        <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
                        <title>Login Form</title>
                        <link rel=\"stylesheet\" href=\"https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600&display=swap\">
                        <link rel=\"stylesheet\" href=\"index.css\">
                    </head>
                    <body>
                        <div id=\"errorPopup\" class=\"popup\" style=\"display: block;\">
                            <div class=\"popup-content\">
                                <div style=\"height: 50px; width: 0px;\"></div>
                                <img class=\"Error\" src=\"images/Error.png\" height=\"100\">
                                <div style=\"height: 20px; width: 0px;\"></div>
                                <p class=\"popup-text\">There is no account with Email: $email<br>exists in the system.</p>
                                <div style=\"height: 50px; width: 0px;\"></div>
                                <button class=\"cancel\" onclick=\"window.location.href='login.php'\">OKAY</button>
                                <div style=\"height: 100px; width: 0px;\"></div>
                                <div class=\"hairpop-up\"></div>
                            </div>
                        </div>
                    </body>
                    </html>";
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
            echo "<!DOCTYPE html>
            <html lang=\"en\">
            <head>
                <meta charset=\"UTF-8\">
                <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
                <title>Login Form</title>
                <link rel=\"stylesheet\" href=\"https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600&display=swap\">
                <link rel=\"stylesheet\" href=\"index.css\">
            </head>
            <body>
                <div id=\"errorPopup\" class=\"popup\" style=\"display: block;\">
                    <div class=\"popup-content\">
                        <div style=\"height: 50px; width: 0px;\"></div>
                        <img class=\"Error\" src=\"images/Error.png\" height=\"100\">
                        <div style=\"height: 20px; width: 0px;\"></div>
                        <p class=\"popup-text\">Failed to obtain access token. Please try again later.</p>
                        <div style=\"height: 50px; width: 0px;\"></div>
                        <button class=\"cancel\" onclick=\"window.location.href='login.php'\">OKAY</button>
                        <div style=\"height: 100px; width: 0px;\"></div>
                        <div class=\"hairpop-up\"></div>
                    </div>
                </div>
            </body>
            </html>";
        }
    } catch (Exception $e) {
        echo "<!DOCTYPE html>
        <html lang=\"en\">
        <head>
            <meta charset=\"UTF-8\">
            <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
            <title>Login Form</title>
            <link rel=\"stylesheet\" href=\"https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600&display=swap\">
            <link rel=\"stylesheet\" href=\"index.css\">
        </head>
        <body>
            <div id=\"errorPopup\" class=\"popup\" style=\"display: block;\">
                <div class=\"popup-content\">
                    <div style=\"height: 50px; width: 0px;\"></div>
                    <img class=\"Error\" src=\"images/Error.png\" height=\"100\">
                    <div style=\"height: 20px; width: 0px;\"></div>
                    <p class=\"popup-text\">Error: \" . htmlspecialchars($e->getMessage()) . \"</p>
                    <div style=\"height: 50px; width: 0px;\"></div>
                    <button class=\"cancel\" onclick=\"window.location.href='login.php'\">OKAY</button>
                    <div style=\"height: 100px; width: 0px;\"></div>
                    <div class=\"hairpop-up\"></div>
                </div>
            </div>
        </body>
        </html>";
    }
} else {
    echo "<!DOCTYPE html>
    <html lang=\"en\">
    <head>
        <meta charset=\"UTF-8\">
        <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
        <title>Login Form</title>
        <link rel=\"stylesheet\" href=\"https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600&display=swap\">
        <link rel=\"stylesheet\" href=\"index.css\">
    </head>
    <body>
        <div id=\"errorPopup\" class=\"popup\" style=\"display: block;\">
            <div class=\"popup-content\">
                <div style=\"height: 50px; width: 0px;\"></div>
                <img class=\"Error\" src=\"images/Error.png\" height=\"100\">
                <div style=\"height: 20px; width: 0px;\"></div>
                <p class=\"popup-text\">OAuth authentication failed. Please try again.</p>
                <div style=\"height: 50px; width: 0px;\"></div>
                <button class=\"cancel\" onclick=\"window.location.href='login.php'\">OKAY</button>
                <div style=\"height: 100px; width: 0px;\"></div>
                <div class=\"hairpop-up\"></div>
            </div>
        </div>
    </body>
    </html>";
}
?>