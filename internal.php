<?php
include 'connection.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$is_admin = false;

// Check user type and redirect accordingly
if ($user_id === 'admin' && basename($_SERVER['PHP_SELF']) !== 'admin_sidebar.php') {
    $is_admin = true;
    header("Location: admin_sidebar.php");
    exit();
} else {
    $user_type_code = substr($user_id, 3, 2);

    if ($user_type_code === '11') {
        // Internal user
        if (basename($_SERVER['PHP_SELF']) !== 'internal.php') {
            header("Location: internal.php");
            exit();
        }
    } elseif ($user_type_code === '22') {
        // External user
        if (basename($_SERVER['PHP_SELF']) !== 'external.php') {
            header("Location: external.php");
            exit();
        }
    } else {
        // Handle unexpected user type, redirect to login or error page
        header("Location: login.php");
        exit();
    }
}

// Fetch user details
$sql_user = "SELECT prefix, first_name, middle_initial, last_name, email, gender, college_code, profile_picture, password, otp FROM internal_users WHERE user_id = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("s", $user_id);
$stmt_user->execute();
$stmt_user->bind_result($prefix, $first_name, $middle_initial, $last_name, $email, $gender, $college_code, $profile_picture, $password, $otp);
$stmt_user->fetch();
$stmt_user->close();

// Fetch college name
$sql_college = "SELECT college_name FROM college WHERE code = ?";
$stmt_college = $conn->prepare($sql_college);
$stmt_college->bind_param("s", $college_code);
$stmt_college->execute();
$stmt_college->bind_result($college_name1);
$stmt_college->fetch();
$stmt_college->close();

// Fetch notification count
$sql_notifications = "
    SELECT COUNT(*) 
    FROM team t
    JOIN schedule s ON t.schedule_id = s.id
    WHERE t.internal_users_id = ? AND t.status = 'pending' AND s.schedule_status NOT IN ('cancelled', 'finished')
";
$stmt_notifications = $conn->prepare($sql_notifications);
$stmt_notifications->bind_param("s", $user_id);
$stmt_notifications->execute();
$stmt_notifications->bind_result($notification_count);
$stmt_notifications->fetch();
$stmt_notifications->close();

$accreditor_type = ($user_type_code === '11') ? 'Internal Accreditor' : 'External Accreditor';

// Fetch all colleges except the current user's college
$sql_all_colleges = "SELECT code, college_name FROM college WHERE code != ?";
$stmt_all_colleges = $conn->prepare($sql_all_colleges);
$stmt_all_colleges->bind_param("s", $college_code);
$stmt_all_colleges->execute();
$stmt_all_colleges->store_result();
$stmt_all_colleges->bind_result($college_code_option, $college_name_option);
$colleges = [];
while ($stmt_all_colleges->fetch()) {
    $colleges[] = ['code' => $college_code_option, 'name' => $college_name_option];
}
$stmt_all_colleges->close();

// SQL query to count the number of open assessments (accepted status, excluding 'cancelled' and 'finished' schedules)
$sql_assessment_count = "
    SELECT COUNT(*) 
    FROM team t
    JOIN schedule s ON t.schedule_id = s.id
    WHERE t.internal_users_id = ? 
    AND t.status = 'accepted'
    AND s.schedule_status NOT IN ('cancelled', 'finished')
";

$stmt_assessment_count = $conn->prepare($sql_assessment_count);
$stmt_assessment_count->bind_param("s", $user_id);
$stmt_assessment_count->execute();
$stmt_assessment_count->bind_result($assessment_count);
$stmt_assessment_count->fetch();
$stmt_assessment_count->close();

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Internal</title>
    <link rel="stylesheet" href="index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        .edit-icon {
            display: inline-block;
            margin-left: 10px;
            /* Add space between the text and icon */
            cursor: pointer;
        }

        .edit-icon i {
            font-size: 14px;
            /* Adjust icon size */
            color: #000;
            /* Change icon color if needed */
        }
        .notification-counter {
    color: #E6A33E; /* Text color */
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <div class="hair"></div>
        <div class="container">
            <div class="header">
                <div class="headerLeft">
                    <div class="USePData">
                        <img class="USeP" src="images/USePLogo.png" height="36">
                        <div style="height: 0px; width: 16px;"></div>
                        <div style="height: 32px; width: 1px; background: #E5E5E5"></div>
                        <div style="height: 0px; width: 16px;"></div>
                        <div class="headerLeftText">
                            <div class="onedata">
                                <h><span class="one">One</span>
                                    <span class="datausep">Data.</span>
                                    <span class="one">One</span>
                                    <span class="datausep">USeP.</span>
                                </h>
                            </div>
                            <h>Accreditor Portal</h>
                        </div>
                    </div>
                </div>

                <div class="headerRight">
                    <div class="QAD">
                        <div class="headerRightText">
                            <h>Quality Assurance Division</h>
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
        <div style="height: 10px; width: 0px;"></div>
        <nav id="sidebar">
            <ul class="sidebar-nav">
                <li class="sidebar-item has-dropdown">
                    <a href="#" class="sidebar-link">
                        <span style="margin-left: 8px;">Schedule</span>
                    </a>
                    <div class="sidebar-dropdown">
                        <a href="dashboard.php" class="sidebar-link">
                            <span style="margin-left: 8px;">View Schedule</span>
                        </a>
                        <a href="<?php echo $is_admin ? 'schedule.php' : '#'; ?>" class="<?php echo $is_admin ? 'sidebar-link' : 'sidebar-link-disabled'; ?>">
                            <span style="margin-left: 8px;">Add Schedule</span>
                        </a>
                        <a href="<?php echo $is_admin ? 'orientation.php' : '#'; ?>" class="<?php echo $is_admin ? 'sidebar-link' : 'sidebar-link-disabled'; ?>">
                            <span style="margin-left: 8px;">View Orientation</span>
                        </a>
                        <a href="<?php echo $is_admin === false ? 'internal_orientation.php' : '#'; ?>" class="<?php echo $is_admin === false ? 'sidebar-link' : 'sidebar-link-disabled'; ?>">
                            <span style="margin-left: 8px;">Request Orientation</span>
                        </a>
                    </div>
                </li>
                <li class="sidebar-item has-dropdown">
                    <a href="#" class="sidebar-link">
                        <span style="margin-left: 8px;">College</span>
                    </a>
                    <div class="sidebar-dropdown">
                        <a href="college.php" class="sidebar-link">
                            <span style="margin-left: 8px;">View College</span>
                        </a>
                        <a href="<?php echo $is_admin ? 'add_college.php' : '#'; ?>" class="<?php echo $is_admin ? 'sidebar-link' : 'sidebar-link-disabled'; ?>">
                            <span style="margin-left: 8px;">Add College</span>
                        </a>
                    </div>
                </li>
                <li class="sidebar-item has-dropdown">
                    <a href="#" class="sidebar-link">
                    <span style="margin-left: 8px;">Assessment</span>
                        <?php if ($assessment_count > 0): ?>
                            <span class="notification-counter">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-dot" viewBox="0 0 16 16">
                            <path d="M8 9.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3"/>
                            </svg>
                            </span>
                        <?php endif; ?>
                    </a>
                    <div class="sidebar-dropdown">
                        <a href="<?php echo $is_admin ? 'assessment.php' : 'internal_assessment.php'; ?>" class="sidebar-link">
                            <span style="margin-left: 8px;">View Assessments</span>
                            <?php if ($assessment_count > 0): ?>
                            <span class="notification-counter"><?php echo $assessment_count; ?></span>
                        <?php endif; ?>
                        </a>
                        <a href="<?php echo $is_admin ? 'udas_assessment.php' : '#'; ?>" class="<?php echo $is_admin ? 'sidebar-link' : 'sidebar-link-disabled'; ?>">
                            <span style="margin-left: 8px;">UDAS Assessments</span>
                        </a>
                    </div>
                </li>
                <li class="sidebar-item has-dropdown">
                    <a href="#" class="sidebar-link">
                        <span style="margin-left: 8px;">Administrative</span>
                    </a>
                    <div class="sidebar-dropdown">
                        <a href="<?php echo $is_admin ? 'area.php' : '#'; ?>" class="<?php echo $is_admin ? 'sidebar-link' : 'sidebar-link-disabled'; ?>">
                            <span style="margin-left: 8px;">View Area</span>
                        </a>
                        <a href="<?php echo $is_admin ? 'registration.php' : '#'; ?>" class="<?php echo $is_admin ? 'sidebar-link' : 'sidebar-link-disabled'; ?>">
                            <span style="margin-left: 8px;">Register Verification</span>
                        </a>
                        <a href="<?php echo $is_admin ? 'college_transfer.php' : '#'; ?>" class="<?php echo $is_admin ? 'sidebar-link' : 'sidebar-link-disabled'; ?>">
                            <span style="margin-left: 8px;">College Transfer</span>
                        </a>
                    </div>
                </li>
                <li class="sidebar-item has-dropdown">
                    <a href="#" class="sidebar-link">
                        <span style="margin-left: 8px;">Reports</span>
                    </a>
                    <div class="sidebar-dropdown">
                        <a href="<?php echo $is_admin === false ? 'internal_assigned_schedule.php' : 'reports_program_schedule.php'; ?>" class="sidebar-link">
                            <span style="margin-left: 8px;"><?php echo $is_admin === false ? 'View Assigned Schedule' : 'View Program Schedule'; ?></span></a>
                        <a href="reports_dashboard.php" class="sidebar-link">
                            <span style="margin-left: 8px;">View Programs</span></a>
                        <a href="program_timeline.php" class="sidebar-link">
                            <span style="margin-left: 8px;">View Timeline</span></a>
                        <a href="<?php echo $is_admin ? 'reports_member.php' : '#'; ?>" class="<?php echo $is_admin ? 'sidebar-link' : 'sidebar-link-disabled'; ?>">
                            <span style="margin-left: 8px;">View Accreditors</span></a>
                    </div>
                </li>
                <li class="sidebar-item has-dropdown">
                <a href="#" class="sidebar-link-active">
                        <span style="margin-left: 8px;">Account</span>
                        <?php if ($notification_count > 0): ?>
                            <span class="notification-counter">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-dot" viewBox="0 0 16 16">
                            <path d="M8 9.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3"/>
                            </svg>
                            </span>
                        <?php endif; ?>
                    </a>

                    <div class="sidebar-dropdown">
                        <a href="<?php echo $is_admin ? 'admin_sidebar.php' : 'internal.php'; ?>" class="sidebar-link">
                            <span style="margin-left: 8px;">Profile</span>
                        </a>
                        <a href="<?php echo $is_admin === false ? 'internal_notification.php' : '#'; ?>" class="<?php echo $is_admin === false ? 'sidebar-link' : 'sidebar-link-disabled'; ?>">
                            <span style="margin-left: 8px;">Notifications</span>
                        <?php if ($notification_count > 0): ?>
                            <span class="notification-counter"><?php echo $notification_count; ?></span>
                        <?php endif; ?>
                        </a>
                        <a href="logout.php" class="sidebar-link">
                            <span style="margin-left: 8px;">Logout</span>
                        </a>
                    </div>
                </li>
            </ul>
        </nav>
        <div style="height: 24px; width: 0px;"></div>
        <div class="container1">
            <div class="profile-info">
                <p class="personal">PERSONAL INFORMATION</p>
                <div class="profile">
                    <div class="profile-details">
                        <p class="profile-name"><?php echo $last_name . ',' . ' ' . $first_name . ' ' . $middle_initial . '.'; ?></p>
                        <p class="profile-type"><?php echo $accreditor_type; ?></p>
                        <div class="button-group">
                            <p class="user-id"><?php echo $user_id; ?></p>
                        </div>
                    </div>
                    <div class="profile-picture-container">
                        <img src="<?php echo $profile_picture; ?>" alt="Profile Picture" class="profile-picture">
                        <div class="edit-icon" onclick="openModal('profilePictureModal')">
                            <i class="fa-solid fa-pen"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="changepassword">
                <p class="personal">CHANGE PASSWORD</p>
                <form action="change_password_process.php" method="post">
                    <div style="height: 32px; width: 0px;"></div>
                    <div class="password">
                        <div class="passwordContainer">
                            <input class="passwordText" type="password" id="currentPassword" name="currentPassword" placeholder="CURRENT PASSWORD" required>
                        </div>
                    </div>
                    <div style="height: 20px; width: 0px;"></div>
                    <p><strong class="prefix">NEW PASSWORD MUST CONTAIN:</strong></p>
                    <div id="passwordChecklist" class="checklist">
                        <ul>
                            <li id="minLength" class="invalid">Minimum of 8 characters</li>
                            <li id="uppercase" class="invalid">An uppercase character</li>
                            <li id="lowercase" class="invalid">A lowercase character</li>
                            <li id="number" class="invalid">A number</li>
                            <li id="specialChar" class="invalid">A special character</li>
                        </ul>
                    </div>

                    <div style="height: 10px; width: 0px;"></div>

                    <div class="password">
                        <div class="passwordContainer" id="passwordContainer">
                            <input class="passwordText" type="password" id="newPassword" name="newPassword" placeholder="NEW PASSWORD" required oninput="checkPasswordStandards()"><br>
                        </div>
                    </div>

                    <div style="height: 10px; width: 0px;"></div>

                    <div class="password">
                        <div class="passwordContainer" id="confirmPasswordContainer">
                            <input class="passwordText" type="password" id="confirmPassword" name="confirmPassword" placeholder="CONFIRM PASSWORD" required oninput="checkPasswordMatch()"><br>
                        </div>
                    </div>

                    <div style="height: 10px; width: 0px;"></div>

                    <div class="showpassword">
                        <div class="showpasswordContainer">
                            <label id="showpassword">
                                <input type="checkbox" id="showPasswordCheckbox" onclick="togglePasswordVisibility()">
                                <span class="custom-checkbox"></span>
                                <span class="showpasswordText">Show Password</span>
                            </label>
                        </div>
                    </div>

                    <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">

                    <div style="height: 30px; width: 0px;"></div>

                    <button class="changepassword-button" type="submit" id="changePasswordButton" disabled>CHANGE PASSWORD</button>
                </form>
            </div>

            <div class="profile-info">
                <p><strong class="prefix">Prefix</strong><br><strong class="prefix1"><?php echo htmlspecialchars($prefix); ?></strong>
                    <button class="edit-link" onclick="openModal('prefixModal')"><i class="fas fa-edit"></i></button>
                </p><br>

                <p><strong class="prefix">Full Name:</strong><br><strong class="prefix1"><?php echo htmlspecialchars($first_name . ' ' . $middle_initial . '. ' . $last_name); ?></strong>
                    <button class="edit-link" onclick="openModal('fullNameModal')"><i class="fas fa-edit"></i></button>
                </p><br>

                <p><strong class="prefix">Gender</strong><br><strong class="prefix1"><?php echo htmlspecialchars($gender); ?></strong>
                    <button class="edit-link" onclick="openModal('genderModal')"><i class="fas fa-edit"></i></button>
                </p><br>

                <p><strong class="prefix">College</strong><br><strong class="prefix1"><?php echo htmlspecialchars($college_name1); ?></strong>
                    <button class="edit-link" onclick="openModal('collegeModal')"><i class="fas fa-edit"></i></button>
                </p><br>

                <p><strong class="prefix">Email</strong><br><strong class="prefix1"><?php echo htmlspecialchars($email); ?></strong>
                    <button class="edit-link" onclick="openModal('emailModal')"><i class="fas fa-edit"></i></button>
                </p>
            </div>


        </div>

        <div id="passwordMatchMessage"></div>

        <!-- Modals -->
        <div id="profilePictureModal" class="modal">
            <div class="modal-content">
                <form action="update_profile.php" method="post" enctype="multipart/form-data">
                    <h2>EDIT PROFILE PICTURE</h2>
                    <div class="nameContainer orientationContainer uploadContainer">
                        <span class="upload-text">UPLOAD</span>
                        <img id="upload-icon-profile" src="images/download-icon1.png" alt="Upload Icon" class="upload-icon">
                        <input class="uploadInput" type="file" id="profilePicture" name="profilePicture" accept="image/*" required>
                        <input type="hidden" name="field" value="profilePicture">
                    </div>
                    <div class="button-container">
                        <button type="button" class="cancel-button" onclick="cancelAction()">CANCEL</button>
                        <button type="submit" class="accept-button1">CONFIRM</button>
                    </div>
                </form>
            </div>
        </div>

        <div id="prefixModal" class="modal">
            <div class="modal-content">
                <form action="update_profile.php" method="post">
                    <h2>Edit Prefix</h2>
                    <div class="prefixContainer">
                        <select class="newPrefix" name="newPrefix">
                            <option value="<?php echo htmlspecialchars($prefix); ?>"><?php echo htmlspecialchars($prefix); ?></option>
                            <?php if ($prefix !== 'Mr.') { ?><option value="Mr.">Mr.</option><?php } ?>
                            <?php if ($prefix !== 'Ms.') { ?><option value="Ms.">Ms.</option><?php } ?>
                            <?php if ($prefix !== 'Mrs.') { ?><option value="Mrs.">Mrs.</option><?php } ?>
                            <?php if ($prefix !== 'Dr.') { ?><option value="Dr.">Dr.</option><?php } ?>
                            <?php if ($prefix !== 'Prof.') { ?><option value="Prof.">Prof.</option><?php } ?>
                            <?php if ($prefix !== 'Assoc. Prof.') { ?><option value="Assoc. Prof.">Assoc. Prof.</option><?php } ?>
                            <?php if ($prefix !== 'Assist. Prof.') { ?><option value="Assist. Prof.">Assist. Prof.</option><?php } ?>
                            <?php if ($prefix !== 'Engr.') { ?><option value="Engr.">Engr.</option><?php } ?>
                            <!-- Add more options as needed -->
                        </select>
                    </div>
                    <input type="hidden" name="field" value="prefix">
                    <div class="button-container">
                        <button type="button" class="cancel-button" onclick="cancelAction()">CANCEL</button>
                        <button type="submit" class="accept-button1">CONFIRM</button>
                    </div>
                </form>
            </div>
        </div>

        <div id="fullNameModal" class="modal">
            <div class="modal-content">
                <form action="update_profile.php" method="post">
                    <h2>Edit Full Name</h2>
                    <div class="name1">
                        <div class="profilenameContainer">
                            <input type="text" id="newFirstName" name="newFirstName" class="firstname" value="<?php echo htmlspecialchars($first_name); ?>" required>
                        </div>
                        <div class="profilenameContainer middleinitialContainer">
                            <input class="middleinitial" type="text" id="newMiddleInitial" name="newMiddleInitial" value="<?php echo htmlspecialchars($middle_initial); ?>" maxlength="1" required>
                        </div>
                        <div class="profilenameContainer lastnameContainer">
                            <input class="lastname" type="text" id="newLastName" name="newLastName" value="<?php echo htmlspecialchars($last_name); ?>" required>
                        </div>
                        <input type="hidden" name="field" value="fullname">
                    </div>
                    <div class="button-container">
                        <button type="button" class="cancel-button" onclick="cancelAction()">CANCEL</button>
                        <button type="submit" class="accept-button1">CONFIRM</button>
                    </div>
                </form>
            </div>
        </div>

        <div id="emailModal" class="modal">
            <div class="modal-content">
                <form action="update_email.php" method="post">
                    <h2>Edit Email</h2>
                    <div class="username">
                        <div class="usernameContainer">
                            <input class="email" type="email" id="newEmail" name="newEmail" value="<?php echo htmlspecialchars($email); ?>" required>
                        </div>
                        <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                    </div>
                    <div class="button-container">
                        <button type="button" class="cancel-button" onclick="cancelAction()">CANCEL</button>
                        <button type="submit" class="accept-button1">CONFIRM</button>
                    </div>
                </form>
            </div>
        </div>

        <div id="genderModal" class="modal">
            <div class="modal-content">
                <form action="update_profile.php" method="post">
                    <h2>Edit Gender</h2>
                    <div class="gender">
                        <div class="edit-gender">
                            <select class="prefix" id="genderSelect" name="newGender" required>
                                <option value="<?php echo htmlspecialchars($gender); ?>"><?php echo htmlspecialchars($gender); ?></option>
                                <?php if ($gender !== 'Male') { ?><option value="Male">Male</option><?php } ?>
                                <?php if ($gender !== 'Female') { ?><option value="Female">Female</option><?php } ?>
                                <?php if ($gender !== 'Prefer not to say') { ?><option value="Prefer not to say">Prefer not to say</option><?php } ?>
                                <?php if ($gender !== 'Others') { ?><option value="Others">Others</option><?php } ?>
                            </select>
                            <input class="specify-gender" type="text" id="genderInput" name="gender_others" placeholder="Specify Gender" value="<?php echo ($gender === 'Others') ? $gender : ''; ?>"><br><br>
                            <input type="hidden" name="field" value="gender">
                        </div>
                    </div>
                    <div class="button-container">
                        <button type="button" class="cancel-button" onclick="cancelAction()">CANCEL</button>
                        <button type="submit" class="accept-button1">CONFIRM</button>
                    </div>
                </form>
            </div>
        </div>

        <div id="collegeModal" class="modal">
            <div class="modal-content">
                <form action="update_college.php" method="post">
                    <h2>Request College Transfer</h2>
                    <div class="college">
                        <div class="college1">
                            <select id="newCollege" name="newCollege" required>
                                <option value="" disabled selected hidden>Select College</option>
                                <?php foreach ($colleges as $college) { ?>
                                    <option value="<?php echo htmlspecialchars($college['code']); ?>"><?php echo htmlspecialchars($college['name']); ?></option>
                                <?php } ?>
                            </select>
                            <input type="hidden" name="field" value="college">
                            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_id); ?>">
                            <input type="hidden" name="first_name" value="<?php echo htmlspecialchars($first_name); ?>">
                            <input type="hidden" name="middle_initial" value="<?php echo htmlspecialchars($middle_initial); ?>">
                            <input type="hidden" name="last_name" value="<?php echo htmlspecialchars($last_name); ?>">
                            <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                        </div>
                    </div>
                    <div class="button-container">
                        <button type="button" class="cancel-button" onclick="cancelAction()">CANCEL</button>
                        <button type="submit" class="accept-button1">CONFIRM</button>
                    </div>
                </form>
            </div>
        </div>

        <div id="logoutModal" class="modal1">
            <div class="modal-content1">
                <h4 id="confirmationMessage" style="font-size: 20px;">Are you sure you want to logout?</h4>
                <div class="button-container">
                    <button type="button" class="accept-back-button" id="backButton" onclick="cancelLogout()">NO</button>
                    <button type="button" class="accept-confirm-button" id="confirmButton" onclick="confirmLogout()">YES</button>
                </div>
            </div>
        </div>

        <script>
            function openLogoutModal() {
                document.getElementById('logoutModal').style.display = 'block'; // Show the modal
            }

            function confirmLogout() {
                window.location.href = 'logout.php'; // Redirect to logout.php
            }

            function cancelLogout() {
                document.getElementById('logoutModal').style.display = 'none';
            }

            document.addEventListener('click', function(event) {
                var modal = document.getElementById('logoutModal');
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });


            function handleFileChange(inputElement, iconElement) {
                inputElement.addEventListener('change', function() {
                    if (this.files && this.files.length > 0) {
                        // Change icon to check mark if a file is selected
                        iconElement.src = 'images/success.png'; // Ensure this path is correct and the image exists
                    } else {
                        // Change icon back to download if no file is selected
                        iconElement.src = 'images/download-icon1.png';
                    }
                });
            }

            handleFileChange(document.getElementById('profilePicture'), document.getElementById('upload-icon-profile'));

            function togglePasswordVisibility() {
                const showPasswordCheckbox = document.getElementById('showPasswordCheckbox');
                const newPassword = document.getElementById('newPassword');
                const confirmPassword = document.getElementById('confirmPassword');

                if (showPasswordCheckbox.checked) {
                    newPassword.type = 'text';
                    confirmPassword.type = 'text';
                } else {
                    newPassword.type = 'password';
                    confirmPassword.type = 'password';
                }
            }

            function checkPasswordStandards() {
                const password = document.getElementById('newPassword').value;
                const minLength = document.getElementById('minLength');
                const uppercase = document.getElementById('uppercase');
                const lowercase = document.getElementById('lowercase');
                const number = document.getElementById('number');
                const specialChar = document.getElementById('specialChar');
                const changePasswordButton = document.getElementById('changePasswordButton');

                minLength.classList.toggle('valid', password.length >= 8);
                minLength.classList.toggle('invalid', password.length < 8);

                uppercase.classList.toggle('valid', /[A-Z]/.test(password));
                uppercase.classList.toggle('invalid', !/[A-Z]/.test(password));

                lowercase.classList.toggle('valid', /[a-z]/.test(password));
                lowercase.classList.toggle('invalid', !/[a-z]/.test(password));

                number.classList.toggle('valid', /[0-9]/.test(password));
                number.classList.toggle('invalid', !/[0-9]/.test(password));

                specialChar.classList.toggle('valid', /[^A-Za-z0-9]/.test(password));
                specialChar.classList.toggle('invalid', !/[^A-Za-z0-9]/.test(password));

                const allValid = document.querySelectorAll('#passwordChecklist .valid').length === 5;
                const passwordsMatch = password === document.getElementById('confirmPassword').value;

                changePasswordButton.disabled = !(allValid && passwordsMatch);
            }

            function checkPasswordMatch() {
                const password = document.getElementById('newPassword').value;
                const confirmPassword = document.getElementById('confirmPassword').value;
                const passwordMatchMessage = document.getElementById('passwordMatchMessage');
                const changePasswordButton = document.getElementById('changePasswordButton');
                var confirmPasswordContainer = document.getElementById('confirmPasswordContainer');

                passwordMatchMessage.classList.toggle('valid', password === confirmPassword);
                passwordMatchMessage.classList.toggle('invalid', password !== confirmPassword);

                const allValid = document.querySelectorAll('#passwordChecklist .valid').length === 5;

                changePasswordButton.disabled = !(password === confirmPassword && allValid);
                if (password === confirmPassword && confirmPassword !== '') {
                    confirmPasswordContainer.style.borderColor = 'green';
                } else {
                    confirmPasswordContainer.style.borderColor = 'red';
                }
            }

            function cancelAction() {
                window.location.href = 'internal.php';
            }

            function toggleNotifications() {
                var dropdown = document.getElementById('notificationDropdown');
                dropdown.classList.toggle('show');
            }

            function openModal(modalId) {
                document.getElementById(modalId).style.display = "block";
            }

            function closeModal(modalId) {
                document.getElementById(modalId).style.display = "none";
            }

            // Show/hide gender input based on selection
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

            // Close the dropdown if the user clicks outside of it
            window.onclick = function(event) {
                if (!event.target.matches('.notification-bell, .notification-bell *')) {
                    var dropdowns = document.getElementsByClassName("dropdown-content");
                    for (var i = 0; i < dropdowns.length; i++) {
                        var openDropdown = dropdowns[i];
                        if (openDropdown.classList.contains('show')) {
                            openDropdown.classList.remove('show');
                        }
                    }
                }

                // Close modals if clicked outside
                var modals = document.getElementsByClassName('modal');
                for (var i = 0; i < modals.length; i++) {
                    if (event.target == modals[i]) {
                        modals[i].style.display = "none";
                    }
                }
            }
        </script>
</body>

</html>