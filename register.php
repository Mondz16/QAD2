<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/pagestyle.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row top-bar"></div>
        <div class="row header">
            <div class="col-12 col-md-6 d-flex align-items-center">
                <img src="images/USePLogo.png" alt="USeP Logo">
                <div class="divider"></div>
                <div class="vertical-line"></div>
                <div class="divider"></div>
                <div class="text">
                    <span class="one">One</span>
                    <span class="datausep">Data.</span>
                    <span class="one">One</span>
                    <span class="datausep">USeP.</span><br>
                    <span>Accreditor Portal</span>
                </div>
            </div>
            <div class="col-6 d-none d-md-flex align-items-center justify-content-end">
                <div class="text-right"></div>
                <span>Quality Assurance Division</span>
                <div class="divider"></div>
                <div class="vertical-line"></div>
                <div class="divider"></div>
                <img src="images/sdmdlogo.png" alt="USeP Logo">
            </div>
        </div>

        <div class="row justify-content-left content">
            <div style="height: 0px; width: 45px;"></div>
            <div class="col-md-8 col-lg-6">
                <h1>Register</h1>
                <p class="mt-3">Select Account Type</p>
                <form action="register_process.php" method="post">
                    <div class="account-type-selection row justify-content-between m-2">
                        <label class="account-type-option col-md-5 col-12">
                            <input type="radio" class="form-control" name="type" value="internal" onclick="handleAccountTypeChange(this)">
                            Internal Accreditor
                        </label>
                        <label class="account-type-option col-md-5 col-12">
                            <input type="radio" class="form-control" name="type" value="external" onclick="handleAccountTypeChange(this)">
                            External Accreditor
                        </label>
                    </div>
                    <p class="mt-3">Profile</p>
                    <div class="row">
                        <div class="col-md-5 col-12">
                            <input type="text" name="first_name" class="form-control" placeholder="First Name" required>
                        </div>
                        <div class="col-md-2 col-12">
                            <input type="text" name="middle_initial" class="form-control" placeholder="M.I.">
                        </div>
                        <div class="col-md-5 col-12">
                            <input type="text" name="last_name" class="form-control" placeholder="Last Name" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 col-12">
                            <select name="gender" onchange="handleGenderChange(this)" class="pt-3 form-control form-select" required>
                                <option value="" disabled selected>Gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other Gender</option>
                            </select>
                        </div>
                        <div class="col-md-6 col-12">
                                <input type="text" name="other_gender" id="otherGender" class="form-control" placeholder="Specify Gender" style="display:none;">
                            </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <select class="pt-3 form-control form-select" name="institution" id="institutionDropdown" class="form-control" disabled>
                                <option value="" disabled selected>Please select type of account first</option>
                            </select>
                            <select class="pt-3 form-control form-select" name="college" id="collegeDropdown" class="form-control" style="display: none;">
                                <option value="" disabled selected>College</option>
                                <?php
                                include_once 'connection.php';
                                $sql_colleges = "SELECT id, college_name FROM college ORDER BY college_name";
                                $result_colleges = $conn->query($sql_colleges);
                                if ($result_colleges && $result_colleges->num_rows > 0) {
                                    while ($row_college = $result_colleges->fetch_assoc()) {
                                        echo "<option value='{$row_college['id']}'>{$row_college['college_name']}</option>";
                                    }
                                } else {
                                    echo "<option value=''>No colleges found</option>";
                                }
                                ?>
                            </select>
                            <select class="pt-3 form-control form-select" name="company" id="companyDropdown" class="form-control" style="display: none;">
                                    <option value="" disabled selected>Company</option>
                                    <?php
                                    include_once 'connection.php';

                                    $sql_companies = "SELECT id, company_name FROM company ORDER BY company_name";
                                    $result_companies = $conn->query($sql_companies);

                                    if ($result_companies && $result_companies->num_rows > 0) {
                                        while ($row_company = $result_companies->fetch_assoc()) {
                                            echo "<option value='{$row_company['id']}'>{$row_company['company_name']}</option>";
                                        }
                                    } else {
                                        echo "<option value='' disabled selected>No companies found</option>";
                                    }
                                    ?>
                                </select>
                        </div>
                    </div>
                    <input id="uemail" type="email" name="email" class="form-control" placeholder="USeP Email" style="display: none;" required>
                    <input id="cemail" type="email" name="email" class="form-control" placeholder="Email" required>
                    <div class="row">
                        <div class="col-md-6 col-12">
                            <input type="password" name="password" id="password" class="form-control" placeholder="Password" required>
                        </div>
                        <div class="col-md-6 col-12">
                            <input type="password" name="confirm_password" id="confirmPassword" class="form-control" placeholder="Confirm Password" required>
                        </div>
                    </div>
                    <div class="row mx-auto">
                        <div class="form-check col-md-6 col-12">
                            <input type="checkbox" class="form-check-input" id="showPassword">
                            <label class="form-check-label" for="showPassword" style="font-size: 0.6em">Show Password</label>
                        </div>
                        <div class="form-check col-md-6 col-12">
                            <input type="checkbox" class="form-check-input" id="agreeTerms" required>
                            <label class="form-check-label" for="agreeTerms" style="font-size: 0.6em">I agree to the terms and conditions</label>
                        </div>
                    </div>
                    <div class="row mt-3 justify-content-end">
                        <div class="col-md-4 mb-2 col-6 mb-md-0">
                            <a href="login.php" class="lbtn btn btn-block">Log in Instead</a>
                        </div>
                        <div class="col-md-4 col-6">
                            <button type="submit" class="stbtn btn btn-block">SIGN UP</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <footer class="text-center mt-5">
            <p>Copyright © 2024. All Rights Reserved.</p>
            <a href="#">Terms of Use</a> | <a href="#">Privacy Policy</a>
        </footer>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script>
        function handleGenderChange(selectElement) {
            var otherGenderField = document.getElementById('otherGender');
            otherGenderField.style.display = selectElement.value === 'other' ? 'block' : 'none';
        }

        function handleAccountTypeChange(radioElement) {
            var institutionDropdown = document.getElementById('institutionDropdown');
            var collegeDropdown = document.getElementById('collegeDropdown');
            var companyDropdown = document.getElementById('companyDropdown');
            var uemail = document.getElementById('uemail');
            var cemail = document.getElementById('cemail');
            
            institutionDropdown.disabled = false;
            if (radioElement.value === 'internal') {
                collegeDropdown.style.display = 'block';
                companyDropdown.style.display = 'none';
                institutionDropdown.style.display = 'none';
                uemail.style.display = 'block';
                cemail.style.display = 'none';
            } else if (radioElement.value === 'external') {
                collegeDropdown.style.display = 'none';
                companyDropdown.style.display = 'block';
                institutionDropdown.style.display = 'none';
                uemail.style.display = 'none';
                cemail.style.display = 'block';
            }
        }

        document.querySelectorAll('.account-type-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.account-type-option').forEach(opt => opt.classList.remove('selected'));
                this.classList.add('selected');
                this.querySelector('input').checked = true;
            });
        });

        document.getElementById('showPassword').addEventListener('change', function() {
            var password = document.getElementById('password');
            var confirmPassword = document.getElementById('confirmPassword');
            if (this.checked) {
                password.type = 'text';
                confirmPassword.type = 'text';
            } else {
                password.type = 'password';
                confirmPassword.type = 'password';
            }
        });

        document.querySelector('form').addEventListener('submit', function (event) {
            var password = document.querySelector('input[name="password"]').value;
            var confirmPassword = document.querySelector('input[name="confirm_password"]').value;

            if (password !== confirmPassword) {
                event.preventDefault();
                alert('Passwords do not match!');
            }
        });
    </script>
</body>
</html>
