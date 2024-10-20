<?php
require 'vendor/autoload.php';
include 'connection.php';
use Dotenv\Dotenv;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__, 'sensitive_information.env');
$dotenv->load();

session_start();

if (!isset($_SESSION['user_id']) || substr($_SESSION['user_id'], 3, 2) !== '11') {
    header("Location: login.php");
    exit();
}

$success = false;
$message = '';

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

// Handle the POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $schedule_id = $_POST['schedule_id'];
    $evaluator = $_POST['evaluator'];
    $evaluator_signature = $_FILES['evaluator_signature'];
    $ratings = $_POST['area_rating'];  // Ratings for each area

    // Retrieve team_id for the specific schedule_id
    $sql_team = "SELECT id FROM team WHERE internal_users_id = ? AND schedule_id = ? AND status = 'accepted'";
    $stmt_team = $conn->prepare($sql_team);
    $stmt_team->bind_param("si", $user_id, $schedule_id);
    $stmt_team->execute();
    $stmt_team->bind_result($team_id);
    $stmt_team->fetch();
    $stmt_team->close();

    if (!$team_id) {
        $message = "No matching team found for the user.";
    } else {
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

        // Read the image file into binary data
        $signature_data = file_get_contents($evaluator_signature['tmp_name']);

        // Encrypt the binary data
        $encryption_key = $_ENV['ENCRYPTION_KEY'];  // Load encryption key from .env
        $encrypted_signature_data = encryptData($signature_data, $encryption_key);

        // Load PDF template
        $pdf = new FPDI();
        $pdf->AddPage();
        $pdf->setSourceFile('Ratings/Area-Rating.pdf');  // Use your provided template
        $tplIdx = $pdf->importPage(1);
        $pdf->useTemplate($tplIdx);

        // Set font for PDF
        $pdf->SetFont('Arial', '', 10);

        // Add schedule details (college, program, etc.)
        $pdf->SetXY(53, 69);  // Position for college
        $pdf->Write(0, $college_name);

        $pdf->SetXY(54, 75);  // Position for program
        $pdf->MultiCell(80, 5, $program_name);

        // Iterate over each rating and add it to the PDF
        // Starting positions and column widths
$yPosition = 97;  // Starting Y position for areas and ratings
$xAreaPosition = 27;  // X position for area_name
$xRatingPosition = 166;  // X position for rating
$verticalSpacing = 7.5;  // Space between rows

foreach ($ratings as $area_id => $rating) {
    // Retrieve the area name
    $sql_area = "SELECT area_name FROM area WHERE id = ?";
    $stmt_area = $conn->prepare($sql_area);
    $stmt_area->bind_param("i", $area_id);
    $stmt_area->execute();
    $stmt_area->bind_result($area_name);
    $stmt_area->fetch();
    $stmt_area->close();

    // Write area name and corresponding rating in the PDF

    // Set X and Y position for area_name
    $pdf->SetXY($xAreaPosition, $yPosition);  
    $pdf->MultiCell(100, 5, $area_name);  // 50 is the width of the area column

    // Set X and Y position for rating, keeping in line with the area_name's row
    $pdf->SetXY($xRatingPosition, $yPosition);
    $pdf->MultiCell(100, 5, $rating);  // 50 is the width of the rating column

    // Increment Y position for the next row
    $yPosition += $verticalSpacing;
}

        // Add evaluator's name
        $pdf->SetXY(25, 205);  // Adjust based on your layout
        $pdf->Write(0, $evaluator);

        // Decrypt the signature image before adding to PDF
        $decrypted_signature_data = decryptData($encrypted_signature_data, $encryption_key);

        // Create an image resource from the decrypted data
        $signature_image = imagecreatefromstring($decrypted_signature_data);
        if ($signature_image === false) {
            $message = "Error: Failed to create an image from the decrypted signature data.";
        } else {
            // Save the image temporarily
            $temp_image_path = 'temp_signature.png';
            imagesavealpha($signature_image, true);
            imagepng($signature_image, $temp_image_path);
            imagedestroy($signature_image);  // Free up memory

            // Calculate the X and Y positions to center the image
            $imageXPosition = 19;
            $imageYPosition = 195;
            $imageWidth = 40;
            $imageHeight = 15;

            // Add signature to the PDF
            $pdf->Image($temp_image_path, $imageXPosition, $imageYPosition, $imageWidth, $imageHeight, 'PNG');

            // Delete the temporary image file
            unlink($temp_image_path);
        }

        // Save the filled PDF
        $output_path = 'Ratings/' . $team_id . '-Rating.pdf';
        $pdf->Output('F', $output_path);

        // Update the team table with the file path using the team_id
        $sql_update_team = "UPDATE team SET area_rating_file = ? WHERE id = ?";
        $stmt_update_team = $conn->prepare($sql_update_team);
        $stmt_update_team->bind_param("si", $output_path, $team_id);
        $stmt_update_team->execute();
        $stmt_update_team->close();

        // Update team_areas with the ratings
        foreach ($ratings as $area_id => $rating) {
            $rating = floatval($rating); // Ensure that the rating is being passed as a float (decimal)

            $sql_update_areas = "UPDATE team_areas SET rating = ? WHERE team_id = ? AND area_id = ?";
            $stmt_update_areas = $conn->prepare($sql_update_areas);
            $stmt_update_areas->bind_param("dii", $rating, $team_id, $area_id);
            $stmt_update_areas->execute();
            $stmt_update_areas->close();
        }

        // Set session variable to indicate successful submission
        $_SESSION['rating_submitted'] = true;
        $success = true;
        $message = "Rating submitted and PDF generated successfully.";
    }
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
    <title>Rating Submission</title>
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
