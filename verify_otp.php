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
        $mail->Password = 'vmvf vnvq ileu tmev';
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

$verified = false;
$message = '';

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

    $stmt = $conn->prepare("UPDATE $table SET otp = ? WHERE email = ?");
    $stmt->bind_param("ss", $hashed_otp, $email);
    if ($stmt->execute()) {
        sendOTPEmail($email, $otp); // Send the plain OTP to the user
        $message = "OTP resent successfully.";
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

    $stmt = $conn->prepare("SELECT otp FROM $table WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($stored_otp);
    $stmt->fetch();
    $stmt->close();

    if (password_verify($otp, $stored_otp)) {  // Verify the hashed OTP
        $stmt = $conn->prepare("UPDATE $table SET status = 'pending', otp = 'verified' WHERE email = ?");
        $stmt->bind_param("s", $email);
        if ($stmt->execute()) {
            $verified = true;
            $message = "Email verified successfully. Your account is now pending for approval. <a href='login.php'>Login</a>";
        } else {
            $message = "Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $message = "Invalid OTP.";
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
            var duration = 180; // 180 seconds timer
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
                    var duration = 180; // 180 seconds timer
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
                                    <span class="datausep" style="color: rgb(151, 57, 57); font-weight: 600; font-size: 18px;">Data.</span>
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
                    <p><?php echo $message; ?></p>
                <?php else: ?>
                    <form method="post" action="verify_otp.php">
                        <div class="username" style="width: 455px;">
                            <div class="usernameContainer" style="padding: 12px 20px; border-color: rgb(170, 170, 170); border-style: solid; border-width: 1px; border-radius: 8px;">
                                <input class="email" type="text" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" readonly>
                            </div>
                        </div>
                        <div style="height: 10px; width: 0px;"></div>

                        <div class="username" style="width: 455px;">
                            <div class="usernameContainer" style="padding: 12px 20px; border-color: rgb(170, 170, 170); border-style: solid; border-width: 1px; border-radius: 8px;">
                                <input class="email" type="text" id="type" name="type" value="<?php echo htmlspecialchars($type); ?>" readonly>
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

                    <p id="message"><?php echo $message; ?></p>
                    <div style="height: 20px; width: 0px;"></div>
                <?php endif; ?>
                <a href="login.php" style="color: rgb(87, 87, 87); font-weight: 500; text-decoration: underline;">Already have an account?</a>
                <button type="button" class="resend disabled" id="resendOTP" onclick="resendOTP()" disabled>RESEND OTP IN <span id="time">03:00</span></button>
            </div>

            <div class="bodyRight">
                    <div style="height: 200px; width: 0px;"></div>
                    <img class="USeP" src="images/LoginCover.png" height="400">
                </div>
        </div>
    </div>
</body>
</html>
