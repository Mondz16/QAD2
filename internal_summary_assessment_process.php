<?php
require 'vendor/autoload.php';
include 'connection.php';
session_start();

use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\StreamReader;

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
    $areas = $_POST['areas'];
    $results = $_POST['results'];
    $evaluator = $_POST['evaluator'];
    $evaluator_signature = $_FILES['evaluator_signature'];

    // Check if result is set and not empty
    $result = isset($_POST['result']) && !empty($_POST['result']) ? $_POST['result'] : null;

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
        $encryption_key = 'your-encryption-key-here'; // Use a secure method to generate and store the encryption key
        $encrypted_signature_data = encryptData($signature_data, $encryption_key);

        // Store the encrypted data in a file
        $encrypted_signature_path = 'signatures/' . basename($evaluator_signature['name']) . '.enc';
        file_put_contents($encrypted_signature_path, $encrypted_signature_data);

        // Remove the plain image file from the temporary location
        unlink($evaluator_signature['tmp_name']);

        // Load PDF template for summary
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

    $pdf->SetXY(50, 145); // Adjust position
    $pdf->MultiCell(148, 5, $results);

    // Add result to the PDF if it is provided
    if ($result) {
        $pdf->SetXY(50, 142); // Adjust position for the result
        $pdf->Write(0, $result);
    }

    $centerTextX = 65; // The center X coordinate where you want to center the text
    $textYPosition = 258; // The Y coordinate where you want to place the text
    $textWidth = $pdf->GetStringWidth($evaluator);

    // Calculate the X position to center the text
    $textXPosition = $centerTextX - ($textWidth / 2);

    // Print the QAD Officer's name centered at the specified centerTextX
    $pdf->SetXY($textXPosition, $textYPosition);
    $pdf->Write(0, $evaluator);

    // Decrypt the signature image before adding to PDF
    $decrypted_signature_data = decryptData($encrypted_signature_data, $encryption_key);
    $temp_signature_path = tempnam(sys_get_temp_dir(), 'sig') . '.png';
    file_put_contents($temp_signature_path, $decrypted_signature_data);

    // Calculate the X and Y positions to center the image
    $centerImageX = 67; // The center X coordinate where you want to center the image
    $centerImageY = 252; // The center Y coordinate where you want to center the image
    $imageWidth = 40; // The width of the signature image
    $imageHeight = 15; // The height of the signature image (adjust based on actual image aspect ratio)
    $imageXPosition = $centerImageX - ($imageWidth / 2);
    $imageYPosition = $centerImageY - ($imageHeight / 2);

    // Add QAD Officer Signature
    $pdf->Image($temp_signature_path, $imageXPosition, $imageYPosition, $imageWidth, $imageHeight); // Adjust positions as needed

    // Remove the temporary decrypted signature file
    unlink($temp_signature_path);

    // Save the filled PDF
    $output_path = 'Summary/' . $team_id . '.pdf';
    $pdf->Output('F', $output_path);

    // Insert summary details into the database
    $sql_insert = "INSERT INTO summary (team_id, areas, results, evaluator, evaluator_signature, summary_file) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bind_param("isssss", $team_id, $areas, $results, $evaluator, $encrypted_signature_path, $output_path);
    $stmt_insert->execute();
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
            <span class="close-btn" onclick="closePopup()">&times;</span>
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
