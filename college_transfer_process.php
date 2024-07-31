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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'];
    $new_user_id = $_POST['new_user_id'];
    $previous_user_id = $_POST['previous_user_id'];

    if ($action == 'accept') {
        $to = $_POST['new_user_email'];
        $subject = "College Transfer Accepted";
        $message = "Dear " . $_POST['new_user_name'] . ",\n\nYour request for college transfer has been accepted.\nYour new user ID is " . $new_user_id . ".\n\nBest regards,\nUSeP - Quality Assurance Division";

        if (sendEmail($to, $subject, $message)) {
            // Email sent successfully, update database
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

                echo "Transfer request accepted. <a href='college_transfer.php'>Back to Transfer Requests</a>";
            } catch (Exception $e) {
                $conn->rollback();
                echo "Error processing request. <a href='college_transfer.php'>Back to Transfer Requests</a>";
            }
        } else {
            echo "Failed to send email. Transfer request not processed. <a href='college_transfer.php'>Back to Transfer Requests</a>";
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
        $subject = "College Transfer Rejected";
        $message = "Dear " . $name . ",\n\nYour request for college transfer has been rejected.\nReason:" . $reject_reason . "\n\nBest regards,\nUSeP - Quality Assurance Division";

        if (sendEmail($to, $subject, $message)) {
            // Email sent successfully, update database
            $conn->begin_transaction();

            try {
                // Update the new user's status to 'inactive'
                $sql_reject = "UPDATE internal_users SET status = 'inactive' WHERE user_id = ?";
                $stmt_reject = $conn->prepare($sql_reject);
                $stmt_reject->bind_param("s", $new_user_id);
                $stmt_reject->execute();
                $stmt_reject->close();

                $conn->commit();

                echo "Transfer request rejected. <a href='college_transfer.php'>Back to Transfer Requests</a>";
            } catch (Exception $e) {
                $conn->rollback();
                echo "Error processing request. <a href='college_transfer.php'>Back to Transfer Requests</a>";
            }
        } else {
            echo "Failed to send email. Transfer request not processed. <a href='college_transfer.php'>Back to Transfer Requests</a>";
        }
    }
}
?>