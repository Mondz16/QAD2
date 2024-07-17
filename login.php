<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Form</title>
    <link rel="stylesheet" href="style.css">
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
                    <div class=SDMD>
                        <div class="headerRightText">
                            <h style="color: rgb(87, 87, 87); font-weight: 600; font-size: 16px;">Quality Assurance Division</h>
                        </div>
                        <div style="height: 0px; width: 16px;"></div>
                        <div style="height: 32px; width: 1px; background: #E5E5E5"></div>
                        <div style="height: 0px; width: 16px;"></div>
                        <img class="USeP" src="images/SDMDLogo.png" height="36">
                    </div>
                </div>
            </div>
        </div>
        <div style="height: 1px; width: 100%; background: #E5E5E5"></div>
        <div class="container">
            <div class="body">
                <div class="bodyLeft">
                    <div style="height: 120px; width: 0px;"></div>
                    <div class="bodyLeftText" style="color: rgb(87, 87, 87); font-weight: 500; font-size: 3rem;">
                        <h>Welcome Back!</h>
                    </div>

                    <div style="height: 8px; width: 0px;"></div>

                    <div class="bodyLeftText" style="color: rgb(87, 87, 87); font-weight: 300; font-size: 2.125rem;">
                        <h>Please login to get started.</h>
                    </div>

                    <div style="height: 32px; width: 0px;"></div>

                    <form id="loginForm" method="POST" action="login_process.php">
                        <div class="username" style="width: 400px;">
                            <div class="usernameContainer" style="padding: 12px 20px; border-color: rgb(170, 170, 170); border-style: solid; border-width: 1px; border-radius: 8px;">
                                <input class="user_idText" type="text" name="user_id" placeholder="User ID" required>
                            </div>
                        </div>

                        <div style="height: 16px; width: 0px;"></div>

                        <div class="password" style="width: 400px;">
                            <div class="passwordContainer" style="padding: 12px 20px; border-color: rgb(170, 170, 170); border-style: solid; border-width: 1px; border-radius: 8px;">
                                <input class="passwordText" type="password" name="password" placeholder="Password">
                            </div>
                        </div>

                        <div style="height: 24px; width: 0px;"></div>

                        <div class="showpassword">
                            <div class="showpasswordContainer">
                                <label id="showpassword">
                                    <input type="checkbox" id="showPasswordCheckbox">
                                    <span class="showpasswordText">Show Password</span>
                                </label>
                                <a href="login.php"style="color: rgb(87, 87, 87); font-weight: 500; text-decoration: none;">Forgot password?</a>
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
                                <a href="login.php" style="color: rgb(87, 87, 87); font-weight: bold; text-decoration: underline; font-size: 0.875rem">Privacy Policy</a>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.getElementById('showPasswordCheckbox').addEventListener('change', function() {
            var passwordInput = document.querySelector('.passwordText'); // corrected selector
            if (this.checked) {
                passwordInput.type = 'text';
            } else {
                passwordInput.type = 'password';
            }
        });
    </script>
</body>
</html>