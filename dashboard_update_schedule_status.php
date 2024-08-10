<?php
include 'connection.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Ensure PHPMailer is installed and autoloaded

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $scheduleId = $_POST['id'];
    $status = $_POST['status'];

    // Debugging output
    error_log("Received ID: $scheduleId, Status: $status");

    if (in_array($status, ['failed', 'passed'])) {
        // Update the schedule status
        $stmt = $conn->prepare("UPDATE schedule SET schedule_status = ?, status_date = NOW() WHERE id = ?");
        $stmt->bind_param('si', $status, $scheduleId);

        if ($stmt->execute()) {
            // Notify internal users of the team
            notifyInternalUsers($scheduleId, $status);

            echo 'success';
            header('Location: dashboard.php'); // Redirect after success
        } else {
            error_log("Failed to execute query: " . $stmt->error); // Log any query error
            echo 'error';
        }

        $stmt->close();
    } else {
        error_log("Invalid status received: $status");
        echo 'invalid status';
    }
} else {
    error_log("Invalid request method");
    echo 'invalid request';
}

$conn->close();

// Function to notify internal users
function notifyInternalUsers($scheduleId, $status) {
    global $conn;

    // Retrieve internal users associated with the schedule
    $sql = "SELECT iu.email, iu.first_name, iu.last_name
            FROM team t
            JOIN internal_users iu ON t.internal_users_id = iu.user_id
            WHERE t.schedule_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $scheduleId);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $email = $row['email'];
        $fullName = $row['first_name'] . ' ' . $row['last_name'];

        // Send email to each internal user
        sendEmailNotification($email, $fullName, $status);
    }

    $stmt->close();
}

// Function to send email notification using PHPMailer
function sendEmailNotification($email, $fullName, $status) {
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
        $mail->Subject = 'Schedule Result Notification';

        if ($status == 'passed') {
            $mail->Body = "Dear $fullName,<br><br>The schedule you were involved in has been marked as <strong>Passed</strong>.<br><br>Best regards,<br>USeP - Quality Assurance Division";
        } else if ($status == 'failed') {
            $mail->Body = "Dear $fullName,<br><br>The schedule you were involved in has been marked as <strong>Failed</strong>.<br><br>Best regards,<br>USeP - Quality Assurance Division";
        }

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email could not be sent to $email. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>
