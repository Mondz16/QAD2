<?php
session_start();
require 'connection.php';
require 'vendor/autoload.php'; // Ensure the autoload file is correctly referenced

use setasign\Fpdi\Fpdi;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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

$success = false; // To track if the process was successful
$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $team_id = $_POST['team_id'];
    $assessment_file = $_POST['assessment_file'];
    $team_leader_name = $_POST['team_leader'];

    // Fetch the assessment_id based on the team_id
    $stmt = $conn->prepare("SELECT id FROM assessment WHERE team_id = ?");
    $stmt->bind_param("i", $team_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        $message = "No assessment found for the given team ID.";
    } else {
        $assessment = $result->fetch_assoc();
        $assessment_id = $assessment['id'];
        $stmt->close();

        // Fetch the email and full name of the team member based on the team_id
        $stmt = $conn->prepare("SELECT iu.email, iu.first_name, iu.middle_initial, iu.last_name FROM internal_users iu JOIN team t ON iu.user_id = t.internal_users_id WHERE t.id = ?");
        $stmt->bind_param("i", $team_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            $message = "No user found for the given team ID.";
        } else {
            $user = $result->fetch_assoc();
            $user_email = $user['email'];
            $full_name = $user['first_name'] . ' ' . $user['middle_initial'] . '. ' . $user['last_name'];
            $stmt->close();

            // Handle file upload
            if (isset($_FILES['team_leader_signature']) && $_FILES['team_leader_signature']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath = $_FILES['team_leader_signature']['tmp_name'];
                $fileName = $_FILES['team_leader_signature']['name'];
                $fileNameCmps = explode(".", $fileName);
                $fileExtension = strtolower(end($fileNameCmps));

                $allowedfileExtensions = array('png');
                if (in_array($fileExtension, $allowedfileExtensions)) {
                    $uploadFileDir = './Signatures/';
                    if (!is_dir($uploadFileDir)) {
                        mkdir($uploadFileDir, 0777, true);
                    }

                    // Read the image file into binary data
                    $signature_data = file_get_contents($fileTmpPath);

                    // Encrypt the binary data
                    $encryption_key = 'your-encryption-key-here'; // Use a secure method to generate and store the encryption key
                    $encrypted_signature_data = encryptData($signature_data, $encryption_key);

                    // Store the encrypted data in a file
                    $encrypted_signature_path = $uploadFileDir . basename($fileName) . '.enc';
                    file_put_contents($encrypted_signature_path, $encrypted_signature_data);

                    // Remove the plain image file
                    unlink($fileTmpPath);

                    // Load the existing assessment PDF
                    $pdf = new FPDI();
                    $pageCount = $pdf->setSourceFile($assessment_file);
                    for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                        $templateId = $pdf->importPage($pageNo);
                        $pdf->addPage();
                        $pdf->useTemplate($templateId);

                        // Only add the team leader's name and signature on the first page
                        if ($pageNo == 1) {
                            $pdf->SetFont('Arial', '', 12);

                            // Set font and calculate the position to center the text around a specific X coordinate
                            $centerTextX = 158; // The center X coordinate where you want to center the text
                            $textYPosition = 258; // The Y coordinate where you want to place the text
                            $textWidth = $pdf->GetStringWidth($team_leader_name);

                            // Calculate the X position to center the text
                            $textXPosition = $centerTextX - ($textWidth / 2);

                            // Print the QAD Officer's name centered at the specified centerTextX
                            $pdf->SetXY($textXPosition, $textYPosition);
                            $pdf->Write(0, $team_leader_name);

                            // Decrypt the signature image before adding to PDF
                            $decrypted_signature_data = decryptData($encrypted_signature_data, $encryption_key);
                            $temp_signature_path = tempnam(sys_get_temp_dir(), 'sig') . '.png';
                            file_put_contents($temp_signature_path, $decrypted_signature_data);

                            // Calculate the X and Y positions to center the image
                            $centerImageX = 161; // The center X coordinate where you want to center the image
                            $centerImageY = 252; // The center Y coordinate where you want to center the image
                            $imageWidth = 40; // The width of the signature image
                            $imageHeight = 15; // The height of the signature image (adjust based on actual image aspect ratio)
                            $imageXPosition = $centerImageX - ($imageWidth / 2);
                            $imageYPosition = $centerImageY - ($imageHeight / 2);

                            // Add QAD Officer Signature
                            $pdf->Image($temp_signature_path, $imageXPosition, $imageYPosition, $imageWidth, $imageHeight); // Adjust positions as needed

                            // Remove the temporary decrypted signature file
                            unlink($temp_signature_path);
                        }
                    }

                    // Create the directory if it does not exist
                    $approvedAssessmentsDir = './Approved Assessments/';
                    if (!is_dir($approvedAssessmentsDir)) {
                        mkdir($approvedAssessmentsDir, 0777, true);
                    }

                    // Save the modified PDF in the Approved Assessments folder
                    $approvedAssessmentFile = $approvedAssessmentsDir . basename($assessment_file);
                    $pdf->Output($approvedAssessmentFile, 'F');

                    // Send email to the team member
                    $mail = new PHPMailer(true);

                    try {
                        // Server settings
                        $mail->isSMTP();
                        $mail->Host = 'smtp.gmail.com';
                        $mail->SMTPAuth = true;
                        $mail->Username = 'usepqad@gmail.com';
                        $mail->Password = 'vmvf vnvq ileu tmev';
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port = 587;

                        // Bypass SSL certificate verification
                        $mail->SMTPOptions = array(
                            'ssl' => array(
                                'verify_peer' => false,
                                'verify_peer_name' => false,
                                'allow_self_signed' => true
                            )
                        );

                        //Recipients
                        $mail->setFrom('usepqad@example.com', 'USeP - Quality Assurance Division');
                        $mail->addAddress($user_email); // Add a recipient

                        // Attachments
                        $mail->addAttachment($approvedAssessmentFile); // Add attachments

                        // Content
                        $mail->isHTML(true); // Set email format to HTML
                        $mail->Subject = 'Assessment Approved';
                        $mail->Body    = 'Dear ' . $full_name . ',<br><br>Your assessment has been approved by the your team leader. Please find the approved assessment attached.<br><br>Best Regards,<br>USeP - Quality Assurance Division';
                        $mail->AltBody = 'Dear ' . $full_name . ',\n\nYour assessment has been approved by the team leader. Please find the approved assessment attached.\n\nBest Regards,\nUSeP - Quality Assurance Division';

                        $mail->send();

                        // Insert the approved assessment details into the approved_assessment table only if email is sent successfully
                        $stmt = $conn->prepare("INSERT INTO approved_assessment (assessment_id, team_leader, team_leader_signature, approved_assessment_file) VALUES (?, ?, ?, ?)");
                        $stmt->bind_param("isss", $assessment_id, $team_leader_name, $encrypted_signature_path, $approvedAssessmentFile);
                        $stmt->execute();
                        $stmt->close();

                        $success = true;
                        $message = 'Assessment approved successfully.';
                    } catch (Exception $e) {
                        $message = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
                        // Remove the approved assessment file if email fails
                        if (file_exists($approvedAssessmentFile)) {
                            unlink($approvedAssessmentFile);
                        }
                    }
                } else {
                    $message = "Error moving the uploaded file.";
                }
            } else {
                $message = "Upload failed. Allowed file types: " . implode(',', $allowedfileExtensions);
            }
        }
    }
} else {
    $message = "Invalid request method.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assessment Approval</title>
    <link rel="stylesheet" href="index.css">
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var popup = document.getElementById('successPopup');
            popup.style.display = 'block';

            document.getElementById('closeSuccessBtn').addEventListener('click', function() {
                popup.style.display = 'none';
            });

            document.getElementById('closePopup').addEventListener('click', function() {
                popup.style.display = 'none';
            });

            window.addEventListener('click', function(event) {
                if (event.target == popup) {
                    popup.style.display = 'none';
                }
            });
        });
    </script>
</head>
<body>
    <div id="successPopup" class="popup">
        <div class="popup-content">
            <span class="close-btn" id="closeSuccessBtn">&times;</span>
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
</body>
</html>
