<?php
session_start();

$verified = false;
$message1 = "";
$message2 = "";

// Check if OTP is expired
if (isset($_SESSION['otp_expiry']) && time() > $_SESSION['otp_expiry']) {
    unset($_SESSION['otp']);
    unset($_SESSION['otp_expiry']);
    $message1 = "The OTP has expired. Please request a new one.";
}

include 'connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $entered_otp = $_POST['otp'];
    $newEmail = $_SESSION['newEmail'];
    $user_id = $_SESSION['user_id'];

    // Check if the entered OTP matches the OTP stored in the session and is not expired
    if (isset($_SESSION['otp']) && isset($_SESSION['otp_expiry']) && $entered_otp === $_SESSION['otp'] && time() <= $_SESSION['otp_expiry']) {
        // OTP is correct and not expired, proceed to update the email
        $verified = true;
        $_SESSION['otp_verified'] = true; 

        include 'connection.php';
        $stmt = $conn->prepare("UPDATE internal_users SET email = ? WHERE user_id = ?");
        $stmt->bind_param('ss', $newEmail, $user_id);

        if ($stmt->execute()) {
            unset($_SESSION['otp']);
            unset($_SESSION['otp_expiry']);
            unset($_SESSION['otp_verified']);
            unset($_SESSION['newEmail']);

            header('Location: internal.php'); // Redirect to profile or any page after successful update
            exit;
        } else {
            $message2 = "Failed to update email. Please try again.";
        }

        $stmt->close();
    } else {
        if (!isset($message2)) {
            // OTP is incorrect or expired
            $message2 = "Invalid OTP. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP</title>
    <link rel="stylesheet" href="index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" />
    <script>
        var timer;

        function startTimer(duration, display) {
            var timer = duration, minutes, seconds;
            var resendButton = document.getElementById('resendOTP');
            resendButton.classList.add('disabled'); // Add disabled class initially
            resendButton.disabled = true; // Disable button initially
            
            var interval = setInterval(function () {
                minutes = parseInt(timer / 60, 10);
                seconds = parseInt(timer % 60, 10);

                minutes = minutes < 10 ? "0" + minutes : minutes;
                seconds = seconds < 10 ? "0" + seconds : seconds;

                display.textContent = minutes + ":" + seconds;

                if (--timer < 0) {
                    clearInterval(interval);
                    resendButton.disabled = false; // Enable button after timer ends
                    resendButton.classList.remove('disabled'); // Remove disabled class
                }
            }, 1000);
        }

        window.onload = function () {
            var duration = 60; // 60 seconds timer
            var display = document.querySelector('#time');
            startTimer(duration, display);
        };

        function resendOTP() {
            document.getElementById('resendOTP').disabled = true;
            document.getElementById('resendOTP').classList.add('disabled'); // Add disabled class

            var xhttp = new XMLHttpRequest();
            xhttp.open("GET", "update_email.php?resend=1", true); // Update the file path accordingly
            xhttp.send();

            xhttp.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    document.getElementById('message').innerText = this.responseText;
                    var duration = 60; // 60 seconds timer
                    var display = document.querySelector('#time');
                    startTimer(duration, display);
                }
            };
        }
    </script>
</head>
<body>
    <div class="wrapper">
        <div class="hair" style="height: 15px; background: linear-gradient(275.52deg, #973939 0.28%, #DC7171 100%);"></div>
        <div class="container">
            <div class="header">
                <div class="headerLeft">
                    <div class="USePData">
                        <img class="USeP" src="images/USePLogo.png" height="36">
                        <div style="height: 0px; width: 16px;"></div>
                        <div style="height: 32px; width: 1px; background: #E5E5E5"></div>
                        <div style="height: 0px; width: 16px;"></div>
                        <div class="headerLeftText">
                            <div class="onedata" style="height: 100%; width: 100%; display: flex; flex-flow: unset; place-content: unset; align-items: unset; overflow: unset;">
                                <h><span class="one" style="color: rgb(229, 156, 36); font-weight: 600; font-size: 18px;">One</span>
                                    <span class="datausep" style="color: rgb(151, 57, 57); font-weight: 600; font-size: 18px;">One</span>
                                    <span class="one" style="color: rgb(229, 156, 36); font-weight: 600; font-size: 18px;">One</span>
                                    <span class="datausep" style="color: rgb(151, 57, 57); font-weight: 600; font-size: 18px;">USeP.</span></h>
                            </div>
                            <h>Accreditor Portal</h>
                        </div>
                    </div>
                </div>

                <div class="headerRight">
                    <div class="QAD">
                        <div class="headerRightText">
                            <h style="color: rgb(87, 87, 87); font-weight: 600; font-size: 16px;">Quality Assurance Division</h>
                        </div>
                        <div style="height: 0px; width: 16px;"></div>
                        <div style="height: 32px; width: 1px; background: #E5E5E5"></div>
                        <div style="height: 0px; width: 16px;"></div>
                        <img class="USeP" src="images/QADLogo.png" height="36">
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="container">
        <div class="body1">
            <div class="bodyLeft">
                <div style="height: 180px; width: 0px;"></div>
                <div class="bodyLeftText" style="color: rgb(87, 87, 87); font-weight: 500; font-size: 3rem;">
                    <h>Verify OTP</h>
                </div>

                <div style="height: 8px; width: 0px;"></div>

                <div class="bodyLeftText" style="color: rgb(87, 87, 87); font-weight: 300; font-size: 17px;">
                    <h>A one-time password has been sent to your email address.<br>Use this to verify your account.</h>
                </div>

                <div style="height: 32px; width: 0px;"></div>
                <form method="post" action="update_email_verification.php">
                    <div class="username" style="width: 455px;">
                        <div class="usernameContainer">
                            <input class="email" type="text" id="otp" name="otp" placeholder="Enter OTP" required>
                        </div>
                    </div>
                    <div style="height: 10px; width: 0px;"></div>

                    <button type="submit" class="verify">Verify</button>

                    <div style="height: 10px; width: 0px;"></div>
                </form>

                <p id="message" style="color: red; font-weight: bold;"><?php echo $message1; ?></p>
                <p id="message" style="color: red; font-weight: bold;"><?php echo $message2; ?></p>
                <div style="height: 20px; width: 0px;"></div>
                <a href="internal.php" style="display: inline-block;color: rgb(87, 87, 87);font-weight: 500;text-decoration: underline;width: 195px;height: 50px;margin: 0;text-align: right;">CANCEL</a>
                <button type="button" class="resend disabled" id="resendOTP" onclick="resendOTP()" disabled>RESEND OTP IN <span id="time">01:00</span></button>
            </div>

            <div class="bodyRight">
                <div style="height: 200px; width: 0px;"></div>
                <img class="USeP" src="images/LoginCover.png" height="400">
            </div>
        </div>
    </div>

    <!-- Loading Spinner -->
    <div id="customLoadingOverlay" class="custom-loading-overlay custom-spinner-hidden">
        <div class="custom-spinner"></div>
    </div>

    <script>
        document.getElementById('otp').addEventListener('input', function(e) {
            let otpInput = e.target.value;

            // Remove any non-numeric characters
            otpInput = otpInput.replace(/\D/g, '');

            // Limit to 6 characters
            if (otpInput.length > 6) {
                otpInput = otpInput.slice(0, 6);
            }

            // Set the cleaned and limited value back to the input field
            e.target.value = otpInput;
        });


        document.addEventListener('DOMContentLoaded', function () {
            const verifyForm = document.querySelector('form');
            const loadingSpinner = document.getElementById('customLoadingOverlay');

            verifyForm.addEventListener('submit', function () {
                // Show the loading spinner
                loadingSpinner.classList.remove('custom-spinner-hidden');
            });
        });
    </script>
</body>
</html>
