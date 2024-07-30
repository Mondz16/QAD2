<?php
session_start();
require 'connection.php';
require 'vendor/autoload.php'; // Ensure the autoload file is correctly referenced

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $type = $_POST['type'];
    $first_name = $_POST['first_name'];
    $middle_initial = $_POST['middle_initial'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $college_code = isset($_POST['college']) ? $_POST['college'] : null;
    $company_code = isset($_POST['company']) ? $_POST['company'] : null;

    // File upload handling
    $target_dir = "uploads/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true); // Create directory if it does not exist
    }
    
    if ($_FILES["profile_picture"]["error"] == UPLOAD_ERR_NO_FILE) {
        $profile_picture = "Profile Pictures/placeholder.jpg"; // Use placeholder image
    } else {
        $profile_picture = $target_dir . basename($_FILES["profile_picture"]["name"]);
        if (!move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $profile_picture)) {
            echo "Sorry, there was an error uploading your file.";
            exit;
        }
    }

    if ($password !== $confirm_password) {
        echo "Passwords do not match!";
        exit;
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $otp = rand(100000, 999999); // Generate a 6-digit OTP
    $hashed_otp = password_hash($otp, PASSWORD_DEFAULT); // Hash the OTP

    include 'connection.php';

    function check_existing_user($conn, $first_name, $middle_initial, $last_name, $email) {
        $stmt = $conn->prepare("
            SELECT status 
            FROM internal_users 
            WHERE first_name = ? AND middle_initial = ? AND last_name = ? AND email = ?
            UNION 
            SELECT status 
            FROM external_users 
            WHERE first_name = ? AND middle_initial = ? AND last_name = ? AND email = ?");
        $stmt->bind_param("ssssssss", $first_name, $middle_initial, $last_name, $email, $first_name, $middle_initial, $last_name, $email);
        $stmt->execute();
        return $stmt->get_result();
    }

    function check_existing_email($conn, $email) {
        $stmt = $conn->prepare("SELECT email FROM internal_users WHERE email = ? UNION SELECT email FROM external_users WHERE email = ?");
        $stmt->bind_param("ss", $email, $email);
        $stmt->execute();
        return $stmt->get_result();
    }

    // Check if email already exists
    $result_email = check_existing_email($conn, $email);

    if ($result_email->num_rows > 0) {
        echo "<script>
                alert('This email is already registered in the system. Please use a different email.');
                window.location.href = 'register.php';
              </script>";
        exit;
    }

    $result_existing = check_existing_user($conn, $first_name, $middle_initial, $last_name, $email);

    if ($result_existing->num_rows > 0) {
        $row_existing = $result_existing->fetch_assoc();
        $status = $row_existing['status'];
        if ($status == 'inactive') {
            echo "<script>
                    if (confirm('This information is already registered in the system but inactive. Would you like to apply again?')) {
                        window.location.href = 'register_process_reactivation.php?type=$type&email=$email';
                    } else {
                        window.location.href = 'register.php';
                    }
                  </script>";
        } elseif ($status == 'pending') {
            echo "<script>alert('This information is already registered in the system but pending. Please wait for the admin to approve.');
                  window.location.href = 'register.php';
                  </script>";
        } elseif ($status == 'active') {
            echo "<script>alert('This information is already registered in the system and active.');
                  window.location.href = 'login.php';
                  </script>";
        }
        exit;
    }

    function generate_unique_number($conn, $table) {
        $sql_count_users = "SELECT COUNT(*) AS count FROM $table";
        $result_count_users = $conn->query($sql_count_users);
        $count_users = $result_count_users->fetch_assoc()['count'];

        $unique_number = str_pad($count_users + 1, 4, "0", STR_PAD_LEFT);
        return $unique_number;
    }

    if ($type == 'internal') {
        // Fetch college details based on college_id
        $stmt_college = $conn->prepare("SELECT code, college_name FROM college WHERE code = ?");
        $stmt_college->bind_param("s", $college_code);
        $stmt_college->execute();
        $result_college = $stmt_college->get_result();

        if ($result_college->num_rows > 0) {
            $row_college = $result_college->fetch_assoc();
            $college = $row_college['college_name'];
        } else {
            echo "Invalid college selected.";
            exit;
        }

        $table = "internal_users"; // Table to insert into
        $unique_number = generate_unique_number($conn, $table);
        $user_id = $college_code . "-11-" . $unique_number;

        // Insert into internal_users table
        $stmt_internal = $conn->prepare("INSERT INTO $table (user_id, college_code, first_name, middle_initial, last_name, email, password, profile_picture, otp) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_internal->bind_param("sssssssss", $user_id, $college_code, $first_name, $middle_initial, $last_name, $email, $hashed_password, $profile_picture, $hashed_otp);
        if ($stmt_internal->execute()) {
            // Send OTP Email
            sendOTPEmail($email, $otp); // Send the plain OTP to the user
            header("Location: verify_otp.php?email=" . urlencode($email) . "&type=" . urlencode($type));
            exit();
        } else {
            echo "Error: " . $stmt_internal->error;
        }
        $stmt_internal->close();
    } elseif ($type == 'external') {
        // Fetch company details based on company_id
        $stmt_company = $conn->prepare("SELECT code, company_name FROM company WHERE code = ?");
        $stmt_company->bind_param("s", $company_code);
        $stmt_company->execute();
        $result_company = $stmt_company->get_result();

        if ($result_company->num_rows > 0) {
            $row_company = $result_company->fetch_assoc();
            $company_name = $row_company['company_name'];
        } else {
            echo "Invalid company selected.";
            exit;
        }

        $table = "external_users"; // Table to insert into
        $unique_number = generate_unique_number($conn, $table);
        $user_id = $company_code . "-22-" . $unique_number;

        // Insert into external_users table
        $stmt_external = $conn->prepare("INSERT INTO $table (user_id, company_code, first_name, middle_initial, last_name, email, password, profile_picture, otp) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_external->bind_param("sssssssss", $user_id, $company_code, $first_name, $middle_initial, $last_name, $email, $hashed_password, $profile_picture, $hashed_otp);
        if ($stmt_external->execute()) {
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
?>
