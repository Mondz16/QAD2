<?php
require 'vendor/autoload.php';
include 'connection.php';
session_start();

use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;
use Dotenv\Dotenv;

// Load environment variables from .env file
$dotenv = Dotenv::createImmutable(__DIR__, 'sensitive_information.env');
$dotenv->load();

if (!isset($_SESSION['user_id']) || substr($_SESSION['user_id'], 3, 2) !== '11') {
    header("Location: login.php");
    exit();
}

$success = false;
$message = '';

// Function to get ordinal suffix for a number
function getOrdinalSuffix($number) {
    if (!in_array(($number % 100), [11, 12, 13])) {
        switch ($number % 10) {
            case 1: return "st";
            case 2: return "nd";
            case 3: return "rd";
        }
    }
    return "th";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $team_id = $_POST['team_id'];
    $internal_accreditor = $_POST['internal_accreditor'];
    $date_added = $_POST['date_added'];
    $internal_accreditor_signature = $_FILES['internal_accreditor_signature'];

    // Format the date
    $date = new DateTime($date_added);
    $month = $date->format('F');
    $day = $date->format('j') . getOrdinalSuffix($date->format('j'));
    $year = $date->format('Y');

    // Read the image file into binary data
    $signature_data = file_get_contents($internal_accreditor_signature['tmp_name']);

    // Retrieve the encryption key from environment variables
    $encryption_key = getenv('ENCRYPTION_KEY');
    $iv = openssl_random_pseudo_bytes(16);
    $encrypted_signature_data = openssl_encrypt($signature_data, 'AES-256-CBC', $encryption_key, 0, $iv);

    // Initialize the FPDI object
    $pdf = new FPDI();

    // Convert inches to millimeters (since FPDI uses millimeters)
    $width = 216.12; // Convert width from inches to mm
    $height = 330.42; // Convert height from inches to mm

    // Add the page with custom dimensions
    $pdf->AddPage('P', array($width, $height));
    $pdf->setSourceFile('NDA/NDA.pdf');
    $tplIdx = $pdf->importPage(1);
    $pdf->useTemplate($tplIdx);

    // Add the Century Gothic font
    $pdf->AddFont('CenturyGothic','','CenturyGothic.php');

    // Set font
    $pdf->SetFont('CenturyGothic', '', 11);

    // Add dynamic data to the PDF
    $pdf->SetXY(30, 78); // Adjust position for internal accreditor's name
    $pdf->Write(0, $internal_accreditor);

    // Print the month, day, and year separately
    $pdf->SetXY(117, 247); // Adjust position for month
    $pdf->Write(0, $month);

    $pdf->SetXY(92, 247); // Adjust position for day
    $pdf->Write(0, $day);

    $pdf->SetXY(140, 247); // Adjust position for year
    $pdf->Write(0, $year);

    // Decrypt the signature image before adding it to the PDF
    $decrypted_signature_data = openssl_decrypt($encrypted_signature_data, 'AES-256-CBC', $encryption_key, 0, $iv);

    // Create an image resource from the decrypted data
    $signature_image = imagecreatefromstring($decrypted_signature_data);
    if ($signature_image === false) {
        $message = "Error: Failed to create an image from the decrypted signature data.";
    } else {
        // Save the image temporarily
        $temp_image_path = 'temp_signature.png';
        
        // Preserve transparency when saving the PNG
        imagesavealpha($signature_image, true);
        $transparency = imagecolorallocatealpha($signature_image, 0, 0, 0, 127);
        imagefill($signature_image, 0, 0, $transparency);

        // Save the image as a PNG with transparency
        imagepng($signature_image, $temp_image_path);
        imagedestroy($signature_image); // Free up memory

        // Calculate the X and Y positions to center the image
        $centerImageX = 110; // The center X coordinate where you want to center the image
        $centerImageY = 261; // The center Y coordinate where you want to center the image
        $imageWidth = 40; // The width of the signature image
        $imageHeight = 15; // The height of the signature image (adjust based on actual image aspect ratio)
        $imageXPosition = $centerImageX - ($imageWidth / 2);
        $imageYPosition = $centerImageY - ($imageHeight / 2);

        // Add internal accreditor signature from the temporary file
        $pdf->Image($temp_image_path, $imageXPosition, $imageYPosition, $imageWidth, $imageHeight, 'PNG'); // Adjust positions as needed

        // Delete the temporary image file
        unlink($temp_image_path);
    }

    // Save the filled NDA PDF
    $output_path = 'NDA/' . $team_id . '_NDA.pdf';
    $pdf->Output('F', $output_path);

    // Insert NDA details into the database
    $sql_insert = "INSERT INTO NDA (team_id, date_added, internal_accreditor, internal_accreditor_signature, NDA_file) VALUES (?, ?, ?, ?, ?)";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bind_param("issss", $team_id, $date_added, $internal_accreditor, $encrypted_signature_data, $output_path);
    if ($stmt_insert->execute()) {
        $success = true;
        $message = "Non-disclosure Agreement signed successfully.";
    } else {
        $message = "Error: " . $stmt_insert->error;
    }
    $stmt_insert->close();
    $conn->close();
} else {
    $message = "Invalid request method.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NDA Submission</title>
    <link rel="stylesheet" href="index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" />
    <style>
        body {
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }
    </style>
</head>
<body>
    <?php if ($success): ?>
        <div id="successPopup" class="popup">
            <div class="popup-content">
                <div style="height: 50px; width: 0px;"></div>
                <img class="Success" src="images/Success.png" height="100">
                <div style="height: 20px; width: 0px;"></div>
                <div class="popup-text"><?php echo $message; ?></div>
                <div style="height: 50px; width: 0px;"></div>
                <a href="internal_assessment.php" class="okay" id="closePopup">Okay</a>
                <div style="height: 100px; width: 0px;"></div>
                <div class="hairpop-up"></div>
            </div>
        </div>
        <script>
            document.getElementById('successPopup').style.display = 'block';

            document.getElementById('closeSuccessBtn').addEventListener('click', function() {
                document.getElementById('successPopup').style.display = 'none';
            });

            document.getElementById('closePopup').addEventListener('click', function() {
                document.getElementById('successPopup').style.display = 'none';
            });

            window.addEventListener('click', function(event) {
                if (event.target == document.getElementById('successPopup')) {
                    document.getElementById('successPopup').style.display = 'none';
                }
            });
        </script>
    <?php else: ?>
        <p><?php echo $message; ?></p>
    <?php endif; ?>
</body>
</html>
