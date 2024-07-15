<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Form</title>
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
                    <div style="height: 59px; width: 0px;"></div>
                    <div class="bodyLeftText" style="color: rgb(87, 87, 87); font-weight: 500; font-size: 3rem;">
                        <h>Register</h>
                    </div>

                    <div style="height: 32px; width: 0px;"></div>

                    <div class="bodyLeftText" style="color: rgb(87, 87, 87); font-weight: 300; font-size: 20px;">
                        <h>Select Account Type</h>
                    </div>

                    <div style="height: 8px; width: 0px;"></div>
                    <form action="register_process.php" method="post">
                        <div class="internal-external">
                            <div class="internal-externalSelect">
                                <input id="internal" type="radio" name="type" value="internal" required>
                                <label for="internal">Internal Accreditor</label>
                                <input id="external" type="radio" name="type" value="external" required>
                                <label for="external">External Accreditor</label>
                            </div>
                        </div>
                        <div style="height: 32px; width: 0px;"></div>
                        <div class="bodyLeftText" style="color: rgb(87, 87, 87); font-weight: 300; font-size: 20px;">
                            <h>Profile</h>
                        </div>
                        <div style="height: 10px; width: 0px;"></div>
                        <div class="name">
                            <div class="nameContainer" style="padding: 12px 20px; border-color: rgb(170, 170, 170); border-style: solid; border-width: 1px; border-radius: 8px;">
                                <input class="firstname" type="text" name="first_name" placeholder="First Name" required>
                            </div>
                            <div class="nameContainer" style="padding: 12px 20px; border-color: rgb(170, 170, 170); border-style: solid; border-width: 1px; border-radius: 8px;">
                                <input class="middleinitial" type="text" name="middle_initial" placeholder="Middle Initial" required>
                            </div>
                            <div class="nameContainer" style="padding: 12px 20px; border-color: rgb(170, 170, 170); border-style: solid; border-width: 1px; border-radius: 8px;">
                                <input class="lastname" type="text" name="last_name" placeholder="Last Name" required>
                            </div>
                        </div>
                        <div style="height: 8px; width: 0px;"></div>
                        <div class="username" style="width: 750px;">
                            <div class="usernameContainer" style="padding: 12px 20px; border-color: rgb(170, 170, 170); border-style: solid; border-width: 1px; border-radius: 8px;">
                                <input class="email" type="email" name="email" placeholder="USeP Email" required>
                            </div>
                        </div>
                        <div style="height: 8px; width: 0px;"></div>
                        <div class="name">
                            <div class="nameContainer" style="padding: 12px 20px; border-color: rgb(170, 170, 170); border-style: solid; border-width: 1px; border-radius: 8px;">
                                <input class="middleinitial" type="password" name="password" placeholder="Password" required>
                            </div>
                            <div class="nameContainer" style="padding: 12px 20px; border-color: rgb(170, 170, 170); border-style: solid; border-width: 1px; border-radius: 8px;">
                                <input class="lastname" type="password" name="confirm_password" placeholder="Confirm Password" required>
                            </div>
                        </div>
                        <div style="height: 8px; width: 0px;"></div>
                        <div class="college" id="college-field" style="display:none;">
                            <div class="college-company">
                                <select name="college">
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
                            </div>
                        </div>
                        <div class="company" id="company-field" style="display:none;">
                            <div class="college-company">
                                <select name="company">
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
                                        echo "<option value=''>No companies found</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <div style="height: 8px; width: 0px;"></div>

                        <a href="login.php" class="signup" style="margin-left: 305px;">Log in instead</a>

                        <button type="submit" class="login">Sign Up</button>
                    </form>

                    <div style="height: 80px; width: 0px;"></div>

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
    });
    </script>
</body>
</html>