<?php
include 'connection.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Ensure PHPMailer is installed and autoloaded

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['schedule_id'])) {
    $schedule_id = mysqli_real_escape_string($conn, $_POST['schedule_id']);
    $new_date = mysqli_real_escape_string($conn, $_POST['new_date']);
    $new_time = mysqli_real_escape_string($conn, $_POST['new_time']);

    // Format the date and time
    $formatted_date = date("F j, Y", strtotime($new_date));
    $formatted_time = date("g:i A", strtotime($new_time));

    // Start transaction
    $conn->begin_transaction();

    try {
        // Update schedule with new date and time
        $sql_update_schedule = "UPDATE schedule SET schedule_date = ?, schedule_time = ? WHERE id = ?";
        $stmt_update_schedule = $conn->prepare($sql_update_schedule);
        $stmt_update_schedule->bind_param("ssi", $new_date, $new_time, $schedule_id);

        if ($stmt_update_schedule->execute()) {
            $college_code = isset($_POST['college']) ? mysqli_real_escape_string($conn, $_POST['college']) : '';

            // If schedule update is successful, update team status
            $new_status = 'pending'; // or whatever logic you have for setting the new status

            $sql_update_team_status = "UPDATE team SET status = ? WHERE schedule_id = ?";
            $stmt_update_team_status = $conn->prepare($sql_update_team_status);
            $stmt_update_team_status->bind_param("si", $new_status, $schedule_id);

            if ($stmt_update_team_status->execute()) {
                // Fetch schedule details
                $sql_get_schedule_details = "SELECT s.college_code, p.program_name, s.level_applied FROM schedule s JOIN program p ON s.program_id = p.id WHERE s.id = ?";
                $stmt_get_schedule_details = $conn->prepare($sql_get_schedule_details);
                $stmt_get_schedule_details->bind_param("i", $schedule_id);
                $stmt_get_schedule_details->execute();
                $schedule_result = $stmt_get_schedule_details->get_result();
                $schedule_details = $schedule_result->fetch_assoc();

                // Fetch college email
                $sql_get_college_email = "SELECT college_email FROM college WHERE code = ?";
                $stmt_get_college_email = $conn->prepare($sql_get_college_email);
                $stmt_get_college_email->bind_param("s", $schedule_details['college_code']);
                $stmt_get_college_email->execute();
                $college_result = $stmt_get_college_email->get_result();
                $college_email_row = $college_result->fetch_assoc();
                $college_email = $college_email_row['college_email'];

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
                    $mail->Subject = 'Schedule Rescheduled';
                    $mail->Body = "
                        Dear Team,<br><br>
                        Your schedule has been rescheduled with the following details:<br>
                        <b>College:</b> {$schedule_details['college_code']}<br>
                        <b>Program:</b> {$schedule_details['program_name']}<br>
                        <b>Level Applied:</b> {$schedule_details['level_applied']}<br>
                        <b>New Date:</b> $formatted_date<br>
                        <b>New Time:</b> $formatted_time<br><br>
                        Best regards,<br>
                        USeP - Quality Assurance Division
                    ";

                    $mail->send();
                    $conn->commit();
                    $email_success = true;
                } catch (Exception $e) {
                    $conn->rollback();
                    $email_error = "Schedule update and email notification failed due to internet problem.";
                }

                $stmt_get_emails->close();
                $stmt_get_schedule_details->close();
                $stmt_get_college_email->close();
            } else {
                $conn->rollback();
                $error_message = "Error updating team status: " . $conn->error;
            }
            $stmt_update_team_status->close();
        } else {
            $conn->rollback();
            $error_message = "Error updating schedule: " . $conn->error;
        }
        $stmt_update_schedule->close();
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
            text-decoration: none;
            display: inline-block;
        }

        .button-primary:hover {
            background-color: #259b42;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Operation Result</h2>
        <div class="message">
            <?php if (isset($email_success) && $email_success): ?>
                Schedule updated successfully. Email notifications have been sent.
            <?php elseif (isset($email_error) && $email_error): ?>
                <?php echo $email_error; ?>
            <?php else: ?>
                <?php echo isset($error_message) ? $error_message : 'Unknown error.'; ?>
            <?php endif; ?>
        </div>
        <a class="button-primary" href="schedule_college.php?college=<?php echo urlencode($college_code); ?>#">OK</a>
    </div>
</body>
</html>
