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
    $sql_rating = "INSERT INTO team_areas (team_id, area_id, rating) VALUES (?, ?, ?) 
                  ON DUPLICATE KEY UPDATE rating = ?";
    $stmt_rating = $conn->prepare($sql_rating);
    $updated_rating = $rating;  // Ensure it's a separate variable
    $stmt_rating->bind_param("iidd", $team_id, $area_id, $rating, $updated_rating);
    $stmt_rating->execute();
    $stmt_rating->close();

    // Prepare for the PDF
    $areas[] = $area_name;
    $results[] = $rating;
}

// **Refactored Logic Starts Here**

// Step 1: Retrieve level_applied from schedule table using schedule_id
$sql_level = "SELECT level_applied FROM schedule WHERE id = ?";
$stmt_level = $conn->prepare($sql_level);
$stmt_level->bind_param("i", $schedule_id);
$stmt_level->execute();
$stmt_level->bind_result($level_applied);
$stmt_level->fetch();
$stmt_level->close();

// Step 2: Retrieve standard from accreditation_standard table using level_applied
$sql_standard = "SELECT Standard FROM accreditation_standard WHERE Level = ?";
$stmt_standard = $conn->prepare($sql_standard);
$stmt_standard->bind_param("s", $level_applied);
$stmt_standard->execute();
$stmt_standard->bind_result($standard);
$stmt_standard->fetch();
$stmt_standard->close();

// Step 3: Determine the interpretation based on the standard and ratings
if (empty($results)) {
    $interpretation = "No Ratings"; // If no ratings are available
} else {
    // Calculate the threshold
    $threshold = $standard - 0.50;

    // Determine counts based on the threshold
    $above_standard_count = count(array_filter($results, function($r) use ($standard) {
        return $r > $standard;
    }));
    $below_threshold_count = count(array_filter($results, function($r) use ($threshold) {
        return $r < $threshold;
    }));
    $total_ratings = count($results);

    // Apply the logic based on the level and standard
    if ($below_threshold_count === $total_ratings && $total_ratings > 0) {
        $interpretation = "Revisit"; // All ratings are strictly less than (standard - 0.50)
    } elseif ($above_standard_count === $total_ratings && $below_threshold_count === 0) {
        $interpretation = "Ready"; // All ratings are greater than standard and none are below (standard - 0.50)
    } elseif ($below_threshold_count >= 1 && $below_threshold_count <= 3) {
        $interpretation = "Needs Improvement"; // 1-3 ratings are strictly less than (standard - 0.50)
    } else {
        $interpretation = "Needs Improvement"; // Default case
    }
}

// **Refactored Logic Ends Here**

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

$pdf->SetXY(12, 141); // Starting position for area names
$pdf->MultiCell(37, 5, implode("\n", $areas)); // Print all area names, line by line

$pdf->SetXY(50, 145); // Starting position for ratings
$pdf->MultiCell(148, 5, implode("\n", $results)); // Print all ratings, line by line

// Add interpretation below the last rating
$last_result_y = 145 + (count($results) * 5); // Calculate Y position dynamically based on the number of ratings
$pdf->SetXY(50, $last_result_y + 10); // Add some spacing after the last rating
$pdf->SetFont('Arial', 'B', 12); // Bold font for emphasis
$pdf->Write(0, "$interpretation");

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
    // Handle error if needed
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
        $sql_insert = "INSERT INTO summary (team_id, areas, results, evaluator, evaluator_signature, summary_file) VALUES (?, ?, ?, ?, ?, ?) 
                       ON DUPLICATE KEY UPDATE areas = VALUES(areas), results = VALUES(results), evaluator = VALUES(evaluator), 
                       evaluator_signature = VALUES(evaluator_signature), summary_file = VALUES(summary_file)";

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

        // === New Logic to Combine NDA Files and Approved Assessments ===

        // Initialize FPDI for NDA and Approved Assessments compilation
        $compiled_pdf = new FPDI();

        // === Step 1: Add the Summary PDF as the first page ===
        $compiled_pdf->AddPage();
        $compiled_pdf->setSourceFile($output_path);
        $tplIdx = $compiled_pdf->importPage(1);
        $compiled_pdf->useTemplate($tplIdx);

        // === Step 2: Retrieve all team IDs associated with the schedule_id ===
        $sql_all_teams = "SELECT id FROM team WHERE schedule_id = ?";
        $stmt_all_teams = $conn->prepare($sql_all_teams);
        $stmt_all_teams->bind_param("i", $schedule_id);
        $stmt_all_teams->execute();
        $result_all_teams = $stmt_all_teams->get_result();

        $team_ids = [];
        while ($row = $result_all_teams->fetch_assoc()) {
            $team_ids[] = $row['id'];
        }
        $stmt_all_teams->close();

        if (!empty($team_ids)) {
            // Prepare statements outside the loop for efficiency
            $sql_assessment = "SELECT id FROM assessment WHERE team_id = ?";
            $stmt_assessment = $conn->prepare($sql_assessment);

            $sql_approved_assessment = "SELECT approved_assessment_file FROM approved_assessment WHERE assessment_id = ?";
            $stmt_approved_assessment = $conn->prepare($sql_approved_assessment);

            foreach ($team_ids as $tid) {
                // === Step 3: Retrieve all assessment IDs for the current team ID ===
                $stmt_assessment->bind_param("i", $tid);
                $stmt_assessment->execute();
                $result_assessment = $stmt_assessment->get_result();

                $assessment_ids = [];
                while ($assessment_row = $result_assessment->fetch_assoc()) {
                    $assessment_ids[] = $assessment_row['id'];
                }

                // === Step 4: Retrieve all approved_assessment_files for the current assessment IDs ===
                foreach ($assessment_ids as $aid) {
                    $stmt_approved_assessment->bind_param("i", $aid);
                    $stmt_approved_assessment->execute();
                    $stmt_approved_assessment->bind_result($approved_file);
                    while ($stmt_approved_assessment->fetch()) {
                        if (file_exists($approved_file)) {
                            // === Step 5: Add each approved_assessment_file to the compiled PDF ===
                            $compiled_pdf->AddPage();
                            $compiled_pdf->setSourceFile($approved_file);
                            $tplIdx = $compiled_pdf->importPage(1);
                            $compiled_pdf->useTemplate($tplIdx);
                        }
                    }
                    $stmt_approved_assessment->free_result();
                }
            }

            // Close prepared statements
            $stmt_assessment->close();
            $stmt_approved_assessment->close();
        }

        // === Step 6: Save the compiled PDF ===
        $compiled_output_path = 'Summary/' . $team_id . '_summary_compilation.pdf';
        $compiled_pdf->Output('F', $compiled_output_path);

        // === Step 7: Insert the compiled PDF path into the summary table ===
        $sql_update_summary = "UPDATE summary SET summary_compilation_file = ? WHERE team_id = ?";
        $stmt_update_summary = $conn->prepare($sql_update_summary);
        $stmt_update_summary->bind_param("si", $compiled_output_path, $team_id);
        $stmt_update_summary->execute();
        $stmt_update_summary->close();

        // === Optional: Handle NDA Compilation Separately if Needed ===
        // If you still need to compile NDA files as per your original code, you can retain or modify that section here.

        // Set session variable to indicate successful submission
        $_SESSION['summary_submitted'] = true;
        $message = "Summary and compilation submitted successfully.";
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
            <div class="popup-text"><?php echo htmlspecialchars($message); ?></div>
            <div style="height: 50px; width: 0px;"></div>
            <a href="javascript:void(0);" class="okay" onclick="closePopup()">Okay</a>
            <div style="height: 100px; width: 0px;"></div>
            <div class="hairpop-up"></div>
        </div>
    </div>
</body>
</html>
