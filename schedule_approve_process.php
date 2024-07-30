<?php
include 'connection.php';
include 'token_storage.php'; // Include token storage functions

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Google\Client;
use Google\Service\Calendar;

require 'vendor/autoload.php'; // Ensure PHPMailer and Google Client libraries are autoloaded

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $schedule_id = intval($_POST['schedule_id']);

    // Set the timezone to Philippines
    date_default_timezone_set('Asia/Manila');
    $currentDateTime = date('Y-m-d H:i:s'); // Get the current date and time

    // Start transaction
    $conn->begin_transaction();

    try {
        // Update the schedule_status to "approved"
        $sql_update_status = "UPDATE schedule SET schedule_status = 'approved', status_date = ? WHERE id = ?";
        $stmt_update_status = $conn->prepare($sql_update_status);
        $stmt_update_status->bind_param("si", $currentDateTime, $schedule_id);

        if ($stmt_update_status->execute()) {
            // Fetch schedule details
            $sql_get_schedule_details = "SELECT s.college_code, s.schedule_date, s.schedule_time, p.program_name, s.level_applied, s.program_id FROM schedule s JOIN program p ON s.program_id = p.id WHERE s.id = ?";
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
                $mail->Subject = 'Schedule Approved';
                $mail->Body = "
                    Dear Team,<br><br>
                    The schedule has been approved with the following details:<br>
                    <b>College:</b> {$college_name}<br>
                    <b>Program:</b> {$schedule_details['program_name']}<br>
                    <b>Level Applied:</b> {$schedule_details['level_applied']}<br>
                    <b>Scheduled Date:</b> $formatted_schedule_date<br>
                    <b>Scheduled Time:</b> $formatted_schedule_time<br><br>
                    Best regards,<br>
                    USeP - Quality Assurance Division
                ";

                $mail->send();
                $email_success = true;

                // Google Client for Calendar API
                $client = new Client();
                $client->setAuthConfig('F:/xampp/htdocs/QAD2/secure/credentials.json'); // Path to your credentials.json file
                $client->addScope(Google\Service\Calendar::CALENDAR);
                $accessToken = getToken(); // Retrieve the access token
                $client->setAccessToken($accessToken);

                // Check if the access token is expired and refresh it if needed
                if ($client->isAccessTokenExpired()) {
                    $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                    storeToken($client->getAccessToken()); // Store the new access token
                }

                $service = new Calendar($client);

                // Create the events for the scheduled date and two days before
                $eventDetails = [
                    [
                        'summary' => "UDAS Assessment for {$schedule_details['program_name']} schedule",
                        'start' => date('c', strtotime($schedule_details['schedule_date'] . ' 08:00:00')),
                        'end' => date('c', strtotime($schedule_details['schedule_date'] . ' 17:00:00'))
                    ],
                    [
                        'summary' => "UDAS Assessment for {$schedule_details['program_name']} schedule",
                        'start' => date('c', strtotime('-2 days', strtotime($schedule_details['schedule_date'] . ' 08:00:00'))),
                        'end' => date('c', strtotime('-2 days', strtotime($schedule_details['schedule_date'] . ' 17:00:00')))
                    ]
                ];

                foreach ($eventDetails as $details) {
                    $event = new Calendar\Event([
                        'summary' => $details['summary'],
                        'start' => ['dateTime' => $details['start'], 'timeZone' => 'Asia/Manila'],
                        'end' => ['dateTime' => $details['end'], 'timeZone' => 'Asia/Manila']
                    ]);
                    $createdEvent = $service->events->insert('primary', $event);

                    // Log event details to a file
                    $logMessage = "Event created: " . $createdEvent->htmlLink . "\n";
                    file_put_contents('F:/xampp/htdocs/QAD2/secure/calendar_event_log.txt', $logMessage, FILE_APPEND);
                }

                $conn->commit();
            } catch (Exception $e) {
                $conn->rollback();
                $email_error = "Schedule approval and email notification failed: " . $mail->ErrorInfo . ' ' . $e->getMessage();
            }

            $stmt_get_emails->close();
            $stmt_get_schedule_details->close();
            $stmt_get_college_details->close();
        } else {
            $conn->rollback();
            $error_message = "Error approving schedule: " . $conn->error;
        }
        $stmt_update_status->close();
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
                Schedule approved successfully. Email notifications have been sent.
            <?php elseif (isset($email_error) && $email_error): ?>
                <?php echo $email_error; ?>
            <?php else: ?>
                <?php echo isset($error_message) ? $error_message : 'Unknown error.'; ?>
            <?php endif; ?>
        </div>
        <a class="button-primary" href="schedule_college.php?college=<?php echo urlencode($college_name); ?>&college_code=<?php echo urlencode($schedule_details['college_code']); ?>">OK</a>
    </div>
</body>
</html>
