<?php
session_start();
include 'connection.php'; // Include your database connection file
require 'vendor/autoload.php'; // Include PHPMailer via Composer autoload

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function generateRandomPassword($length = 8) {
    $upper = str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ');
    $lower = str_shuffle('abcdefghijklmnopqrstuvwxyz');
    $numbers = str_shuffle('0123456789');
    $symbols = str_shuffle('!@#$%^&*()_+-=[]{}|;:,.<>?');

    $password = substr($upper, 0, 1) . substr($lower, 0, 1) . substr($numbers, 0, 1) . substr($symbols, 0, 1);
    $remainingLength = $length - 4;

    $allCharacters = $upper . $lower . $numbers . $symbols;
    $password .= substr(str_shuffle($allCharacters), 0, $remainingLength);

    return str_shuffle($password);
}

function resetPassword($user_id, $email, $prefix, $gender, $gender_others) {
    global $conn;
    $new_password_plain = generateRandomPassword();
    $new_password_hashed = password_hash($new_password_plain, PASSWORD_DEFAULT);
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
            if (sendEmail($db_email, $new_password_plain, $full_name)) {
                // Update password only if email is sent successfully
                $stmt->close();

                $update_query = "UPDATE $table SET password=? WHERE user_id=? AND email=? AND prefix=? AND gender=?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param('sssss', $new_password_hashed, $user_id, $email, $prefix, $gender_value);
                $update_stmt->execute();
                $email_sent = ($update_stmt->affected_rows > 0);
                $update_stmt->close();
                return $email_sent;
            } else {
                return false;
            }
        }
        $stmt->close();
    }
    return false;
}

function sendEmail($toEmail, $new_password, $full_name) {
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
        $mail->Body = "Dear $full_name,<br><br>Your password has been reset. Your new password is: <strong>$new_password</strong><br>Please change your password after logging in.<br><br>Best regards,<br>USeP - Quality Assurance Division";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_POST['user_id'];
    $email = $_POST['email'];
    $prefix = $_POST['prefix'];
    $gender = $_POST['gender'];
    $gender_others = $_POST['gender_others'];

    $response = [];

    if (empty($user_id) || empty($email) || empty($prefix) || empty($gender) || ($gender == 'Others' && empty($gender_others))) {
        $response['error'] = "All fields are required.";
    } else {
        if (resetPassword($user_id, $email, $prefix, $gender, $gender_others)) {
            $response['success'] = "Password has been reset. Please check your email for the new password.";
        } else {
            $response['error'] = "No matching user found or email could not be sent.";
        }
    }

    // Return the response as JSON
    echo json_encode($response);
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
                    <h>Please enter your information below to reset your<br> password. After successful reset, your new password will be sent to your email.</h>
                </div>

                <div style="height: 32px; width: 0px;"></div>

                <form id="resetPasswordForm" method="post">
                    <div class="prefixContainer" style="width: 455px;">
                        <div class="custom-select-wrapper">
                            <select class="prefix" name="prefix" id="prefixSelect" required>
                                <option value="">Prefix</option>
                                <option value="Mr.">Mr.</option>
                                <option value="Ms.">Ms.</option>
                                <option value="Mrs.">Mrs.</option>
                                <option value="Dr.">Dr.</option>
                                <option value="Prof.">Prof.</option>
                                <option value="Assoc. Prof.">Assoc. Prof.</option>
                                <option value="Assist. Prof.">Assist. Prof.</option>
                                <option value="Engr.">Engr.</option>
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
                            </select>
                            <input type="text" id="genderInput" name="gender_others" style="display:none; width: 455px; padding: 12px 20px; border: 1px solid #aaa; border-radius: 8px; font-size: 1rem; background-color: #fff;" placeholder="Specify Gender">
                        </div>
                    </div>
                    <div style="height: 10px; width: 0px;"></div>

                    <a href="login.php" class="loginstead">Log in instead</a>
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

    <div id="successPopup" class="popup" style="display: none;">
        <div class="popup-content">
            <div style="height: 50px; width: 0px;"></div>
            <img class="Success" src="images/Success.png" height="100">
            <div style="height: 20px; width: 0px;"></div>
            <div class="popup-text"></div>
            <div style="height: 50px; width: 0px;"></div>
            <a href="login.php" class="okay" id="closeSuccessPopup">Okay</a>
            <div style="height: 100px; width: 0px;"></div>
            <div class="hairpop-up"></div>
        </div>
    </div>

    <div id="errorPopup" class="popup" style="display: none;">
        <div class="popup-content">
            <div style="height: 50px; width: 0px;"></div>
            <img class="Error" src="images/Error.png" height="100">
            <div style="height: 20px; width: 0px;"></div>
            <div class="popup-text" id="errorMessage"></div>
            <div style="height: 50px; width: 0px;"></div>
            <a href="javascript:void(0);" class="okay" id="closeErrorPopup">Okay</a>
            <div style="height: 100px; width: 0px;"></div>
            <div class="hairpop-up"></div>
        </div>
    </div>

    <script>
        // Temporary storage variables
        let tempPrefix = '';
        let tempUserId = '';
        let tempEmail = '';
        let tempGender = '';
        let tempGenderOthers = '';

        // Store user input in temporary variables
        document.getElementById('prefixSelect').addEventListener('change', function(e) {
            tempPrefix = e.target.value;
        });

        document.getElementById('user_id').addEventListener('input', function(e) {
            let userIdInput = e.target.value;

            // Limit to 10 characters
            if (userIdInput.length > 10) {
                userIdInput = userIdInput.slice(0, 10);
            }

            tempUserId = userIdInput;

            // Set the cleaned value back to the input
            e.target.value = userIdInput;
        });

        document.getElementById('email').addEventListener('input', function(e) {
            tempEmail = e.target.value;
        });

        document.getElementById('genderSelect').addEventListener('change', function(e) {
            var genderInput = document.getElementById('genderInput');
            if (e.target.value === 'Others') {
                e.target.style.display = 'none';
                genderInput.style.display = 'block';
                genderInput.required = true;
                genderInput.focus();
                tempGender = e.target.value;
            } else {
                tempGender = e.target.value;
                tempGenderOthers = ''; // Clear other gender input
                genderInput.style.display = 'none';
                genderInput.required = false;
            }
        });

        document.getElementById('genderInput').addEventListener('input', function(e) {
            tempGenderOthers = e.target.value;
        });

        document.getElementById('genderInput').addEventListener('blur', function(e) {
            var genderSelect = document.getElementById('genderSelect');
            if (e.target.value === '') {
                e.target.style.display = 'none';
                genderSelect.style.display = 'block';
            }
        });

        document.getElementById('resetPasswordForm').addEventListener('submit', function(event) {
            event.preventDefault(); // Prevent the default form submission

            // Prepare form data
            let formData = new FormData(this);

            // Send the form data using AJAX
            fetch('forgot_password.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success popup
                    document.querySelector('#successPopup .popup-text').innerText = data.success;
                    document.getElementById('successPopup').style.display = 'block';
                } else {
                    // Show error popup with error message
                    document.getElementById('errorMessage').innerText = data.error;
                    document.getElementById('errorPopup').style.display = 'block';
                }
                restoreInputValues(); // Restore input values
            })
            .catch(error => {
                console.error('Error:', error);
                // Show error popup with a generic error message
                document.getElementById('errorMessage').innerText = "An error occurred. Please try again.";
                document.getElementById('errorPopup').style.display = 'block';
                restoreInputValues(); // Restore input values
            });
        });

        function restoreInputValues() {
            document.getElementById('prefixSelect').value = tempPrefix;
            document.getElementById('user_id').value = tempUserId;
            document.getElementById('email').value = tempEmail;
            document.getElementById('genderSelect').value = tempGender;
            if (tempGender === 'Others') {
                document.getElementById('genderInput').value = tempGenderOthers;
                document.getElementById('genderInput').style.display = 'block';
                document.getElementById('genderSelect').style.display = 'none';
            } else {
                document.getElementById('genderInput').value = '';
                document.getElementById('genderInput').style.display = 'none';
            }
        }

        document.getElementById('closeSuccessPopup').addEventListener('click', function() {
            document.getElementById('successPopup').style.display = 'none';
        });

        document.getElementById('closeErrorPopup').addEventListener('click', function() {
            document.getElementById('errorPopup').style.display = 'none';
        });

        // Optional: Close popups if the user clicks outside of them
        window.addEventListener('click', function(event) {
            if (event.target == document.getElementById('successPopup')) {
                document.getElementById('successPopup').style.display = 'none';
            }
            if (event.target == document.getElementById('errorPopup')) {
                document.getElementById('errorPopup').style.display = 'none';
            }
        });
    </script>
</body>
</html>
