<?php
session_start();

// Check if the user just logged out
if (isset($_GET['logged_out']) && $_GET['logged_out'] == 'true') {
    // Allow access without referer check
} else {
    // Allowed referring pages
    $allowed_referers = ['oauth2callback_sso.php', 'register.php', 'admin_sidebar.php', 'dashboard.php', 'schedule.php', 'college.php', 'orientation.php', 'assessment.php', 'udas_assessment.php', 'registration.php', 'college_transfer.php', 'reports_dashboard.php', 'program_timeline.php', 'reports_member.php', 'internal.php', 'index.php', 'logout.php', 'verify_otp.php', 'login_process.php', 'forgot_password.php', 'forgot_password_verification.php', 'reset_password.php'];

    // Check if the referer is set and validate it
    if (isset($_SERVER['HTTP_REFERER'])) {
        $referer = basename(parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH));
        if (!in_array($referer, $allowed_referers)) {
            // Redirect to index.php if the referer is not allowed
            header("Location: index.php");
            exit();
        }
    } else {
        // If no referer is set, redirect to index.php
        header("Location: index.php");
        exit();
    }
}

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // Parse user_id to get role
    if ($user_id === 'admin') {
        header("Location: dashboard.php");
    } else {
        list($college_code, $role_code, $unique_number) = explode('-', $user_id);

        if ($role_code === '11') {
            header("Location: internal.php");
        } elseif ($role_code === '22') {
            header("Location: external.php");
        }
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Form</title>
    <link rel="stylesheet" href="index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" />
</head>
<body>
    <!-- Your existing HTML code goes here -->
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Form</title>
    <link rel="stylesheet" href="index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" />
</head>
<body>
    <div class="wrapper">
        <div class="hair" style="height: 15px; background: linear-gradient(275.52deg, #973939 0.28%, #DC7171 100%);"></div>
        <div class="container">
            <div class="header">
                <div class="headerLeft">
                    <div class=USePData>
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
                    <div class=QAD>
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
        <div style="height: 1px; width: 100%; background: #E5E5E5"></div>
        <div class="container">
            <div class="body1">
                <div class="bodyLeft">
                    <div style="height: 120px; width: 0px;"></div>
                    <div class="bodyLeftText" style="color: rgb(87, 87, 87); font-weight: 500; font-size: 3rem;">
                        <h>Hello, there!</h>
                    </div>

                    <div style="height: 8px; width: 0px;"></div>

                    <div class="bodyLeftText" style="color: rgb(87, 87, 87); font-weight: 300; font-size: 2.125rem;">
                        <h>Please login to get started.</h>
                    </div>

                    <div style="height: 32px; width: 0px;"></div>

                    <form id="loginForm" method="POST" action="login_process.php" novalidate>
                        <div class="username" style="width: 400px;">
                            <div class="usernameContainer" style="padding: 12px 20px; border-color: rgb(170, 170, 170); border-style: solid; border-width: 1px; border-radius: 8px;">
                                <input class="user_idText" type="text" name="user_id" id="user_id" placeholder="User ID">
                            </div>
                        </div>

                        <div style="height: 16px; width: 0px;"></div>

                        <div class="password" style="width: 400px;">
                            <div class="passwordContainer" style="padding: 12px 20px; border-color: rgb(170, 170, 170); border-style: solid; border-width: 1px; border-radius: 8px;">
                                <input class="passwordText" type="password" name="password" id="password" placeholder="Password">
                            </div>
                        </div>

                        <div style="height: 24px; width: 0px;"></div>

                        <a href="google_login.php" class="google"><img src="images/GoogleLogo.png" height="36">Sign in with Google</a>

                        <div style="height: 24px; width: 0px;"></div>

                        <div class="showpassword">
                            <div class="showpasswordContainer">
                                <label id="showpassword">
                                    <input type="checkbox" id="showPasswordCheckbox">
                                    <span class="custom-checkbox"></span>
                                    <span class="showpasswordText">Show Password</span>
                                </label>
                                <a href="forgot_password.php" style="color: rgb(87, 87, 87); font-weight: 500; text-decoration: underline;">FORGOT PASSWORD?</a>
                            </div>
                        </div>

                        <div style="height: 70px; width: 0px;"></div>

                        <a href="register.php" class="signup">Sign Up</a>

                        <button type="submit" class="login">Log me in</button>
                    </form>

                    <div style="height: 50px; width: 0px;"></div>

                    <div class="footer">
                        <div style="height: 64px; width: 0px;"></div>
                        <div style="color: rgb(87, 87, 87); font-weight: 500; text-align: left; font-size: 0.875rem;">
                            Copyright © 2024. All Rights Reserved.
                        </div>

                        <div class="termsandpolicy">
                            <span class="terms">
                                <a href="login.php" style="color: rgb(87, 87, 87); font-weight: bold; text-decoration: underline; font-size: 0.875rem">Terms of Use</a>
                            </span>
                              |  
                            <span class="policy">
                                <a href="https://www.usep.edu.ph/usep-data-privacy-statement/" target="_blank" style="color: rgb(87, 87, 87); font-weight: bold; text-decoration: underline; font-size: 0.875rem">Privacy Policy</a>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="bodyRight">
                    <div style="height: 147px; width: 0px;"></div>
                    <img class="USeP" src="images/LoginCover.png" height="400">
                </div>
            </div>
        </div>
    </div>

    <div id="errorPopup" class="popup">
        <div class="popup-content">
            <div style="height: 50px; width: 0px;"></div>
            <img class="Error" src="images/Error.png" height="100">
            <div style="height: 20px; width: 0px;"></div>
            <div class="popup-text" id="errorMessage">Error</div>
            <div id="userIdErrorMessage" style="display: none;">User ID is Empty<br><br><span style="color: #7B7B7B;">User ID is a required<br>field. Please fill it up to continue.</span></div>
            <div id="passwordErrorMessage" style="display: none;">Password is Empty<br><br><span style="color: #7B7B7B;">Password is a required<br>field. Please fill it up to continue.</span></div>
            <div style="height: 50px; width: 0px;"></div>
            <a href="javascript:void(0);" class="okay" id="closePopup">Okay</a>
            <div style="height: 100px; width: 0px;"></div>
            <div class="hairpop-up"></div>
        </div>
    </div>

    <div id="customLoadingOverlay" class="custom-loading-overlay custom-spinner-hidden">
        <div class="custom-spinner"></div>
    </div>

    <script>
        let tempUserId = '';

        document.getElementById('user_id').addEventListener('input', function(e) {
            let userIdInput = e.target.value;

            // Limit to 10 characters
            if (userIdInput.length > 10) {
                userIdInput = userIdInput.slice(0, 10);
            }

            // Set the cleaned value back to the input
            e.target.value = userIdInput;
        });

        document.getElementById('showPasswordCheckbox').addEventListener('change', function() {
            var passwordInput = document.querySelector('.passwordText');
            if (this.checked) {
                passwordInput.type = 'text';
            } else {
                passwordInput.type = 'password';
            }
        });

        document.getElementById('loginForm').addEventListener('submit', function(event) {
            var userIdInput = document.querySelector('.user_idText');
            var passwordInput = document.querySelector('.passwordText');
            var errorMessage = '';

            if (!userIdInput.value) {
                errorMessage = document.getElementById('userIdErrorMessage').innerHTML;
                tempUserId = userIdInput.value;
            } else if (!passwordInput.value) {
                errorMessage = document.getElementById('passwordErrorMessage').innerHTML;
                tempUserId = userIdInput.value;
            }

            if (errorMessage) {
                event.preventDefault();
                document.getElementById('errorMessage').innerHTML = errorMessage;
                document.getElementById('errorPopup').style.display = 'block';
            }
        });

        document.getElementById('closePopup').addEventListener('click', function() {
            document.getElementById('errorPopup').style.display = 'none';
            document.getElementById('user_id').value = tempUserId;
        });

        window.addEventListener('click', function(event) {
            if (event.target == document.getElementById('errorPopup')) {
                document.getElementById('errorPopup').style.display = 'none';
                document.getElementById('user_id').value = tempUserId;
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            tempUserId = '';
        });

        document.getElementById('loginForm').addEventListener('submit', function(event) {
            // Clear any previous error message
            var errorMessage = '';

            var userIdInput = document.querySelector('.user_idText');
            var passwordInput = document.querySelector('.passwordText');

            if (!userIdInput.value) {
                errorMessage = document.getElementById('userIdErrorMessage').innerHTML;
                tempUserId = userIdInput.value;
            } else if (!passwordInput.value) {
                errorMessage = document.getElementById('passwordErrorMessage').innerHTML;
                tempUserId = userIdInput.value;
            }

            if (errorMessage) {
                // Prevent form submission and display the error modal
                event.preventDefault();
                document.getElementById('errorMessage').innerHTML = errorMessage;
                document.getElementById('errorPopup').style.display = 'block';
            } else {
                // No errors, show the loading spinner
                document.getElementById('loadingSpinner').classList.remove('spinner-hidden');
            }
        });

        document.getElementById('closePopup').addEventListener('click', function() {
            document.getElementById('errorPopup').style.display = 'none';
            document.getElementById('user_id').value = tempUserId;
        });

        window.addEventListener('click', function(event) {
            if (event.target == document.getElementById('errorPopup')) {
                document.getElementById('errorPopup').style.display = 'none';
                document.getElementById('user_id').value = tempUserId;
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            tempUserId = '';
        });

    </script>
</body>
</html>
