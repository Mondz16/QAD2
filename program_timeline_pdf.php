<?php
session_start();
require 'connection.php';
require 'vendor/autoload.php';

use setasign\Fpdi\Fpdi;

$pdfDir = './Program Level History/';
if (!is_dir($pdfDir)) {
    mkdir($pdfDir, 0777, true);
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['images']) || !isset($data['selectionType'])) {
    http_response_code(400);
    echo 'Invalid input';
    exit;
}

$images = $data['images'];
$selectionType = $data['selectionType'];

$pdf = new FPDF();
$pdf->SetFont('Arial', 'B', 16);

$programNames = [];
$timelineCountPerPageCollege = 2; // Maximum timelines per page for college
$timelineCountPerPageProgram = 5; // Maximum timelines per page for program
$currentTimelineCount = 0; // Count timelines added to the current page

foreach ($images as $item) {
    if (!isset($item['name']) || !isset($item['data'])) {
        continue;
    }

    // Determine timeline limit and logic based on selection type
    if ($selectionType === 'college') {
        // Apply multi-page logic for college
        if ($currentTimelineCount === 0 || $currentTimelineCount >= $timelineCountPerPageCollege) {
            $pdf->AddPage();
            $currentTimelineCount = 0; // Reset the count for the new page
        }
    } elseif ($selectionType === 'program') {
        // Apply multi-page logic for program
        if ($currentTimelineCount === 0 || $currentTimelineCount >= $timelineCountPerPageProgram) {
            $pdf->AddPage();
            $currentTimelineCount = 0; // Reset the count for the new page
        }
    }

    $programNames[] = $item['name'];

    // Add the timeline title
    $pdf->SetFont('Arial', 'B', 12);
    $titleWidth = 160;
    $x = ($pdf->GetPageWidth() - $titleWidth) / 2;
    $lineHeight = 5;
    $pdf->SetX($x);
    $pdf->MultiCell($titleWidth, $lineHeight, $item['name'], 0, 'C');
    $pdf->Ln(5);

    // Dynamically set $imgHeight based on selectionType
    $imgWidth = 180;
    $x = ($pdf->GetPageWidth() - $imgWidth) / 2;
    $imgHeight = $selectionType === 'college' ? 100 : 30;

    // Add the timeline image
    $pdf->Image($item['data'], $x, $pdf->GetY(), $imgWidth, $imgHeight, 'PNG');
    $pdf->Ln($imgHeight + 10);

    // Increment the timeline count for the current page
    $currentTimelineCount++;
}

if (empty($programNames)) {
    http_response_code(400);
    echo 'No valid program data to generate PDF';
    exit;
}

// Generate filename based on program names
$programNamesString = implode('_', $programNames);
$programNamesString = preg_replace('/[^a-zA-Z0-9_]/', '', $programNamesString);
$programNamesString = substr($programNamesString, 0, 50);

$pdfFileName = 'program_history_' . $programNamesString . '.pdf';
$pdfFilePath = $pdfDir . $pdfFileName;
$pdf->Output($pdfFilePath, 'F');

if (file_exists($pdfFilePath)) {
    echo $pdfFileName;
} else {
    http_response_code(500);
    echo 'Error: File not created';
}
