<?php
require 'vendor/autoload.php';
include 'connection.php';
session_start();

use setasign\Fpdi\Fpdi;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $schedule_id = $_POST['schedule_id'];
    $area = $_POST['area'];
    $comments = $_POST['comments'];
    $remarks = $_POST['remarks'];
    $current_datetime = $_POST['current_datetime'];
    $qad_officer = $_POST['qad_officer'];
    $qad_officer_signature = $_FILES['qad_officer_signature'];
    $qad_director = $_POST['qad_director'];

    // Check if file upload was successful
    if (!isset($qad_officer_signature) || $qad_officer_signature['error'] !== UPLOAD_ERR_OK) {
        $message = "Failed to upload signature.";
        displayResult($message);
        exit();
    }

    // Retrieve schedule details from the database
    $sql_schedule = "
        SELECT 
            c.college_name,
            c.college_email,
            p.program_name, 
            s.level_applied, 
            s.schedule_date,
            s.schedule_time
        FROM schedule s
        JOIN college c ON s.college_code = c.code
        JOIN program p ON s.program_id = p.id
        WHERE s.id = ?";
    $stmt_schedule = $conn->prepare($sql_schedule);
    $stmt_schedule->bind_param("i", $schedule_id);
    $stmt_schedule->execute();
    $stmt_schedule->bind_result($college_name, $college_email, $program_name, $level_applied, $schedule_date, $schedule_time);
    $stmt_schedule->fetch();
    $stmt_schedule->close();

    // Retrieve team members for the schedule
    $sql_team = "
        SELECT 
            CONCAT(iu.first_name, ' ', iu.middle_initial, '. ', iu.last_name) AS full_name,
            t.role
        FROM team t
        JOIN internal_users iu ON t.internal_users_id = iu.user_id
        WHERE t.schedule_id = ?";
    $stmt_team = $conn->prepare($sql_team);
    $stmt_team->bind_param("i", $schedule_id);
    $stmt_team->execute();
    $result_team = $stmt_team->get_result();

    $team_leader = '';
    $team_members = [];

    while ($row = $result_team->fetch_assoc()) {
        if ($row['role'] === 'team leader') {
            $team_leader = $row['full_name'];
        } else {
            $team_members[] = $row['full_name'];
        }
    }
    $stmt_team->close();

    // Debugging output to check roles
    error_log("Team Leader: $team_leader");
    error_log("Team Members: " . implode(', ', $team_members));

    // Upload evaluator signature
    $signature_path = 'signatures/' . basename($qad_officer_signature['name']);
    if (!move_uploaded_file($qad_officer_signature['tmp_name'], $signature_path)) {
        $message = "Failed to upload signature.";
        displayResult($message);
        exit();
    }

    // Ensure the UDAS Assessments directory exists
    $directory = 'UDAS Assessments';
    if (!is_dir($directory)) {
        mkdir($directory, 0777, true);
    }

    // Load PDF template
    $pdf = new FPDI();
    $pdf->AddPage();
    $pdf->setSourceFile($directory . '/UDAS-Assessment-Report.pdf');
    $tplIdx = $pdf->importPage(1);
    $pdf->useTemplate($tplIdx);

    // Set font
    $pdf->SetFont('Arial', '', 10);

    // Add dynamic data to the PDF
    $pdf->SetXY(48, 77); // Adjust position
    $pdf->Write(0, $college_name);

    $pdf->SetXY(20, 96); // Adjust position
    $pdf->MultiCell(51, 5, $program_name);

    $pdf->SetXY(81, 99); // Adjust position
    $pdf->Write(0, $level_applied);

    $pdf->SetXY(92, 96.5); // Adjust position
    $pdf->MultiCell(24, 5, $area);

    $pdf->SetXY(50, 82.8); // Adjust position
    $pdf->Write(0, date("F j, Y", strtotime($schedule_date)) . ' at ' . date("g:i A", strtotime($schedule_time)));

    $pdf->SetXY(117, 96.5); // Adjust position
    $pdf->MultiCell(50, 5, $comments);

    $pdf->SetXY(166.7, 96.5); // Adjust position
    $pdf->MultiCell(24, 5, $remarks);

    $pdf->SetXY(36, 167); // Adjust position
    $pdf->Write(0, $current_datetime);

    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetXY(24.5, 201); // Adjust position
    $pdf->Write(0, $qad_officer);

    $pdf->SetFont('Arial', '', 10);

    // Add evaluator signature
    $pdf->Image($signature_path, 24.5, 190, 40); // Adjust position and size

    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetXY(24.5, 252); // Adjust position
    $pdf->Write(0, $qad_director);

    // Save the filled PDF
    $output_path = $directory . '/UDAS-Assessment-Report-' . $schedule_id . '.pdf';
    $pdf->Output('F', $output_path);

    // Create a new PHPMailer instance
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Set the SMTP server to send through
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
        $mail->setFrom('usepqad@example.com', 'USeP - Quality Assurance Division');
        $mail->addAddress($college_email, $college_name);

        // Attachments
        $mail->addAttachment($output_path);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'UDAS Assessment Submission';
        $mail->Body    = "Dear $college_name,<br><br>
                          The UDAS Assessment for the schedule details below has been completed.<br>
                          <b>Program:</b> $program_name<br>
                          <b>Level Applied:</b> $level_applied<br>
                          <b>Date:</b> " . date("F j, Y", strtotime($schedule_date)) . "<br>
                          <b>Time:</b> " . date("g:i A", strtotime($schedule_time)) . "<br>
                          <b>Team Leader:</b> $team_leader<br>
                          <b>Team Members:</b> " . implode(', ', $team_members) . "<br><br>
                          Please find the attached assessment report.<br><br>
                          Regards,<br>USeP - Quality Assurance Division";

        // Send email
        $mail->send();

        // Insert assessment details into the database
        $sql_insert = "INSERT INTO udas_assessment (schedule_id, area, comments, remarks, udas_assessment_file, submission_date, qad_officer, qad_officer_signature, qad_director) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param("issssssss", $schedule_id, $area, $comments, $remarks, $output_path, $current_datetime, $qad_officer, $signature_path, $qad_director);
        $stmt_insert->execute();
        $stmt_insert->close();

        // Set session variable to indicate successful submission
        $_SESSION['udas_assessment_submitted'] = true;

        $message = "UDAS Assessment submitted successfully. Email notifications sent.";
    } catch (Exception $e) {
        $message = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
} else {
    $message = "Invalid request method.";
}

displayResult($message, $team_leader, $team_members);

function displayResult($message, $team_leader, $team_members) {
    $team_members_list = implode(', ', $team_members);

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
        <button class=\"button-primary\" onclick=\"window.location.href='udas_assessment.php'\">OK</button>
    </div>
</body>
</html>";
}
?>
