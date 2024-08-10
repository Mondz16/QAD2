<?php
require 'vendor/autoload.php';
include 'connection.php';
session_start();

use setasign\Fpdi\Fpdi;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Function to encrypt data
function encryptData($data, $key) {
    $iv = openssl_random_pseudo_bytes(16);
    $encryptedData = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
    return base64_encode($iv . $encryptedData);
}

// Function to decrypt data
function decryptData($data, $key) {
    $data = base64_decode($data);
    $iv = substr($data, 0, 16);
    $encryptedData = substr($data, 16);
    return openssl_decrypt($encryptedData, 'AES-256-CBC', $key, 0, $iv);
}

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
        displayResult($message, $team_leader, $team_members);
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

    // Read the image file into binary data
    $signature_data = file_get_contents($qad_officer_signature['tmp_name']);

    // Encrypt the binary data
    $encryption_key = bin2hex(openssl_random_pseudo_bytes(32)); // Use a secure method to generate and store the encryption key
    $iv = openssl_random_pseudo_bytes(16);
    $encrypted_signature_data = openssl_encrypt($signature_data, 'AES-256-CBC', $encryption_key, 0, $iv);

    // Store the encrypted data in a file
    $encrypted_signature_path = 'signatures/' . basename($qad_officer_signature['name']) . '.enc';
    file_put_contents($encrypted_signature_path, $encrypted_signature_data);

    // Remove the plain image file from the temporary location
    unlink($qad_officer_signature['tmp_name']);

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

    // Decrypt the signature image before adding to PDF
    $decrypted_signature_data = openssl_decrypt($encrypted_signature_data, 'AES-256-CBC', $encryption_key, 0, $iv);
    $temp_signature_path = tempnam(sys_get_temp_dir(), 'sig') . '.png';
    file_put_contents($temp_signature_path, $decrypted_signature_data);

    // Add evaluator signature
    $pdf->Image($temp_signature_path, 24.5, 190, 40); // Adjust position and size

    // Remove the temporary decrypted signature file
    unlink($temp_signature_path);

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
        $mail->setFrom('usepqad@gmail.com', 'USeP - Quality Assurance Division');
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
        $stmt_insert->bind_param("issssssss", $schedule_id, $area, $comments, $remarks, $output_path, $current_datetime, $qad_officer, $encrypted_signature_path, $qad_director);
        $stmt_insert->execute();
        $stmt_insert->close();

        // Set session variable to indicate successful submission
        $_SESSION['udas_assessment_submitted'] = true;

        $message = "UDAS Assessment submitted successfully";
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
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Operation Result</title>
    <link rel='stylesheet' href='https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600&display=swap'>
    <link rel='stylesheet' href='index.css'>
</head>
<body>
    <div id='successPopup' class='popup'>
        <div class='popup-content'>
            <div style='height: 50px; width: 0px;'></div>
            <img class='Success' src='images/Success.png' height='100'>
            <div style='height: 20px; width: 0px;'></div>
            <div class='popup-text'>$message</div>
            <div style='height: 50px; width: 0px;'></div>
            <a href='udas_assessment.php' class='okay' id='closePopup'>Okay</a>
            <div style='height: 100px; width: 0px;'></div>
            <div class='hairpop-up'></div>
        </div>
    </div>
    <script>
        document.getElementById('successPopup').style.display = 'block';
    </script>
</body>
</html>";
}

?>
