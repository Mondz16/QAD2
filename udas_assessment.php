<?php
include 'connection.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Retrieve full name of the logged-in user from the admin table without the prefix
$sql = "SELECT CONCAT(first_name, ' ', middle_initial, '. ', last_name) AS full_name FROM admin WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user_id);
$stmt->execute();
$stmt->bind_result($full_name);
$stmt->fetch();
$stmt->close();

// Check user type and redirect accordingly
if ($user_id === 'admin') {
    // If current page is not admin.php, redirect
    if (basename($_SERVER['PHP_SELF']) !== 'udas_assessment.php') {
        header("Location: udas_assessment.php");
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

// Fetch approved schedules
$approvedSchedulesQuery = "
    SELECT s.id, s.level_applied, s.schedule_date, s.schedule_time, 
           c.college_name, p.program_name, ua.udas_assessment_file
    FROM schedule s
    JOIN college c ON s.college_code = c.code
    JOIN program p ON s.program_id = p.id
    LEFT JOIN udas_assessment ua ON s.id = ua.schedule_id
    WHERE s.schedule_status = 'approved'
";
$approvedSchedulesResult = $conn->query($approvedSchedulesQuery);
$approvedSchedules = $approvedSchedulesResult->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UDAS Assessment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/navbar.css">
    <link href="css/pagestyle.css" rel="stylesheet">
    <style>
        /* Additional CSS for numbered boxes */
        .assessment-box {
            border: 1px solid #ccc;
            border-radius: 8px;
            padding: 20px;
            width: 800px;
            height: 350px;
            margin-bottom: 20px;
        }

        .assessment-box h2 {
            font-size: 18px;
            margin-bottom: 10px;
            text-align: end;
        }

        .assessment-college {
            text-align: left;
            font-size: 16px;
            width: 540px;
            margin-right: 10px;
        }

        .assessment-holder-1 {
            display: flex;
            justify-content: space-around;
        }

        .assessment-holder-2 {
            display: flex;
            justify-content: flex-start;
            margin-top: 10px;
        }

        .assessment-dateTime {
            text-align: left;
            width: 265px;
            margin-right: 12px;
        }

        .assessment-dateTime p {
            margin: 0;
        }

        .assessment-udas {
            text-align: left;
            width: 200px;
        }

        .assessment-udas .udas-button {
            height: 46px;
            width: 100%;
            margin: 10px 0;
            background-color: white;
            font-weight: bold;
            color: #006118;
            border: 1px solid #006118;
        }

        .assessment-udas .udas-button:hover {
            background-color: #D4FFDF;
            border: 1px solid #006118;
            color: #006118;
        }

        .assessment-udas .download-button {
            height: 46px;
            width: 100%;
            margin: 10px 0;
            background-color: #D4FFDF;
            color: #006118;
            font-weight: bold;
            border: 1px solid #006118;
            padding-top: 9px;
        }

        .assessment-level-applied p {
            margin-bottom: 10px;
            text-align: left;
        }

        .assessment-level-applied h3 {
            width: 200px;
            height: 140px;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #FFF1C0;
            border-radius: 10px;
            font-size: 5rem;
            font-weight: bold;
            border: 1px solid #E6A33E;
            color: #575757;
        }

        .assessment-college p {
            margin: 0px;
        }

        .assessment-values {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 10px;
            margin: 10px 0px;
            font-weight: bold;
        }

        
        .scrollable-container {
            max-height: 650px;
            max-width: 1200px;
            overflow-y: auto;
            overflow-x: hidden;
            display: inline-block;
            margin-left: 30px;
        }

        .scrollable-container-holder{
            display: inline-block;
            width: fit-content;
            padding: 20px 20px 20px 0px;
            border: 1px solid #ddd;
            border-radius: 20px;
            background: #f9f9f9;
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%; /* This ensures the overlay covers the full width */
            height: 100%; /* This ensures the overlay covers the full height */
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5); /* Semi-transparent background */
        }

        .modal-content {
            background-color: #fefefe;
            padding: 20px;
            border: 1px solid #888;
            width: 535px; /* Set the width of the modal content */
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.2); /* Optional: add a subtle shadow */
            max-height: 850px; /* Limits the height of the modal content */
            overflow-y: auto; /* Enables vertical scrolling if content exceeds the height */

            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%); /* This centers the modal */
        }




        .modal-content h2 {
            text-align: center;
            margin: 20px;
        }

        .assessment-group input {
            border-color: rgb(170, 170, 170);
            border-style: solid;
            border-width: 1px;
            border-radius: 8px;
            flex: 1;
        }

        .assessment-group input[type="text"], .assessment-group-college {
            width: 100%;
            padding: 12px;
            box-sizing: border-box;
            text-align: left; /* Aligns the input text to the left */
        }

        .assessment-group-college, .assessment-group-program {
            height: 48px;
            font-size: 16px;
        }

        .assessment-group-college {
            margin-bottom: 20px;
        }

        .assessment-group-college, .assessment-group-program {
            height: 48px;
            font-size: 16px;
        }

        .assessment-group label {
            text-transform: uppercase;
            font-weight: bold;
            display: block;
            margin-bottom: 10px;
        }

        .name, .orientationname, .orientationname1, .assessmentname, .assessmentname1, .assessmentname2 {
            display: flex;
            gap: 10px;
        }

        .orientationname1 {
            width: 100%;
        }

        .nameContainer, .prefixContainer, .profilenameContainer, .form-group input, .assessment-group input {
            border-color: rgb(170, 170, 170);
            border-style: solid;
            border-width: 1px;
            border-radius: 8px;
            flex: 1;
        }

        .nameContainer {
            padding: 12px 20px;
        }

        .orientationContainer, .orientationContainer1 {
            text-align: center;
        }

        .orientationContainer1 {
            background-color: #FFEBA3;
        }

        .level, .time, .result, .area_evaluated {
            color: #575757;
            width: 100%;
            border: 0;
            resize: none;
            outline: 0;
            padding: 0;
            font-size: 16px;
            background: transparent;
            caret-color: #575757;
        }

        .titleContainer {
            flex: 1;
            padding-top: 20px;
            padding-bottom: 10px;
        }

        .upload {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .uploadContainer {
            display: flex;
            align-items: center;
            position: relative;
            padding: 12px 20px;
        }

        .upload-text {
            margin-left: auto;
            font-weight: bold;
            color: #575757;
            font-size: 16px;
            cursor: pointer;
        }

        .upload-icon {
            width: 20px;
            height: 20px;
            margin-left: auto;
            cursor: pointer;
        }

        .uploadInput {
            position: absolute;
            opacity: 0;
            right: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
            z-index: 2;
        }

        .submit-button1, .cancel-button1, .approve-cancel-button, .orientation-button, .assessment-button, .assessment-button-done {
            padding: 10px 25px;
            margin: 0 5px;
            cursor: pointer;
            border: none;
            border-radius: 8px;
            font-size: 14px;
        }

        .cancel-button, .cancel-button1 {
            color: #AFAFAF;
            border: 1px solid #AFAFAF;
        }

        .cancel-button1:hover {
            background-color: #FF6262;
            color: white;
            font-weight: bold;
        }
        .cancel-button1 {
            width: 150px;
        }

        .submit-button, .submit-button1, .export-button {
            color: #006118;
            border: 1px solid #006118;
            background-color: #D4FFDF;
        }

        .submit-button1 {
            width: 228px;
        }

        .submit-button:hover, .submit-button1:hover, .export-button:hover {
            background-color: #76FA97;
            border: 1px solid #76FA97;
            font-weight: bold;
        }

        .button-container, .e-sign-container {
            display: flex;
            width: 100%; /* Ensure the container takes full width */
            margin-top: 20px; /* Add some spacing from other elements */
        }

        .button-container {
            justify-content: flex-end;
        }

        .custom-loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .custom-spinner {
            width: 40px; /* Size similar to Bootstrap's default spinner */
            height: 40px; /* Size similar to Bootstrap's default spinner */
            border-width: 5px;
            border-style: solid;
            border-radius: 50%;
            border-color: #FF7A7A; /* Custom color for the spinner */
            border-right-color: transparent; /* Transparent border to create the spinning effect */
            animation: custom-spin 0.75s linear infinite; /* Bootstrap-like spinning animation */
        }

        .custom-spinner-hidden {
            display: none;
        }

        /* Custom spin animation similar to Bootstrap */
        @keyframes custom-spin {
            100% {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <!-- Sidebar -->
        <aside id="sidebar">
            <div class="d-flex">
                <button class="toggle-btn" type="button">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-grid" viewBox="0 0 16 16">
                        <path d="M1 2.5A1.5 1.5 0 0 1 2.5 1h3A1.5 1.5 0 0 1 7 2.5v3A1.5 1.5 0 0 1 5.5 7h-3A1.5 1.5 0 0 1 1 5.5zM2.5 2a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5zm6.5.5A1.5 1.5 0 0 1 10.5 1h3A1.5 1.5 0 0 1 15 2.5v3A1.5 1.5 0 0 1 13.5 7h-3A1.5 1.5 0 0 1 9 5.5zm1.5-.5a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5zM1 10.5A1.5 1.5 0 0 1 2.5 9h3A1.5 1.5 0 0 1 7 10.5v3A1.5 1.5 0 0 1 5.5 15h-3A1.5 1.5 0 0 1 1 13.5zm1.5-.5a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5zm6.5.5A1.5 1.5 0 0 1 10.5 9h3a1.5 1.5 0 0 1 1.5 1.5v3a1.5 1.5 0 0 1-1.5 1.5h-3A1.5 1.5 0 0 1 9 13.5zm1.5-.5a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5z" />
                    </svg>
                </button>
                <div class="sidebar-logo">
                    <a href="udas_assessment.php">QAD</a>
                </div>
            </div>
            <ul class="sidebar-nav">
                <li class="sidebar-item">
                    <a href="dashboard.php" class="sidebar-link">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-graph-up" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M0 0h1v15h15v1H0zm14.817 3.113a.5.5 0 0 1 .07.704l-4.5 5.5a.5.5 0 0 1-.74.037L7.06 6.767l-3.656 5.027a.5.5 0 0 1-.808-.588l4-5.5a.5.5 0 0 1 .758-.06l2.609 2.61 4.15-5.073a.5.5 0 0 1 .704-.07" />
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
                <li class="sidebar-item">
                    <a href="area.php" class="sidebar-link">
                        <i class="fa-solid fa-clipboard"></i>
                            <path d="M8.211 2.047a.5.5 0 0 0-.422 0l-7.5 3.5a.5.5 0 0 0 .025.916l7.5 3a.5.5 0 0 0 .372 0L14 7.14V13a1 1 0 0 0-1 1v2h3v-2a1 1 0 0 0-1-1V6.739l.686-.275a.5.5 0 0 0 .025-.916zM8 8.46 1.758 5.965 8 3.052l6.242 2.913z" />
                            <path d="M4.166 9.032a.5.5 0 0 0-.656.327l-.5 1.7a.5.5 0 0 0 .294.605l4.5 1.8a.5.5 0 0 0 .372 0l4.5-1.8a.5.5 0 0 0 .294-.605l-.5-1.7a.5.5 0 0 0-.656-.327L8 10.466zm-.068 1.873.22-.748 3.496 1.311a.5.5 0 0 0 .352 0l3.496-1.311.22.748L8 12.46z" />
                        <span style="margin-left: 8px;">Area</span>
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
                    <a href="udas_assessment.php" class="sidebar-link-active">
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
                            <path fill-rule="evenodd" d="M1 11.5a.5.5 0 0 0 .5.5h11.793l-3.147 3.146a.5.5 0 0 0 .708.708l4-4a.5.5 0 0 0 0-.708l-4-4a.5.5 0 0 0-.708.708L13.293 11H1.5a.5.5 0 0 0-.5.5m14-7a.5.5 0 0 1-.5.5H2.707l3.147 3.146a.5.5 0 1 1-.708.708l-4-4a.5.5 0 0 1 0-.708l4-4a.5.5 0 1 1 .708.708L2.707 4H14.5a.5.5 0 0 1 .5.5" />
                        </svg>
                        <span style="margin-left: 8px;">College Transfer</span>
                    </a>
                </li>
                <li class="sidebar-item">
                <a href="#" class="sidebar-link collapsed has-dropdown" data-bs-toggle="collapse"
                data-bs-target="#auth" aria-expanded="false" aria-controls="auth">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-bar-chart-line" viewBox="0 0 16 16">
                        <path d="M11 2a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v12h.5a.5.5 0 0 1 0 1H.5a.5.5 0 0 1 0-1H1v-3a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v3h1V7a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v7h1zm1 12h2V2h-2zm-3 0V7H7v7zm-5 0v-3H2v3z"/>
                        </svg>
                        <span style="margin-left: 8px;">Reports</span>
                    </a>
                    <ul id="auth" class="sidebar-dropdown list-unstyled collapse" data-bs-parent="#sidebar">
                        <li class="sidebar-item1">
                            <a href="reports_dashboard.php" class="sidebar-link"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-list-columns me-2" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M0 .5A.5.5 0 0 1 .5 0h9a.5.5 0 0 1 0 1h-9A.5.5 0 0 1 0 .5m13 0a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2a.5.5 0 0 1-.5-.5m-13 2A.5.5 0 0 1 .5 2h8a.5.5 0 0 1 0 1h-8a.5.5 0 0 1-.5-.5m13 0a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2a.5.5 0 0 1-.5-.5m-13 2A.5.5 0 0 1 .5 4h10a.5.5 0 0 1 0 1H.5a.5.5 0 0 1-.5-.5m13 0a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2a.5.5 0 0 1-.5-.5m-13 2A.5.5 0 0 1 .5 6h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5m13 0a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2a.5.5 0 0 1-.5-.5m-13 2A.5.5 0 0 1 .5 8h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5m13 0a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2a.5.5 0 0 1-.5-.5m-13 2a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5m13 0a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2a.5.5 0 0 1-.5-.5m-13 2a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5m13 0a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2a.5.5 0 0 1-.5-.5m-13 2a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H.5a.5.5 0 0 1-.5-.5m13 0a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2a.5.5 0 0 1-.5-.5"/>
                            </svg>
                            <span style="margin-left: 8px;">Programs</span></a>
                        </li>
                        <li class="sidebar-item1">
                            <a href="program_timeline.php" class="sidebar-link"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-bar-chart-steps me-2" viewBox="0 0 16 16">
                            <path d="M.5 0a.5.5 0 0 1 .5.5v15a.5.5 0 0 1-1 0V.5A.5.5 0 0 1 .5 0M2 1.5a.5.5 0 0 1 .5-.5h4a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-4a.5.5 0 0 1-.5-.5zm2 4a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-7a.5.5 0 0 1-.5-.5zm2 4a.5.5 0 0 1 .5-.5h6a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-6a.5.5 0 0 1-.5-.5zm2 4a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-7a.5.5 0 0 1-.5-.5z"/>
                            </svg>
                            <span style="margin-left: 8px;">Timeline</span></a>
                        </li>
                        <li class="sidebar-item1">
                            <a href="reports_member.php" class="sidebar-link"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-people-fill me-2" viewBox="0 0 16 16">
                        <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6m-5.784 6A2.24 2.24 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.3 6.3 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1zM4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5"/>
                        </svg>
                        <span style="margin-left: 8px;">Members</span></a>
                        </li>
                    </ul>
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

        <!-- Main Content -->
        <div class="main">
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
            <div class="container text-center mt-4">
                <h1 class="mt-5 mb-5">UDAS ASSESSMENTS</h1>
                <div class="scrollable-container-holder">
                    <div class="scrollable-container">
                        <?php
                        if (count($approvedSchedules) > 0) {
                            $counter = 1; // Counter for numbering assessments
                            foreach ($approvedSchedules as $schedule) {
                                $scheduleDate = date("F j, Y", strtotime($schedule['schedule_date']));
                                $scheduleTime = date("g:i A", strtotime($schedule['schedule_time']));
                                echo "<div class='assessment-box'>";
                                echo "<h2>#" . $counter . "</h2>";
                                echo "<div class='assessment-details'>";
                                echo "<div class='assessment-holder-1'><div class='assessment-college'><p>COLLEGE: <br><div class='assessment-values'>" . $schedule['college_name'] . "</div>PROGRAM:<br> <div class='assessment-values'>" . $schedule['program_name'] . "</div></div> <div class='assessment-level-applied'><p> LEVEL APPLIED: <br><h3>";

                                            // Display level applied with abbreviations
                                            switch ($schedule['level_applied']) {
                                                case "Not Accreditable":
                                                    echo "NA";
                                                    break;
                                                case "Candidate":
                                                    echo "CAN";
                                                    break;
                                                default:
                                                    echo $schedule['level_applied'];
                                                    break;
                                            }

                                            echo "</h3></p>
            </div>
          </div>";
                                echo "<div class='assessment-holder-2'><div class='assessment-dateTime'><p>DATE:<br><div class='assessment-values'>" . $scheduleDate . "</div> </div><div class='assessment-dateTime'><p>TIME: <br><div class='assessment-values'>" . $scheduleTime . "</div></div></br></p>";

                                if (!empty($schedule['udas_assessment_file'])) {
                                    echo "<div class='assessment-udas'><p>DOWNLOAD FILE:<br><a href='" . $schedule['udas_assessment_file'] . "' download class='btn download-button' data-schedule='" . json_encode($schedule) . "'>UDAS ASSESSMENT</a></div> </div>";
                                } else {
                                    echo "<div class='assessment-udas'><p>UDAS Assessment:<br><button class='btn open-modal udas-button' data-schedule='" . json_encode($schedule) . "'>START</button></div> </div>";
                                }

                                echo "</div></div>";
                                $counter++; // Increment counter for next assessment
                            }
                        } else {
                            echo "<div class='no-schedule-prompt'><p>NO APPROVED SCHEDULES FOUND.</p></div>";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- The Modal -->
    <div id="udasModal" class="modal">
        <div class="modal-content">
            <h2>UDAS Assessment</h2>
            <form action="udas_assessment_process.php" method="post" enctype="multipart/form-data">
                <input type="hidden" id="schedule_id" name="schedule_id">
                <div class="assessment-group">
                    <label for="college">COLLEGE</label>
                    <input class="assessment-group-college" type="text" id="college" name="college" readonly>
                    <label for="program">PROGRAM</label>
                    <input class="assessment-group-program" type="text" id="program" name="program" readonly>
                </div>
                <div class="orientationname1">
                    <div class="titleContainer">
                        <label for="level_applied"><strong>LEVEL APPLIED</strong></label>
                    </div>
                    <div class="titleContainer">
                        <label for="date"><strong>DATE</strong></label>
                    </div>
                    <div class="titleContainer">
                        <label for="time"><strong>TIME</strong></label>
                    </div>
                </div>
                <div style="height: 10px;"></div>
                <div class="orientationname1">
                    <div class="nameContainer orientationContainer1">
                        <input class="level" type="text" id="level_applied" name="level_applied" readonly>
                    </div>
                    <div class="nameContainer orientationContainer">
                        <input class="level" type="text" id="date" name="date" readonly>
                    </div>
                    <div class="nameContainer orientationContainer">
                        <input class="time" type="text" id="time" name="time" readonly>
                    </div>
                </div>
                <div style="height: 20px;"></div>
                <div class="assessment-group">
                    <label for="area"><strong>AREA<span style="color: red;"> *<span></strong></label>
                    <textarea style="border: 1px solid #AFAFAF; border-radius: 10px; width: 100%; padding: 20px;" id="area" name="area" rows="10" placeholder="Add area" required></textarea>
                    <div style="height: 20px;"></div>
                    <label for="comments"><strong>COMMENTS<span style="color: red;"> *<span></strong></label>
                    <textarea style="border: 1px solid #AFAFAF; border-radius: 10px; width: 100%; padding: 20px;" id="comments" name="comments" rows="10" placeholder="Add comments" required></textarea>
                    <div style="height: 20px;"></div>
                    <label for="remarks"><strong>REMARKS<span style="color: red;"> *<span></strong></label>
                    <textarea style="border: 1px solid #AFAFAF; border-radius: 10px; width: 100%; padding: 20px;" id="remarks" name="remarks" rows="10" placeholder="Add remarks" required></textarea>
                </div>
                <div style="height: 20px;"></div>
                <div class="assessment-group">
                    <label for="current_datetime">CURRENT DATE AND TIME</label>
                    <input class="assessment-group-program" type="text" id="current_datetime" name="current_datetime" readonly>
                </div>
                <div class="orientationname1">
                    <div class="titleContainer">
                        <label for="qad_officer"><strong>QAD OFFICER</strong></label>
                    </div>
                    <div class="titleContainer">
                        <label for="qad_officer_signature"><strong>QAD Officer E-SIGN<span style="color: red;"> *<span></strong></label>
                    </div>
                </div>
                <div class="orientationname1 upload">
                    <div class="nameContainer orientationContainer">
                        <input class="area_evaluated" type="text" id="qad_officer" name="qad_officer" value="<?php echo $full_name; ?>" readonly>
                    </div>
                    <div class="nameContainer orientationContainer uploadContainer">
                        <span class="upload-text">UPLOAD</span>
                        <img id="upload-icon-officer" src="images/download-icon1.png" alt="Upload Icon" class="upload-icon">
                        <input class="uploadInput" type="file" id="qad_officer_signature" name="qad_officer_signature" accept="image/png" required>
                    </div>
                </div>
                <div style="height: 20px;"></div>
                <div class="assessment-group">
                    <label for="qad_director">QAD DIRECTOR<span style="color: red;"> *<span></label>
                    <input class="assessment-group-program" type="text" id="qad_director" name="qad_director" required>
                </div>
                <div class="button-container">
                    <button class="cancel-button1" type="button" onclick="closePopup()">CLOSE</button>
                    <button class="submit-button1" type="submit">SUBMIT</button>
                </div>
            </form>
        </div>
    </div>

    <div id="customLoadingOverlay" class="custom-loading-overlay custom-spinner-hidden">
        <div class="custom-spinner"></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
    <script>
        document.querySelector('#udasModal form').addEventListener('submit', function() {
            document.getElementById('customLoadingOverlay').classList.remove('custom-spinner-hidden');
        });

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

        // Get the modal
        var modal = document.getElementById("udasModal");

        // Get the button that opens the modal
        var btns = document.getElementsByClassName("open-modal");

        // Get the <span> element that closes the modal
        var span = document.getElementsByClassName("close")[0];

        // When the user clicks the button, open the modal
        for (let btn of btns) {
            btn.onclick = function() {
                var schedule = JSON.parse(this.getAttribute('data-schedule'));
                document.getElementById('schedule_id').value = schedule.id;
                document.getElementById('college').value = schedule.college_name;
                document.getElementById('program').value = schedule.program_name;
                document.getElementById('level_applied').value = schedule.level_applied;
                document.getElementById('date').value = new Date(schedule.schedule_date).toLocaleDateString('en-US', {
                    month: 'long',
                    day: 'numeric',
                    year: 'numeric'
                });

                // Fix the time parsing issue
                var timeParts = schedule.schedule_time.split(':');
                var hours = parseInt(timeParts[0]);
                var minutes = parseInt(timeParts[1]);
                var ampm = hours >= 12 ? 'PM' : 'AM';
                hours = hours % 12;
                hours = hours ? hours : 12; // the hour '0' should be '12'
                var formattedTime = hours + ':' + (minutes < 10 ? '0' + minutes : minutes) + ' ' + ampm;

                document.getElementById('time').value = formattedTime;

                // Set current date and time
                var now = new Date();
                var formattedNow = now.toLocaleString('en-US', {
                    month: 'long',
                    day: 'numeric',
                    year: 'numeric',
                    hour: 'numeric',
                    minute: 'numeric',
                    hour12: true
                });
                document.getElementById('current_datetime').value = formattedNow;

                modal.style.display = "block";
            }
        }

        // When the user clicks anywhere outside of the modal, close it
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }

        function closePopup() {
            document.getElementById('udasModal').style.display = 'none';
        }

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

        handleFileChange(document.getElementById('qad_officer_signature'), document.getElementById('upload-icon-officer'));
    </script>
</body>

</html>