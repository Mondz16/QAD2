<?php
session_start();
require 'connection.php';
require 'vendor/autoload.php'; // Ensure the autoload file is correctly referenced

use setasign\Fpdi\Fpdi;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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
        $fileSize = $_FILES['qadOfficerSignature']['size'];
        $fileType = $_FILES['qadOfficerSignature']['type'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        $allowedfileExtensions = array('png');
        if (in_array($fileExtension, $allowedfileExtensions)) {
            $uploadFileDir = './Signatures/';
            $signaturePath = $uploadFileDir . $fileName;

            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0777, true);
            }

            if (move_uploaded_file($fileTmpPath, $signaturePath)) {
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

                // Calculate the X and Y positions to center the image
                $centerImageX = 161; // The center X coordinate where you want to center the image
                $centerImageY = 252; // The center Y coordinate where you want to center the image
                $imageWidth = 40; // The width of the signature image
                $imageHeight = 15; // The height of the signature image (adjust based on actual image aspect ratio)
                $imageXPosition = $centerImageX - ($imageWidth / 2);
                $imageYPosition = $centerImageY - ($imageHeight / 2);

                // Add QAD Officer Signature
                $pdf->Image($signaturePath, $imageXPosition, $imageYPosition, $imageWidth, $imageHeight); // Adjust positions as needed

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
                    $stmt->bind_param("isss", $summaryId, $qadOfficerName, $signaturePath, $modifiedSummaryPath);
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

                    echo "Summary approved successfully and email sent.";
                } catch (Exception $e) {
                    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
                }
            } else {
                echo "Error moving the uploaded file.";
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
