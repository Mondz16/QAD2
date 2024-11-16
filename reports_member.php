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
    if (basename($_SERVER['PHP_SELF']) !== 'reports_member.php') {
        header("Location: reports_member.php");
        exit();
    }
} else {
    $user_type_code = substr($user_id, 3, 2);

    if ($user_type_code === '11') {
        // Internal user
        if (basename($_SERVER['PHP_SELF']) !== 'reports_member.php') {
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

function getSchedules($conn, $year)
{
    $query = "SELECT schedule_date, COUNT(team.internal_users_id) AS user_count
              FROM schedule 
              INNER JOIN team ON schedule.id = team.schedule_id
              WHERE YEAR(schedule_date) = ? 
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

function getMembers($conn, $campus, $college, $search, $offset, $year)
{
    $query = "SELECT internal_users.first_name, internal_users.last_name, 
                    COALESCE(COUNT(CASE WHEN schedule.schedule_status IN ('passed', 'failed', 'finished') THEN team.id END), 0) AS schedule_count
                    FROM internal_users 
                    LEFT JOIN team ON internal_users.user_id = team.internal_users_id
                    LEFT JOIN schedule ON team.schedule_id = schedule.id AND YEAR(schedule.schedule_date) = ?
                    LEFT JOIN program ON schedule.program_id = program.id
                    WHERE (CONCAT(internal_users.first_name, ' ', internal_users.last_name) LIKE ?)
                    AND (internal_users.college_code LIKE ? OR ? = '')
                    GROUP BY internal_users.user_id
                    ORDER BY schedule_count DESC
                    LIMIT 10 OFFSET ?";

    $stmt = $conn->prepare($query);
    $search_param = "%$search%";
    $stmt->bind_param('isssi', $year, $search_param, $college, $college, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    $members = [];
    while ($row = $result->fetch_assoc()) {
        $members[] = $row;
    }

    return $members;
}



function getDropdownOptions($conn, $table, $valueField, $textField)
{
    $query = "SELECT DISTINCT $valueField, $textField FROM $table";
    $result = $conn->query($query);

    $options = [];
    while ($row = $result->fetch_assoc()) {
        $options[] = $row;
    }

    return $options;
}

function getCollegesByCampus($conn, $campus)
{
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

function getUserDistributionByCampus($conn)
{
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

function getUserStatusCount($conn)
{
    $query = "SELECT status, COUNT(user_id) AS count FROM internal_users GROUP BY status";
    $result = $conn->query($query);

    $statusCount = [];
    while ($row = $result->fetch_assoc()) {
        $statusCount[] = $row;
    }

    return $statusCount;
}

function getRecentActivities($conn)
{
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

$members = getMembers($conn, $campus, $college, $search, $offset, $year);

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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/sidebar_updated.css">
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
            margin-bottom: 30px;
            margin-top: 40px;
        }

        .filters select {
            padding: 5px;
            margin-right: 15px;
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
            margin-top: 10px;
        }

        table.dataTable th,
        table.dataTable td {
            padding: 10px;
            text-align: left;
        }

        table.dataTable thead th {
            background-color:  #B73033;
            color: #fff;
        }

        #recentActivitiesTable {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }

        #recentActivitiesTable th,
        #recentActivitiesTable td {
            padding: 10px;
            text-align: left;
        }

        #recentActivitiesTable thead th {
            background-color:  #B73033;
            color: #fff;
        }

        h3 {
            font-size: 1.5rem;
        }
    </style>
</head>

<body>
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
                    <a href="#" class="sidebar-link-active">
                        <span style="margin-left: 8px;">Reports</span>
                    </a>
                    <div class="sidebar-dropdown">
                        <a href="<?php echo $is_admin === false ? 'internal_assigned_schedule.php' : '#'; ?>" class="<?php echo $is_admin === false ? 'sidebar-link' : 'sidebar-link-disabled'; ?>">
                            <span style="margin-left: 8px;">View Assigned Schedule</span></a>
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
        <div class="container">
            <div class="filters">
                <div>
                    <label for="year">Year:</label>
                    <select id="year">
                        <?php foreach ($schedules as $schedule): ?>
                            <option value="<?= date('Y', strtotime($schedule['schedule_date'])) ?>"><?= date('Y', strtotime($schedule['schedule_date'])) ?></option>
                        <?php echo strtotime($schedule['schedule_date']); endforeach; ?>
                    </select>
                    <label for="campus">Campus:</label>
                    <select id="campus">
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
            </div>
            <div class="charts-container">
                <div class="charts-left">
                    <table id="memberTable" class="display">
                        <thead>
                            <tr>
                                <th>Full Name</th>
                                <th>Schedule Count</th>
                                <th>Accepted Count</th>
                                <th>Declined Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($members as $member): ?>
                                <tr>
                                    <td><?= $member['first_name'] . ' ' . $member['last_name'] ?></td>
                                    <td><?= $member['schedule_count'] ?></td>
                                    <td><?= $member['accepted_count'] ?></td>
                                    <td><?= $member['declined_count'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="charts-right">
                    <div class="chart-header">
                        <h3>Users Per Campus</h3>
                    </div>
                    <canvas id="userDistributionChart" height="200"></canvas>
                    <div class="chart-header">
                        <h3>User Status</h3>
                    </div>
                    <canvas id="userStatusChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>

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
                            'rgba(255, 153, 152, .2)',
                            'rgba(255, 99, 132, 0.2)',
                            'rgba(54, 162, 235, 0.2)',
                            'rgba(255, 206, 86, 0.2)',
                            'rgba(153, 102, 255, 0.2)',
                            'rgba(255, 159, 64, 0.2)'
                        ],
                        borderColor: [
                            'rgba(255, 153, 152, 1)',
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
                        backgroundColor: 'rgba(255, 153, 152, .2)',
                        borderColor: 'rgba(255, 153, 152, 1)',
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
                    const year = $('#year').val();
                    const search = data.search.value;
                    const offset = data.start;

                    $.post('analytics_get_members.php', {
                        action: 'getMembers',
                        campus: campus,
                        college: college,
                        search: search,
                        offset: offset,
                        year: year
                    }, function(response) {
                        const members = JSON.parse(response);
                        callback({
                            draw: data.draw,
                            recordsTotal: members.recordsTotal,
                            recordsFiltered: members.recordsFiltered,
                            data: members.data.map(member => [
                                member.first_name + ' ' + member.last_name,
                                member.schedule_count,
                                member.accepted_count,
                                member.declined_count
                            ])
                        });
                    });
                },
                columns: [
                    { title: "Full Name" },
                    { title: "Schedules" },
                    { title: "Accepted" },
                    { title: "Declined" }
                ],
                pageLength: 10
            });


            $('#campus').change(function() {
                const campus = $(this).val();
                $.post('analytics_get_members.php', {
                    action: 'getColleges',
                    campus: campus
                }, function(response) {
                    const colleges = JSON.parse(response);
                    $('#college').empty().append('<option value="">All Colleges</option>');
                    colleges.forEach(college => {
                        $('#college').append(`<option value="${college.code}">${college.college_name}</option>`);
                    });
                    table.draw();
                });
            });

            $('#year, #campus, #college').change(function() {
                table.draw();
            });

            $('#year').change(function() {
                const year = $(this).val();
                $.post('analytics_get_members.php', {
                    action: 'getSchedules',
                    year: year
                }, function(data) {
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