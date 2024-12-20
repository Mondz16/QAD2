<?php
session_start();
require 'connection.php';  // Your database connection
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$email = ''; // Initialize email variable

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id_verify'];

    // Fetch admin email
    $stmt = $conn->prepare("SELECT email FROM admin WHERE user_id = ?");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $stmt->bind_result($email);
    $stmt->fetch();
    $stmt->close();
}

function sendOTPEmail($email, $otp) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'usepqad@gmail.com';
        $mail->Password = 'ofcx jwfa ghkv hsgz';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Bypass SSL certificate verification
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        $mail->setFrom('usepqad@gmail.com', 'USeP - Quality Assurance Division');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Email Verification OTP';
        $mail->Body    = 'Your OTP for login is: <b>' . $otp . '</b>';

        $mail->send();
    } catch (Exception $e) {
        echo 'Message could not be sent. Mailer Error: ', $mail->ErrorInfo;
    }
}

$verified = false;
$message = '';
$message1 = '';

if (isset($_GET['resend']) && $_GET['resend'] == '1') {
    $otp = rand(100000, 999999); // Generate a new OTP
    $hashed_otp = password_hash($otp, PASSWORD_DEFAULT); // Hash the new OTP

    // Update OTP and otp_created_at
    $stmt = $conn->prepare("UPDATE admin SET otp = ?, otp_created_at = NOW() WHERE user_id = ?");
    $stmt->bind_param("ss", $hashed_otp, $user_id);
    if ($stmt->execute()) {
        // Use your function to send the OTP email
        sendOTPEmail($email, $otp); // Make sure this function is defined
        $message1 = "OTP resent successfully.";
    } else {
        $message = "Error: " . $stmt->error;
    }
    $stmt->close();
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $otp = $_POST['otp'];
    $user_id = $_SESSION['user_id_verify'];

    // Fetch OTP and otp_created_at
    $stmt = $conn->prepare("SELECT otp, otp_created_at FROM admin WHERE user_id = ?");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $stmt->bind_result($stored_otp, $otp_created_at);
    $stmt->fetch();
    $stmt->close();

    if ($stored_otp && $otp_created_at) {
        $timezone = new DateTimeZone('Asia/Manila');
        $current_time = new DateTime('now', $timezone);
        $otp_time = new DateTime($otp_created_at, $timezone);

        // Calculate the difference in seconds
        $interval = $current_time->getTimestamp() - $otp_time->getTimestamp();

        // Check if OTP is expired (5 minutes = 300 seconds)
        if ($interval >= 300) {
            $message1 = "OTP has expired. Please request a new one.";
        } else {
            if (password_verify($otp, $stored_otp)) {
                $_SESSION['admin_verified'] = true;
                $verified = true;
                $message = "OTP verified successfully. You will be redirected shortly.";

                $stmt = $conn->prepare("SELECT * FROM admin WHERE user_id = ?");
                $stmt->bind_param("s", $user_id);
                $stmt->execute();
                $result_admin = $stmt->get_result();

                if ($result_admin->num_rows == 1) {
                    $admin = $result_admin->fetch_assoc();

                    $_SESSION['user_id'] = $admin['user_id'];  // Set session variable to 'user_id'
                    unset($_SESSION['user_id_verify']);
                    header("Location: dashboard.php");
                    exit;
                }
            } else {
                $message = "Invalid OTP.";
            }
        }
    } else {
        $message = "No OTP found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin OTP Verification</title>
    <link rel="stylesheet" href="index.css">
    <script>
        var timer;

        function startTimer(duration, display) {
            var timer = duration, minutes, seconds;
            var resendButton = document.getElementById('resendOTP');
            resendButton.classList.add('disabled');
            resendButton.disabled = true;

            var interval = setInterval(function () {
                minutes = parseInt(timer / 60, 10);
                seconds = parseInt(timer % 60, 10);

                minutes = minutes < 10 ? "0" + minutes : minutes;
                seconds = seconds < 10 ? "0" + seconds : seconds;

                display.textContent = minutes + ":" + seconds;

                if (--timer < 0) {
                    clearInterval(interval);
                    resendButton.disabled = false;
                    resendButton.classList.remove('disabled');
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
            document.getElementById('resendOTP').classList.add('disabled');
            var xhttp = new XMLHttpRequest();
            xhttp.open("GET", "admin_verify_otp.php?resend=1", true);
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
        <div class="hair" style="height: 15px; background: #9B0303;"></div>
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
                    <h>A one time password has been sent to your email address.<br>Use this to verify your identity as admin.</h>
                </div>

                <div style="height: 32px; width: 0px;"></div>

                <form method="post" action="admin_verify_otp.php">
                    <div class="username" style="width: 455px;">
                        <div class="usernameContainer" style="padding: 12px 20px; border-color: rgb(170, 170, 170); border-style: solid; border-width: 1px; border-radius: 8px;">
                            <input class="email" type="text" id="otp" name="otp" placeholder="OTP" required>
                        </div>
                    </div>
                    <div style="height: 10px; width: 0px;"></div>

                    <button type="submit" class="verify">Verify</button>

                    <div style="height: 10px; width: 0px;"></div>
                </form>

                <p id="message" style="color: red; font-weight: bold;"><?php echo $message1; ?></p>
                <div style="height: 20px; width: 0px;"></div>
                <a href="login.php" style="color: rgb(87, 87, 87); font-weight: 500; text-decoration: underline;">Log in as user instead?</a>
                <button type="button" class="resend disabled" style="margin-left: 55px;" id="resendOTP" onclick="resendOTP()" disabled>RESEND OTP IN <span id="time">01:00</span></button>
            </div>

            <div class="bodyRight">
                <div style="height: 200px; width: 0px;"></div>
                <img class="USeP" src="images/LoginCover.png" height="400">
            </div>
        </div>
    </div>
</body>
</html>
