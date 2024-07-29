<?php
session_start();

$servername = "localhost";
$db_username = "root";
$db_password = "";
$dbname = "qadDB";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

$conn = new mysqli($servername, $db_username, $db_password, $dbname);

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
    $stmt->close(); // Close the statement after fetching the data

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
    $stmt->close(); // Close the statement after fetching the data

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
    $stmt->close(); // Close the statement after fetching the data

    // Prepare email body
    $subject = "Orientation Request " . ucfirst($action);
    $body .= "<p>The orientation for the schedule detail below has been " . ($action == "approve" ? "approved." : "denied.") . "</p>";
    $body = "<p>Schedule Details:</p>";
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
            $message = "Orientation request has been successfully " . ($action == "approve" ? "approved." : "denied.") . " Email notifications sent.";
        } else {
            $message = "Failed to update the orientation request.";
        }
        $stmt->close(); // Close the statement after executing the update
    } else {
        $message = "Orientation request could not be updated because the email notification could not be sent due to an internet problem. Please try again.";
    }

    $conn->close(); // Close the connection after finishing

    echo "<!DOCTYPE html>
<html lang=\"en\">
<head>
    <meta charset=\"UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <title>Operation Result</title>
    <link rel=\"stylesheet\" href=\"https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600&display=swap\">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: \"Quicksand\", sans-serif;
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
    <div class=\"container\">
        <h2>Operation Result</h2>
        <div class=\"message " . (strpos($message, 'successfully') !== false ? 'success' : 'error') . "\">
            $message
        </div>
        <button class=\"button-primary\" onclick=\"window.location.href='orientation.php'\">OK</button>
    </div>
</body>
</html>";

} else {
    header("Location: orientation.php");
    exit();
}
?>
