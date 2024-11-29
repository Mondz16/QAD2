<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
include 'connection.php';
session_start();

function sendEmail($to, $subject, $message) {
    $mail = new PHPMailer(true);

    try {
        //Server settings
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

        //Recipients
        $mail->setFrom('usepqad@example.com', 'USeP - Quality Assurance Division');
        $mail->addAddress($to);  // Add a recipient

        //Content
        $mail->isHTML(true);  // Set email format to HTML
        $mail->Subject = $subject;
        $mail->Body    = nl2br($message);
        $mail->AltBody = $message;

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

$message = "";
$status = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'];
    $new_user_id = $_POST['new_user_id'];
    $previous_user_id = $_POST['previous_user_id'];

    if ($action == 'accept') {
        $to = $_POST['new_user_email'];
        $subject = "College Transfer Accepted";
        $message = "Dear " . $_POST['new_user_name'] . ",\n\nYour request for college transfer has been accepted.\nYour new user ID is " . $new_user_id . ".\n\nBest regards,\nUSeP - Quality Assurance Division";

        if (sendEmail($to, $subject, $message)) {
            $conn->begin_transaction();
            try {
                // Update the new user's status to 'active'
                $sql_accept = "UPDATE internal_users SET status = 'active' WHERE user_id = ?";
                $stmt_accept = $conn->prepare($sql_accept);
                $stmt_accept->bind_param("s", $new_user_id);
                $stmt_accept->execute();
                $stmt_accept->close();

                // Update the previous user's status to 'inactive'
                $sql_inactivate = "UPDATE internal_users SET status = 'inactive' WHERE user_id = ?";
                $stmt_inactivate = $conn->prepare($sql_inactivate);
                $stmt_inactivate->bind_param("s", $previous_user_id);
                $stmt_inactivate->execute();
                $stmt_inactivate->close();

                $conn->commit();

                $message = "Transfer request accepted successfully.";
                $status = "success";
            } catch (Exception $e) {
                $conn->rollback();
                $message = "Error processing request.";
                $status = "error";
            }
        } else {
            $message = "Failed to send email. Transfer request not processed.";
            $status = "error";
        }
    } elseif ($action == 'reject') {
        $reject_reason = $_POST['reject_reason'];

        $sql_user = "SELECT email, CONCAT(first_name, ' ', middle_initial, '. ', last_name) AS name FROM internal_users WHERE user_id = ?";
        $stmt_user = $conn->prepare($sql_user);
        $stmt_user->bind_param("s", $new_user_id);
        $stmt_user->execute();
        $stmt_user->bind_result($email, $name);
        $stmt_user->fetch();
        $stmt_user->close();

        $to = $email;
        $subject = "College Transfer Disapproved";
        $message = "Dear " . $name . ",\n\nYour request for college transfer has been disapproved.\nReason: " . $reject_reason . "\n\nBest regards,\nUSeP - Quality Assurance Division";

        if (sendEmail($to, $subject, $message)) {
            $conn->begin_transaction();
            try {
                // Update the new user's status to 'inactive'
                $sql_reject = "UPDATE internal_users SET status = 'inactive' WHERE user_id = ?";
                $stmt_reject = $conn->prepare($sql_reject);
                $stmt_reject->bind_param("s", $new_user_id);
                $stmt_reject->execute();
                $stmt_reject->close();

                $conn->commit();

                $message = "Transfer request disapproved successfully.";
                $status = "success";
            } catch (Exception $e) {
                $conn->rollback();
                $message = "Error processing request.";
                $status = "error";
            }
        } else {
            $message = "Failed to send email. Transfer request not processed.";
            $status = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Operation Result</title>
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
        <img src="images/<?php echo ucfirst($status); ?>.png" height="100" alt="<?php echo ucfirst($status); ?>">
        <div style="height: 25px; width: 0px;"></div>
        <div class="message <?php echo $status; ?>">
            <?php echo $message; ?>
        </div>
        <div style="height: 50px; width: 0px;"></div>
        <a href="college_transfer.php" class="btn-hover">OKAY</a>
        <div style='height: 100px; width: 0px;'></div>
        <div class='hairpop-up'></div>
    </div>
</body>
</html>
