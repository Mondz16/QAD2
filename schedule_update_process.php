<?php
include 'connection.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Ensure PHPMailer is installed and autoloaded

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['schedule_id'])) {
    $schedule_id = mysqli_real_escape_string($conn, $_POST['schedule_id']);
    $new_date = mysqli_real_escape_string($conn, $_POST['new_date']);
    $new_time = mysqli_real_escape_string($conn, $_POST['new_time']);
    $new_zoom = mysqli_real_escape_string($conn, $_POST['new_zoom']); // Get the new Zoom link
    $reason = mysqli_real_escape_string($conn, $_POST['reason']); // Get the reason

    // Capture college name and code from the form
    $college_name = mysqli_real_escape_string($conn, $_POST['college']);
    $actual_college_code = mysqli_real_escape_string($conn, $_POST['college_code']);

    // Format the new date and time
    $formatted_new_date = date("F j, Y", strtotime($new_date));
    $formatted_new_time = date("g:i A", strtotime($new_time));

    // Check for conflicting schedules
    $conflict_sql = "SELECT s.id, s.college_code, s.schedule_date, s.schedule_time 
                     FROM schedule s 
                     WHERE s.schedule_date = ? 
                     AND s.schedule_time = ? 
                     AND s.schedule_status IN ('pending', 'approved') 
                     AND s.id != ?";
    
    $stmt_conflict = $conn->prepare($conflict_sql);
    $stmt_conflict->bind_param("ssi", $new_date, $new_time, $schedule_id);
    $stmt_conflict->execute();
    $conflict_result = $stmt_conflict->get_result();

    $conflict_found = false;

    while ($row = $conflict_result->fetch_assoc()) {
        if ($row['college_code'] !== $actual_college_code) {
            // Conflict found with a different college
            $conflict_found = true;
            break;
        }
    }

    if ($conflict_found) {
        // Capture the current college and college_code
        $redirect_url = "schedule_college.php?college=" . urlencode($college_name) . "&college_code=" . urlencode($actual_college_code);
        echo "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Operation Result</title>
            <link rel='stylesheet' href='https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600&display=swap'>
            <link rel='stylesheet' href='index.css'>
            <style>
                .popup {
            display: block;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .popup-content {
            background-color: #fff;
            margin: 10% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
            text-align: center;
            border-radius: 10px;
            position: relative;
        }

        .popup-image {
            max-width: 100%;
            height: auto;
        }

        .popup-text {
            margin: 20px 50px;
            font-size: 17px;
            font-weight: 500;
        }

        .hairpop-up {
            height: 15px;
            background: #9B0303;
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            width: 100%;
            border-bottom-left-radius: 10px;
            border-bottom-right-radius: 10px;
        }

        .okay {
            color: black;
            text-decoration: none;
            white-space: unset;
            font-size: 1rem;
            font-weight: bold;
            text-transform: uppercase;
            border: 1px solid;
            border-radius: 10px;
            cursor: pointer;
            padding: 16px 55px;
            min-width: 120px;
        }

        .okay:hover {
            background-color: #EAEAEA;
        }
            </style>
        </head>
        <body>
        <div id='errorPopup' class='popup'>
            <div class='popup-content'>
                <div style='height: 50px; width: 0px;'></div>
                <img class='Error' src='images/Error.png' height='100'>
                <div style='height: 20px; width: 0px;'></div>
                <div class='popup-text'>A schedule for the selected date already exists.</div>
                <div style='height: 50px; width: 0px;'></div>
                <a href='#' class='okay' id='closeErrorPopup'>Okay</a>
                <div style='height: 100px; width: 0px;'></div>
                <div class='hairpop-up'></div>
            </div>
        </div>
        <script>
        document.getElementById('closeErrorPopup').addEventListener('click', function() {
            window.location.href = '$redirect_url';
        });
    </script>";
        
        // Stop further processing to avoid showing the success message
        exit();
    } else {
        // Proceed with updating the schedule if no conflict
        // Fetch the old date and time before updating
        $sql_get_old_schedule = "SELECT schedule_date, schedule_time, zoom FROM schedule WHERE id = ?";
        $stmt_get_old_schedule = $conn->prepare($sql_get_old_schedule);
        $stmt_get_old_schedule->bind_param("i", $schedule_id);
        $stmt_get_old_schedule->execute();
        $old_schedule_result = $stmt_get_old_schedule->get_result();
        $old_schedule = $old_schedule_result->fetch_assoc();

        $formatted_old_date = date("F j, Y", strtotime($old_schedule['schedule_date']));
        $formatted_old_time = date("g:i A", strtotime($old_schedule['schedule_time']));

        // Start transaction
        $conn->begin_transaction();

        try {
            // Update schedule with new date and time
            $sql_update_schedule = "UPDATE schedule SET schedule_date = ?, schedule_time = ? WHERE id = ?";
            $stmt_update_schedule = $conn->prepare($sql_update_schedule);
            $stmt_update_schedule->bind_param("ssi", $new_date, $new_time, $schedule_id);
            $stmt_update_schedule->execute();

            // Update the Zoom link if provided
            if (!empty($new_zoom)) {
                $sql_update_zoom = "UPDATE schedule SET zoom = ? WHERE id = ?";
                $stmt_update_zoom = $conn->prepare($sql_update_zoom);
                $stmt_update_zoom->bind_param("si", $new_zoom, $schedule_id);
                $stmt_update_zoom->execute();
            }

            // If schedule update is successful, update team status
            $new_status = 'pending'; // or whatever logic you have for setting the new status

            $sql_update_team_status = "UPDATE team SET status = ? WHERE schedule_id = ?";
            $stmt_update_team_status = $conn->prepare($sql_update_team_status);
            $stmt_update_team_status->bind_param("si", $new_status, $schedule_id);

            if ($stmt_update_team_status->execute()) {
                // Fetch schedule details
                $sql_get_schedule_details = "SELECT s.college_code, p.program_name, s.level_applied FROM schedule s JOIN program p ON s.program_id = p.id WHERE s.id = ?";
                $stmt_get_schedule_details = $conn->prepare($sql_get_schedule_details);
                $stmt_get_schedule_details->bind_param("i", $schedule_id);
                $stmt_get_schedule_details->execute();
                $schedule_result = $stmt_get_schedule_details->get_result();
                $schedule_details = $schedule_result->fetch_assoc();

                // Fetch college name
                $sql_get_college_name = "SELECT college_name FROM college WHERE code = ?";
                $stmt_get_college_name = $conn->prepare($sql_get_college_name);
                $stmt_get_college_name->bind_param("s", $schedule_details['college_code']);
                $stmt_get_college_name->execute();
                $college_result = $stmt_get_college_name->get_result();
                $college_name_row = $college_result->fetch_assoc();
                $college_name = $college_name_row['college_name'];

                // Fetch college email
                $sql_get_college_email = "SELECT college_email FROM college WHERE code = ?";
                $stmt_get_college_email = $conn->prepare($sql_get_college_email);
                $stmt_get_college_email->bind_param("s", $schedule_details['college_code']);
                $stmt_get_college_email->execute();
                $college_result = $stmt_get_college_email->get_result();
                $college_email_row = $college_result->fetch_assoc();
                $college_email = $college_email_row['college_email'];

                // Fetch team leader and team members' email addresses
                $sql_get_emails = "SELECT iu.email FROM team t JOIN internal_users iu ON t.internal_users_id = iu.user_id WHERE t.schedule_id = ?";
                $stmt_get_emails = $conn->prepare($sql_get_emails);
                $stmt_get_emails->bind_param("i", $schedule_id);
                $stmt_get_emails->execute();
                $result = $stmt_get_emails->get_result();

                // Initialize PHPMailer
                $mail = new PHPMailer(true);
                $email_success = false;
                $email_error = '';

                try {
                    // Server settings
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com'; // Set the SMTP server to send through
                    $mail->SMTPAuth = true;
                    $mail->Username = 'usepqad@gmail.com'; // SMTP username
                    $mail->Password = 'vmvf vnvq ileu tmev'; // SMTP password (App Password if 2FA enabled)
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
                    $mail->addReplyTo('usepqad@gmail.com', 'Information');

                    // Add college email
                    $mail->addAddress($college_email);

                    // Add team members' emails
                    while ($row = $result->fetch_assoc()) {
                        $mail->addAddress($row['email']);
                    }

                    // Prepare the Zoom link section for the email, if provided
                    $zoom_link_section = !empty($new_zoom) ? "<b>New Meeting Link:</b> {$new_zoom}<br>" : "";

                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = 'Schedule Rescheduled';
                    $mail->Body = "
                        Dear Team,<br><br>
                        The schedule below has been rescheduled from <b style='color:red;'>{$formatted_old_date} at {$formatted_old_time}</b> to <b style='color:green;'>{$formatted_new_date} at {$formatted_new_time}</b> with the following details:<br>
                        <b>College:</b> {$college_name}<br>
                        <b>Program:</b> {$schedule_details['program_name']}<br>
                        <b>Level Applied:</b> {$schedule_details['level_applied']}<br>
                        {$zoom_link_section}
                        <b>Reason:</b> $reason<br><br>
                        Best regards,<br>
                        USeP - Quality Assurance Division
                    ";

                    $mail->send();
                    $conn->commit();
                    $email_success = true;
                } catch (Exception $e) {
                    $conn->rollback();
                    $email_error = "Schedule update and email notification failed due to internet problem.";
                }

                $stmt_get_emails->close();
                $stmt_get_schedule_details->close();
                $stmt_get_college_email->close();
                $stmt_get_college_name->close();
            } else {
                $conn->rollback();
                $error_message = "Error updating team status: " . $conn->error;
            }
            $stmt_update_team_status->close();
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Transaction failed: " . $e->getMessage();
        }

        $conn->close();
    }
} else {
    $error_message = "This page should only be accessed through a valid form submission.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Operation Result</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600&display=swap">
    <link rel="stylesheet" href="index.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Quicksand", sans-serif;
        }

        .popup {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
        }
        
        body {
            background-color: #f9f9f9;
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
<div id="successPopup" class="popup" style="display: none;">
    <div class="popup-content">
        <div style='height: 50px; width: 0px;'></div>

        <?php if (isset($email_success) && $email_success): ?>
            <!-- Show success message -->
            <img src="images/Success.png" height="100" alt="Success">
            <div style="height: 20px; width: 0px;"></div>
            <span>Schedule updated successfully.<br>Email notifications have been sent.</span>
        
        <?php elseif (isset($email_error) && $email_error): ?>
            <!-- Show email error -->
            <img src="images/Error.png" height="100" alt="Error">
            <div style="height: 20px; width: 0px;"></div>
            <span><?php echo $email_error; ?></span>
        
        <?php elseif (isset($error_message)): ?>
            <!-- Show other errors -->
            <img src="images/Error.png" height="100" alt="Error">
            <div style="height: 20px; width: 0px;"></div>
            <span><?php echo $error_message; ?></span>
        
        <?php endif; ?>

        <div style="height: 50px; width: 0px;"></div>
        <a href="schedule_college.php?college=<?php echo urlencode($college_name); ?>&college_code=<?php echo urlencode($actual_college_code); ?>" class="btn-hover">OKAY</a>
        <div style='height: 100px; width: 0px;'></div>
        <div class='hairpop-up'></div>
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
</body>
</html>