<?php
include 'connection.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Ensure PHPMailer is installed and autoloaded
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $scheduleId = $_POST['id'];
    $status = $_POST['status'];

    // Debugging output
    debug_to_console("Received ID: $scheduleId Status: $status");

    if (in_array($status, ['failed', 'passed'])) {
        $stmt = $conn->prepare("UPDATE schedule SET schedule_status = ?, status_date = NOW() WHERE id = ?");
        $stmt->bind_param('si', $status, $scheduleId);

        if ($stmt->execute()) {
            // Perform program level checks and updates
            handleProgramLevelUpdates($scheduleId, $status);

            // Notify the college
            notifyCollege($scheduleId, $status);

            echo 'success';
            header('Location: dashboard.php'); // Redirect after success
        }
        else{
            echo 'error';
        }
    } else {
        error_log("Invalid status received: $status");
        echo 'invalid status';
    }
} else {
    debug_to_console("Invalid request method");
    echo 'invalid request';
}

$conn->close();

// Function to handle program level updates based on the given criteria
function handleProgramLevelUpdates($scheduleId, $status)
{
    global $conn;

    // Retrieve program and level information associated with the schedule
    $sql = "SELECT p.id AS program_id, p.program_level_id, plh.program_level, s.level_applied
            FROM schedule s
            JOIN program p ON s.program_id = p.id
            LEFT JOIN program_level_history plh ON p.id = plh.program_id AND p.program_level_id = plh.id
            WHERE s.id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $scheduleId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $programId = $row['program_id'];
        $programLevelId = $row['program_level_id'];
        $currentProgramLevel = $row['program_level'];
        $levelApplied = $row['level_applied'];
        debug_to_console("Program ID: $programId, Program Level: $programLevelId , Current Level: $currentProgramLevel , Level Applied: {$levelApplied}");

        if ($status == 'passed') {
            if ($currentProgramLevel != 4) {
                // Check if the level_applied exists in the program_level_history
                $stmtCheck = $conn->prepare("SELECT COUNT(*) FROM program_level_history WHERE program_id = ? AND program_level = ?");
                $stmtCheck->bind_param('is', $programId, $levelApplied);
                $stmtCheck->execute();
                $stmtCheck->bind_result($count);
                $stmtCheck->fetch();
                $stmtCheck->close();
                debug_to_console("Program Level Passed and Exists! $programId | $levelApplied");

                if ($count == 0) {
                    // Insert new level into program_level_history
                    $stmtInsert = $conn->prepare("INSERT INTO program_level_history (program_id, program_level, date_received) VALUES (?, ?, CURDATE())");
                    $stmtInsert->bind_param('is', $programId, $levelApplied);
                    $stmtInsert->execute();
                    $stmtInsert->close();

                    // Update program_level_id in program table
                    $stmtUpdate = $conn->prepare("UPDATE program SET program_level_id = (SELECT MAX(id) FROM program_level_history WHERE program_id = ?) WHERE id = ?");
                    $stmtUpdate->bind_param('ii', $programId, $programId);
                    $stmtUpdate->execute();
                    $stmtUpdate->close();
                    debug_to_console("Inserted New Program Level! $levelApplied");
                }
                else{
                    $stmtCheckLevel = $conn->prepare("SELECT id FROM program_level_history WHERE program_id = ? AND program_level = ?");
                    $stmtCheckLevel->bind_param('is', $programId, $levelApplied);
                    $stmtCheckLevel->execute();
                    $stmtCheckLevel->bind_result($levelId);
                    $levelExists = $stmtCheckLevel->fetch();
                    $stmtCheckLevel->close();

                    if($levelExists){
                        // Program level is already 4, just update the date_received
                        $stmtUpdateDate = $conn->prepare("UPDATE program_level_history SET date_received = CURDATE() WHERE program_id = ? AND program_level = ?");
                        $stmtUpdateDate->bind_param('is', $programId, $levelApplied);
                        $stmtUpdateDate->execute();
                        $stmtUpdateDate->close();

                        // Update program_level_id in program table to the existing level 3
                        $stmtUpdateToLevel = $conn->prepare("UPDATE program SET program_level_id = ? WHERE id = ?");
                        $stmtUpdateToLevel->bind_param('ii', $levelId, $programId);
                        $stmtUpdateToLevel->execute();
                        $stmtUpdateToLevel->close();
                        debug_to_console("Program level is already 4, just update the date_received! $levelApplied");
                    }
                }
            } else {
                $stmtCheckLevel = $conn->prepare("SELECT id FROM program_level_history WHERE program_id = ? AND program_level = ?");
                $stmtCheckLevel->bind_param('is', $programId, $currentProgramLevel);
                $stmtCheckLevel->execute();
                $stmtCheckLevel->bind_result($levelId);
                $levelExists = $stmtCheckLevel->fetch();
                $stmtCheckLevel->close();

                if($levelExists){
                    // Program level is already 4, just update the date_received
                    $stmtUpdateDate = $conn->prepare("UPDATE program_level_history SET date_received = CURDATE() WHERE program_id = ? AND program_level = ?");
                    $stmtUpdateDate->bind_param('is', $programId, $currentProgramLevel);
                    $stmtUpdateDate->execute();
                    $stmtUpdateDate->close();

                    // Update program_level_id in program table to the existing level 3
                    $stmtUpdateToLevel = $conn->prepare("UPDATE program SET program_level_id = ? WHERE id = ?");
                    $stmtUpdateToLevel->bind_param('ii', $levelId, $programId);
                    $stmtUpdateToLevel->execute();
                    $stmtUpdateToLevel->close();
                    debug_to_console("Program level is already 4, just update the date_received! $currentProgramLevel");
                }
            }
        } elseif ($status == 'failed') {
            if ($currentProgramLevel == 4) {
                // Check if level 3 exists in program_level_history
                $stmtCheckLevel3 = $conn->prepare("SELECT id FROM program_level_history WHERE program_id = ? AND program_level = '3'");
                $stmtCheckLevel3->bind_param('i', $programId);
                $stmtCheckLevel3->execute();
                $stmtCheckLevel3->bind_result($level3Id);
                $level3Exists = $stmtCheckLevel3->fetch();
                $stmtCheckLevel3->close();

                if ($level3Exists) {
                    // Update program_level_id in program table to the existing level 3
                    $stmtUpdateToLevel3 = $conn->prepare("UPDATE program SET program_level_id = ? WHERE id = ?");
                    $stmtUpdateToLevel3->bind_param('ii', $level3Id, $programId);
                    $stmtUpdateToLevel3->execute();
                    $stmtUpdateToLevel3->close();
                } else {
                    // Insert level 3 into program_level_history
                    $stmtInsertLevel3 = $conn->prepare("INSERT INTO program_level_history (program_id, program_level, date_received) VALUES (?, '3', CURDATE())");
                    $stmtInsertLevel3->bind_param('i', $programId);
                    $stmtInsertLevel3->execute();

                    // Get the ID of the newly inserted level 3
                    $newLevel3Id = $stmtInsertLevel3->insert_id;
                    $stmtInsertLevel3->close();

                    // Update program_level_id in program table to the newly inserted level 3
                    $stmtUpdateToLevel3 = $conn->prepare("UPDATE program SET program_level_id = ? WHERE id = ?");
                    $stmtUpdateToLevel3->bind_param('ii', $newLevel3Id, $programId);
                    $stmtUpdateToLevel3->execute();
                    $stmtUpdateToLevel3->close();
                }
            }
        }
    }

    $stmt->close();
}

// Function to notify the college
function notifyCollege($scheduleId, $status)
{
    global $conn;

    // Retrieve college information associated with the schedule
    $sql = "SELECT c.college_email, c.college_name, p.program_name
            FROM schedule s
            JOIN college c ON s.college_code = c.code
            JOIN program p ON s.program_id = p.id
            WHERE s.id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $scheduleId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $email = $row['college_email'];
        $collegeName = $row['college_name'];
        $programName = $row['program_name'];

        // Send email to the college
        sendEmailNotification($email, $collegeName, $programName, $status);
    }

    $stmt->close();
}

// Function to send email notification using PHPMailer
function sendEmailNotification($email, $collegeName, $programName, $status)
{
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'usepqad@gmail.com'; // SMTP username
        $mail->Password = 'vmvf vnvq ileu tmev'; // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Optional: Disable SSL certificate verification
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        // Recipients
        $mail->setFrom('usepqad@gmail.com', 'USeP - Quality Assurance Division');
        $mail->addAddress($email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Program Result Notification';

        if ($status == 'passed') {
            $mail->Body = "Dear $collegeName,<br><br>The $programName has been marked as <strong>Passed</strong>.<br><br>Best regards,<br>USeP - Quality Assurance Division";
        } else if ($status == 'failed') {
            $mail->Body = "Dear $collegeName,<br><br>The $programName has been marked as <strong>Failed</strong>.<br><br>Best regards,<br>USeP - Quality Assurance Division";
        }

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email could not be sent to $email. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

function debug_to_console($data)
{
    $output = $data;
    if (is_array($output))
        $output = implode(',', $output);

    echo "<script>console.log('Debug Objects: " . $output . "' );</script>";
}
