<?php
session_start();
require 'connection.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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
        $mail->Body    = 'Your OTP for email verification is: <b>' . $otp . '</b>';

        $mail->send();
    } catch (Exception $e) {
        echo 'Message could not be sent. Mailer Error: ', $mail->ErrorInfo;
    }
}

function sendAccountApprovalEmail($email, $firstName , $user_id) {
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
        $mail->Subject = 'Account Verification Successful';
        $mail->Body    = "Dear $firstName,<br><br>Your account with User ID: <b>{$user_id}</b> has been successfully verified. However, please note that you can only fully access your account once the admin has approved it.<br><br>Best Regards,<br>USeP - Quality Assurance Division";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

$verified = false;
$message = '';
$message1 = '';
$message2 = '';
$otpExpired = false; // Track if OTP has expired

$email = isset($_POST['email']) ? $_POST['email'] : (isset($_GET['email']) ? $_GET['email'] : '');
$type = isset($_POST['type']) ? $_POST['type'] : (isset($_GET['type']) ? $_GET['type'] : '');

if (isset($_GET['resend']) && $_GET['resend'] == '1') {
    $otp = rand(100000, 999999); // Generate a new OTP
    $hashed_otp = password_hash($otp, PASSWORD_DEFAULT); // Hash the new OTP

    if ($type == 'internal') {
        $table = "internal_users";
    } elseif ($type == 'external') {
        $table = "external_users";
    } else {
        echo "Invalid type.";
        exit;
    }

    // Update OTP and otp_created_at
    $stmt = $conn->prepare("UPDATE $table SET otp = ?, otp_created_at = NOW() WHERE email = ?");
    $stmt->bind_param("ss", $hashed_otp, $email);
    if ($stmt->execute()) {
        sendOTPEmail($email, $otp); // Send the plain OTP to the user
        $message1 = "OTP resent successfully.";
    } else {
        $message = "Error: " . $stmt->error;
    }
    $stmt->close();
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $otp = $_POST['otp'];

    if ($type == 'internal') {
        $table = "internal_users";
    } elseif ($type == 'external') {
        $table = "external_users";
    } else {
        echo "Invalid type.";
        exit;
    }

    // Fetch OTP and otp_created_at
    $stmt = $conn->prepare("SELECT otp, otp_created_at FROM $table WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($stored_otp, $otp_created_at);
    $stmt->fetch();
    $stmt->close();

    if ($stored_otp && $otp_created_at) {
        $timezone = new DateTimeZone('Asia/Manila'); // e.g., 'America/New_York'
        $current_time = new DateTime('now', $timezone);
        $otp_time = new DateTime($otp_created_at, $timezone);
        
        // Calculate the difference in seconds
        $interval = $current_time->getTimestamp() - $otp_time->getTimestamp();

        // Check if OTP is expired (5 minutes = 300 seconds)
        if ($interval >= 300) {
            $otpExpired = true; // Set expired flag
            $message1 = "OTP has expired. Please request a new one.";
        } else {
            if (password_verify($otp, $stored_otp)) {  // Verify the hashed OTP
                // Fetch the user's ID
                $stmt = $conn->prepare("SELECT user_id, first_name FROM $table WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $stmt->bind_result($user_id, $first_name);
                $stmt->fetch();
                $stmt->close();

                // Try to send the account approval email
                if (sendAccountApprovalEmail($email, firstName: $first_name , user_id: $user_id)) {
                    $stmt = $conn->prepare("UPDATE $table SET status = 'pending', otp = 'verified' WHERE email = ?");
                    $stmt->bind_param("s", $email);
                    if ($stmt->execute()) {
                        $verified = true;
                        $message = "Email verified successfully.<br>Your account is now pending for approval.<br>Please check your email for more details.";
                    } else {
                        $message = "Error: " . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    $message2 = "Email verification succeeded, but we were unable to send the final account verification email. Please try again later.";
                }
            } else {
                $message2 = "Invalid OTP.";
            }
        }
    } else {
        $message = "No OTP found or OTP already verified.";
    }

    $conn->close();
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
    </style>
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
            var email = "<?php echo $email; ?>";
            var type = "<?php echo $type; ?>";
            var xhttp = new XMLHttpRequest();
            xhttp.open("GET", "verify_otp.php?resend=1&email=" + email + "&type=" + type, true);
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
                    <h>A one time password has been sent to your email address.<br>Use this to verify your account.</h>
                </div>

                <div style="height: 32px; width: 0px;"></div>

                <?php if ($verified): ?>
                    <div id="successPopup" class="popup">
                        <div class="popup-content">
                            <div style="height: 50px; width: 0px;"></div>
                            <img class="Success" src="images/Success.png" height="100">
                            <div style="height: 20px; width: 0px;"></div>
                            <div class="popup-text"><?php echo $message; ?></div>
                            <div style="height: 50px; width: 0px;"></div>
                            <a href="login.php" class="okay" id="closePopup">Okay</a>
                            <div style="height: 100px; width: 0px;"></div>
                            <div class="hairpop-up"></div>
                        </div>
                    </div>
                    <script>
                        document.getElementById('successPopup').style.display = 'block';

                        document.getElementById('closeSuccessBtn').addEventListener('click', function() {
                            document.getElementById('successPopup').style.display = 'none';
                        });

                        document.getElementById('closePopup').addEventListener('click', function() {
                            document.getElementById('successPopup').style.display = 'none';
                        });

                        window.addEventListener('click', function(event) {
                            if (event.target == document.getElementById('successPopup')) {
                                document.getElementById('successPopup').style.display = 'none';
                            }
                        });
                    </script>
                <?php endif; ?>

                <?php if (!$verified && $message2): ?>
                    <div id="errorPopup" class="popup">
                        <div class="popup-content">
                            <div style="height: 50px; width: 0px;"></div>
                            <img class="Error" src="images/Error.png" height="100">
                            <div style="height: 20px; width: 0px;"></div>
                            <div class="popup-text"><?php echo $message2; ?></div>
                            <div style="height: 50px; width: 0px;"></div>
                            <button id="closeErrorPopup" class="okay">Okay</button>
                            <div style="height: 100px; width: 0px;"></div>
                            <div class="hairpop-up"></div>
                        </div>
                    </div>
                    <script>
                        document.getElementById('errorPopup').style.display = 'block';

                        document.getElementById('closeErrorPopup').addEventListener('click', function() {
                            document.getElementById('errorPopup').style.display = 'none';
                        });

                        window.addEventListener('click', function(event) {
                            if (event.target == document.getElementById('errorPopup')) {
                                document.getElementById('errorPopup').style.display = 'none';
                            }
                        });
                    </script>
                <?php endif; ?>

                <form method="post" action="verify_otp.php">
                    <div class="username" style="width: 455px;">
                        <div class="usernameContainer" style="padding: 12px 20px; border-color: rgb(170, 170, 170); border-style: solid; border-width: 1px; border-radius: 8px;">
                            <input class="email" type="text" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" readonly>
                        </div>
                    </div>
                    <div style="height: 10px; width: 0px;"></div>

                    <div class="username" style="width: 455px;">
                        <div style="display: flex; align-items: center; padding: 12px 20px; border-color: rgb(170, 170, 170); border-style: solid; border-width: 1px; border-radius: 8px;">
                            <input class="email" type="text" id="type" name="type" value="<?php echo htmlspecialchars($type); ?>" readonly style="text-transform: uppercase; flex: 1;">
                            <span style="margin-right: 233px;">ACCREDITOR</span>
                        </div>
                    </div>
                    <div style="height: 10px; width: 0px;"></div>

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
                <p id="message" style="color: red; font-weight: bold;"><?php echo $message2; ?></p>
                <div style="height: 20px; width: 0px;"></div>
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
        document.addEventListener('DOMContentLoaded', function () {
            const verifyForm = document.querySelector('form');
            const loadingSpinner = document.getElementById('customLoadingOverlay');

            verifyForm.addEventListener('submit', function () {
                // Show the loading spinner
                loadingSpinner.classList.remove('custom-spinner-hidden');
            });
        });

        document.getElementById('otp').addEventListener('input', function(e) {
            let otpinput = e.target.value;

            // Remove any non-letter characters
            otpinput = otpinput.replace(/[^0-9]/g, '');

            // Limit to 1 character
            if (otpinput.length > 6) {
                otpinput = otpinput.slice(0, 6);
            }

            // Set the cleaned value back to the input
            e.target.value = otpinput;
        });
    </script>
</body>
</html>
