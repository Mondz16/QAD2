<?php
session_start();

// Allowed referring pages
$allowed_referers = ['login.php', 'index.php', 'verify_otp.php'];

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

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // Parse user_id to get role
    if ($user_id === 'admin') {
        header("Location: admin.php");
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
    <title>Register Form</title>
    <link rel="stylesheet" href="index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
                                    <select class="prefix" name="prefix" required>
                                        <option value="" disabled selected hidden>Prefix</option>
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
                                <div class="nameContainer firstnameContainer">
                                    <input class="firstname" type="text" name="first_name" placeholder="First Name">
                                </div>
                                <div class="nameContainer middleinitialContainer">
                                    <input class="middleinitial" type="text" name="middle_initial" id="middleinitial" placeholder="M.I." style="text-transform: uppercase;">
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
                                <div class="nameContainer" id="passwordContainer">
                                    <input class="middleinitial" type="password" name="password" id="passwordInput" placeholder="Password">
                                </div>
                                <div class="nameContainer" id="confirmPasswordContainer">
                                    <input class="lastname" type="password" name="confirm_password" id="confirmPasswordInput" placeholder="Confirm Password">
                                </div>
                                <div class="nameContainer eyeContainer" style="padding: 12px 12px; display: flex; justify-content: center; align-items: center;">
                                    <i class="fa-regular fa-eye-slash" id="togglePasswordConfirmPassword" style="cursor: pointer;"></i>
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
                                        <option value="" disabled selected hidden>Gender</option>
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
                            <button type="submit" class="login loginregister" id="registerButton">Register</button>
                        </div>
                    </form>
                </div>

                <div class="bodyRight1">
                    <div style="height: 250px; width: 0px;"></div>
                    <img class="USeP" src="images/LoginCover.png" height="385">
                </div>
            </div>
        </div>
    </div>
    
    <div id="errorPopup" class="popup" style="display:none;">
        <div class="popup-content">
            <div style="height: 50px; width: 0px;"></div>
            <img class="Error" src="images/Error.png" height="100">
            <div style="height: 20px; width: 0px;"></div>
            <div class="popup-text" id="errorMessage">Error</div>
            <div id="passwordRequirementErrorMessage" style="display: none;">Password does not meet requirements</div>
            <div style="height: 50px; width: 0px;"></div>
            <a href="javascript:void(0);" class="okay" id="closePopup">Okay</a>
            <div style="height: 100px; width: 0px;"></div>
            <div class="hairpop-up"></div>
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
            <!-- Error messages are hidden by default -->
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
            <a href="javascript:void(0);" class="okay" id="closePopup">Okay</a>
            <div style="height: 100px; width: 0px;"></div>
            <div class="hairpop-up"></div>
        </div>
    </div>
    
    <!-- Terms and Conditions Modal -->
    <div id="termsModal" class="e-sign">
        <div class="e-sign-modal">
            <div style="height: 20px; width: 0px;"></div>
            <div class="e-sign-text">
                <h2>Electronic Signature<br>Usage Agreement</h2>
                <p>By agreeing to this statement, you consent to the following terms and conditions regarding the use of your electronic signature:<br><br>

1. You acknowledge and agree that your electronic signature will be used exclusively for internal accreditation purposes within our organization. This includes, but is not limited to, verifying and validating documents, authorizations, and other internal procedures.<br><br>

2. You understand and agree that your electronic signature will be encrypted using AES-256-CBC encryption. This ensures that your electronic signature is secure and protected against unauthorized access, tampering, and breaches.<br><br>

3. You consent to the secure storage of your electronic signature in our database, which is protected by advanced security measures. Access to this database is restricted to authorized personnel only, ensuring that your electronic signature is used appropriately and solely for the purposes outlined above.<br><br>

4. You agree that your electronic signature will be kept confidential and will not be shared, disclosed, or used for any purposes other than those specified in this agreement without your explicit consent.<br><br>

5. You acknowledge that it is your responsibility to ensure that your electronic signature is accurate and to safeguard any credentials or devices used to create your electronic signature.<br><br>

6. You understand that we reserve the right to update or modify these terms and conditions at any time. Any changes will be communicated to you, and your continued use of your electronic signature for internal accreditation purposes will constitute your acceptance of the revised terms.<br><br>

If you have any questions or concerns regarding the use of your electronic signature or these terms and conditions, please contact us at usepqad@gmail.com.<br><br>

By clicking "Agree," you consent to the use of your electronic signature as described above and agree to the security measures implemented for its protection.</p><br><br>
                <label>
                    <input type="checkbox" id="agreeTermsCheckbox"> I agree to the terms and conditions
                </label><br><br>
                <div class="e-sign-container">
                    <button class="cancel-button1" id="closeTermsBtn" type="button">CLOSE</button>
                    <button class="approve-assessment-button" id="acceptTerms" disabled>SUBMIT</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    let tempFormData = {};

    document.getElementById('middleinitial').addEventListener('input', function(e) {
        let middleinitialInput = e.target.value;

        // Limit to 10 characters
        if (middleinitialInput.length > 1) {
            middleinitialInput = middleinitialInput.slice(0, 1);
        }

        // Set the cleaned value back to the input
        e.target.value = middleinitialInput;
    });

    document.addEventListener('DOMContentLoaded', function() {
    // Set initial state
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

    document.getElementById('registerButton').addEventListener('click', function(event) {
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

        // Password validation check
        var isPasswordValid = passwordInput.dataset.valid === 'true';

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
        } else if (typeInput.value === 'internal' && !collegeSelect.value) {
            errorMessage = document.getElementById('collegeErrorMessage').innerHTML;
        } else if (typeInput.value === 'external' && !companySelect.value) {
            errorMessage = document.getElementById('companyErrorMessage').innerHTML;
        } else if (genderSelect.value === '' && genderInput.style.display === 'none') {
            errorMessage = document.getElementById('genderErrorMessage').innerHTML;
        } else if (!isPasswordValid) {
            errorMessage = document.getElementById('passwordRequirementErrorMessage').innerHTML;
        } else if (passwordInput.value !== confirmPasswordInput.value) {
            errorMessage = "Password and Confirm Password do not match.";
        }

        if (errorMessage) {
            // Save form data to temporary object
            tempFormData = {
                type: typeInput ? typeInput.value : '',
                prefix: prefixInput.value,
                first_name: firstNameInput.value,
                middle_initial: middleInitialInput.value,
                last_name: lastNameInput.value,
                email: emailInput.value,
                password: passwordInput.value,
                confirm_password: confirmPasswordInput.value,
                college: collegeSelect.value,
                company: companySelect.value,
                gender: genderSelect.value,
                gender_others: genderInput.value
            };

            document.getElementById('errorMessage').innerHTML = errorMessage;
            document.getElementById('errorPopup').style.display = 'block';
        } else {
            document.getElementById('termsModal').style.display = 'block';
        }
    });

    document.getElementById('agreeTermsCheckbox').addEventListener('change', function() {
        var acceptButton = document.getElementById('acceptTerms');
        if (this.checked) {
            acceptButton.disabled = false;
            acceptButton.classList.remove('disabled');
        } else {
            acceptButton.disabled = true;
            acceptButton.classList.add('disabled');
        }
    });

    document.getElementById('acceptTerms').addEventListener('click', function() {
        document.getElementById('termsModal').style.display = 'none';
        document.getElementById('registerForm').submit();
    });

    document.getElementById('closeErrorBtn').addEventListener('click', function() {
        document.getElementById('errorPopup').style.display = 'none';
        restoreFormData();
    });

    document.getElementById('closePopup').addEventListener('click', function() {
        document.getElementById('errorPopup').style.display = 'none';
        restoreFormData();
    });

    document.getElementById('closeTermsBtn').addEventListener('click', function() {
        document.getElementById('termsModal').style.display = 'none';
    });

    window.addEventListener('click', function(event) {
        if (event.target == document.getElementById('errorPopup')) {
            document.getElementById('errorPopup').style.display = 'none';
            restoreFormData();
        } else if (event.target == document.getElementById('termsModal')) {
            document.getElementById('termsModal').style.display = 'none';
        }
    });

    function restoreFormData() {
        // Restore form data from temporary object
        if (tempFormData) {
            document.querySelector(`input[name="type"][value="${tempFormData.type}"]`).checked = true;
            document.querySelector('select[name="prefix"]').value = tempFormData.prefix;
            document.querySelector('input[name="first_name"]').value = tempFormData.first_name;
            document.querySelector('input[name="middle_initial"]').value = tempFormData.middle_initial;
            document.querySelector('input[name="last_name"]').value = tempFormData.last_name;
            document.querySelector('input[name="email"]').value = tempFormData.email;
            document.querySelector('input[name="password"]').value = tempFormData.password;
            document.querySelector('input[name="confirm_password"]').value = tempFormData.confirm_password;
            document.querySelector('select[name="college"]').value = tempFormData.college;
            document.querySelector('select[name="company"]').value = tempFormData.company;
            document.querySelector('select[name="gender"]').value = tempFormData.gender;
            document.querySelector('input[name="gender_others"]').value = tempFormData.gender_others;
        }
    }

    document.getElementById('passwordInput').addEventListener('input', function() {
    var password = this.value;

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
    this.dataset.valid = isValidPassword;

    // Change border color of the parent div based on password validity
    var passwordContainer = document.getElementById('passwordContainer');
    if (isValidPassword) {
        passwordContainer.style.borderColor = 'green';
    } else {
        passwordContainer.style.borderColor = 'red';
    }
});

});

document.getElementById('togglePasswordConfirmPassword').addEventListener('click', function () {
    const passwordInput = document.getElementById('passwordInput');
    const confirmPasswordInput = document.getElementById('confirmPasswordInput');
    const icon = this;

    if (passwordInput.type === 'password' || confirmPasswordInput.type === 'password') {
        passwordInput.type = 'text';
        confirmPasswordInput.type = 'text';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    } else {
        passwordInput.type = 'password';
        confirmPasswordInput.type = 'password';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    }
});

document.getElementById('passwordInput').addEventListener('input', function() {
    validatePassword();
    checkPasswordMatch();
});

document.getElementById('confirmPasswordInput').addEventListener('input', function() {
    checkPasswordMatch();
});

function validatePassword() {
    var password = document.getElementById('passwordInput').value;

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
    document.getElementById('passwordInput').dataset.valid = isValidPassword;

    // Change border color of the parent div based on password validity
    var passwordContainer = document.getElementById('passwordContainer');
    if (isValidPassword) {
        passwordContainer.style.borderColor = 'green';
    } else {
        passwordContainer.style.borderColor = 'red';
    }
}

function checkPasswordMatch() {
    var password = document.getElementById('passwordInput').value;
    var confirmPassword = document.getElementById('confirmPasswordInput').value;
    var confirmPasswordContainer = document.getElementById('confirmPasswordContainer');

    if (password === confirmPassword && confirmPassword !== '') {
        confirmPasswordContainer.style.borderColor = 'green';
    } else {
        confirmPasswordContainer.style.borderColor = 'red';
    }
}

</script>

</body>
</html>
