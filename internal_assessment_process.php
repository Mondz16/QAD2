<?php
require 'vendor/autoload.php';
include 'connection.php';
session_start();

use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;

if (!isset($_SESSION['user_id']) || substr($_SESSION['user_id'], 3, 2) !== '11') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $result = $_POST['result'];
    $area_evaluated = $_POST['area_evaluated'];
    $findings = $_POST['findings'];
    $recommendations = $_POST['recommendations'];
    $evaluator = $_POST['evaluator'];
    $evaluator_signature = $_FILES['evaluator_signature'];

    // Retrieve user details
    $sql_user = "SELECT user_id FROM internal_users WHERE user_id = ?";
    $stmt_user = $conn->prepare($sql_user);
    $stmt_user->bind_param("s", $user_id);
    $stmt_user->execute();
    $stmt_user->bind_result($user_id);
    $stmt_user->fetch();
    $stmt_user->close();

    // Retrieve team_id from the team table
    $sql_team = "SELECT id, schedule_id FROM team WHERE internal_users_id = ? AND role IN ('team leader', 'team member') AND status = 'accepted'";
    $stmt_team = $conn->prepare($sql_team);
    $stmt_team->bind_param("s", $user_id);  // Corrected variable name here
    $stmt_team->execute();
    $stmt_team->bind_result($team_id, $schedule_id);
    $stmt_team->fetch();
    $stmt_team->close();

    if (!$team_id) {
        echo "No matching team found for the user.";
        exit();
    }

     // Retrieve schedule details from the database
    $sql_schedule = "
        SELECT 
            c.college_name, 
            p.program, 
            s.level_applied, 
            s.schedule_date 
        FROM schedule s
        JOIN college c ON s.college_id = c.id
        JOIN program p ON s.program_id = p.id
        WHERE s.id = ?";
    $stmt_schedule = $conn->prepare($sql_schedule);
    $stmt_schedule->bind_param("i", $schedule_id);
    $stmt_schedule->execute();
    $stmt_schedule->bind_result($college_name, $program_name, $level_applied, $schedule_date);
    $stmt_schedule->fetch();
    $stmt_schedule->close();

    // Upload evaluator signature
    $signature_path = 'signatures/' . basename($evaluator_signature['name']);
    if (!move_uploaded_file($evaluator_signature['tmp_name'], $signature_path)) {
        echo "Failed to upload signature.";
        exit();
    }

    // Load PDF template
    $pdf = new FPDI();
    $pdf->AddPage();
    $pdf->setSourceFile('Assessment/assessment.pdf');
    $tplIdx = $pdf->importPage(1);
    $pdf->useTemplate($tplIdx);

    // Set font
    $pdf->SetFont('Arial', '', 12);

    // Add dynamic data to the PDF
    $pdf->SetXY(43, 90); // Adjust position
    $pdf->Write(0, $college_name);

    $pdf->SetXY(43, 103); // Adjust position
    $pdf->Write(0, $program_name);

    $pdf->SetXY(43, 116); // Adjust position
    $pdf->Write(0, $level_applied);

    $pdf->SetXY(170, 103); // Adjust position
    $pdf->Write(0, $schedule_date);

    $pdf->SetXY(145, 116); // Adjust position
    $pdf->Write(0, $result);

    $pdf->SetXY(13, 141); // Adjust position
    $pdf->MultiCell(38, 5, $area_evaluated);

    $pdf->SetXY(58, 141); // Adjust position
    $pdf->MultiCell(61, 5, $findings);

    $pdf->SetXY(125, 141); // Adjust position
    $pdf->MultiCell(70, 5, $recommendations);

    $pdf->SetXY(45, 259); // Adjust position
    $pdf->Write(0, $evaluator);

    // Add evaluator signature
    $pdf->Image($signature_path, 45, 248, 40); // Adjust position and size

    // Save the filled PDF
    $output_path = 'Assessment/' . $team_id . '.pdf';
    $pdf->Output('F', $output_path);

    // Insert assessment details into the database
    $sql_insert = "INSERT INTO assessment (team_id, result, area_evaluated, findings, recommendations, evaluator, evaluator_signature, assessment_file) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bind_param("isssssss", $team_id, $result, $area_evaluated, $findings, $recommendations, $evaluator, $signature_path, $output_path);
    $stmt_insert->execute();
    $stmt_insert->close();

    // Set session variable to indicate successful submission
    $_SESSION['assessment_submitted'] = true;

    echo "Assessment submitted successfully. <a href='internal.php'>Go back</a>";
} else {
    echo "Invalid request method.";
}
?>
