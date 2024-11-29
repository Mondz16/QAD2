<?php
session_start();
include 'connection.php';
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

date_default_timezone_set('Asia/Manila');

function generateOTP($length = 6)
{
    return str_pad(rand(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
}

function sendOTP($email, $firstName, $otp)
{
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'usepqad@gmail.com';
        $mail->Password = 'ofcx jwfa ghkv hsgz';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

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
        $mail->Subject = 'Your OTP for Email Update';
        $mail->Body = "Dear $firstName,<br><br>Your OTP for email update is: <strong>$otp</strong><br>Please use this OTP to verify your identity.<br><br>Best regards,<br>USeP - Quality Assurance Division";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $newEmail = $_POST['newEmail'];
    $email = $_POST['email'];
    $user_id = $_SESSION['user_id'];

    // Check if newEmail and email match
    if ($newEmail === $email) {
        header("Location: internal.php");
        exit;
    }

    // Validate if newEmail is empty
    if (empty($newEmail)) {
        $_SESSION['error'] = "Email field cannot be empty.";
        header("Location: internal.php");
        exit;
    }

    // Query to fetch the user details
    $sql = "SELECT first_name FROM internal_users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $user_id); // Assuming user_id is a string
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        // Fetch the user's first name
        $row = $result->fetch_assoc();
        $first_name = $row['first_name'];

        // Generate OTP and store necessary session data
        $otp = generateOTP();
        $_SESSION['otp'] = $otp;
        $_SESSION['otp_timestamp'] = time();
        $_SESSION['otp_expiry'] = $_SESSION['otp_timestamp'] + 300; // 5-minute expiry
        $_SESSION['newEmail'] = $newEmail;

        // Send OTP
        if (sendOTP($newEmail, $first_name, $otp)) {
            header("Location: update_email_verification.php");
            exit;
        } else {
            $_SESSION['error'] = "Failed to send OTP. Please try again.";
            header("Location: internal.php");
            exit;
        }
    } else {
        $_SESSION['error'] = "User not found.";
        header("Location: internal.php");
        exit;
    }

    $stmt->close();
}

