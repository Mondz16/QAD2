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
if ($user_id === 'admin') {
    $is_admin = true;
    // If current page is not admin.php, redirect
    if (basename($_SERVER['PHP_SELF']) !== 'admin_sidebar.php') {
        header("Location: admin_sidebar.php");
        exit();
    }
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

$user_id = $_SESSION['user_id'];

// Fetch admin details
$sql_admin = "SELECT prefix, first_name, middle_initial, last_name, email, gender, profile_picture, password, otp FROM admin WHERE user_id = ?";
$stmt_admin = $conn->prepare($sql_admin);
$stmt_admin->bind_param("s", $user_id);
$stmt_admin->execute();
$stmt_admin->bind_result($prefix, $first_name, $middle_initial, $last_name, $email, $gender, $profile_picture, $password, $otp);
$stmt_admin->fetch();
$stmt_admin->close();

$accreditor_type = ($user_id === 'admin') ? 'admin' : '';

// Query to count assessments
$countQuery = "
    SELECT COUNT(DISTINCT s.id) AS assessment_count
        FROM schedule s
        JOIN team t ON s.id = t.schedule_id
        WHERE s.schedule_status IN ('approved', 'pending')
";
$Aresult = $conn->query($countQuery);
$Arow = $Aresult->fetch_assoc();
$assessmentCount = $Arow['assessment_count'];

// Query to count pending internal users
$sqlInternalPendingCount = "
    SELECT COUNT(*) AS internal_pending_count
    FROM internal_users i
    LEFT JOIN college c ON i.college_code = c.code
    WHERE i.status = 'pending' AND i.otp = 'verified'
";
$internalResult = $conn->query($sqlInternalPendingCount);
$internalPendingCount = $internalResult->fetch_assoc()['internal_pending_count'] ?? 0;

// Query to count pending external users
$sqlExternalPendingCount = "
    SELECT COUNT(*) AS external_pending_count
    FROM external_users e
    LEFT JOIN company c ON e.company_code = c.code
    WHERE e.status = 'pending'
";
$externalResult = $conn->query($sqlExternalPendingCount);
$externalPendingCount = $externalResult->fetch_assoc()['external_pending_count'] ?? 0;

// SQL query to count unique transfer requests based on bb-cccc part of user_id
$sqlTransferRequestCount = "
    SELECT COUNT(DISTINCT bb_cccc) AS transfer_request_count
    FROM (
        SELECT SUBSTRING(user_id, 4) AS bb_cccc, status
        FROM internal_users
        WHERE status = 'pending'
        GROUP BY bb_cccc
        HAVING COUNT(*) > 1
    ) AS transfer_groups
";
$Tresult = $conn->query($sqlTransferRequestCount);
$transferRequestCount = $Tresult->fetch_assoc()['transfer_request_count'] ?? 0;

// Total pending users count
$totalPendingUsers = $internalPendingCount + $externalPendingCount - $transferRequestCount;

$sqlPendingSchedulesCount = "
    SELECT COUNT(*) AS total_pending_schedules
    FROM schedule s
    WHERE s.schedule_status ='pending'
";
$Sresult = $conn->query($sqlPendingSchedulesCount);
$Srow = $Sresult->fetch_assoc();
$totalPendingSchedules = $Srow['total_pending_schedules'];

// Fetch the total count of missing udas_assessment_file
$sqlMissingAssessmentsCount = "
    SELECT COUNT(*) AS total_missing_assessments
    FROM schedule s
    LEFT JOIN udas_assessment ua ON s.id = ua.schedule_id
    WHERE s.schedule_status = 'approved' 
      AND (ua.udas_assessment_file IS NULL OR ua.udas_assessment_file = '')
";
$Dresult = $conn->query($sqlMissingAssessmentsCount);
$Drow = $Dresult->fetch_assoc();
$totalMissingAssessments = $Drow['total_missing_assessments'];

$sqlPendingOrientationsCount = "
        SELECT COUNT(*) AS total_pending_orientations
        FROM orientation o
        WHERE o.orientation_status = 'pending'
    ";

    $Qresult = $conn->query($sqlPendingOrientationsCount);
    $Qrow = $Qresult->fetch_assoc();
    $totalPendingOrientations = $Qrow['total_pending_orientations'];

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule List</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link rel="stylesheet" href="css/sidebar_updated.css">
    <link href="css/pagestyle.css" rel="stylesheet">
    <link rel="stylesheet" href="index.css">
    <style>
        * {
            font-family: "Quicksand";
        }

        .custom-btn-group {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 10px;
            border: 1px solid #AFAFAF;
            border-radius: 10px;
        }

        .custom-btn-group .btn-toggle {
            border-radius: 4px;
            padding: 10px 20px;
            transition: background-color 0.3s ease;
            width: 160px;
            height: 50px;
            font-size: 18px;
        }

        .btn-colleges {
            background-color: #B73033;
            color: white;
            border: none;
            font-weight: bold;
        }

        .btn-company {
            background-color: #f8f9fa;
            color: #888;
            border: 1px solid #ced4da;
            font-weight: lighter;
        }

        .btn-add-schedule {
            background-color: #2CB84F;
            color: white;
            border: none;
            padding: 10px 20px;
            display: flex;
            align-items: center;
            font-weight: bold;
            border-radius: 4px;
            transition: background-color 0.3s ease;
            width: 200px;
            height: 50px;
        }

        .btn-add-schedule:hover {
            background-color: #259b42;
        }

        .custom-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
        }

        .custom-table th,
        .custom-table td {
            padding: 15px 30px;
            text-align: left;
            border-bottom: 1px solid #AFAFAF;
            font-size: 16px
        }

        .custom-table th {
            background-color: #fff;
            font-weight: bold;
        }

        .custom-table th:last-child {
            padding-right: 80px;
            text-align: right;
        }

        .custom-table th:nth-child(2) {
            padding: 15px 0px;
            text-align: right;
        }


        .custom-table tr:last-child td {
            border-bottom: none;
        }

        .custom-table tr td:nth-child(1) {
            width: 600px;
        }

        .custom-table tr td:nth-child(2) {
            text-align: center;
            width: 175px;
        }

        .custom-table tr td:nth-child(3) {
            display: flex;
            padding-right: 80px;
            justify-content: flex-end;
        }

        .custom-table .btn-view {
            background-color: transparent;
            border: 1px solid #ced4da;
            border-radius: 7px;
            padding: 5px 15px;
            color: black;
            transition: background-color 0.3s ease;
        }

        .custom-table .btn-view:hover {
            background-color: #ced4da;
        }

        .hidden {
            display: none;
        }

        thead {
            background-color: #fff;
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <div class="main">
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
                                        <span class="datausep" style="color: rgb(151, 57, 57); font-weight: 600; font-size: 18px;">Data.</span>
                                        <span class="one" style="color: rgb(229, 156, 36); font-weight: 600; font-size: 18px;">One</span>
                                        <span class="datausep" style="color: rgb(151, 57, 57); font-weight: 600; font-size: 18px;">USeP.</span>
                                    </h>
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
            <nav id="sidebar">
                <ul class="sidebar-nav">
                    <li class="sidebar-item has-dropdown">
                        <a href="#" class="sidebar-link">
                            <span style="margin-left: 8px;">Schedule</span>
                            <?php if ($totalPendingSchedules > 0 || $totalPendingOrientations > 0): ?>
                                <span class="notification-counter">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-dot" viewBox="0 0 16 16">
                            <path d="M8 9.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3"/>
                            </svg>
                            </span>
                            <?php endif; ?>
                        </a>
                        <div class="sidebar-dropdown">
                            <a href="dashboard.php" class="sidebar-link">
                                <span style="margin-left: 8px;">View Schedule</span>
                            </a>
                            <a href="<?php echo $is_admin ? 'schedule.php' : '#'; ?>" class="<?php echo $is_admin ? 'sidebar-link' : 'sidebar-link-disabled'; ?>">
                                <span style="margin-left: 8px;">Add Schedule</span>
                                <?php if ($totalPendingSchedules > 0): ?>
                                    <span class="notification-counter"><?= $totalPendingSchedules; ?></span>
                                <?php endif; ?>
                            </a>
                            <a href="<?php echo $is_admin ? 'orientation.php' : '#'; ?>" class="<?php echo $is_admin ? 'sidebar-link' : 'sidebar-link-disabled'; ?>">
                                <span style="margin-left: 8px;">View Orientation</span>
                                <?php if ($totalPendingOrientations > 0): ?>
                                    <span class="notification-counter"><?= $totalPendingSchedules; ?></span>
                                <?php endif; ?>

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
                            <?php if ($assessmentCount > 0): ?>
                                <span class="notification-counter">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-dot" viewBox="0 0 16 16">
                                        <path d="M8 9.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3" />
                                    </svg>
                                </span>
                            <?php endif; ?>
                        </a>
                        <div class="sidebar-dropdown">
                            <a href="<?php echo $is_admin ? 'assessment.php' : 'internal_assessment.php'; ?>" class="sidebar-link">
                                <span style="margin-left: 8px;">View Assessments</span>
                                <?php if ($assessmentCount > 0): ?>
                                    <span class="notification-counter"><?= $assessmentCount; ?></span>
                                <?php endif; ?>
                            </a>
                            <a href="<?php echo $is_admin ? 'udas_assessment.php' : '#'; ?>" class="<?php echo $is_admin ? 'sidebar-link' : 'sidebar-link-disabled'; ?>">
                                <span style="margin-left: 8px;">UDAS Assessments</span>
                                <?php if ($totalMissingAssessments > 0): ?>
                                    <span class="notification-counter"><?= $totalMissingAssessments; ?></span>
                                <?php endif; ?>
                            </a>                            <a href="<?php echo $is_admin ? 'assessment_history.php' : '#'; ?>" class="<?php echo $is_admin ? 'sidebar-link' : 'sidebar-link-disabled'; ?>">
                                <span style="margin-left: 8px;">Assessment History</span>
                            </a>
                        </div>
                    </li>
                    <li class="sidebar-item has-dropdown">
                        <a href="#" class="sidebar-link">
                            <span style="margin-left: 8px;">Administrative</span>
                            <?php if ($totalPendingUsers > 0 || $transferRequestCount > 0): ?>
                                <span class="notification-counter">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-dot" viewBox="0 0 16 16">
                                        <path d="M8 9.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3" />
                                    </svg>
                                </span>
                            <?php endif; ?>
                        </a>
                        <div class="sidebar-dropdown">
                            <a href="<?php echo $is_admin ? 'area.php' : '#'; ?>" class="<?php echo $is_admin ? 'sidebar-link' : 'sidebar-link-disabled'; ?>">
                                <span style="margin-left: 8px;">View Area</span>
                            </a>
                            <a href="<?php echo $is_admin ? 'registration.php' : '#'; ?>" class="<?php echo $is_admin ? 'sidebar-link' : 'sidebar-link-disabled'; ?>">
                                <span style="margin-left: 8px;">Register Verification</span>
                                <?php if ($totalPendingUsers > 0): ?>
                                    <span class="notification-counter"><?= $totalPendingUsers; ?></span>
                                <?php endif; ?>
                            </a>
                            <a href="<?php echo $is_admin ? 'college_transfer.php' : '#'; ?>" class="<?php echo $is_admin ? 'sidebar-link' : 'sidebar-link-disabled'; ?>">
                                <span style="margin-left: 8px;">College Transfer</span>
                                <?php if ($transferRequestCount > 0): ?>
                                    <span class="notification-counter"><?= $transferRequestCount; ?></span>
                                <?php endif; ?>
                            </a>
                            <a href="<?php echo $is_admin ? 'program_level.php' : '#'; ?>" class="<?php echo $is_admin ? 'sidebar-link' : 'sidebar-link-disabled'; ?>">
                                <span style="margin-left: 8px;">Update Program Level</span>
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
                        </a>

                        <div class="sidebar-dropdown">
                            <a href="<?php echo $is_admin ? 'admin_sidebar.php' : 'internal.php'; ?>" class="sidebar-link">
                                <span style="margin-left: 8px;">Profile</span>
                            </a>
                            <a href="<?php echo $is_admin === false ? 'internal_notification.php' : '#'; ?>" class="<?php echo $is_admin === false ? 'sidebar-link' : 'sidebar-link-disabled'; ?>">
                                <span style="margin-left: 8px;">Notifications</span>
                            </a>
                            <a href="logout.php" class="sidebar-link">
                                <span style="margin-left: 8px;">Logout</span>
                            </a>
                        </div>
                    </li>
                </ul>
            </nav>
            <div style="height: 28px; width: 0px;"></div>
            <h1 style="text-align: center;">ADMIN PROFILE</h1>
            <div style="height: 28px; width: 0px;"></div>
            <div class="container1">
                <div class="profile-info">
                    <p class="personal">PERSONAL INFORMATION</p>
                    <div class="profile">
                        <div class="profile-details">
                            <h class="profile-name"><?php echo $last_name . ',' . ' ' . $first_name . ' ' . $middle_initial . '.'; ?></h>
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

                <div class="changepassword1">
                    <p class="personal">CHANGE PASSWORD</p>
                    <form action="change_password_process.php" method="post">
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
                            <div class="passwordContainer">
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
                    <h><strong class="prefix">Prefix</strong><br><strong class="prefix1"><?php echo htmlspecialchars($prefix); ?></strong>
                        <button class="edit-link" onclick="openModal('prefixModal')"><i class="fas fa-edit"></i></button>
                    </h><br><br>

                    <h><strong class="prefix">Full Name:</strong><br><strong class="prefix1"><?php echo htmlspecialchars($first_name . ' ' . $middle_initial . '. ' . $last_name); ?></strong>
                        <button class="edit-link" onclick="openModal('fullNameModal')"><i class="fas fa-edit"></i></button>
                    </h><br><br>

                    <h><strong class="prefix">Email</strong><br><strong class="prefix1"><?php echo htmlspecialchars($email); ?></strong>
                        <button class="edit-link" onclick="openModal('emailModal')"><i class="fas fa-edit"></i></button>
                    </h><br><br>

                    <h><strong class="prefix">Gender</strong><br><strong class="prefix1"><?php echo htmlspecialchars($gender); ?></strong>
                        <button class="edit-link" onclick="openModal('genderModal')"><i class="fas fa-edit"></i></button>
                    </h><br>
                </div>
            </div>
        </div>
    </div>

    <div id="passwordMatchMessage"></div>
    <!-- Modals -->
    <div id="profilePictureModal" class="modal">
        <div class="modal-content">
            <form action="update_profile.php" method="post" enctype="multipart/form-data">
                <h2>EDITT PROFILE PICTURE</h2>
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
            <form action="update_profile.php" method="post">
                <h2>Edit Email</h2>
                <div class="username">
                    <div class="usernameContainer">
                        <input class="email" type="email" id="newEmail" name="newEmail" value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>
                    <input type="hidden" name="field" value="email">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous">
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarNav = document.querySelector('.sidebar-nav');
            const sidebarFooter = document.querySelector('.sidebar-footer');
            const sidebar = document.getElementById('sidebar');
            const toggleBtn = document.querySelector('.toggle-btn');
            let isSidebarPermanentlyExpanded = false;

            // Toggle sidebar expansion on hamburger button click
            toggleBtn.addEventListener('click', function() {
                isSidebarPermanentlyExpanded = !isSidebarPermanentlyExpanded;
                sidebar.classList.toggle('expand', isSidebarPermanentlyExpanded);
            });

            // Hover effect to apply on both .sidebar-nav and .sidebar-footer
            function handleMouseEnter() {
                if (!isSidebarPermanentlyExpanded) {
                    sidebar.classList.add('expand');
                }
            }

            function handleMouseLeave() {
                if (!isSidebarPermanentlyExpanded) {
                    sidebar.classList.remove('expand');
                }
            }

            sidebarNav.addEventListener('mouseenter', handleMouseEnter);
            sidebarNav.addEventListener('mouseleave', handleMouseLeave);

            sidebarFooter.addEventListener('mouseenter', handleMouseEnter);
            sidebarFooter.addEventListener('mouseleave', handleMouseLeave);
        });

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

            changePasswordButton.disabled = !(password === confirmPassword && allValid);
            if (password === confirmPassword && confirmPassword !== '') {
                confirmPasswordContainer.style.borderColor = 'green';
            } else {
                confirmPasswordContainer.style.borderColor = 'red';
            }
        }

        function cancelAction() {
            window.location.href = 'admin_sidebar.php';
        }

        function openModal(modalId) {
            document.getElementById(modalId).style.display = "block";
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = "none";
        }

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
    </script>
</body>

</html>