<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Form</title>
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
        <div style="height: 1px; width: 100%; background: #E5E5E5"></div>
        <div class="container">
            <div class="body2">
                <div class="bodyLeft1">
                    <div style="height: 59px; width: 0px;"></div>
                    <div class="bodyLeftText" style="color: rgb(87, 87, 87); font-weight: 500; font-size: 3rem;">
                        <h>Register</h>
                    </div>
                    <div style="height: 32px; width: 0px;"></div>
                    <div class="bodyLeftText" style="color: rgb(87, 87, 87); font-weight: 300; font-size: 20px;">
                        <h>Select Account Type</h>
                    </div>
                    <div style="height: 8px; width: 0px;"></div>
                    <form id="registerForm" action="register_process.php" method="post" enctype="multipart/form-data">
                        <div class="register-form">
                            <div class="internal-external">
                                <div class="internal-externalSelect">
                                    <input id="internal" type="radio" name="type" value="internal">
                                    <label for="internal">Internal Accreditor</label>
                                    <input id="external" type="radio" name="type" value="external">
                                    <label for="external">External Accreditor</label>
                                </div>
                            </div>
                            <div style="height: 32px; width: 0px;"></div>
                            <div class="bodyLeftText" style="color: rgb(87, 87, 87); font-weight: 300; font-size: 20px;">
                                <h>Profile</h>
                            </div>
                            <div style="height: 10px; width: 0px;"></div>
                            <div class="name">
                                <div class="prefixContainer">
                                    <div class="custom-select-wrapper">
                                        <select class="prefix" name="prefix">
                                            <option value="">Prefix</option>
                                            <option value="Mr.">Mr.</option>
                                            <option value="Ms.">Ms.</option>
                                            <option value="Mrs.">Mrs.</option>
                                            <option value="Dr.">Dr.</option>
                                            <option value="Prof.">Prof.</option>
                                            <option value="Prof.">Assoc. Prof.</option>
                                            <option value="Prof.">Assist. Prof.</option>
                                            <option value="Prof.">Engr.</option>
                                            <!-- Add more options as needed -->
                                        </select>
                                    </div>
                                </div>
                                <div class="nameContainer firstnameContainer">
                                    <input class="firstname" type="text" name="first_name" placeholder="First Name">
                                </div>
                                <div class="nameContainer middleinitialContainer">
                                    <input class="middleinitial" type="text" name="middle_initial" placeholder="M.I.">
                                </div>
                                <div class="nameContainer lastnameContainer">
                                    <input class="lastname" type="text" name="last_name" placeholder="Last Name">
                                </div>
                            </div>
                            <div style="height: 8px; width: 0px;"></div>
                            <div class="username" style="width: 721px;">
                                <div class="usernameContainer" style="padding: 12px 20px; border-color: rgb(170, 170, 170); border-style: solid; border-width: 1px; border-radius: 8px;">
                                    <input class="email" type="email" name="email" placeholder="USeP Email">
                                </div>
                            </div>
                            <div style="height: 8px; width: 0px;"></div>
                            <div class="name">
                                <div class="nameContainer">
                                    <input class="middleinitial" type="password" name="password" placeholder="Password">
                                </div>
                                <div class="nameContainer">
                                    <input class="lastname" type="password" name="confirm_password" placeholder="Confirm Password">
                                </div>
                            </div>
                            <div style="height: 8px; width: 0px;"></div>
                            <div class="college" id="college-field" style="display:none;">
                                <div class="college-company-gender">
                                    <select name="college">
                                        <option value="" disabled selected>College</option>
                                        <?php
                                        include_once 'connection.php';

                                        $sql_colleges = "SELECT code, college_name FROM college ORDER BY college_name";
                                        $result_colleges = $conn->query($sql_colleges);

                                        if ($result_colleges && $result_colleges->num_rows > 0) {
                                            while ($row_college = $result_colleges->fetch_assoc()) {
                                                echo "<option value='{$row_college['code']}'>{$row_college['college_name']}</option>";
                                            }
                                        } else {
                                            echo "<option value=''>No colleges found</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="company" id="company-field" style="display:none;">
                                <div class="college-company-gender">
                                    <select name="company">
                                        <option value="" disabled selected>Company</option>
                                        <?php
                                        include_once 'connection.php';

                                        $sql_companies = "SELECT code, company_name FROM company ORDER BY company_name";
                                        $result_companies = $conn->query($sql_companies);

                                        if ($result_companies && $result_companies->num_rows > 0) {
                                            while ($row_company = $result_companies->fetch_assoc()) {
                                                echo "<option value='{$row_company['code']}'>{$row_company['company_name']}</option>";
                                            }
                                        } else {
                                            echo "<option value=''>No companies found</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="gender" style="width: 721px;">
                                <div class="college-company-gender">
                                    <select class="prefix" name="gender" id="genderSelect">
                                        <option value="">Gender</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Prefer not to say">Prefer not to say</option>
                                        <option value="Others">Others</option>
                                        <!-- Add more options as needed -->
                                    </select>
                                    <input type="text" id="genderInput" name="gender_others" style="display:none; width: 721px; padding: 12px 20px; border: 1px solid #aaa; border-radius: 8px; font-size: 1rem; background-color: #fff;" placeholder="Specify Gender">
                                </div>
                            </div>
                            <div style="height: 30px; width: 0px;"></div>
                            <a href="login.php" class="signup signupregister" style="margin-left: 265px;">Log in instead</a>
                            <button type="submit" class="login loginregister">Register</button>
                        </div>
                    </form>
                </div>

                <div class="bodyRight1">
                    <div style="height: 250px; width: 0px;"></div>
                    <img class="USeP" src="images/LoginCover.png" height="340">
                </div>
            </div>
        </div>
    </div>
    
    <!-- Error Modal -->
    <div id="errorPopup" class="popup">
        <div class="popup-content">
            <span class="close-btn" id="closeErrorBtn">&times;</span>
            <div style="height: 50px; width: 0px;"></div>
            <img class="Error" src="images/Error.png" height="100">
            <div style="height: 20px; width: 0px;"></div>
            <div class="popup-text" id="errorMessage">Error</div>
            <div id="typeErrorMessage" style="display: none;">Account Type is Empty<br><br><span style="color: #7B7B7B;">Account Type is a required<br>field. Please fill it up to continue.</span></div>
            <div id="prefixErrorMessage" style="display: none;">Prefix is Empty<br><br><span style="color: #7B7B7B;">Prefix is a required<br>field. Please fill it up to continue.</span></div>
            <div id="firstNameErrorMessage" style="display: none;">First Name is Empty<br><br><span style="color: #7B7B7B;">First Name is a required<br>field. Please fill it up to continue.</span></div>
            <div id="middleInitialErrorMessage" style="display: none;">Middle Initial is Empty<br><br><span style="color: #7B7B7B;">Middle Initial is a required<br>field. Please fill it up to continue.</span></div>
            <div id="lastNameErrorMessage" style="display: none;">Last Name is Empty<br><br><span style="color: #7B7B7B;">Last Name is a required<br>field. Please fill it up to continue.</span></div>
            <div id="emailErrorMessage" style="display: none;">Email is Empty<br><br><span style="color: #7B7B7B;">Email is a required<br>field. Please fill it up to continue.</span></div>
            <div id="passwordErrorMessage" style="display: none;">Password is Empty<br><br><span style="color: #7B7B7B;">Password is a required<br>field. Please fill it up to continue.</span></div>
            <div id="confirmPasswordErrorMessage" style="display: none;">Confirm Password is Empty<br><br><span style="color: #7B7B7B;">Confirm Password is a required<br>field. Please fill it up to continue.</span></div>
            <div id="collegeErrorMessage" style="display: none;">College is Empty<br><br><span style="color: #7B7B7B;">College is a required<br>field. Please fill it up to continue.</span></div>
            <div id="companyErrorMessage" style="display: none;">Company is Empty<br><br><span style="color: #7B7B7B;">Company is a required<br>field. Please fill it up to continue.</span></div>
            <div id="genderErrorMessage" style="display: none;">Gender is Empty<br><br><span style="color: #7B7B7B;">Gender is a required<br>field. Please fill it up to continue.</span></div>
            <div style="height: 50px; width: 0px;"></div>
            <a href="register.php" class="okay">Okay</a>
            <div style="height: 100px; width: 0px;"></div>
            <div class="hairpop-up"></div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelector('input[name="type"][value="internal"]').checked = true;
            document.getElementById('college-field').style.display = 'block';
            document.getElementById('company-field').style.display = 'none';
        
            document.querySelector('input[name="type"][value="internal"]').addEventListener('change', function() {
                document.getElementById('college-field').style.display = 'block';
                document.getElementById('company-field').style.display = 'none';
                document.querySelector('select[name="college"]').required = true;
                document.querySelector('select[name="company"]').required = false;
            });

            document.querySelector('input[name="type"][value="external"]').addEventListener('change', function() {
                document.getElementById('college-field').style.display = 'none';
                document.getElementById('company-field').style.display = 'block';
                document.querySelector('select[name="college"]').required = false;
                document.querySelector('select[name="company"]').required = true;
            });

            document.querySelector('input[name="profile_picture"]').addEventListener('change', function(event) {
                const file = event.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        document.getElementById('profilePreviewImg').style.display = 'block';
                        document.getElementById('profilePreviewImg').src = e.target.result;
                    };
                    reader.readAsDataURL(file);
                }
            });
        });

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

        document.getElementById('registerForm').addEventListener('submit', function(event) {
            event.preventDefault();
            var typeInput = document.querySelector('input[name="type"]:checked');
            var prefixInput = document.querySelector('select[name="prefix"]');
            var firstNameInput = document.querySelector('input[name="first_name"]');
            var middleInitialInput = document.querySelector('input[name="middle_initial"]');
            var lastNameInput = document.querySelector('input[name="last_name"]');
            var emailInput = document.querySelector('input[name="email"]');
            var passwordInput = document.querySelector('input[name="password"]');
            var confirmPasswordInput = document.querySelector('input[name="confirm_password"]');
            var collegeSelect = document.querySelector('select[name="college"]');
            var companySelect = document.querySelector('select[name="company"]');
            var genderSelect = document.querySelector('select[name="gender"]');
            var genderInput = document.querySelector('input[name="gender_others"]');

            var errorMessage = '';

            if (!typeInput) {
                errorMessage = document.getElementById('typeErrorMessage').innerHTML;
            } else if (prefixInput.value === '') {
                errorMessage = document.getElementById('prefixErrorMessage').innerHTML;
            } else if (!firstNameInput.value) {
                errorMessage = document.getElementById('firstNameErrorMessage').innerHTML;
            } else if (!middleInitialInput.value) {
                errorMessage = document.getElementById('middleInitialErrorMessage').innerHTML;
            } else if (!lastNameInput.value) {
                errorMessage = document.getElementById('lastNameErrorMessage').innerHTML;
            } else if (!emailInput.value) {
                errorMessage = document.getElementById('emailErrorMessage').innerHTML;
            } else if (!passwordInput.value) {
                errorMessage = document.getElementById('passwordErrorMessage').innerHTML;
            } else if (!confirmPasswordInput.value) {
                errorMessage = document.getElementById('confirmPasswordErrorMessage').innerHTML;
            } else if (document.querySelector('input[name="type"]:checked').value === 'internal' && !collegeSelect.value) {
                errorMessage = document.getElementById('collegeErrorMessage').innerHTML;
            } else if (document.querySelector('input[name="type"]:checked').value === 'external' && !companySelect.value) {
                errorMessage = document.getElementById('companyErrorMessage').innerHTML;
            } else if (genderSelect.value === '' && genderInput.style.display === 'none') {
                errorMessage = document.getElementById('genderErrorMessage').innerHTML;
            }

            if (errorMessage) {
                document.getElementById('errorMessage').innerHTML = errorMessage;
                document.getElementById('errorPopup').style.display = 'block';
            } else {
                event.target.submit();
            }
        });

        document.getElementById('closeErrorBtn').addEventListener('click', function() {
            document.getElementById('errorPopup').style.display = 'none';
        });

        window.addEventListener('click', function(event) {
            if (event.target == document.getElementById('errorPopup')) {
                document.getElementById('errorPopup').style.display = 'none';
            }
        });
    </script>
</body>
</html>
