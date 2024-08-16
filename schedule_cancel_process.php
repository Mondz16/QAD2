<?php
include 'connection.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Ensure PHPMailer is installed and autoloaded

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['schedule_id']) && isset($_POST['college'])) {
    $schedule_id = mysqli_real_escape_string($conn, $_POST['schedule_id']);
    $college_code = mysqli_real_escape_string($conn, $_POST['college']);
    $cancel_reason = mysqli_real_escape_string($conn, $_POST['cancel_reason']); // Get the cancellation reason

    // Set the timezone to Philippines
    date_default_timezone_set('Asia/Manila');
    $currentDateTime = date('Y-m-d H:i:s'); // Get the current date and time

    // Start transaction
    $conn->begin_transaction();

    try {
        // Fetch schedule details
        $sql_get_schedule_details = "SELECT s.college_code, s.schedule_date, s.schedule_time, p.program_name, s.level_applied FROM schedule s JOIN program p ON s.program_id = p.id WHERE s.id = ?";
        $stmt_get_schedule_details = $conn->prepare($sql_get_schedule_details);
        $stmt_get_schedule_details->bind_param("i", $schedule_id);
        $stmt_get_schedule_details->execute();
        $schedule_result = $stmt_get_schedule_details->get_result();
        $schedule_details = $schedule_result->fetch_assoc();

        // Fetch college name and email
        $sql_get_college_details = "SELECT college_name, college_email FROM college WHERE code = ?";
        $stmt_get_college_details = $conn->prepare($sql_get_college_details);
        $stmt_get_college_details->bind_param("s", $schedule_details['college_code']);
        $stmt_get_college_details->execute();
        $college_result = $stmt_get_college_details->get_result();
        $college_details = $college_result->fetch_assoc();
        $college_name = $college_details['college_name'];
        $college_email = $college_details['college_email'];

        // Format the existing schedule date and time
        $formatted_schedule_date = date("F j, Y", strtotime($schedule_details['schedule_date']));
        $formatted_schedule_time = date("g:i A", strtotime($schedule_details['schedule_time']));

        // Fetch team leader and team members' email addresses
        $sql_get_emails = "SELECT iu.email FROM team t JOIN internal_users iu ON t.internal_users_id = iu.user_id WHERE t.schedule_id = ?";
        $stmt_get_emails = $conn->prepare($sql_get_emails);
        $stmt_get_emails->bind_param("i", $schedule_id);
        $stmt_get_emails->execute();
        $result = $stmt_get_emails->get_result();

        // Initialize PHPMailer
        $mail = new PHPMailer(true);
        $email_success = false;
        $email_error = '';

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com'; // Set the SMTP server to send through
            $mail->SMTPAuth = true;
            $mail->Username = 'usepqad@gmail.com'; // SMTP username
            $mail->Password = 'vmvf vnvq ileu tmev'; // SMTP password (App Password if 2FA enabled)
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
            $mail->addReplyTo('usepqad@gmail.com', 'Information');

            // Add college email
            $mail->addAddress($college_email);

            // Add team members' emails
            while ($row = $result->fetch_assoc()) {
                $mail->addAddress($row['email']);
            }

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Schedule Cancelled';
            $mail->Body = "
                Dear Team,<br><br>
                The schedule below has been cancelled with the following details:<br>
                <b>College:</b> {$college_name}<br>
                <b>Program:</b> {$schedule_details['program_name']}<br>
                <b>Level Applied:</b> {$schedule_details['level_applied']}<br>
                <b>Scheduled Date:</b> $formatted_schedule_date<br>
                <b>Scheduled Time:</b> $formatted_schedule_time<br>
                <b>Reason:</b> $cancel_reason<br><br>
                Best regards,<br>
                USeP - Quality Assurance Division
            ";

            // Attempt to send the email
            $mail->send();

            // If the email is sent successfully, proceed with updating the database
            // Update the schedule status to "cancelled"
            $sql_update_status = "UPDATE schedule SET schedule_status = 'cancelled', status_date = ? WHERE id = ?";
            $stmt_update_status = $conn->prepare($sql_update_status);
            $stmt_update_status->bind_param("si", $currentDateTime, $schedule_id);
            $stmt_update_status->execute();

            // Update the status of team members and leader to "cancelled"
            $sql_update_team_status = "UPDATE team SET status = 'cancelled' WHERE schedule_id = ?";
            $stmt_update_team_status = $conn->prepare($sql_update_team_status);
            $stmt_update_team_status->bind_param("i", $schedule_id);
            $stmt_update_team_status->execute();

            // Commit the transaction
            $conn->commit();
            $email_success = true;

        } catch (Exception $e) {
            // If email sending fails, roll back the transaction
            $conn->rollback();
            $email_error = "Schedule cancellation and email notification failed due to an error: " . $e->getMessage();
        }

        $stmt_get_emails->close();
        $stmt_get_schedule_details->close();
        $stmt_get_college_details->close();
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Transaction failed: " . $e->getMessage();
    }

    $conn->close();
} else {
    $error_message = "This page should only be accessed through a valid form submission.";
}

// Display the operation result
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

        .popup {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
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
        <div class="message">
            <?php if (isset($email_success) && $email_success): ?>
                <img src="images/Success.png" height="100" alt="Success">
                <div style="height: 20px; width: 0px;"></div>
                <div style="height: 20px; width: 0px;"></div>
                <span>Schedule cancelled successfully.<br>Email notifications have been sent.</span>
                <div style="height: 25px; width: 0px;"></div>
            <?php elseif (isset($email_error) && $email_error): ?>
                <img src="images/Error.png" height="100" alt="Error">
                <div style="height: 20px; width: 0px;"></div>
                <?php echo $email_error; ?>
            <?php else: ?>
                <img src="images/Error.png" height="100" alt="Error">
                <div style="height: 20px; width: 0px;"></div>
                <?php echo isset($error_message) ? $error_message : 'Unknown error.'; ?>
            <?php endif; ?>
        </div>
        <div style="height: 50px; width: 0px;"></div>
        <a class="btn-hover" href="schedule_college.php?college=<?php echo urlencode($college_name); ?>&college_code=<?php echo urlencode($college_code); ?>">OKAY</a>
        <div style='height: 100px; width: 0px;'></div>
        <div class='hairpop-up'></div>
    </div>
</body>
</html>
