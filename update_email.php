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
        $mail->Password = 'vmvf vnvq ileu tmev';
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

    $user_type = "";
    $sql = "SELECT * FROM internal_users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $user_id); // Change "i" to "s" since user_id is a string
    $stmt->execute();
    $result = $stmt->get_result();

    if ($newEmail === $email) {
        header("Location: internal.php");
        exit;
    }
    if (empty($newEmail)) {
        $_SESSION['error'] = "Email field cannot be empty.";
        header("Location: internal.php");
        exit;
    } else {
        $otp = generateOTP();
        $_SESSION['otp'] = $otp;
        $_SESSION['otp_timestamp'] = time();
        $_SESSION['otp_expiry'] = $_SESSION['otp_timestamp'] + 300;
        $_SESSION['newEmail'] = $newEmail;


        if ($result->num_rows == 1) {
            $first_name = $row['first_name'];

            if (sendOTP($newEmail, $first_name, $otp)) {
                header("Location: update_email_verification.php");
                exit;
            } else {
                $_SESSION['error'] = "Failed to send OTP. Please try again.";
                header("Location: internal.php");
                exit;
            }
        }
    }
}
