<?php
session_start();
require 'connection.php';
require 'vendor/autoload.php'; // Ensure the autoload file is correctly referenced

use setasign\Fpdi\Fpdi;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Directory where PDFs will be saved
$pdfDir = './Program Level History/';  // Make sure this directory exists and is writable

// Ensure directory exists
if (!is_dir($pdfDir)) {
    mkdir($pdfDir, 0777, true);  // Create the directory if it doesn't exist
}

// Get the JSON data sent via AJAX
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    echo 'Invalid input';
    exit;
}

$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);

$programNames = []; // Collect program names

foreach ($data as $item) {
    if (!isset($item['name']) || !isset($item['data'])) {
        continue; // Skip this item if it doesn't have the required fields
    }

    $programNames[] = $item['name'];
    $pdf->SetFont('Arial', 'B', 12);  // Set title font
    
    $titleWidth = 160;  // Set the desired width in mm
    $x = ($pdf->GetPageWidth() - $titleWidth) / 2;  // Center the title on the page
    
    // Use MultiCell to wrap the program name with specified width
    $lineHeight = 5;  // Set line height
    $pdf->SetX($x);  // Set the X position for centered text
    $pdf->MultiCell($titleWidth, $lineHeight, $item['name'], 0, 'C');  // Wrap text and center it

    $pdf->Ln(5);

    // Calculate width to maintain aspect ratio
    $imgWidth = 180;
    $x = ($pdf->GetPageWidth() - $imgWidth) / 2;  // Center the image

    // Calculate the image height dynamically based on your aspect ratio
    // Here we assume a fixed height for demonstration purposes; adjust as needed
    $imgHeight = 30; // Adjust the height based on your canvas size and desired output

    $pdf->Image($item['data'], $x, $pdf->GetY(), $imgWidth, $imgHeight, 'PNG');
    $pdf->Ln($imgHeight + 10); // Add space after each chart
}

if (empty($programNames)) {
    http_response_code(400);
    echo 'No valid program data to generate PDF';
    exit;
}

// Generate filename based on program names
// Remove special characters and limit the filename length
$programNamesString = implode('_', $programNames);
$programNamesString = preg_replace('/[^a-zA-Z0-9_]/', '', $programNamesString);
$programNamesString = substr($programNamesString, 0, 50); // Limit length to 50 characters

$pdfFileName = 'program_history_' . $programNamesString . '.pdf';
$pdfFilePath = $pdfDir . $pdfFileName;
$pdf->Output($pdfFilePath, 'F');

// Debugging: Log the file path
error_log("PDF file created at: " . $pdfFilePath);

// Return the relative file path to the client
if (file_exists($pdfFilePath)) {
    echo $pdfFileName;  // Send the file name back to the client
} else {
    http_response_code(500);
    echo 'Error: File not created';
}
?>
