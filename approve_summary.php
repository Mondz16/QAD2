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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $qadOfficerName = $_POST['qadOfficerName'];
    $summaryFile = $_POST['summaryFile'];

    // Get the summary_id from the summary file path
    $stmt = $conn->prepare("SELECT id, team_id FROM summary WHERE summary_file = ?");
    $stmt->bind_param("s", $summaryFile);
    $stmt->execute();
    $result = $stmt->get_result();
    $summary = $result->fetch_assoc();
    $summaryId = $summary['id'];
    $teamId = $summary['team_id'];
    $stmt->close();

    // Get the schedule_id from the team_id
    $stmt = $conn->prepare("SELECT schedule_id FROM team WHERE id = ?");
    $stmt->bind_param("i", $teamId);
    $stmt->execute();
    $stmt->bind_result($scheduleId);
    $stmt->fetch();
    $stmt->close();

    // Handle file upload
    if (isset($_FILES['qadOfficerSignature']) && $_FILES['qadOfficerSignature']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['qadOfficerSignature']['tmp_name'];
        $fileName = $_FILES['qadOfficerSignature']['name'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        $allowedfileExtensions = array('png');
            if (in_array($fileExtension, $allowedfileExtensions)) {
                // Read the image file into binary data
                $signature_data = file_get_contents($fileTmpPath);

                // Encrypt the binary data
                $encrypted_signature_data = encryptData($signature_data, $encryption_key);

                // Remove the plain image file
                unlink($fileTmpPath);

            // Load the existing summary PDF
            $pdf = new FPDI();
            $pageCount = $pdf->setSourceFile($summaryFile);
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $templateId = $pdf->importPage($pageNo);
                $pdf->addPage();
                $pdf->useTemplate($templateId);
            }

            // Set font and calculate the position to center the text around a specific X coordinate
            $pdf->SetFont('Arial', '', 12);
            $centerTextX = 158; // The center X coordinate where you want to center the text
            $textYPosition = 258; // The Y coordinate where you want to place the text
            $textWidth = $pdf->GetStringWidth($qadOfficerName);

            // Calculate the X position to center the text
            $textXPosition = $centerTextX - ($textWidth / 2);

            // Print the QAD Officer's name centered at the specified centerTextX
            $pdf->SetXY($textXPosition, $textYPosition);
            $pdf->Write(0, $qadOfficerName);

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

            // Create the directory if it does not exist
            $approvedSummaryDir = './Approved Summaries/';
            if (!is_dir($approvedSummaryDir)) {
                mkdir($approvedSummaryDir, 0777, true);
            }

            // Save the modified PDF in the Approved Summary folder
            $modifiedSummaryPath = $approvedSummaryDir . basename($summaryFile);
            $pdf->Output($modifiedSummaryPath, 'F');

            // Fetch email of the college associated with the summary
            $stmt = $conn->prepare("
                SELECT c.college_name, c.college_email, p.program_name 
                FROM summary s 
                JOIN team t ON s.team_id = t.id 
                JOIN schedule sch ON t.schedule_id = sch.id 
                JOIN college c ON sch.college_code = c.code 
                JOIN program p ON sch.program_id = p.id 
                WHERE s.id = ?");
            $stmt->bind_param("i", $summaryId);
            $stmt->execute();
            $stmt->bind_result($college_name, $college_email, $program_name);
            $stmt->fetch();
            $stmt->close();

            // Prepare and send email
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
                $mail->addAddress($college_email); // Add a recipient
                $mail->addReplyTo('usepqad@example.com', 'Information');

                // Attachments
                $mail->addAttachment($modifiedSummaryPath);

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Approved Summary Assessment';
                $mail->Body    = "Dear $college_name,<br><br>
                                  The summary assessment of $program_name's schedule has been approved by the Quality Assurance Division. Please find the approved summary assessment attached.<br><br>
                                  Best Regards,<br>
                                  USeP - Quality Assurance Division";

                // Send the email
                $mail->send();

                // Insert the approved summary details into the approved_summary table only if email is sent successfully
                $stmt = $conn->prepare("INSERT INTO approved_summary (summary_id, qad, qad_signature, approved_summary_file) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("isss", $summaryId, $qadOfficerName, $encrypted_signature_data, $modifiedSummaryPath);
                $stmt->execute();
                $stmt->close();

                // Update the status of all team members and the schedule to 'finished'
                $stmt = $conn->prepare("UPDATE team SET status = 'finished' WHERE schedule_id = ?");
                $stmt->bind_param("i", $scheduleId);
                $stmt->execute();
                $stmt->close();

                $stmt = $conn->prepare("UPDATE schedule SET schedule_status = 'finished' WHERE id = ?");
                $stmt->bind_param("i", $scheduleId);
                $stmt->execute();
                $stmt->close();

                // Display success message
                echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Operation Result</title>
    <link rel='stylesheet' href='index.css'>
    <link rel='stylesheet' href='https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600&display=swap'>
<style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Quicksand', sans-serif;
        }
        body {
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }
        h2 {
            font-size: 24px;
            color: #292D32;
            margin-bottom: 20px;
        }

        .message {
            margin-bottom: 20px;
            font-size: 18px;
        }
        .success {
            color: green;
        }

        .error {
            color: red;
        }
        .btn-hover{
            border: 1px solid #AFAFAF;
            text-decoration: none;
            color: black;
            border-radius: 10px;
            padding: 20px 50px;
            font-size: 1rem;
            font-weight: bold;
            text-transform: uppercase;
        }
        .btn-hover:hover {
            background-color: #AFAFAF;
        }
    </style>
    </head>
<body>
    <div id='successPopup' class='popup'>
        <div class='popup-content'>
            <div style='height: 50px; width: 0px;'></div>
            <img class='Success' src='images/Success.png' height='100'>
            <div style='height: 20px; width: 0px;'></div>
            <div class='popup-text'>Summary approved successfully.</div>
            <div style='height: 50px; width: 0px;'></div>
            <a href='assessment.php' class='okay' id='closePopup'>Okay</a>
            <div style='height: 100px; width: 0px;'></div>
            <div class='hairpop-up'></div>
        </div>
    </div>
    <script>
        document.getElementById('successPopup').style.display = 'block';

        document.getElementById('closePopup').addEventListener('click', function() {
            document.getElementById('successPopup').style.display = 'none';
            window.location.href = 'schedule.php';
        });

        window.addEventListener('click', function(event) {
            if (event.target == document.getElementById('successPopup')) {
                document.getElementById('successPopup').style.display = 'none';
                window.location.href = 'schedule.php';
            }
        });
    </script>
</body>
</html>";

            } catch (Exception $e) {
                echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
            }
        } else {
            echo "Upload failed. Allowed file types: " . implode(',', $allowedfileExtensions);
        }
    } else {
        echo "No file uploaded or there was an upload error.";
    }
} else {
    echo "Invalid request method.";
}
?>
