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
    $areas = $_POST['areas'];
    $results = $_POST['results'];
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
        echo "Failed to upload signature.";
        exit();
    }

    // Load PDF template
    $pdf = new FPDI();
    $pdf->AddPage();
    $pdf->setSourceFile('Summary/summary.pdf');
    $tplIdx = $pdf->importPage(1);
    $pdf->useTemplate($tplIdx);

    // Set font
    $pdf->SetFont('Arial', '', 12);

    // Add dynamic data to the PDF
    $pdf->SetXY(50, 90); // Adjust position
    $pdf->Write(0, $college_name);

    $pdf->SetXY(50, 103); // Adjust position
    $pdf->Write(0, $program_name);

    $pdf->SetXY(50, 116); // Adjust position
    $pdf->Write(0, $level_applied);

    $pdf->SetXY(12, 141); // Adjust position
    $pdf->MultiCell(37, 5, $areas);

    $pdf->SetXY(50, 139); // Adjust position
    $pdf->MultiCell(148, 5, $results);

    $pdf->SetXY(45, 258); // Adjust position
    $pdf->Write(0, $evaluator);

    // Add evaluator signature
    $pdf->Image($signature_path, 45, 248, 40); // Adjust position and size

    // Save the filled PDF
    $output_path = 'Summary/' . $team_id . '.pdf';
    $pdf->Output('F', $output_path);

    // Insert or update summary details in the database
    $sql_check_summary = "SELECT id FROM summary WHERE team_id = ?";
    $stmt_check_summary = $conn->prepare($sql_check_summary);
    $stmt_check_summary->bind_param("i", $team_id);
    $stmt_check_summary->execute();
    $stmt_check_summary->bind_result($summary_id);
    $stmt_check_summary->fetch();
    $stmt_check_summary->close();

    if ($summary_id) {
        // Update existing assessment
        $sql_update_summary = "UPDATE summary SET areas = ?, results = ?, evaluator = ?, evaluator_signature = ?, summary_file = ? WHERE id = ?";
        $stmt_update_summary = $conn->prepare($sql_update_summary);
        $stmt_update_summary->bind_param("sssssi", $areas, $results, $evaluator, $signature_path, $output_path, $summary_id);
        $stmt_update_summary->execute();
        $stmt_update_summary->close();
        $message = "Summary updated successfully.";
    } else {
        // Insert new summary
        $sql_insert_summary = "INSERT INTO summary (team_id, areas, results, evaluator, evaluator_signature, summary_file) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_insert_summary = $conn->prepare($sql_insert_summary);
        $stmt_insert_summary->bind_param("isssss", $team_id, $areas, $results, $evaluator, $signature_path, $output_path);
        $stmt_insert_assessment->execute();
        $stmt_insert_assessment->close();
        $message = "Summary submitted successfully.";
    }

    // Set session variable to indicate successful submission
    $_SESSION['summary_submitted'] = true;

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