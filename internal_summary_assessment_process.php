<?php
require 'vendor/autoload.php';
include 'connection.php';
session_start();

use Dotenv\Dotenv;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__, 'sensitive_information.env');
$dotenv->load();

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

if (!isset($_SESSION['user_id']) || substr($_SESSION['user_id'], 3, 2) !== '11') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $schedule_id = $_POST['schedule_id'];
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
    $sql_team = "SELECT id FROM team WHERE internal_users_id = ? AND schedule_id = ? AND role = 'team leader' AND status = 'accepted'";
    $stmt_team = $conn->prepare($sql_team);
    $stmt_team->bind_param("si", $user_id, $schedule_id);
    $stmt_team->execute();
    $stmt_team->bind_result($team_id);
    $stmt_team->fetch();
    $stmt_team->close();

    if (!$team_id) {
        $message = "No matching team found for the user.";
        $success = false;
    } else {
        // Read the image file into binary data
        $signature_data = file_get_contents($evaluator_signature['tmp_name']);
        $encryption_key = $_ENV['ENCRYPTION_KEY'];
        $encrypted_signature_data = encryptData($signature_data, $encryption_key);

        // Process the areas and ratings
        $area_ratings = $_POST['area_rating']; // Contains the ratings from the form
        $areas = [];
        $results = [];

        foreach ($area_ratings as $area_id => $rating) {
            // Fetch the area name using the area_id
            $sql_area = "SELECT area_name FROM area WHERE id = ?";
            $stmt_area = $conn->prepare($sql_area);
            $stmt_area->bind_param("i", $area_id);
            $stmt_area->execute();
            $stmt_area->bind_result($area_name);
            $stmt_area->fetch();
            $stmt_area->close();

            // Insert or update the rating in team_areas
            // Insert or update the rating in team_areas
$sql_rating = "INSERT INTO team_areas (team_id, area_id, rating) VALUES (?, ?, ?) 
ON DUPLICATE KEY UPDATE rating = ?";

// Prepare the SQL statement
$stmt_rating = $conn->prepare($sql_rating);

// Assign the rating to another variable for the update
$updated_rating = $rating;  // Ensure it's a separate variable

// Bind the variables to the prepared statement
$stmt_rating->bind_param("iidd", $team_id, $area_id, $rating, $updated_rating);

// Execute the statement
$stmt_rating->execute();

// Close the statement
$stmt_rating->close();


            // Prepare for the PDF
            $areas[] = $area_name;
            $results[] = $rating;
        }

        // Save the filled PDF for summary
        $pdf = new FPDI();
        $pdf->AddPage();
        $pdf->setSourceFile('Summary/summary.pdf');
        $tplIdx = $pdf->importPage(1);
        $pdf->useTemplate($tplIdx);

        // Set font
        $pdf->SetFont('Arial', '', 12);

        // Add dynamic data to the PDF
        $pdf->SetXY(50, 90);
        $pdf->Write(0, $_POST['college']);

        $pdf->SetXY(50, 103);
        $pdf->Write(0, $_POST['program']);

        $pdf->SetXY(50, 116);
        $pdf->Write(0, $_POST['level']);

        // Add areas and their ratings to the PDF
        $pdf->SetXY(12, 141);
        $pdf->MultiCell(37, 5, implode("\n", $areas));

        $pdf->SetXY(50, 145);
        $pdf->MultiCell(148, 5, implode("\n", $results));

        // Add evaluator's signature and name
        $centerTextX = 65;
        $textYPosition = 258;
        $textWidth = $pdf->GetStringWidth($evaluator);
        $textXPosition = $centerTextX - ($textWidth / 2);

        // Print evaluator's name
        $pdf->SetXY($textXPosition, $textYPosition);
        $pdf->Write(0, $evaluator);

        // Decrypt and add signature to the PDF
        $decrypted_signature_data = decryptData($encrypted_signature_data, $encryption_key);
        $signature_image = imagecreatefromstring($decrypted_signature_data);
        if ($signature_image === false) {
            $message = "Error: Failed to create an image from the decrypted signature data.";
        } else {
            $temp_image_path = 'temp_signature.png';
            imagesavealpha($signature_image, true);
            $transparency = imagecolorallocatealpha($signature_image, 0, 0, 0, 127);
            imagefill($signature_image, 0, 0, $transparency);
            imagepng($signature_image, $temp_image_path);
            imagedestroy($signature_image);

            $centerImageX = 67;
            $centerImageY = 252;
            $imageWidth = 40;
            $imageHeight = 15;
            $imageXPosition = $centerImageX - ($imageWidth / 2);
            $imageYPosition = $centerImageY - ($imageHeight / 2);
            $pdf->Image($temp_image_path, $imageXPosition, $imageYPosition, $imageWidth, $imageHeight, 'PNG');
            unlink($temp_image_path);
        }

        // Save the filled PDF
        $output_path = 'Summary/' . $team_id . '_summary.pdf';
        $pdf->Output('F', $output_path);

        // Prepare the SQL query for inserting summary details into the database
$sql_insert = "INSERT INTO summary (team_id, areas, results, evaluator, evaluator_signature, summary_file) VALUES (?, ?, ?, ?, ?, ?)";

// Prepare the statement
$stmt_insert = $conn->prepare($sql_insert);

// Assign the results of implode() to variables before passing them to bind_param()
$areas_imploded = implode("\n", $areas);
$results_imploded = implode("\n", $results);

// Bind the variables to the statement
$stmt_insert->bind_param("isssss", $team_id, $areas_imploded, $results_imploded, $evaluator, $encrypted_signature_data, $output_path);

// Execute the statement
$stmt_insert->execute();

// Close the statement
$stmt_insert->close();

// New Logic to Combine NDA Files
$nda_pdf = new FPDI(); // Create a new FPDI instance for NDA compilation

// Retrieve all team IDs for the specific schedule_id
$sql_all_teams = "SELECT id FROM team WHERE schedule_id = ?";
$stmt_all_teams = $conn->prepare($sql_all_teams);
$stmt_all_teams->bind_param("i", $schedule_id);
$stmt_all_teams->execute();
$result_all_teams = $stmt_all_teams->get_result();

$nda_files = [];
while ($row = $result_all_teams->fetch_assoc()) {
    $team_ids[] = $row['id'];
}
$stmt_all_teams->close();

// Find NDA files for these team IDs
foreach ($team_ids as $tid) {
    $sql_nda = "SELECT NDA_file FROM nda WHERE team_id = ?";
    $stmt_nda = $conn->prepare($sql_nda);
    $stmt_nda->bind_param("i", $tid);
    $stmt_nda->execute();
    $stmt_nda->bind_result($nda_file);
    while ($stmt_nda->fetch()) {
        $nda_files[] = $nda_file;
    }
    $stmt_nda->close();
}

// Combine all NDA files into one PDF
foreach ($nda_files as $file) {
    $nda_pdf->AddPage();
    $nda_pdf->setSourceFile($file);
    $tplIdx = $nda_pdf->importPage(1);
    $nda_pdf->useTemplate($tplIdx);
}

// Save the combined NDA PDF
$nda_output_path = 'NDA Compilation/' . $team_id . '_nda_compilation.pdf';
$nda_pdf->Output('F', $nda_output_path);

// Insert NDA compilation details into the database
$sql_insert_nda = "INSERT INTO NDA_compilation (team_id, NDA_compilation_file) VALUES (?, ?)";
$stmt_insert_nda = $conn->prepare($sql_insert_nda);
$stmt_insert_nda->bind_param("is", $team_id, $nda_output_path);
$stmt_insert_nda->execute();
$stmt_insert_nda->close();

        // Set session variable to indicate successful submission
        $_SESSION['summary_submitted'] = true;
        $message = "Summary submitted successfully.";
        $success = true;
    }
} else {
    $message = "Invalid request method.";
    $success = false;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Summary Submission</title>
    <link rel="stylesheet" href="index.css">
    <script>
        function showPopup() {
            var popup = document.getElementById('successPopup');
            popup.style.display = 'block';
        }

        function closePopup() {
            var popup = document.getElementById('successPopup');
            popup.style.display = 'none';
            window.location.href = 'internal_assessment.php'; // Redirect to internal.php after closing the popup
        }

        window.onload = function() {
            showPopup();
        }
    </script>
</head>
<body>
    <div id="successPopup" class="popup">
        <div class="popup-content">
            <div style="height: 50px; width: 0px;"></div>
            <?php if ($success): ?>
                <img class="Success" src="images/Success.png" height="100">
            <?php else: ?>
                <img class="Success" src="images/Failure.png" height="100">
            <?php endif; ?>
            <div style="height: 20px; width: 0px;"></div>
            <div class="popup-text"><?php echo $message; ?></div>
            <div style="height: 50px; width: 0px;"></div>
            <a href="javascript:void(0);" class="okay" onclick="closePopup()">Okay</a>
            <div style="height: 100px; width: 0px;"></div>
            <div class="hairpop-up"></div>
        </div>
    </div>
</body>
</html>
