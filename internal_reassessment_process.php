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

    // Retrieve team_id from the team table
    $sql_team = "SELECT id, schedule_id FROM team WHERE internal_users_id = ? AND role IN ('team leader', 'team member') AND status = 'accepted'";
    $stmt_team = $conn->prepare($sql_team);
    $stmt_team->bind_param("s", $user_id);
    $stmt_team->execute();
    $stmt_team->bind_result($team_id, $schedule_id);
    $stmt_team->fetch();
    $stmt_team->close();

    if (!$team_id) {
        echo "<script>alert('No matching team found for the user.'); window.location.href = 'internal_assessment.php';</script>";
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
        echo "<script>alert('Failed to upload signature.'); window.location.href = 'internal_assessment.php';</script>";
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

    // Insert or update assessment details in the database
    $sql_check_assessment = "SELECT id FROM assessment WHERE team_id = ?";
    $stmt_check_assessment = $conn->prepare($sql_check_assessment);
    $stmt_check_assessment->bind_param("i", $team_id);
    $stmt_check_assessment->execute();
    $stmt_check_assessment->bind_result($assessment_id);
    $stmt_check_assessment->fetch();
    $stmt_check_assessment->close();

    if ($assessment_id) {
        // Update existing assessment
        $sql_update_assessment = "UPDATE assessment SET result = ?, area_evaluated = ?, findings = ?, recommendations = ?, evaluator = ?, evaluator_signature = ?, assessment_file = ? WHERE id = ?";
        $stmt_update_assessment = $conn->prepare($sql_update_assessment);
        $stmt_update_assessment->bind_param("sssssssi", $result, $area_evaluated, $findings, $recommendations, $evaluator, $signature_path, $output_path, $assessment_id);
        $stmt_update_assessment->execute();
        $stmt_update_assessment->close();
        $message = "Assessment updated successfully.";
    } else {
        // Insert new assessment
        $sql_insert_assessment = "INSERT INTO assessment (team_id, result, area_evaluated, findings, recommendations, evaluator, evaluator_signature, assessment_file) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_insert_assessment = $conn->prepare($sql_insert_assessment);
        $stmt_insert_assessment->bind_param("isssssss", $team_id, $result, $area_evaluated, $findings, $recommendations, $evaluator, $signature_path, $output_path);
        $stmt_insert_assessment->execute();
        $stmt_insert_assessment->close();
        $message = "Assessment submitted successfully.";
    }

    // Set session variable to indicate successful submission
    $_SESSION['assessment_submitted'] = true;

    // Output success message with popup
    echo "<script>
        alert('$message');
        window.location.href = 'internal_assessment.php';
    </script>";

    exit();
} else {
    echo "Invalid request method.";
}

$conn->close();
?>
