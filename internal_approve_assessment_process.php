<?php
session_start();
require 'connection.php';
require 'vendor/autoload.php'; // Ensure the autoload file is correctly referenced

use setasign\Fpdi\Fpdi;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__, 'sensitive_information.env');
$dotenv->load();

$encryption_key = getenv('ENCRYPTION_KEY'); // Retrieve the encryption key from environment variables

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
$imgnotif = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $team_id = $_POST['team_id'];
    $assessment_file = $_POST['assessment_file'];
    $team_leader_name = $_POST['team_leader'];

    try {
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

            if (isset($_FILES['team_leader_signature']) && $_FILES['team_leader_signature']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath = $_FILES['team_leader_signature']['tmp_name'];
                $fileSize = $_FILES['team_leader_signature']['size'];
                $fileName = $_FILES['team_leader_signature']['name'];
                $fileNameCmps = explode(".", $fileName);
                $fileExtension = strtolower(end($fileNameCmps));

                $allowedfileExtensions = array('png');
                $maxFileSize = 2 * 1024 * 1024;

                if ($fileSize > $maxFileSize) {
                    $imgnotif = "images/error.png";
                    $message = "File size exceeds the maximum allowed size of 2MB.";
                } elseif (!in_array($fileExtension, $allowedfileExtensions)) {
                    $imgnotif = "images/error.png";
                    $message = "Invalid file type. Only PNG files are allowed.";
                } else {
                    $signature_data = file_get_contents($fileTmpPath);

                    $encrypted_signature_data = encryptData($signature_data, $encryption_key);

                    unlink($fileTmpPath);

                    $pdf = new FPDI();
                    $pageCount = $pdf->setSourceFile($assessment_file);
                    for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                        $templateId = $pdf->importPage($pageNo);
                        $pdf->addPage();
                        $pdf->useTemplate($templateId);

                        if ($pageNo == 1) {
                            $pdf->SetFont('Arial', '', 12);

                            $centerTextX = 158; 
                            $textYPosition = 258; 
                            $textWidth = $pdf->GetStringWidth($team_leader_name);

                            $textXPosition = $centerTextX - ($textWidth / 2);

                            $pdf->SetXY($textXPosition, $textYPosition);
                            $pdf->Write(0, $team_leader_name);

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
                        $mail->Password = 'ofcx jwfa ghkv hsgz';
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
                        $mail->Body    = 'Dear ' . $full_name . ',<br><br>Your assessment has been approved by your team leader. Please find the approved assessment attached.<br><br>Best Regards,<br>USeP - Quality Assurance Division';
                        $mail->AltBody = 'Dear ' . $full_name . ',\n\nYour assessment has been approved by your team leader. Please find the approved assessment attached.\n\nBest Regards,\nUSeP - Quality Assurance Division';

                        $mail->send();
                        // Insert the approved assessment details into the approved_assessment table only if email is sent successfully
                        $stmt = $conn->prepare("INSERT INTO approved_assessment (assessment_id, team_leader, team_leader_signature, approved_assessment_file) VALUES (?, ?, ?, ?)");
                        $stmt->bind_param("isss", $assessment_id, $team_leader_name, $encrypted_signature_data, $approvedAssessmentFile);
                        $stmt->execute();
                        $stmt->close();

                        $success = true;
                        $imgnotif = "images/success.png";
                        $message = 'Assessment approved successfully.';
                    } catch (Exception $e) {
                        $imgnotif = "images/error.png";
                        $message = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
                        // Remove the approved assessment file if email fails
                        if (file_exists($approvedAssessmentFile)) {
                            unlink($approvedAssessmentFile);
                        }
                    } finally {
                        // Remove the temporary decrypted signature file
                        if (file_exists($temp_signature_path)) {
                            unlink($temp_signature_path);
                        }
                    }
                }
            } else {
                $imgnotif ="images/error.png";
                $message = "Upload failed. Allowed file types: " . implode(',', $allowedfileExtensions);
            }
        }
    }
}
    catch (mysqli_sql_exception $e) {
        if (strpos($e->getMessage(), 'max_allowed_packet') !== false) {
            $imgnotif = "images/error.png";
            $message = "The electronic signature you are trying to upload exceeds the maximum allowed packet size. Please reduce the file size or contact support.";
        } else {
            $imgnotif = "images/error.png";
            $message = "An unexpected error occurred: " . htmlspecialchars($e->getMessage());
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
            <div style="height: 50px; width: 0px;"></div>
            <img class="Success" src="<?php echo $imgnotif; ?>" height="100">
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
