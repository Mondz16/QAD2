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
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP</title>
    <script>
        var timer;
        function startTimer(duration, display) {
            var timer = duration, minutes, seconds;
            var interval = setInterval(function () {
                minutes = parseInt(timer / 60, 10);
                seconds = parseInt(timer % 60, 10);

                minutes = minutes < 10 ? "0" + minutes : minutes;
                seconds = seconds < 10 ? "0" + seconds : seconds;

                display.textContent = minutes + ":" + seconds;

                if (--timer < 0) {
                    clearInterval(interval);
                    document.getElementById('resendOTP').disabled = false;
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
    <?php if ($verified): ?>
        <p><?php echo $message; ?></p>
    <?php else: ?>
        <h2>Verify OTP</h2>
        <form method="post" action="verify_otp.php">
            <label for="email">Email:</label>
            <input type="text" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" readonly><br><br>
            <label for="type">Type:</label>
            <input type="text" id="type" name="type" value="<?php echo htmlspecialchars($type); ?>" readonly><br><br>
            <label for="otp">OTP:</label>
            <input type="text" id="otp" name="otp" required><br><br>
            <button type="submit">Verify</button>
        </form>
        <div>
            <button id="resendOTP" onclick="resendOTP()" disabled>Resend OTP</button>
            <p>Resend OTP available in <span id="time">03:00</span> minutes.</p>
        </div>
        <p id="message"><?php echo $message; ?></p>
    <?php endif; ?>
</body>
</html>
