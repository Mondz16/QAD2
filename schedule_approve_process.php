<?php
include 'connection.php';
include 'token_storage.php'; // Include token storage functions

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Google\Client;
use Google\Service\Calendar;

require 'vendor/autoload.php'; // Ensure PHPMailer and Google Client libraries are autoloaded

session_start();

// Capture the parameters from the form or session
$schedule_id = $_POST['schedule_id'] ?? $_SESSION['schedule_id'] ?? null;
$college = $_POST['college'] ?? $_SESSION['college'] ?? null;
$college_code = $_POST['college_code'] ?? $_SESSION['college_code'] ?? null;

// Store these parameters in the session if they were received via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $_SESSION['schedule_id'] = $schedule_id;
    $_SESSION['college'] = $college;
    $_SESSION['college_code'] = $college_code;
}

// Check if the necessary data is present
if (!$schedule_id || !$college || !$college_code) {
    $error_message = "This page should only be accessed through a valid form submission.";
} else {
    // Initialize Google Client
    $client = new Client();
    $client->setAuthConfig('./secure/credentials.json'); // Path to your credentials.json file
    $client->addScope(Google\Service\Calendar::CALENDAR);

    // Check if the access token is already stored in the session or token storage
    if (!isset($_SESSION['access_token'])) {
        // Redirect to OAuth2 callback if the access token is not available
        header('Location: oauth2callback.php');
        exit();
    } else {
        // Set the access token for the client
        $client->setAccessToken($_SESSION['access_token']);
        
        // If the token has expired, refresh it
        if ($client->isAccessTokenExpired()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            $_SESSION['access_token'] = $client->getAccessToken();
            storeToken($client->getAccessToken()); // Store the refreshed token
        }

        // Continue with the schedule approval process
        if ($_SERVER["REQUEST_METHOD"] == "POST" || isset($_SESSION['schedule_id'])) {
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
                    // Fetch schedule details, including the zoom link
                    $sql_get_schedule_details = "SELECT s.college_code, s.schedule_date, s.schedule_time, s.zoom, p.program_name, s.level_applied, s.program_id 
                                                 FROM schedule s 
                                                 JOIN program p ON s.program_id = p.id 
                                                 WHERE s.id = ?";
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
                    $zoom_link = $schedule_details['zoom'];

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
                            <b>Scheduled Time:</b> $formatted_schedule_time<br>
                            <b>Zoom Link:</b> <a href='{$zoom_link}'>{$zoom_link}</a><br><br>
                            Best regards,<br>
                            USeP - Quality Assurance Division
                        ";

                        $mail->send();
                        $email_success = true;

                        // Google Client for Calendar API
                        $service = new Calendar($client);

                        // Create the events for the scheduled date and two days before
                        $eventDetails = [
                            [
                                'summary' => "UDAS Assessment for {$schedule_details['program_name']} schedule",
                                'start' => date('c', strtotime('-16 days', strtotime($schedule_details['schedule_date'] . ' 08:00:00'))),
                                'end' => date('c', strtotime('-16 days', strtotime($schedule_details['schedule_date'] . ' 17:00:00'))),
                                'description' => "Zoom Link: {$zoom_link}",
                                'location' => $zoom_link
                            ],
                            [
                                'summary' => "UDAS Assessment for {$schedule_details['program_name']} schedule",
                                'start' => date('c', strtotime('-14 days', strtotime($schedule_details['schedule_date'] . ' 08:00:00'))),
                                'end' => date('c', strtotime('-14 days', strtotime($schedule_details['schedule_date'] . ' 17:00:00'))),
                                'description' => "Zoom Link: {$zoom_link}",
                                'location' => $zoom_link
                            ]
                        ];

                        foreach ($eventDetails as $details) {
                            $event = new Calendar\Event([
                                'summary' => $details['summary'],
                                'start' => ['dateTime' => $details['start'], 'timeZone' => 'Asia/Manila'],
                                'end' => ['dateTime' => $details['end'], 'timeZone' => 'Asia/Manila'],
                                'description' => $details['description'],
                                'location' => $details['location']
                            ]);
                            $createdEvent = $service->events->insert('primary', $event);

                            // Log event details to a file
                            $logMessage = "Event created: " . $createdEvent->htmlLink . "\n";
                            file_put_contents('C:/xampp/htdocs/QAD2/secure/calendar_event_log.txt', $logMessage, FILE_APPEND);
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
        }
    }
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

        .popup-text {
            margin: 20px 50px;
            font-size: 17px;
            font-weight: 500;
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
                <div style="height: 25px; width: 0px;"></div>
                <p>Schedule approved successfully. Email notifications have been sent.</p>
            <?php elseif (isset($email_error) && $email_error): ?>
                <img src="images/Error.png" height="100" alt="Error">
                <div style="height: 25px; width: 0px;"></div>
                <?php echo $email_error; ?>              
            <?php else: ?>
                <?php echo isset($error_message) ? $error_message : 'Unknown error.'; ?>
                <div style="height: 25px; width: 0px;"></div>
            <?php endif; ?>
        </div>
        <div style="height: 25px; width: 0px;"></div>
        <a class="btn-hover" href="schedule_college.php?college=<?php echo urlencode($college_name); ?>&college_code=<?php echo urlencode($schedule_details['college_code']); ?>">OKAY</a>
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
