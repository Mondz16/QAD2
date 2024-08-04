<?php
session_start();
include 'connection.php'; // Include your database connection file
require 'vendor/autoload.php'; // Include PHPMailer via Composer autoload

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function resetPassword($user_id, $email, $prefix, $gender, $gender_others) {
    global $conn;
    $new_password = password_hash($user_id, PASSWORD_DEFAULT);
    $tables = ['internal_users', 'external_users'];
    $email_sent = false;

    foreach ($tables as $table) {
        // Fetch full name and email
        $query = "SELECT prefix, first_name, middle_initial, last_name, email FROM $table WHERE user_id=? AND email=? AND prefix=? AND gender=?";
        $stmt = $conn->prepare($query);
        
        // Determine gender value for comparison
        $gender_value = ($gender === 'Others') ? $gender_others : $gender;

        $stmt->bind_param('ssss', $user_id, $email, $prefix, $gender_value);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($db_prefix, $first_name, $middle_initial, $last_name, $db_email);

        if ($stmt->num_rows > 0 && $stmt->fetch()) {
            // Construct full name
            $full_name = trim("$db_prefix $first_name $middle_initial $last_name");

            // Attempt to send email
            if (sendEmail($db_email, $user_id, $full_name)) {
                // Update password only if email is sent successfully
                $stmt->close();

                $update_query = "UPDATE $table SET password=? WHERE user_id=? AND email=? AND prefix=? AND gender=?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param('sssss', $new_password, $user_id, $email, $prefix, $gender_value);
                $update_stmt->execute();
                $email_sent = ($update_stmt->affected_rows > 0);
                $update_stmt->close();
                break;
            } else {
                $_SESSION['error'] = "Email could not be sent.";
                return false;
            }
        }
        $stmt->close();
    }
    return $email_sent;
}

function sendEmail($toEmail, $user_id, $full_name) {
    $mail = new PHPMailer(true);

    try {
        //Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Set the SMTP server to send through
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

        //Recipients
        $mail->setFrom('usepqad@gmail.com', 'USeP - Quality Assurance Division');
        $mail->addAddress($toEmail);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Notification';
        $mail->Body = "Dear $full_name,<br><br>Your password has been reset. Your new password is your User ID: <strong>$user_id</strong>.<br>Please change your password after logging in.<br><br>Best regards,<br>USeP - Quality Assurance Division";

        $mail->send();
        return true;
    } catch (Exception $e) {
        $_SESSION['error'] = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_POST['user_id'];
    $email = $_POST['email'];
    $prefix = $_POST['prefix'];
    $gender = $_POST['gender'];
    $gender_others = $_POST['gender_others'];

    if (empty($user_id) || empty($email) || empty($prefix) || empty($gender) || ($gender == 'Others' && empty($gender_others))) {
        $_SESSION['error'] = "All fields are required.";
    } else {
        if (resetPassword($user_id, $email, $prefix, $gender, $gender_others)) {
            $_SESSION['success'] = "Password has been reset. Your new password is your User ID.";
        } else {
            $_SESSION['error'] = "No matching user found or email could not be sent.";
        }
    }

    header('Location: forgot_password.php');
    exit;
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" />
</head>
<body>
    <div class="wrapper">
        <div class="hair" style="height: 15px; background: linear-gradient(275.52deg, #973939 0.28%, #DC7171 100%);"></div>
        <div class="container">
            <div class="header">
                <div class="headerLeft">
                    <div class="USePData">
                        <img class="USeP" src="images/USePLogo.png" height="36">
                        <div style="height: 0px; width: 16px;"></div>
                        <div style="height: 32px; width: 1px; background: #E5E5E5"></div>
                        <div style="height: 0px; width: 16px;"></div>
                        <div class="headerLeftText">
                            <div class="onedata" style="height: 100%; width: 100%; display: flex; flex-flow: unset; place-content: unset; align-items: unset; overflow: unset;">
                                <h><span class="one" style="color: rgb(229, 156, 36); font-weight: 600; font-size: 18px;">One</span>
                                    <span class="datausep" style="color: rgb(151, 57, 57); font-weight: 600; font-size: 18px;">Data.</span>
                                    <span class="one" style="color: rgb(229, 156, 36); font-weight: 600; font-size: 18px;">One</span>
                                    <span class="datausep" style="color: rgb(151, 57, 57); font-weight: 600; font-size: 18px;">USeP.</span></h>
                            </div>
                            <h>Accreditor Portal</h>
                        </div>
                    </div>
                </div>

                <div class="headerRight">
                    <div class="QAD">
                        <div class="headerRightText">
                            <h style="color: rgb(87, 87, 87); font-weight: 600; font-size: 16px;">Quality Assurance Division</h>
                        </div>
                        <div style="height: 0px; width: 16px;"></div>
                        <div style="height: 32px; width: 1px; background: #E5E5E5"></div>
                        <div style="height: 0px; width: 16px;"></div>
                        <img class="USeP" src="images/QADLogo.png" height="36">
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="container">
        <div class="body1">
            <div class="bodyLeft">
                <div style="height: 150px; width: 0px;"></div>
                <div class="bodyLeftText" style="color: rgb(87, 87, 87); font-weight: 500; font-size: 3rem;">
                    <h>Forgot Password?</h>
                </div>

                <div style="height: 8px; width: 0px;"></div>

                <div class="bodyLeftText" style="color: rgb(87, 87, 87); font-weight: 300; font-size: 17px;">
                    <h>Please enter your information below to reset your<br> password. After successful reset, your new password will be your User ID.</h>
                </div>

                <div style="height: 32px; width: 0px;"></div>

                <form method="post" action="forgot_password.php">
                    <div class="prefixContainer" style="width: 455px;">
                        <div class="custom-select-wrapper">
                            <select class="prefix" name="prefix" required>
                                <option value="">Prefix</option>
                                <option value="Mr.">Mr.</option>
                                <option value="Ms.">Ms.</option>
                                <option value="Mrs.">Mrs.</option>
                                <option value="Dr.">Dr.</option>
                                <option value="Prof.">Prof.</option>
                                <option value="Assoc. Prof.">Assoc. Prof.</option>
                                <option value="Assist. Prof.">Assist. Prof.</option>
                                <option value="Engr.">Engr.</option>
                                <!-- Add more options as needed -->
                            </select>
                        </div>
                    </div>
                    <div style="height: 10px; width: 0px;"></div>

                    <div class="username" style="width: 455px;">
                        <div class="usernameContainer" style="padding: 12px 20px; border-color: rgb(170, 170, 170); border-style: solid; border-width: 1px; border-radius: 8px;">
                            <input class="email" type="text" id="user_id" name="user_id" placeholder="User ID" required>
                        </div>
                    </div>
                    <div style="height: 10px; width: 0px;"></div>

                    <div class="username" style="width: 455px;">
                        <div class="usernameContainer" style="padding: 12px 20px; border-color: rgb(170, 170, 170); border-style: solid; border-width: 1px; border-radius: 8px;">
                            <input class="email" type="email" id="email" name="email" placeholder="Email" required>
                        </div>
                    </div>
                    <div style="height: 10px; width: 0px;"></div>

                    <div class="gender" style="width: 455px;">
                        <div class="gender1">
                            <select class="prefix" name="gender" id="genderSelect" required>
                                <option value="">Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Prefer not to say">Prefer not to say</option>
                                <option value="Others">Others</option>
                                <!-- Add more options as needed -->
                            </select>
                            <input type="text" id="genderInput" name="gender_others" style="display:none; width: 455px; padding: 12px 20px; border: 1px solid #aaa; border-radius: 8px; font-size: 1rem; background-color: #fff;" placeholder="Specify Gender">
                        </div>
                    </div>
                    <div style="height: 10px; width: 0px;"></div>

                    <a href="login.php" class="loginstead" >Log in instead</a>
                    <button type="submit" class="reset">Reset Password</button>

                    <div style="height: 10px; width: 0px;"></div>
                </form>
            </div>

            <div class="bodyRight">
                <div style="height: 200px; width: 0px;"></div>
                <img class="USeP" src="images/LoginCover.png" height="400">
            </div>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div id="successPopup" class="popup">
            <div class="popup-content">
                <span class="close-btn" id="closeSuccessBtn">&times;</span>
                <div style="height: 50px; width: 0px;"></div>
                <img class="Success" src="images/Success.png" height="100">
                <div style="height: 20px; width: 0px;"></div>
                <div class="popup-text"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                <div style="height: 50px; width: 0px;"></div>
                <a href="login.php" class="okay" id="closePopup">Okay</a>
                <div style="height: 100px; width: 0px;"></div>
                <div class="hairpop-up"></div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div id="errorPopup" class="popup">
            <div class="popup-content">
                <span class="close-btn" id="closeErrorBtn">&times;</span>
                <div style="height: 50px; width: 0px;"></div>
                <img class="Error" src="images/Error.png" height="100">
                <div style="height: 20px; width: 0px;"></div>
                <div class="popup-text"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <div style="height: 50px; width: 0px;"></div>
                <a href="javascript:void(0);" class="okay" id="closeErrorPopup">Okay</a>
                <div style="height: 100px; width: 0px;"></div>
                <div class="hairpop-up"></div>
            </div>
        </div>
    <?php endif; ?>

    <script>
        // Show/Hide "Specify Gender" input based on selection
        document.getElementById('genderSelect').addEventListener('change', function() {
            var genderSelect = document.getElementById('genderSelect');
            var genderInput = document.getElementById('genderInput');
            if (genderSelect.value === 'Others') {
                genderSelect.style.display = 'none';
                genderInput.style.display = 'block';
                genderInput.required = true;
                genderInput.focus();
            } else {
                genderInput.style.display = 'none';
                genderInput.required = false;
            }
        });

        document.getElementById('genderInput').addEventListener('blur', function() {
            var genderSelect = document.getElementById('genderSelect');
            var genderInput = document.getElementById('genderInput');
            if (genderInput.value === '') {
                genderInput.style.display = 'none';
                genderSelect.style.display = 'block';
            }
        });

        // Success Popup Script
        if (document.getElementById('successPopup')) {
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
        }

        // Error Popup Script
        if (document.getElementById('errorPopup')) {
            document.getElementById('errorPopup').style.display = 'block';

            document.getElementById('closeErrorBtn').addEventListener('click', function() {
                document.getElementById('errorPopup').style.display = 'none';
            });

            document.getElementById('closeErrorPopup').addEventListener('click', function() {
                document.getElementById('errorPopup').style.display = 'none';
            });

            window.addEventListener('click', function(event) {
                if (event.target == document.getElementById('errorPopup')) {
                    document.getElementById('errorPopup').style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>
