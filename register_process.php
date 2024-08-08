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

    function check_existing_user($conn, $first_name, $middle_initial, $last_name, $email) {
        $stmt = $conn->prepare("
        SELECT status, otp 
        FROM internal_users 
        WHERE first_name = ? AND middle_initial = ? AND last_name = ? AND email = ?
        UNION 
        SELECT status, otp 
        FROM external_users 
        WHERE first_name = ? AND middle_initial = ? AND last_name = ? AND email = ?
        UNION 
        SELECT 'active' as status, otp 
        FROM admin 
        WHERE first_name = ? AND middle_initial = ? AND last_name = ? AND email = ?");

        $stmt->bind_param("ssssssssssss",
        $first_name, $middle_initial, $last_name, $email,
        $first_name, $middle_initial, $last_name, $email,
        $first_name, $middle_initial, $last_name, $email
        );
        $stmt->execute();
        return $stmt->get_result();
    }

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
        $stmt_internal = $conn->prepare("INSERT INTO $table (user_id, college_code, prefix, first_name, middle_initial, last_name, email, password, gender, e_sign_agreement, profile_picture, otp) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_internal->bind_param("ssssssssssss", $user_id, $college_code, $prefix, $first_name, $middle_initial, $last_name, $email, $hashed_password, $gender, $e_sign_agreement, $profile_picture, $hashed_otp);
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
        $stmt_external = $conn->prepare("INSERT INTO $table (user_id, company_code, prefix, first_name, middle_initial, last_name, email, password, gender, e_sign_agreement, profile_picture, otp) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_external->bind_param("ssssssssssss", $user_id, $company_code, $prefix, $first_name, $middle_initial, $last_name, $email, $hashed_password, $gender, $e_sign_agreement, $profile_picture, $hashed_otp);
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
