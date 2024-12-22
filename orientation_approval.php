<?php
session_start();
include 'connection.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function sendEmail($recipients, $subject, $body) {
    $mail = new PHPMailer(true);
    try {
        // Server settings
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

        // Recipients
        $mail->setFrom('usepqad@gmail.com', 'USeP - Quality Assurance Division');
        foreach ($recipients as $recipient) {
            $mail->addAddress($recipient);
        }

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $action = $_POST['action'];
    $reason = isset($_POST['reason']) ? $_POST['reason'] : '';

    // Fetch schedule_id from orientation
    $sql = "SELECT schedule_id, orientation_date, orientation_time, orientation_type FROM orientation WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($schedule_id, $orientation_date, $orientation_time, $orientation_type);
    $stmt->fetch();
    $stmt->close();

    // Format the date and time
    $formatted_orientation_date = date("F j, Y", strtotime($orientation_date));
    $formatted_orientation_time = date("g:i A", strtotime($orientation_time));

    // Fetch schedule details
    $sql = "SELECT s.schedule_date, s.schedule_time, s.level_applied, s.schedule_status, 
                   p.program_name, c.college_name, c.college_campus, c.college_email, c.code AS college_code
            FROM schedule s
            JOIN program p ON s.program_id = p.id
            JOIN college c ON s.college_code = c.code
            WHERE s.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $schedule_id);
    $stmt->execute();
    $stmt->bind_result($schedule_date, $schedule_time, $level_applied, $schedule_status, 
                       $program_name, $college_name, $college_campus, $college_email, $college_code);
    $stmt->fetch();
    $stmt->close();

    // Format the date and time
    $formatted_schedule_date = date("F j, Y", strtotime($schedule_date));
    $formatted_schedule_time = date("g:i A", strtotime($schedule_time));

    // Fetch internal users' emails
    $sql = "SELECT email FROM internal_users WHERE college_code = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $college_code);
    $stmt->execute();
    $result = $stmt->get_result();

    $recipients = [$college_email];
    while ($row = $result->fetch_assoc()) {
        $recipients[] = $row['email'];
    }
    $stmt->close();

    // Prepare email body
    $subject = "Orientation Request " . ucfirst($action);
    $body = "<p>The orientation for the schedule detail below has been " . ($action == "approve" ? "approved." : "denied.") . "</p>";
    $body .= "<p>Schedule Details:</p>";
    $body .= "<p><strong>Program:</strong> " . htmlspecialchars($program_name) . "</p>";
    $body .= "<p><strong>College:</strong> " . htmlspecialchars($college_name) . " (" . htmlspecialchars($college_campus) . ")</p>";
    $body .= "<p><strong>Date:</strong> " . htmlspecialchars($formatted_schedule_date) . "</p>";
    $body .= "<p><strong>Time:</strong> " . htmlspecialchars($formatted_schedule_time) . "</p>";
    $body .= "<p><strong>Level Applied:</strong> " . htmlspecialchars($level_applied) . "</p>";
    $body .= "<p><strong>Status:</strong> " . htmlspecialchars($schedule_status) . "</p>";

    $body .= "<br><p>Orientation Details:</p>";
    $body .= "<p><strong>Orientation Date:</strong> " . htmlspecialchars($formatted_orientation_date) . "</p>";
    $body .= "<p><strong>Orientation Time:</strong> " . htmlspecialchars($formatted_orientation_time) . "</p>";
    $body .= "<p><strong>Orientation Type:</strong> " . htmlspecialchars($orientation_type) . "</p>";

    if ($action == "deny") {
        $body .= "<p><strong>Reason for Denial:</strong> " . htmlspecialchars($reason) . "</p>";
    }

    // Send email
    if (sendEmail($recipients, $subject, $body)) {
        // Update the orientation status only if email is sent successfully
        if ($action == "approve") {
            $sql = "UPDATE orientation SET orientation_status = 'approved' WHERE id = ?";
        } elseif ($action == "deny") {
            $sql = "UPDATE orientation SET orientation_status = 'denied' WHERE id = ?";
        }

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $status = "success";
            $message = "Orientation request has been successfully " . ($action == "approve" ? "approved." : "denied.") . " Email notifications sent.";
        } else {
            $status = "error";
            $message = "Failed to update the orientation request.";
        }
        $stmt->close();
    } else {
        $status = "error";
        $message = "Orientation request could not be updated because the email notification could not be sent due to an internet problem. Please try again.";
    }

    $conn->close();

    echo "<!DOCTYPE html>
<html lang=\"en\">
<head>
    <meta charset=\"UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <title>Operation Result</title>
    <link rel=\"stylesheet\" href=\"https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600&display=swap\">
    <link rel=\"stylesheet\" href=\"index.css\">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: \"Quicksand\", sans-serif;
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
    <div class=\"popup-content\">
        <div style='height: 50px; width: 0px;'></div>
        <img src=\"images/" . ucfirst($status) . ".png\" height=\"100\" alt=\"" . ucfirst($status) . "\">
        <div style=\"height: 25px; width: 0px;\"></div>
        <div class=\"message " . $status . "\">" . $message . "</div>
        <div style=\"height: 50px; width: 0px;\"></div>
        <a href=\"orientation.php\" class=\"btn-hover\">OKAY</a>
        <div style='height: 100px; width: 0px;'></div>
        <div class='hairpop-up'></div>
    </div>
</body>
</html>";
} else {
    header("Location: orientation.php");
    exit();
}
?>
