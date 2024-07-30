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
    $schedule_id = $_POST['schedule_id'];
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

    // Retrieve team_id for the specific schedule_id
    $sql_team = "SELECT id FROM team WHERE internal_users_id = ? AND schedule_id = ? AND status = 'accepted'";
    $stmt_team = $conn->prepare($sql_team);
    $stmt_team->bind_param("si", $user_id, $schedule_id);
    $stmt_team->execute();
    $stmt_team->bind_result($team_id);
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
            p.program_name, 
            s.level_applied, 
            s.schedule_date 
        FROM schedule s
        JOIN college c ON s.college_code = c.code
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
    $pdf->setSourceFile('Assessments/assessment.pdf');
    $tplIdx = $pdf->importPage(1);
    $pdf->useTemplate($tplIdx);

    // Set font
    $pdf->SetFont('Arial', '', 12);

    // Add dynamic data to the PDF
    $pdf->SetXY(43, 90); // Adjust position
    $pdf->Write(0, $college_name);

    $pdf->SetXY(43, 98); // Adjust position
    $pdf->MultiCell(80, 5, $program_name);

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

    $centerTextX = 65.5; // The center X coordinate where you want to center the text
    $textYPosition = 258; // The Y coordinate where you want to place the text
    $textWidth = $pdf->GetStringWidth($evaluator);

    // Calculate the X position to center the text
    $textXPosition = $centerTextX - ($textWidth / 2);

    // Print the QAD Officer's name centered at the specified centerTextX
    $pdf->SetXY($textXPosition, $textYPosition);
    $pdf->Write(0, $evaluator);

    // Calculate the X and Y positions to center the image
    $centerImageX = 67; // The center X coordinate where you want to center the image
    $centerImageY = 252; // The center Y coordinate where you want to center the image
    $imageWidth = 40; // The width of the signature image
    $imageHeight = 15; // The height of the signature image (adjust based on actual image aspect ratio)
    $imageXPosition = $centerImageX - ($imageWidth / 2);
    $imageYPosition = $centerImageY - ($imageHeight / 2);

    // Add QAD Officer Signature
    $pdf->Image($signature_path, $imageXPosition, $imageYPosition, $imageWidth, $imageHeight); // Adjust positions as needed

    // Save the filled PDF
    $output_path = 'Assessments/' . $team_id . '.pdf';
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
