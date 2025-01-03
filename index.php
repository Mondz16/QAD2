<?php
session_start();

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

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
    <title>Quality Assurance Division</title>
    <link rel="stylesheet" href="index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" />
</head>

<body>
    <div class="wrapper">
        <div class="hair" style="height: 15px; background: #9B0303;"></div>
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
            <div class="body">
                <div class="bodyMid">
                    <div style="height: 70px; width: 0px;"></div>
                    <div class="bodyMidText" style="color: rgb(87, 87, 87); font-weight: 500; font-size: 50px; color: #D21011;">
                        <h>Hello, there!</h>
                    </div>
                    <div class="bodyMidText.small" style="color: rgb(87, 87, 87); font-weight: 700; font-size: 20px;">
                        <h>USeP's Quality Assurance Division's Portal is now available.</h>
                    </div>

                    <div style="height: 45px; width: 0px;"></div>

                    <img class="USeP" src="images/LoginCover.png" height="400">

                    <div style="height: 45px; width: 0px;"></div>

                    <div class="index-button-container">
                    <button type="button" class="login" id="loginBtn">Log in</button>
                    <span>OR</span>
                    <button type="button" class="login" id="registerBtn">Register</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="loginPopup" class="popup">
        <div class="popup-content">
            <span class="close-btn" id="closeBtn">&times;</span>
            <div style="height: 50px; width: 0px;"></div>
            <a href="https://www.usep.edu.ph/usep-data-privacy-statement/" target="_blank">
                <img class="USeP" src="images/USePLogo.png" alt="Popup Image" class="popup-image" height="140">
            </a>
            <div class="popup-text" style="color: #7B7B7B">By continuing to browse this website, you agree to the University of Southeastern Philippines' Data Privacy Statement. The full text or statement can be accessed by clicking the image above.</div>
            <div style="height: 30px; width: 0px;"></div>
            <button type="button" class="continue">Continue</button>
            <div style="height: 70px; width: 0px;"></div>
            <div class="hairpop-up"></div>
        </div>
    </div>

    <div id="registerPopup" class="popup1">
        <div class="popup-content">
            <span class="close-btn" id="closeBtn1">&times;</span>
            <div style="height: 50px; width: 0px;"></div>
            <a href="https://www.usep.edu.ph/usep-data-privacy-statement/" target="_blank">
                <img class="USeP" src="images/USePLogo.png" alt="Popup Image" class="popup-image" height="140">
            </a>
            <div class="popup-text" style="color: #7B7B7B">By continuing to browse this website, you agree to the University of Southeastern Philippines' Data Privacy Statement. The full text or statement can be accessed by clicking the image above.</div>
            <div style="height: 30px; width: 0px;"></div>
            <button type="button" class="continue1">Continue</button>
            <div style="height: 70px; width: 0px;"></div>
            <div class="hairpop-up"></div>
        </div>
    </div>

    <script>
        document.getElementById('loginBtn').addEventListener('click', function() {
            document.getElementById('loginPopup').style.display = 'block';
        });

        document.getElementById('closeBtn').addEventListener('click', function() {
            document.getElementById('loginPopup').style.display = 'none';
        });

        document.querySelector('.popup .continue').addEventListener('click', function() {
            window.location.href = 'login.php';
        });

        window.addEventListener('click', function(event) {
            if (event.target == document.getElementById('loginPopup')) {
                document.getElementById('loginPopup').style.display = 'none';
            }
        });

        document.getElementById('registerBtn').addEventListener('click', function() {
            document.getElementById('registerPopup').style.display = 'block';
        });

        document.getElementById('closeBtn1').addEventListener('click', function() {
            document.getElementById('registerPopup').style.display = 'none';
        });

        document.querySelector('.popup1 .continue1').addEventListener('click', function() {
            window.location.href = 'register.php';
        });

        window.addEventListener('click', function(event) {
            if (event.target == document.getElementById('registerPopup')) {
                document.getElementById('registerPopup').style.display = 'none';
            }
        });
    </script>
</body>
</html>
