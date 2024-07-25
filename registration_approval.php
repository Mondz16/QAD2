<?php
session_start();

$servername = "localhost";
$db_username = "root";
$db_password = "";
$dbname = "qadDB";

$conn = new mysqli($servername, $db_username, $db_password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Ensure PHPMailer is installed and autoloaded

function sendEmailNotification($email, $userId, $action) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'usepqad@gmail.com'; // SMTP username
        $mail->Password = 'vmvf vnvq ileu tmev'; // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Optional: Disable SSL certificate verification
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        // Recipients
        $mail->setFrom('usepqad@gmail.com', 'USeP - Quality Assurance Division');
        $mail->addAddress($email);

        // Content
        $mail->isHTML(true);
        if ($action == 'approve') {
            $mail->Subject = 'Registration Approved';
            $mail->Body = "Dear User,<br><br>Your registration has been approved.<br><br>User ID: $userId<br><br>Best regards,<br>USeP - Quality Assurance Division";
        } else if ($action == 'reject') {
            $mail->Subject = 'Registration Rejected';
            $mail->Body = "Dear User,<br><br>Your registration has been rejected.<br><br>User ID: $userId<br><br>Best regards,<br>USeP - Quality Assurance Division";
        }

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $action = $_POST['action'];

    $user_type = "";
    $sql = "SELECT * FROM internal_users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $id); // Change "i" to "s" since user_id is a string
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        $sql = "SELECT * FROM external_users WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user_type = "external";
    } else {
        $user_type = "internal";
    }

    $message = "";
    $message_class = "";

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        $email = $row['email'];
        $bb_cccc = substr($id, 3); // Extract bb-cccc part of the user_id

        $conn->begin_transaction();

        if ($action == "approve") {
            if ($user_type == "internal") {
                // Check for existing active user with the same bb-cccc part
                $sql_check_active = "SELECT user_id FROM internal_users WHERE user_id LIKE ? AND status = 'active'";
                $stmt_check_active = $conn->prepare($sql_check_active);
                $like_pattern = '%-' . $bb_cccc;
                $stmt_check_active->bind_param("s", $like_pattern);
                $stmt_check_active->execute();
                $result_check_active = $stmt_check_active->get_result();

                if ($result_check_active->num_rows > 0) {
                    $row_active = $result_check_active->fetch_assoc();
                    $active_user_id = $row_active['user_id'];

                    // Update the existing active user to inactive
                    $sql_update_active = "UPDATE internal_users SET status = 'inactive' WHERE user_id = ?";
                    $stmt_update_active = $conn->prepare($sql_update_active);
                    $stmt_update_active->bind_param("s", $active_user_id);
                    $stmt_update_active->execute();
                    $stmt_update_active->close();
                }

                // Approve the current user
                $sql_update_internal = "UPDATE internal_users SET status = 'active', date_added = CURRENT_TIMESTAMP WHERE user_id = ?";
                $stmt_update_internal = $conn->prepare($sql_update_internal);
                $stmt_update_internal->bind_param("s", $id);

            } elseif ($user_type == "external") {
                // Check for existing active user with the same bb-cccc part
                $sql_check_active = "SELECT user_id FROM external_users WHERE user_id LIKE ? AND status = 'active'";
                $stmt_check_active = $conn->prepare($sql_check_active);
                $like_pattern = '%-' . $bb_cccc;
                $stmt_check_active->bind_param("s", $like_pattern);
                $stmt_check_active->execute();
                $result_check_active = $stmt_check_active->get_result();

                if ($result_check_active->num_rows > 0) {
                    $row_active = $result_check_active->fetch_assoc();
                    $active_user_id = $row_active['user_id'];

                    // Update the existing active user to inactive
                    $sql_update_active = "UPDATE external_users SET status = 'inactive' WHERE user_id = ?";
                    $stmt_update_active = $conn->prepare($sql_update_active);
                    $stmt_update_active->bind_param("s", $active_user_id);
                    $stmt_update_active->execute();
                    $stmt_update_active->close();
                }

                // Approve the current user
                $sql_update_external = "UPDATE external_users SET status = 'active', date_added = CURRENT_TIMESTAMP WHERE user_id = ?";
                $stmt_update_external = $conn->prepare($sql_update_external);
                $stmt_update_external->bind_param("s", $id);
            }

            // Send approval email
            $email_status = sendEmailNotification($email, $id, $action);
            if ($email_status === true) {
                // Commit the transaction if email is sent successfully
                if ($user_type == "internal") {
                    $stmt_update_internal->execute();
                    $stmt_update_internal->close();
                } else {
                    $stmt_update_external->execute();
                    $stmt_update_external->close();
                }
                $conn->commit();
                $message = "User approved with ID: " . $id;
                $message_class = "success";
            } else {
                // Rollback the transaction if email fails
                $conn->rollback();
                $message = "User approval failed due to email notification error.";
                $message_class = "error";
            }

        } else if ($action == "reject") {
            if ($user_type == "internal") {
                $sql_update_internal = "UPDATE internal_users SET status = 'inactive', date_added = CURRENT_TIMESTAMP WHERE user_id = ?";
                $stmt_update_internal = $conn->prepare($sql_update_internal);
                $stmt_update_internal->bind_param("s", $id);
            } else if ($user_type == "external") {
                $sql_update_external = "UPDATE external_users SET status = 'inactive', date_added = CURRENT_TIMESTAMP WHERE user_id = ?";
                $stmt_update_external = $conn->prepare($sql_update_external);
                $stmt_update_external->bind_param("s", $id);
            }

            // Send rejection email
            $email_status = sendEmailNotification($email, $id, $action);
            if ($email_status === true) {
                // Commit the transaction if email is sent successfully
                if ($user_type == "internal") {
                    $stmt_update_internal->execute();
                    $stmt_update_internal->close();
                } else {
                    $stmt_update_external->execute();
                    $stmt_update_external->close();
                }
                $conn->commit();
                $message = "User rejected with ID: " . $id;
                $message_class = "success";
            } else {
                // Rollback the transaction if email fails
                $conn->rollback();
                $message = "User rejection failed due to email notification error.";
                $message_class = "error";
            }
        }
    } else {
        $message = "Invalid registration ID.";
        $message_class = "error";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Operation Result</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600&display=swap">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Quicksand", sans-serif;
        }

        body {
            background-color: #f9f9f9;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }

        .container {
            max-width: 750px;
            padding: 24px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        h2 {
            font-size: 24px;
            color: #973939;
            margin-bottom: 20px;
        }

        .message {
            margin-bottom: 20px;
            font-size: 18px;
        }

        .success {
            color: green;
        }

        .error {
            color: red;
        }

        .button-primary {
            background-color: #2cb84f;
            color: #fff;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 4px;
            margin-top: 10px;
            color: white;
            font-size: 16px;
        }

        .button-primary:hover {
            background-color: #259b42;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>Operation Result</h2>
        <div class="message <?php echo $message_class; ?>">
            <?php echo $message; ?>
        </div>
        <button class="button-primary" onclick="window.location.href='registration.php'">OK</button>
    </div>
</body>
</html>
