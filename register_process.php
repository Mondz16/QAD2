<?php
session_start();
require 'connection.php';
require 'vendor/autoload.php'; // Ensure the autoload file is correctly referenced

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $type = $_POST['type'];
    $prefix = $_POST['prefix'];
    $first_name = $_POST['first_name'];
    $middle_initial = $_POST['middle_initial'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $gender = $_POST['gender'] === 'Others' ? $_POST['gender_others'] : $_POST['gender'];
    $college_code = isset($_POST['college']) ? $_POST['college'] : null;
    $company_code = isset($_POST['company']) ? $_POST['company'] : null;

    // Set profile_picture to default value
    $profile_picture = "Profile Pictures/placeholder.jpg";
    $e_sign_agreement = 'agreed';

    if ($password !== $confirm_password) {
        echo "Passwords do not match!";
        exit;
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $otp = rand(100000, 999999); // Generate a 6-digit OTP
    $hashed_otp = password_hash($otp, PASSWORD_DEFAULT); // Hash the OTP

    include 'connection.php';

    function check_existing_email($conn, $email) {
        $stmt = $conn->prepare("
        SELECT email, otp, status 
        FROM internal_users 
        WHERE email = ? 
        UNION 
        SELECT email, otp, status 
        FROM external_users 
        WHERE email = ? 
        UNION 
        SELECT email, otp, 'active' as status 
        FROM admin 
        WHERE email = ?");

        $stmt->bind_param("sss", $email, $email, $email);
        $stmt->execute();
        return $stmt->get_result();
    }

    // Check if email already exists
    $result_email = check_existing_email($conn, $email);

    if ($result_email->num_rows > 0) {
        $row_email = $result_email->fetch_assoc();
        $otp_status = $row_email['otp'];
        $status = $row_email['status'];

        if ($otp_status !== 'verified') {
            echo "<script>
                    alert('This account is already registered but not verified. Redirecting to OTP verification page.');
                    window.location.href = 'verify_otp.php?email=" . urlencode($email) . "&type=" . urlencode($type) . "';
                  </script>";
            exit;
        }

        if ($status == 'inactive') {
            echo "<script>
                    if (confirm('This account is already registered in the system but inactive. Would you like to apply again?')) {
                        window.location.href = 'register_process_reactivation.php?type=$type&email=$email';
                    } else {
                        window.location.href = 'register.php';
                    }
                  </script>";
        } elseif ($status == 'pending') {
            echo "<script>alert('This account is already registered in the system but pending. Please wait for the admin to approve.');
                  window.location.href = 'register.php';
                  </script>";
        } elseif ($status == 'active') {
            echo "<script>alert('This account is already registered in the system and active.');
                  window.location.href = 'login.php';
                  </script>";
        }
        exit;
    }

    if ($type == 'internal') {
        $table = "internal_users";

        // Insert into internal_users table
        $stmt_internal = $conn->prepare("INSERT INTO $table (college_code, prefix, first_name, middle_initial, last_name, email, password, gender, e_sign_agreement, profile_picture, otp) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_internal->bind_param("sssssssssss", $college_code, $prefix, $first_name, $middle_initial, $last_name, $email, $hashed_password, $gender, $e_sign_agreement, $profile_picture, $hashed_otp);
        if ($stmt_internal->execute()) {
            $inserted_id = $conn->insert_id; // Get the last inserted ID
            $unique_number = str_pad($inserted_id, 4, "0", STR_PAD_LEFT);
            $user_id = $college_code . "-11-" . $unique_number;

            // Update the record with the generated user_id
            $stmt_update = $conn->prepare("UPDATE $table SET user_id = ? WHERE id = ?");
            $stmt_update->bind_param("si", $user_id, $inserted_id);
            $stmt_update->execute();

            // Send OTP Email
            sendOTPEmail($email, $otp); // Send the plain OTP to the user
            header("Location: verify_otp.php?email=" . urlencode($email) . "&type=" . urlencode($type));
            exit();
        } else {
            echo "Error: " . $stmt_internal->error;
        }
        $stmt_internal->close();
    } elseif ($type == 'external') {
        $table = "external_users";

        // Insert into external_users table
        $stmt_external = $conn->prepare("INSERT INTO $table (company_code, prefix, first_name, middle_initial, last_name, email, password, gender, e_sign_agreement, profile_picture, otp) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_external->bind_param("sssssssssss", $company_code, $prefix, $first_name, $middle_initial, $last_name, $email, $hashed_password, $gender, $e_sign_agreement, $profile_picture, $hashed_otp);
        if ($stmt_external->execute()) {
            $inserted_id = $conn->insert_id; // Get the last inserted ID
            $unique_number = str_pad($inserted_id, 4, "0", STR_PAD_LEFT);
            $user_id = $company_code . "-22-" . $unique_number;

            // Update the record with the generated user_id
            $stmt_update = $conn->prepare("UPDATE $table SET user_id = ? WHERE id = ?");
            $stmt_update->bind_param("si", $user_id, $inserted_id);
            $stmt_update->execute();

            // Send OTP Email
            sendOTPEmail($email, $otp); // Send the plain OTP to the user
            header("Location: verify_otp.php?email=" . urlencode($email) . "&type=" . urlencode($type));
            exit();
        } else {
            echo "Error: " . $stmt_external->error;
        }
        $stmt_external->close();
    } else {
        echo "Invalid registration type.";
    }

    $conn->close();
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
        $mail->Body    = 'Your OTP for email verification is: <b>' . $otp . '</b>';

        $mail->send();
    } catch (Exception $e) {
        echo 'Message could not be sent. Mailer Error: ', $mail->ErrorInfo;
    }
}
?>
