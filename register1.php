<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="css/pagestyle.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row top-bar"></div>
        <div class="row header mb-3">
            <div class="col-6 col-md-2 mx-auto d-flex align-items-center justify-content-end">
                <img src="images/USePLogo.png" alt="USeP Logo">
            </div>
            <div class="col-6 col-md-4 d-flex align-items-center">
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
            <div class="col-md-4 d-none d-md-flex align-items-center justify-content-end">
                <span>Quality Assurance Division</span>
                <div class="divider"></div>
                <div class="vertical-line"></div>
            </div>
            <div class="col-md-2 d-none d-md-flex align-items-center justify-content-start">
                <img src="images/sdmdlogo.png" alt="USeP Logo">
            </div>
        </div>

        <div class="row justify-content-start mt-5">
            <div class="col-2"></div>
            <div class="col-md-8 col-lg-6">
                <h1>Register</h1>
                <form action="register1_process.php" method="post">
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
                    <div class="col-md-3 col-12">
                            <select name="prefix" class="form-control form-select" required>
                                <option value="" disabled selected>Prefix</option>
                                <option value="Dr.">Dr.</option>
                                <option value="Assoc. Prof.">Assoc. Prof.</option>
                                <option value="Asst. Prof.">Asst. Prof.</option>
                                <option value="Engr.">Engr.</option>
                                <option value="Ms.">Ms.</option>
                                <option value="Mr.">Mr.</option>
                                <option value="Mrs.">Mrs.</option>
                                <option value="Mx.">Mx.</option>
                                <option value="Prof.">Prof.</option>
                                <option value="Rev.">Rev.</option>
                                <option value="Sir">Sir</option>
                                <option value="Madam">Madam</option>
                                <option value="None">None</option>
                            </select>
                        </div>
                        <div class="col-md-3 col-12">
                            <select name="gender" onchange="handleGenderChange(this)" class="pt-2 form-control form-select" required>
                                <option value="" disabled selected>Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Secret">Prefer not to say</option>
                                <option value="Other">Other Gender</option>
                            </select>
                        </div>
                        <div class="col-md-3 col-12">
                            <input type="text" name="custom_gender" id="otherGender" class="form-control" placeholder="Specify Gender" style="display:none;">
                        </div>
                        <div class="col-md-3 col-12">
                            <select name="suffix" class="form-control form-select" required>
                                <option value="" disabled selected>Suffix</option>
                                <option value="Jr.">Jr.</option>
                                <option value="Sr.">Sr.</option>
                                <option value="III">III</option>
                                <option value="IV">IV</option>
                                <option value="V">V</option>
                                <option value="None">None</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <select class="pt-3 form-control form-select" name="college" id="collegeDropdown" class="form-control" required>
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
                    <input id="uemail" type="email" name="email" class="form-control" placeholder="USeP Email" required>
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
                    <div class="container mt-3">
                        <div class="row justify-content-start">
                            <button type="submit" class="pobtn btn btn-block col-md-4 col-12 mb-3 mb-md-0 me-md-2">SIGN UP</button>
                            <a href="register.php" class="nebtn btn btn-block col-md-4 col-12">BACK</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <footer class="row text-left mt-5">
            <div class="col-2"></div>
            <div class="col">
                <p>Copyright © 2024. All Rights Reserved.</p>
                <a href="#">Terms of Use</a> | <a href="#">Privacy Policy</a>
            </div>
        </footer>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script>
        function handleGenderChange(selectElement) {
            var otherGenderField = document.getElementById('otherGender');
            otherGenderField.style.display = selectElement.value === 'Other' ? 'block' : 'none';
        }

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
