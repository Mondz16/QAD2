<?php
session_start();

$servername = "localhost";
$db_username = "root";
$db_password = "";
$dbname = "qadDB";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Ensure PHPMailer is installed and autoloaded

$conn = new mysqli($servername, $db_username, $db_password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function sendEmailNotification($email, $userId, $action, $firstName, $reason = '') {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'usepqad@gmail.com'; // SMTP username
        $mail->Password = 'ofcx jwfa ghkv hsgz'; // SMTP password
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
            $mail->Body = "Dear $firstName,<br><br>Your registration has been approved.<br><br>User ID: $userId<br><br>Best regards,<br>USeP - Quality Assurance Division";
        } else if ($action == 'reject') {
            $mail->Subject = 'Registration Disapproved';
            $mail->Body = "Dear $firstName,<br><br>Your registration has been disapproved.<br><br>User ID: $userId<br><br>Reason: $reason<br><br>Best regards,<br>USeP - Quality Assurance Division";
        }

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $message = "";
    $message_class = "";
    
    // Determine if this is a bulk or single operation
    if (isset($_POST['ids'])) {
        // Bulk operation
        processBulkRegistrations($conn);
    } else if (isset($_POST['id'])) {
        // Single operation
        processSingleRegistration($conn);
    } else {
        $message = "Invalid request: No user ID provided.";
        $message_class = "error";
    }
}

function processBulkRegistrations($conn) {
    global $message, $message_class;
    
    $ids = explode(',', $_POST['ids']);
    $action = $_POST['action'];
    $reason = isset($_POST['reason']) ? $_POST['reason'] : '';
    $success_count = 0;
    $error_count = 0;

    foreach ($ids as $id) {
        $result = processUser($conn, $id, $action, $reason);
        if ($result) {
            $success_count++;
        } else {
            $error_count++;
        }
    }

    if ($success_count > 0) {
        $message = "$success_count users processed successfully.";
        if ($error_count > 0) {
            $message .= " $error_count users failed due to email notification errors.";
        }
        $message_class = $error_count > 0 ? "warning" : "success";
    } else {
        $message = "Operation failed. All users failed to process due to email notification errors.";
        $message_class = "error";
    }
}

function processSingleRegistration($conn) {
    global $message, $message_class;
    
    $id = $_POST['id'];
    $action = $_POST['action'];
    $reason = isset($_POST['reason']) ? $_POST['reason'] : '';
    
    $result = processUser($conn, $id, $action, $reason);
    
    if ($result) {
        $message = $action == "approve" ? 
            "User approved with User ID: $id" : 
            "User disapproved with ID: $id";
        $message_class = "success";
    } else {
        $message = $action == "approve" ? 
            "User approval failed due to email notification error." : 
            "User rejection failed due to email notification error.";
        $message_class = "error";
    }
}

function processUser($conn, $id, $action, $reason = '') {
    // Determine user type
    $user_type = "";
    $sql = "SELECT * FROM internal_users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $id);
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

    if ($result->num_rows != 1) {
        return false;
    }

    $row = $result->fetch_assoc();
    $email = $row['email'];
    $first_name = $row['first_name'];
    $bb_cccc = substr($id, 3);

    $conn->begin_transaction();

    try {
        if ($action == "approve" || $action == "bulk_approve") {
            // Handle approval
            $table = $user_type == "internal" ? "internal_users" : "external_users";
            
            // Check for existing active user
            $sql_check_active = "SELECT user_id FROM $table WHERE user_id LIKE ? AND status = 'active'";
            $stmt_check_active = $conn->prepare($sql_check_active);
            $like_pattern = '%-' . $bb_cccc;
            $stmt_check_active->bind_param("s", $like_pattern);
            $stmt_check_active->execute();
            $result_check_active = $stmt_check_active->get_result();

            if ($result_check_active->num_rows > 0) {
                $row_active = $result_check_active->fetch_assoc();
                $active_user_id = $row_active['user_id'];
                
                // Deactivate existing active user
                $sql_update_active = "UPDATE $table SET status = 'inactive' WHERE user_id = ?";
                $stmt_update_active = $conn->prepare($sql_update_active);
                $stmt_update_active->bind_param("s", $active_user_id);
                $stmt_update_active->execute();
                $stmt_update_active->close();
            }

            // Activate current user
            $sql_update = "UPDATE $table SET status = 'active', date_added = CURRENT_TIMESTAMP WHERE user_id = ?";
            
        } else if ($action == "reject" || $action == "bulk_reject") {
            // Handle rejection
            $table = $user_type == "internal" ? "internal_users" : "external_users";
            $sql_update = "UPDATE $table SET status = 'inactive', date_added = CURRENT_TIMESTAMP WHERE user_id = ?";
        }

        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("s", $id);

        // Normalize action for email notification
        $email_action = strpos($action, 'bulk_') === 0 ? substr($action, 5) : $action;
        $email_status = sendEmailNotification($email, $id, $email_action, $first_name, $reason);
        
        if ($email_status === true) {
            $stmt_update->execute();
            $stmt_update->close();
            $conn->commit();
            return true;
        } else {
            $conn->rollback();
            return false;
        }
    } catch (Exception $e) {
        $conn->rollback();
        return false;
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Approval</title>
    <link rel="stylesheet" href="index.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600&display=swap">
    <link rel="stylesheet" href="index.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Quicksand", sans-serif;
        }
        body {
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }
        h2 {
            font-size: 24px;
            color: #292D32;
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
        .btn-hover{
            border: 1px solid #AFAFAF;
            text-decoration: none;
            color: black;
            border-radius: 10px;
            padding: 20px 50px;
            font-size: 1rem;
            font-weight: bold;
            text-transform: uppercase;
        }
        .btn-hover:hover {
            background-color: #AFAFAF;
        }
    </style>
</head>

<body>
    <div class="popup-content">
        <div style='height: 50px; width: 0px;'></div>
        <?php if ($message_class === 'error'): ?>
            <img src="images/Error.png" height="100" alt="Error">
        <?php elseif ($message_class === 'success'): ?>
            <img src="images/Success.png" height="100" alt="Success">
        <?php endif; ?>
        <div style="height: 25px; width: 0px;"></div>
        <div class="message <?php echo $message_class; ?>">
            <?php echo $message; ?>
        </div>
        <div style="height: 25px; width: 0px;"></div>
        <a href="registration.php"class="btn-hover">OKAY</a>
        <div style='height: 100px; width: 0px;'></div>
        <div class='hairpop-up'></div>
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
</body>
</html>
