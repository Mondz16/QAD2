<?php
$file = $_GET['file'];
$pdfDir = './Program Level History/';  // Path to the PDF directory

// Validate and sanitize the file path
$filePath = realpath($pdfDir . $file);
$pdfDirRealPath = realpath($pdfDir);

if (strpos($filePath, $pdfDirRealPath) !== 0 || !file_exists($filePath)) {
    http_response_code(404);
    echo 'File not found';
    exit;
}

header('Content-Description: File Transfer');
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . basename($file) . '"');
header('Content-Length: ' . filesize($filePath));
readfile($filePath);
exit;
?>
