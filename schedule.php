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
    if (basename($_SERVER['PHP_SELF']) !== 'schedule.php') {
        header("Location: schedule.php");
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

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/sidebar_updated.css">
    <link href="css/pagestyle.css" rel="stylesheet">
    <link rel="stylesheet" href="css/navbar.css">
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

        .row {
            max-height: 600px;
            /* Adjust this height as needed */
            overflow-y: auto;
            margin-bottom: 20px;
            /* Optional: Adds some spacing below the row */
        }
    </style>
</head>

<body>
    <div class="wrapper">

        <!-- Main Content -->
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
                        <a href="#" class="sidebar-link-active">
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
                        </a>
                        <div class="sidebar-dropdown">
                            <a href="<?php echo $is_admin ? 'assessment.php' : 'internal_assessment.php'; ?>" class="sidebar-link">
                                <span style="margin-left: 8px;">View Assessments</span>
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
                        <a href="#" class="sidebar-link">
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
            <div class="container text-center mt-4">
                <h1 class="mb-5 mt-5">SCHEDULE</h1>
                <div class="custom-btn-group">
                    <div>
                        <button id="collegesBtn" class="btn-toggle btn-colleges"
                            onclick="showTable('collegeTable', 'collegesBtn')">COLLEGES</button>
                        <button id="sucBtn" class="btn-toggle btn-company"
                            onclick="showTable('sucTable', 'sucBtn')">COMPANY</button>
                    </div>
                    <button class="btn-add-schedule" onclick="location.href='add_schedule.php'" \>ADD SCHEDULE &nbsp;&nbsp;<svg xmlns="http://www.w3.org/2000/svg"
                            width="16" height="16" fill="currentColor" class="bi bi-calendar-plus" viewBox="0 0 16 16">
                            <path
                                d="M8 7a.5.5 0 0 1 .5.5V9H10a.5.5 0 0 1 0 1H8.5v1.5a.5.5 0 0 1-1 0V10H6a.5.5 0 0 1 0-1h1.5V7.5A.5.5 0 0 1 8 7" />
                            <path
                                d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5M1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4z" />
                        </svg></button>
                </div>
                <div class="row">
                    <div class="table-responsive col-12">
                        <table id="collegeTable" class="custom-table">
                            <thead>
                                <tr>
                                    <th>COLLEGE NAME</th>
                                    <th>TOTAL SCHEDULES</th>
                                    <th>ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- PHP code for generating table rows goes here -->
                                <?php
                                include 'connection.php';

                                $sql = "SELECT c.college_name, c.code as college_code, 
                                        COUNT(CASE WHEN s.schedule_status NOT IN ('passed', 'failed', 'finished') THEN s.id END) AS total_schedules, 
                                        MAX(s.schedule_date) AS recent_schedule_date 
                                        FROM college c 
                                        LEFT JOIN schedule s ON c.code = s.college_code 
                                        GROUP BY c.college_name, c.code 
                                        ORDER BY recent_schedule_date DESC, c.college_name";


                                $result = $conn->query($sql);

                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        echo "<tr>";
                                        echo "<td>" . htmlspecialchars($row["college_name"]) . "</td>";
                                        echo "<td>" . htmlspecialchars($row["total_schedules"]) . "</td>";
                                        echo "<td><button class='btn-view' onclick=\"location.href='schedule_college.php?college=" . urlencode($row["college_name"]) . "&college_code=" . htmlspecialchars($row["college_code"]) . "'\">VIEW</button></td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='2'>No colleges found</td></tr>";
                                }

                                $conn->close();
                                ?>
                            </tbody>
                        </table>
                        <table id="sucTable" class="custom-table hidden">
                            <thead>
                                <tr>
                                    <th>SUC/COMPANY CODE</th>
                                    <th>SUC/COMPANY NAME</th>
                                    <th>SUC/COMPANY ADDRESS</th>
                                    <th>SUC/COMPANY EMAIL</th>
                                    <th>PROGRAMS</th>
                                    <th>ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Repeat the <tr> block above as needed -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous">
    </script>
    <script>
        function showTable(tableId, buttonId) {
            const tables = document.querySelectorAll('.custom-table');
            tables.forEach(table => {
                if (table.id === tableId) {
                    table.classList.remove('hidden');
                } else {
                    table.classList.add('hidden');
                }
            });

            const buttons = document.querySelectorAll('.btn-toggle');
            buttons.forEach(button => {
                button.classList.remove('btn-colleges');
                button.classList.add('btn-company');
            });

            const activeButton = document.getElementById(buttonId);
            activeButton.classList.remove('btn-company');
            activeButton.classList.add('btn-colleges');
        }
    </script>
</body>

</html>