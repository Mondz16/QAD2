<?php
session_start();

// Check if OTP was verified before allowing access to this page
if (!isset($_SESSION['otp_verified']) || !$_SESSION['otp_verified']) {
    header('Location: forgot_password_verification.php');
    exit;
}

include 'connection.php';

$message = "";
$message1 = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate the passwords
    if (empty($new_password) || empty($confirm_password)) {
        $message = "Please fill in both password fields.";
    } elseif ($new_password !== $confirm_password) {
        $message = "Passwords do not match.";
    } else {
        // Fetch the current password from the database
        $user_id = $_SESSION['user_id'];
        $email = $_SESSION['email'];
        $query = "SELECT password FROM internal_users WHERE user_id = ? AND email = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ss', $user_id, $email);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($current_hashed_password);
        $stmt->fetch();

        // Verify that the new password is not the same as the current password
        if (password_verify($new_password, $current_hashed_password)) {
            $message1 = "The new password cannot be the same as the current password.";
        } else {
            // Hash the new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            // Update the password in the database
            $update_query = "UPDATE internal_users SET password=? WHERE user_id=? AND email=?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param('sss', $hashed_password, $user_id, $email);

            if ($stmt->execute()) {
                // Password update successful
                $message = "Your password has been successfully reset.";
                // Clear the session variables related to OTP and user info
                unset($_SESSION['otp']);
                unset($_SESSION['otp_expiry']);
                unset($_SESSION['otp_verified']);
                unset($_SESSION['user_id']);
                unset($_SESSION['email']);
            } else {
                $message1 = "An error occurred while updating your password. Please try again.";
            }
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .usernameContainer {
            position: relative; /* Make the container relative for absolute positioning of the icon */
        }

        .usernameContainer input {
            width: 100%; /* Ensure the input takes full width */
            border: none;
            padding-right: 40px; /* Add some padding to the right to make space for the icon */
        }

        .usernameContainer i {
            position: absolute;
            right: 20px; /* Position the icon 20px from the right edge */
            top: 50%;
            transform: translateY(-50%); /* Center the icon vertically */
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="hair" style="height: 15px; background: #9B0303;"></div>
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
                                    <span class="datausep" style="color: rgb(151, 57, 57); font-weight: 600; font-size: 18px;">One</span>
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
                <div style="height: 180px; width: 0px;"></div>
                <div class="bodyLeftText" style="color: rgb(87, 87, 87); font-weight: 500; font-size: 3rem;">
                    <h>Change Password</h>
                </div>

                <div style="height: 8px; width: 0px;"></div>

                <div class="bodyLeftText" style="color: rgb(87, 87, 87); font-weight: 300; font-size: 17px;">
                    <h>Create a new, strong password that you don't use for other websites.</h>
                </div>

                <div style="height: 30px; width: 0px;"></div>

                <?php if ($message): ?>
                    <div id="popupMessage" class="popup">
                        <div class="popup-content">
                            <div style="height: 50px; width: 0px;"></div>
                            <img class="Success" src="images/Success.png" height="100">
                            <div style="height: 20px; width: 0px;"></div>
                            <div class="popup-text"><?php echo $message; ?></div>
                            <div style="height: 50px; width: 0px;"></div>
                            <a href="login.php" class="okay" id="closePopup">Okay</a>
                            <div style="height: 100px; width: 0px;"></div>
                            <div class="hairpop-up"></div>
                        </div>
                    </div>
                    <script>
                        document.getElementById('popupMessage').style.display = 'block';

                        document.getElementById('closePopup').addEventListener('click', function() {
                            document.getElementById('popupMessage').style.display = 'none';
                        });

                        window.addEventListener('click', function(event) {
                            if (event.target == document.getElementById('popupMessage')) {
                                document.getElementById('popupMessage').style.display = 'none';
                            }
                        });
                    </script>
                <?php endif; ?>

                <?php if ($message1): ?>
                    <div id="popupMessage" class="popup">
                        <div class="popup-content">
                            <div style="height: 50px; width: 0px;"></div>
                            <img class="Success" src="images/Error.png" height="100">
                            <div style="height: 20px; width: 0px;"></div>
                            <div class="popup-text"><?php echo $message1; ?></div>
                            <div style="height: 50px; width: 0px;"></div>
                            <a href="reset_password.php" class="okay" id="closePopup">Okay</a>
                            <div style="height: 100px; width: 0px;"></div>
                            <div class="hairpop-up"></div>
                        </div>
                    </div>
                    <script>
                        document.getElementById('popupMessage').style.display = 'block';

                        document.getElementById('closePopup').addEventListener('click', function() {
                            document.getElementById('popupMessage').style.display = 'none';
                        });

                        window.addEventListener('click', function(event) {
                            if (event.target == document.getElementById('popupMessage')) {
                                document.getElementById('popupMessage').style.display = 'none';
                            }
                        });
                    </script>
                <?php endif; ?>

                <form method="post" action="reset_password.php">
                    <div class="username" style="width: 455px;" id="passwordContainer">
                        <div class="usernameContainer">
                            <input class="email" type="password" id="new_password" name="new_password" placeholder="New Password" required>
                            <i class="fa-regular fa-eye-slash" id="togglePassword" style="cursor: pointer;"></i>
                        </div>
                    </div>
                    <div style="height: 4px; width: 0px;"></div>
                    <div class="password-requirements" style="font-size: 14px;">
                        <p id="passwordRequirements">
                            Password must contain: 
                            <span id="charRequirement" style="color:red;">Minimum of 8 characters</span>, 
                            <span id="uppercaseRequirement" style="color:red;">one uppercase character</span>, 
                            <span id="numberRequirement" style="color:red;">a number</span>, and 
                            <span id="specialRequirement" style="color:red;">a special character</span>.
                        </p>
                    </div>
                    <div style="height: 10px; width: 0px;"></div>
                    <div class="username" style="width: 455px;" id="confirmPasswordContainer">
                        <div class="usernameContainer">
                            <input class="email" type="password" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>
                            <i class="fa-regular fa-eye-slash" id="toggleConfirmPassword" style="cursor: pointer;"></i>
                        </div>
                    </div>
                    <div style="height: 10px; width: 0px;"></div>

                    <button type="submit" class="verify">Change Password</button>

                    <div style="height: 10px; width: 0px;"></div>
                </form>
            </div>

            <div class="bodyRight">
                <div style="height: 200px; width: 0px;"></div>
                <img class="USeP" src="images/LoginCover.png" height="400">
            </div>
        </div>
    </div>

    <!-- Loading Spinner -->
    <div id="customLoadingOverlay" class="custom-loading-overlay custom-spinner-hidden">
        <div class="custom-spinner"></div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const verifyForm = document.querySelector('form');
            const loadingSpinner = document.getElementById('customLoadingOverlay');

            verifyForm.addEventListener('submit', function () {
                // Show the loading spinner
                loadingSpinner.classList.remove('custom-spinner-hidden');
            });
        });

        // Validate password and check for password match
        function validatePassword() {
            var password = document.getElementById('new_password').value;

            // Minimum 8 characters
            var charRequirementMet = password.length >= 8;
            document.getElementById('charRequirement').style.color = charRequirementMet ? 'green' : 'red';

            // At least one uppercase character
            var uppercaseRequirementMet = /[A-Z]/.test(password);
            document.getElementById('uppercaseRequirement').style.color = uppercaseRequirementMet ? 'green' : 'red';

            // At least one number
            var numberRequirementMet = /\d/.test(password);
            document.getElementById('numberRequirement').style.color = numberRequirementMet ? 'green' : 'red';

            // At least one special character
            var specialRequirementMet = /[!@#$%^&*(),.?":{}|<>]/.test(password);
            document.getElementById('specialRequirement').style.color = specialRequirementMet ? 'green' : 'red';

            // Store the result of validation
            var isValidPassword = charRequirementMet && uppercaseRequirementMet && numberRequirementMet && specialRequirementMet;
            document.getElementById('new_password').dataset.valid = isValidPassword;

            // Apply inline styles based on validity
            var passwordContainer = document.getElementById('passwordContainer').firstElementChild;
            if (isValidPassword) {
                passwordContainer.style.border = '1px solid green';
            } else {
                passwordContainer.style.border = '1px solid red';
            }

            // Check if the passwords match
            checkPasswordMatch();
        }

        function checkPasswordMatch() {
            var password = document.getElementById('new_password').value;
            var confirmPassword = document.getElementById('confirm_password').value;
            var confirmPasswordContainer = document.getElementById('confirmPasswordContainer').firstElementChild;

            // Apply inline styles based on password match
            if (password === confirmPassword && confirmPassword !== '') {
                confirmPasswordContainer.style.border = '1px solid green';
            } else {
                confirmPasswordContainer.style.border = '1px solid red';
            }
        }

        // Event listeners for real-time validation
        document.getElementById('new_password').addEventListener('input', function() {
            validatePassword();
        });

        document.getElementById('confirm_password').addEventListener('input', function() {
            checkPasswordMatch();
        });

        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function () {
            const passwordInput = document.getElementById('new_password');
            const icon = this;

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            }
        });

        document.getElementById('toggleConfirmPassword').addEventListener('click', function () {
            const confirmPasswordInput = document.getElementById('confirm_password');
            const icon = this;

            if (confirmPasswordInput.type === 'password') {
                confirmPasswordInput.type = 'text';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            } else {
                confirmPasswordInput.type = 'password';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            }
        });
        
    </script>
</body>
</html>