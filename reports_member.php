<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "qadDB";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function getSchedules($conn, $year) {
    $query = "SELECT schedule_date, COUNT(team.internal_users_id) AS user_count
              FROM schedule 
              INNER JOIN team ON schedule.id = team.schedule_id
              WHERE YEAR(schedule_date) = ? 
                AND schedule_status IN ('approved', 'finished')
              GROUP BY schedule_date";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $year);
    $stmt->execute();
    $result = $stmt->get_result();

    $schedules = [];
    while ($row = $result->fetch_assoc()) {
        $schedules[] = $row;
    }

    return $schedules;
}

function getMembers($conn, $campus, $college, $search, $offset) {
    $query = "SELECT internal_users.first_name, internal_users.last_name, COUNT(team.id) AS schedule_count
              FROM internal_users 
              LEFT JOIN team ON internal_users.user_id = team.internal_users_id
              LEFT JOIN schedule ON team.schedule_id = schedule.id
              LEFT JOIN program ON schedule.program_id = program.id
              WHERE (CONCAT(internal_users.first_name, ' ', internal_users.last_name) LIKE ?)
                AND (internal_users.college_code LIKE ? OR ? = '')
              GROUP BY internal_users.user_id
              LIMIT 10 OFFSET ?";
    $stmt = $conn->prepare($query);
    $search_param = "%$search%";
    $stmt->bind_param('sssi', $search_param, $college, $college, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    $members = [];
    while ($row = $result->fetch_assoc()) {
        $members[] = $row;
    }

    return $members;
}

function getDropdownOptions($conn, $table, $valueField, $textField) {
    $query = "SELECT DISTINCT $valueField, $textField FROM $table";
    $result = $conn->query($query);

    $options = [];
    while ($row = $result->fetch_assoc()) {
        $options[] = $row;
    }

    return $options;
}

function getCollegesByCampus($conn, $campus) {
    $query = "SELECT code, college_name FROM college WHERE college_campus = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $campus);
    $stmt->execute();
    $result = $stmt->get_result();

    $colleges = [];
    while ($row = $result->fetch_assoc()) {
        $colleges[] = $row;
    }

    return $colleges;
}

function getUserDistributionByCampus($conn) {
    $query = "SELECT college.college_campus, COUNT(internal_users.user_id) AS user_count
              FROM internal_users 
              INNER JOIN college ON internal_users.college_code = college.code
              GROUP BY college.college_campus";
    $result = $conn->query($query);

    $distribution = [];
    while ($row = $result->fetch_assoc()) {
        $distribution[] = $row;
    }

    return $distribution;
}

function getUserStatusCount($conn) {
    $query = "SELECT status, COUNT(user_id) AS count FROM internal_users GROUP BY status";
    $result = $conn->query($query);

    $statusCount = [];
    while ($row = $result->fetch_assoc()) {
        $statusCount[] = $row;
    }

    return $statusCount;
}

function getRecentActivities($conn) {
    $query = "SELECT internal_users.first_name, internal_users.last_name, schedule.schedule_date
              FROM team
              INNER JOIN internal_users ON team.internal_users_id = internal_users.user_id
              INNER JOIN schedule ON team.schedule_id = schedule.id
              ORDER BY schedule.schedule_date DESC
              LIMIT 10";
    $result = $conn->query($query);

    $activities = [];
    while ($row = $result->fetch_assoc()) {
        $activities[] = $row;
    }

    return $activities;
}

$year = date('Y');
$schedules = getSchedules($conn, $year);
$userDistribution = getUserDistributionByCampus($conn);
$userStatusCount = getUserStatusCount($conn);
$recentActivities = getRecentActivities($conn);

$campus = ''; 
$college = ''; 
$search = ''; 
$offset = 0; 

$members = getMembers($conn, $campus, $college, $search, $offset);

$campuses = getDropdownOptions($conn, 'college', 'college_campus', 'college_campus');
$colleges = getDropdownOptions($conn, 'college', 'code', 'college_name');

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Website Analytics Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.3/css/jquery.dataTables.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
    <style>
        .stats {
            display: flex;
            justify-content: space-around;
            margin-bottom: 20px;
        }
        .stat {
            background-color: #007bff;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px;
            width: 30%;
        }
        .charts-container {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .charts-left {
            width: 65%;
        }
        .charts-right {
            width: 30%;
        }
        .chart-header {
            text-align: center;
            margin-bottom: 10px;
        }
        .filters {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .filters select {
            padding: 5px;
        }
        canvas {
            display: block;
            margin: 0 auto 20px auto;
            max-width: 100%;
        }
        table.dataTable {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }
        table.dataTable th, table.dataTable td {
            padding: 10px;
            text-align: left;
        }
        table.dataTable thead th {
            background-color: #007bff;
            color: #fff;
        }
        #recentActivitiesTable {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }
        #recentActivitiesTable th, #recentActivitiesTable td {
            padding: 10px;
            text-align: left;
        }
        #recentActivitiesTable thead th {
            background-color: #007bff;
            color: #fff;
        }
    </style>
</head>
<body>
    <aside id="sidebar">
            <div class="d-flex">
                <button class="toggle-btn" type="button">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-grid" viewBox="0 0 16 16">
                        <path d="M1 2.5A1.5 1.5 0 0 1 2.5 1h3A1.5 1.5 0 0 1 7 2.5v3A1.5 1.5 0 0 1 5.5 7h-3A1.5 1.5 0 0 1 1 5.5zM2.5 2a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5zm6.5.5A1.5 1.5 0 0 1 10.5 1h3A1.5 1.5 0 0 1 15 2.5v3A1.5 1.5 0 0 1 13.5 7h-3A1.5 1.5 0 0 1 9 5.5zm1.5-.5a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5zM1 10.5A1.5 1.5 0 0 1 2.5 9h3A1.5 1.5 0 0 1 7 10.5v3A1.5 1.5 0 0 1 5.5 15h-3A1.5 1.5 0 0 1 1 13.5zm1.5-.5a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5zm6.5.5A1.5 1.5 0 0 1 10.5 9h3a1.5 1.5 0 0 1 1.5 1.5v3a1.5 1.5 0 0 1-1.5 1.5h-3A1.5 1.5 0 0 1 9 13.5zm1.5-.5a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5z" />
                    </svg>
                </button>
                <div class="sidebar-logo">
                    <a href="program_timeline.php">QAD</a>
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
                            <path fill-rule="evenodd" d="M5 11.5a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5M3.854 2.146a.5.5 0 0 1 0 .708l-1.5 1.5a.5.5 0 0 1-.708 0l-.5-.5a.5.5 0 1 1 .708-.708L2 3.293l1.146-1.147a.5.5 0 0 1 .708 0m0 4a.5.5 0 0 1 0 .708l-1.5 1.5a.5.5 0 0 1-.708 0l-.5-.5a.5.5 0 1 1 .708-.708L2 7.293l1.146-1.147a.5.5 0 0 1 .708 0m0 4a.5.5 0 0 1 0 .708l-1.5 1.5a.5.5 0 0 1-.708 0l-.5-.5a.5.5 0 1 1 .708-.708l.146.147 1.146-1.147a.5.5 0 0 1 .708 0" />
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
                            <path fill-rule="evenodd" d="M1 11.5a.5.5 0 0 0 .5.5h11.793l-3.147 3.146a.5.5 0 0 0 .708.708l4-4a.5.5 0 0 0 0-.708l-4-4a.5.5 0 0 0-.708.708L13.293 11H1.5a.5.5 0 0 0-.5.5m14-7a.5.5 0 0 1-.5.5H2.707l3.147 3.146a.5.5 0 1 1-.708.708l-4-4a.5.5 0 0 1 0-.708l4-4a.5.5 0 1 1 .708.708L2.707 4H14.5a.5.5 0 0 1 .5.5" />
                        </svg>
                        <span style="margin-left: 8px;">College Transfer</span>
                    </a>
                </li>
                
                <li class="sidebar-item">
                <a href="#" class="sidebar-link-active collapsed has-dropdown" data-bs-toggle="collapse"
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
                            <a href="reports_member.php" class="sidebar-link-active"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-people-fill me-2" viewBox="0 0 16 16">
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
            <div class="container">
                <div class="filters">
                    <div>
                        <label for="year">Year:</label>
                        <select id="year">
                            <?php for ($i = date('Y'); $i >= 2000; $i--): ?>
                                <option value="<?= $i ?>" <?= $i == $year ? 'selected' : '' ?>><?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                <div class="charts-container">
                    <div class="charts-right">
                        <div class="chart-header"><h3>Users Per Campus</h3></div>
                        <canvas id="userDistributionChart" height="200"></canvas>
                        <div class="chart-header"><h3>User Status</h3></div>
                        <canvas id="userStatusChart" height="200"></canvas>
                    </div>
                </div>
                    <div>
                        <label for="campus">Campus:</label>
                        <select id="campus">
                            <option value="">All Campuses</option>
                            <?php foreach ($campuses as $campus): ?>
                                <option value="<?= $campus['college_campus'] ?>"><?= $campus['college_campus'] ?></option>
                            <?php endforeach; ?>
                        </select>
                        <label for="college">College:</label>
                        <select id="college">
                            <option value="">All Colleges</option>
                            <?php foreach ($colleges as $college): ?>
                                <option value="<?= $college['code'] ?>"><?= $college['college_name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <table id="memberTable" class="display">
                    <thead>
                        <tr>
                            <th>Full Name</th>
                            <th>Schedule Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($members as $member): ?>
                            <tr>
                                <td><?= $member['first_name'] . ' ' . $member['last_name'] ?></td>
                                <td><?= $member['schedule_count'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <h2>Recent Activities</h2>
                <table id="recentActivitiesTable">
                    <thead>
                        <tr>
                            <th>Full Name</th>
                            <th>Schedule Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentActivities as $activity): ?>
                            <tr>
                                <td><?= $activity['first_name'] . ' ' . $activity['last_name'] ?></td>
                                <td><?= $activity['schedule_date'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>

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
        </script>

        <script>
            $(document).ready(function() {

                const userDistributionCtx = document.getElementById('userDistributionChart').getContext('2d');
                const userDistribution = <?= json_encode($userDistribution) ?>;
                const userDistributionLabels = userDistribution.map(d => d.college_campus);
                const userDistributionData = userDistribution.map(d => d.user_count);

                const userDistributionChart = new Chart(userDistributionCtx, {
                    type: 'pie',
                    data: {
                        labels: userDistributionLabels,
                        datasets: [{
                            label: 'Users Per Campus',
                            data: userDistributionData,
                            backgroundColor: [
                                'rgba(75, 192, 192, 0.2)',
                                'rgba(255, 99, 132, 0.2)',
                                'rgba(54, 162, 235, 0.2)',
                                'rgba(255, 206, 86, 0.2)',
                                'rgba(153, 102, 255, 0.2)',
                                'rgba(255, 159, 64, 0.2)'
                            ],
                            borderColor: [
                                'rgba(75, 192, 192, 1)',
                                'rgba(255, 99, 132, 1)',
                                'rgba(54, 162, 235, 1)',
                                'rgba(255, 206, 86, 1)',
                                'rgba(153, 102, 255, 1)',
                                'rgba(255, 159, 64, 1)'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'top',
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(tooltipItem) {
                                        return `${tooltipItem.label}: ${tooltipItem.raw} users`;
                                    }
                                }
                            }
                        }
                    }
                });

                const userStatusCtx = document.getElementById('userStatusChart').getContext('2d');
                const userStatusCount = <?= json_encode($userStatusCount) ?>;
                const userStatusLabels = userStatusCount.map(d => d.status);
                const userStatusData = userStatusCount.map(d => d.count);

                const userStatusChart = new Chart(userStatusCtx, {
                    type: 'bar',
                    data: {
                        labels: userStatusLabels,
                        datasets: [{
                            label: 'User Status',
                            data: userStatusData,
                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                            borderColor: 'rgba(75, 192, 192, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(tooltipItem) {
                                        return `${tooltipItem.label}: ${tooltipItem.raw} users`;
                                    }
                                }
                            }
                        }
                    }
                });

                const table = $('#memberTable').DataTable({
                    serverSide: true,
                    ajax: function(data, callback, settings) {
                        const campus = $('#campus').val();
                        const college = $('#college').val();
                        const search = data.search.value;  // Use DataTable's search input
                        const offset = data.start;

                        $.post('analytics_get_members.php', { 
                            action: 'getMembers', 
                            campus: campus, 
                            college: college, 
                            search: search, 
                            offset: offset 
                        }, function(response) {
                            const members = JSON.parse(response);
                            callback({
                                draw: data.draw,
                                recordsTotal: members.recordsTotal,
                                recordsFiltered: members.recordsFiltered,
                                data: members.data.map(member => [member.first_name + ' ' + member.last_name, member.schedule_count])
                            });
                        });
                    },
                    pageLength: 10
                });

                $('#campus').change(function() {
                    const campus = $(this).val();
                    $.post('analytics_get_members.php', { action: 'getColleges', campus: campus }, function(response) {
                        const colleges = JSON.parse(response);
                        $('#college').empty().append('<option value="">All Colleges</option>');
                        colleges.forEach(college => {
                            $('#college').append(`<option value="${college.code}">${college.college_name}</option>`);
                        });
                        table.draw();
                    });
                });

                $('#college').change(function() {
                    table.draw();
                });

                $('#year').change(function() {
                    const year = $(this).val();
                    $.post('analytics_get_members.php', { action: 'getSchedules', year: year }, function(data) {
                        const schedules = JSON.parse(data);
                        const labels = schedules.map(s => s.schedule_date);
                        const dataPoints = schedules.map(s => s.user_count);

                        scheduleChart.data.labels = labels;
                        scheduleChart.data.datasets[0].data = dataPoints;
                        scheduleChart.update();
                    });
                });
            });
        </script>
    </body>
</html>