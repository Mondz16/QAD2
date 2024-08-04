<?php
include 'connection.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Check user type and redirect accordingly
if ($user_id === 'admin') {
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
    <link rel="stylesheet" href="css/sidebar.css">
    <link href="css/pagestyle.css" rel="stylesheet">
    <link rel="stylesheet" href="index.css">
    <style>
        *   {
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
            background-color: #FF7A7A;
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

        thead{
            background-color: #fff;
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <aside id="sidebar">
            <div class="d-flex">
                <button class="toggle-btn" type="button">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-grid" viewBox="0 0 16 16">
                        <path d="M1 2.5A1.5 1.5 0 0 1 2.5 1h3A1.5 1.5 0 0 1 7 2.5v3A1.5 1.5 0 0 1 5.5 7h-3A1.5 1.5 0 0 1 1 5.5zM2.5 2a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5zm6.5.5A1.5 1.5 0 0 1 10.5 1h3A1.5 1.5 0 0 1 15 2.5v3A1.5 1.5 0 0 1 13.5 7h-3A1.5 1.5 0 0 1 9 5.5zm1.5-.5a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5zM1 10.5A1.5 1.5 0 0 1 2.5 9h3A1.5 1.5 0 0 1 7 10.5v3A1.5 1.5 0 0 1 5.5 15h-3A1.5 1.5 0 0 1 1 13.5zm1.5-.5a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5zm6.5.5A1.5 1.5 0 0 1 10.5 9h3a1.5 1.5 0 0 1 1.5 1.5v3a1.5 1.5 0 0 1-1.5 1.5h-3A1.5 1.5 0 0 1 9 13.5zm1.5-.5a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5z" />
                    </svg>
                </button>
                <div class="sidebar-logo">
                    <a href="#">QAD</a>
                </div>
            </div>
            <ul class="sidebar-nav">
                <li class="sidebar-item">
                    <a href="dashboard.php" class="sidebar-link">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-graph-up" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M0 0h1v15h15v1H0zm14.817 3.113a.5.5 0 0 1 .07.704l-4.5 5.5a.5.5 0 0 1-.74.037L7.06 6.767l-3.656 5.027a.5.5 0 0 1-.808-.588l4-5.5a.5.5 0 0 1 .758-.06l2.609 2.61 4.15-5.073a.5.5 0 0 1 .704-.07"/>
                        </svg>
                        <span style="margin-left: 8px;">Dashboard</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="admin_sidebar.php" class="sidebar-link">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person" viewBox="0 0 16 16">
                            <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0m4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4m-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10s-3.516.68-4.168 1.332c-.678.678-.83 1.418-.832 1.664z" />
                        </svg>
                        <span style="margin-left: 8px;">Admin Profile</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="schedule.php" class="sidebar-link">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-calendar-range" viewBox="0 0 16 16">
                            <path d="M9 7a1 1 0 0 1 1-1h5v2h-5a1 1 0 0 1-1-1M1 9h4a1 1 0 0 1 0 2H1z" />
                            <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5M1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4z" />
                        </svg>
                        <span style="margin-left: 8px;">Schedule</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="college.php" class="sidebar-link">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-mortarboard" viewBox="0 0 16 16">
                            <path d="M8.211 2.047a.5.5 0 0 0-.422 0l-7.5 3.5a.5.5 0 0 0 .025.916l7.5 3a.5.5 0 0 0 .372 0L14 7.14V13a1 1 0 0 0-1 1v2h3v-2a1 1 0 0 0-1-1V6.739l.686-.275a.5.5 0 0 0 .025-.916zM8 8.46 1.758 5.965 8 3.052l6.242 2.913z" />
                            <path d="M4.166 9.032a.5.5 0 0 0-.656.327l-.5 1.7a.5.5 0 0 0 .294.605l4.5 1.8a.5.5 0 0 0 .372 0l4.5-1.8a.5.5 0 0 0 .294-.605l-.5-1.7a.5.5 0 0 0-.656-.327L8 10.466zm-.068 1.873.22-.748 3.496 1.311a.5.5 0 0 0 .352 0l3.496-1.311.22.748L8 12.46z" />
                        </svg>
                        <span style="margin-left: 8px;">College</span>
                    </a>
                </li>
                <li class="sidebar-item mt-3">
                    <a href="orientation.php" class="sidebar-link">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chat-square-text" viewBox="0 0 16 16">
                            <path d="M14 1a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1h-2.5a2 2 0 0 0-1.6.8L8 14.333 6.1 11.8a2 2 0 0 0-1.6-.8H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1zM2 0a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2.5a1 1 0 0 1 .8.4l1.9 2.533a1 1 0 0 0 1.6 0l1.9-2.533a1 1 0 0 1 .8-.4H14a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2z" />
                            <path d="M3 3.5a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5M3 6a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9A.5.5 0 0 1 3 6m0 2.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5" />
                        </svg>
                        <span style="margin-left: 8px;">Orientation</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="assessment.php" class="sidebar-link">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-list-check" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M5 11.5a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5M3.854 2.146a.5.5 0 0 1 0 .708l-1.5 1.5a.5.5 0 0 1-.708 0l-.5-.5a.5.5 0 1 1 .708-.708L2 3.293l1.146-1.147a.5.5 0 0 1 .708 0m0 4a.5.5 0 0 1 0 .708l-1.5 1.5a.5.5 0 0 1-.708 0l-.5-.5a.5.5 0 1 1 .708-.708L2 7.293l1.146-1.147a.5.5 0 0 1 .708 0m0 4a.5.5 0 0 1 0 .708l-1.5 1.5a.5.5 0 0 1-.708 0l-.5-.5a.5.5 0 0 1 .708-.708l.146.147 1.146-1.147a.5.5 0 0 1 .708 0" />
                        </svg>
                        <span style="margin-left: 8px;">Assessment</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="udas_assessment.php" class="sidebar-link">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-clipboard2-check" viewBox="0 0 16 16">
                            <path d="M9.5 0a.5.5 0 0 1 .5.5.5.5 0 0 0 .5.5.5.5 0 0 1 .5.5V2a.5.5 0 0 1-.5.5h-5A.5.5 0 0 1 5 2v-.5a.5.5 0 0 1 .5-.5.5.5 0 0 0 .5-.5.5.5 0 0 1 .5-.5z" />
                            <path d="M3 2.5a.5.5 0 0 1 .5-.5H4a.5.5 0 0 0 0-1h-.5A1.5 1.5 0 0 0 2 2.5v12A1.5 1.5 0 0 0 3.5 16h9a1.5 1.5 0 0 0 1.5-1.5v-12A1.5 1.5 0 0 0 12.5 1H12a.5.5 0 0 0 0 1h.5a.5.5 0 0 1 .5.5v12a.5.5 0 0 1-.5.5h-9a.5.5 0 0 1-.5-.5z" />
                            <path d="M10.854 7.854a.5.5 0 0 0-.708-.708L7.5 9.793 6.354 8.646a.5.5 0 1 0-.708.708l1.5 1.5a.5.5 0 0 0 .708 0z" />
                        </svg>
                        <span style="margin-left: 8px;">UDAS Assessment</span>
                    </a>
                </li>
                <li class="sidebar-item mt-3">
                    <a href="registration.php" class="sidebar-link">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-people" viewBox="0 0 16 16">
                            <path d="M15 14s1 0 1-1-1-4-5-4-5 3-5 4 1 1 1 1zm-7.978-1L7 12.996c.001-.264.167-1.03.76-1.72C8.312 10.629 9.282 10 11 10c1.716 0 2.687.63 3.24 1.276.593.69.758 1.457.76 1.72l-.008.002-.014.002zM11 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4m3-2a3 3 0 1 1-6 0 3 3 0 0 1 6 0M6.936 9.28a6 6 0 0 0-1.23-.247A7 7 0 0 0 5 9c-4 0-5 3-5 4q0 1 1 1h4.216A2.24 2.24 0 0 1 5 13c0-1.01.377-2.042 1.09-2.904.243-.294.526-.569.846-.816M4.92 10A5.5 5.5 0 0 0 4 13H1c0-.26.164-1.03.76-1.724.545-.636 1.492-1.256 3.16-1.275ZM1.5 5.5a3 3 0 1 1 6 0 3 3 0 0 1-6 0m3-2a2 2 0 1 0 0 4 2 2 0 0 0 0-4" />
                        </svg>
                        <span style="margin-left: 8px;">Register Verification</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="college_transfer.php" class="sidebar-link">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-left-right" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M1 11.5a.5.5 0 0 0 .5.5h11.793l-3.147 3.146a.5.5 0 0 0 .708.708l4-4a.5.5 0 0 0 0-.708l-4-4a.5.5 0 0 0-.708.708L13.293 11H1.5a.5.5 0 0 0-.5.5m14-7a.5.5 0 0 1-.5.5H2.707l3.147 3.146a.5.5 0 1 1-.708.708l-4-4a.5.5 0 0 1 0-.708l4-4a.5.5 0 1 1 .708.708L2.707 4H14.5a.5.5 0 0 1 .5.5"/>
                        </svg>
                        <span style="margin-left: 8px;">College Transfer</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="reports.php" class="sidebar-link">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-bar-chart-line" viewBox="0 0 16 16">
                            <path d="M11 2a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v12h.5a.5.5 0 0 1 0 1H.5a.5.5 0 0 1 0-1H1v-3a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v3h1V7a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v7h1zm1 12h2V2h-2zm-3 0V7H7v7zm-5 0v-3H2v3z" />
                        </svg>
                        <span style="margin-left: 8px;">Reports</span>
                    </a>
                </li>
            </ul>
            <div class="sidebar-footer p-1">
                <a href="logout.php" class="sidebar-link">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-box-arrow-left" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M6 12.5a.5.5 0 0 0 .5.5h8a.5.5 0 0 0 .5-.5v-9a.5.5 0 0 0-.5-.5h-8a.5.5 0 0 0-.5.5v2a.5.5 0 0 1-1 0v-2A1.5 1.5 0 0 1 6.5 2h8A1.5 1.5 0 0 1 16 3.5v9a1.5 1.5 0 0 1-1.5 1.5h-8A1.5 1.5 0 0 1 5 12.5v-2a.5.5 0 0 1 1 0z" />
                        <path fill-rule="evenodd" d="M.146 8.354a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L1.707 7.5H10.5a.5.5 0 0 1 0 1H1.707l2.147 2.146a.5.5 0 0 1-.708.708z" />
                    </svg>
                    <span style="margin-left: 8px;">Logout</span>
                </a>
            </div>
        </aside>

        <div class="main">
            <div class="row top-bar"></div>
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
                                        <span class="datausep">USeP.</span></h>
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

            <div style="height: 24px; width: 0px;"></div>

            <div class="container1">
                <h1>ADMIN PROFILE</h1>
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
                            <div class="passwordContainer">
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
                    <h><strong class="prefix">Prefix</strong><br><strong class="prefix1"><?php echo htmlspecialchars($prefix); ?></strong><button class="edit-link" onclick="openModal('prefixModal')">Edit</button></h><br><br>
                    <h><strong class="prefix">Full Name:</strong><br><strong class="prefix1"><?php echo htmlspecialchars($first_name . ' ' . $middle_initial . '. ' . $last_name); ?></strong><button class="edit-link" onclick="openModal('fullNameModal')">Edit</button></h><br><br>
                    <h><strong class="prefix">Email</strong><br><strong class="prefix1"><?php echo htmlspecialchars($email); ?></strong><button class="edit-link" onclick="openModal('emailModal')">Edit</button></h><br><br>
                    <h><strong class="prefix">Gender</strong><br><strong class="prefix1"><?php echo htmlspecialchars($gender); ?></strong><button class="edit-link" onclick="openModal('genderModal')">Edit</button></h><br>
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
                <select class="prefix"id="genderSelect" name="newGender" required>
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
        const hamBurger = document.querySelector(".toggle-btn");

        hamBurger.addEventListener("click", function () {
            document.querySelector("#sidebar").classList.toggle("expand");
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

            passwordMatchMessage.classList.toggle('valid', password === confirmPassword);
            passwordMatchMessage.classList.toggle('invalid', password !== confirmPassword);

            const allValid = document.querySelectorAll('#passwordChecklist .valid').length === 5;
            
            changePasswordButton.disabled = !(password === confirmPassword && allValid);
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
            inputElement.addEventListener('change', function () {
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