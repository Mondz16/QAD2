<?php
session_start();
require 'connection.php';
require 'vendor/autoload.php';

use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;

// Directory where PDFs will be saved
$pdfDir = './Reports/';

// Ensure directory exists
if (!is_dir($pdfDir)) {
    mkdir($pdfDir, 0777, true);
}

// Create new FPDI instance
$pdf = new Fpdi();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);

// Database connection and fetching data
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "qadDB";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql_colleges = "SELECT COUNT(*) as total_colleges FROM college";
$result_colleges = $conn->query($sql_colleges);
$total_colleges = $result_colleges->fetch_assoc()['total_colleges'];

$sql_programs = "SELECT COUNT(*) as total_programs FROM program";
$result_programs = $conn->query($sql_programs);
$total_programs = $result_programs->fetch_assoc()['total_programs'];

$sql_users = "SELECT (SELECT COUNT(*) FROM internal_users) + (SELECT COUNT(*) FROM external_users) as total_users";
$result_users = $conn->query($sql_users);
$total_users = $result_users->fetch_assoc()['total_users'];

$conn->close();

// Add content to PDF
$pdf->SetXY(10, 20);
$pdf->Cell(0, 10, "Total Colleges: $total_colleges     Total Programs: $total_programs     Total Users: $total_users", 0, 1);

// Check if canvas data was sent
$data = json_decode(file_get_contents('php://input'), true);


foreach ($data as $item) {
    if(!empty($item['data'])) {
        $canvasData = $item['data'];

        // Decode the base64 data
        $canvasData = str_replace('data:image/png;base64,', '', $canvasData);
        $canvasData = str_replace(' ', '+', $canvasData);
        $canvasImage = base64_decode($canvasData);

        // Create an image from the decoded data
        $x = ($pdf->GetPageWidth() - 160) / 2;  // Center the image

        // Add the image to the PDF
        $pdf->Image($item['data'], $x, $pdf->GetY(), 0, 120, 'PNG');
        $pdf->Ln(120 + 10); // Add space after each chart
    }
}

// Generate filename based on timestamp
$pdfFileName = 'reports_dashboard_' . time() . '.pdf';
$pdfFilePath = $pdfDir . $pdfFileName;
$pdf->Output($pdfFilePath, 'F');

// Log the file path for debugging
error_log("PDF file created at: " . $pdfFilePath);

// Return the relative file path to the client
if (file_exists($pdfFilePath)) {
    echo $pdfFileName;  // Send the file name back to the client
} else {
    http_response_code(500);
    echo 'Error: File not created';
}
?>
