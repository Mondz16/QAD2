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
    if (basename($_SERVER['PHP_SELF']) !== 'reports_dashboard.php') {
        header("Location: reports_dashboard.php");
        exit();
    }
} else {
    $user_type_code = substr($user_id, 3, 2);

    if ($user_type_code === '11') {
        // Internal user
        if (basename($_SERVER['PHP_SELF']) !== 'reports_dashboard.php') {
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

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch total counts
$sql_colleges = "SELECT COUNT(*) as total_colleges FROM college";
$result_colleges = $conn->query($sql_colleges);
$total_colleges = $result_colleges->fetch_assoc()['total_colleges'];

$sql_programs = "SELECT COUNT(*) as total_programs FROM program";
$result_programs = $conn->query($sql_programs);
$total_programs = $result_programs->fetch_assoc()['total_programs'];

$sql_users = "SELECT (SELECT COUNT(*) FROM internal_users) + (SELECT COUNT(*) FROM external_users) as total_users";
$result_users = $conn->query($sql_users);
$total_users = $result_users->fetch_assoc()['total_users'];

// Fetch notifications for the logged-in user
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

$conn->close();

?>

<!DOCTYPE html>
<html>

<head>
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="css/sidebar_updated.css">
    <link rel="stylesheet" type="text/css" href="reports_dashboard_styles.css">
    <link href="css/pagestyle.css" rel="stylesheet">
    <link rel="stylesheet" href="index.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/1.5.3/jspdf.debug.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
</head>
<style>
    .notification-counter {
        color: #E6A33E;
        /* Text color */
    }
</style>

<body>
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
                    <a href="#" class="sidebar-link">
                        <span style="margin-left: 8px;">Schedule</span>
                        <?php if ($totalPendingSchedules > 0 && $is_admin): ?>
                            <span class="notification-counter">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-dot" viewBox="0 0 16 16">
                                    <path d="M8 9.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3" />
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
                            <?php if ($totalPendingSchedules > 0 && $is_admin): ?>
                                <span class="notification-counter"><?= $totalPendingSchedules; ?></span>
                            <?php endif; ?>
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
                        <?php if ($assessmentCount > 0 && $is_admin): ?>
                            <span class="notification-counter">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-dot" viewBox="0 0 16 16">
                                    <path d="M8 9.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3" />
                                </svg>
                            </span>
                        <?php endif; ?>
                        <?php if ($assessment_count > 0): ?>
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
                            <?php if ($assessmentCount > 0 && $is_admin): ?>
                                <span class="notification-counter"><?= $assessmentCount; ?></span>
                            <?php endif; ?>
                            <?php if ($assessment_count > 0): ?>
                                <span class="notification-counter"><?php echo $assessment_count; ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="<?php echo $is_admin ? 'udas_assessment.php' : '#'; ?>" class="<?php echo $is_admin ? 'sidebar-link' : 'sidebar-link-disabled'; ?>">
                            <span style="margin-left: 8px;">UDAS Assessments</span>
                        </a>
                        <a href="<?php echo $is_admin ? 'assessment_history.php' : '#'; ?>" class="<?php echo $is_admin ? 'sidebar-link' : 'sidebar-link-disabled'; ?>">
                            <span style="margin-left: 8px;">Assessment History</span>
                        </a>
                    </div>
                </li>
                <li class="sidebar-item has-dropdown">
                    <a href="#" class="sidebar-link">
                        <span style="margin-left: 8px;">Administrative</span>
                        <?php if (($transferRequestCount > 0) && $is_admin || ($totalPendingUsers > 0 && $is_admin)): ?>
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
                            <?php if ($totalPendingUsers > 0 && $is_admin): ?>
                                <span class="notification-counter"><?= $totalPendingUsers; ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="<?php echo $is_admin ? 'college_transfer.php' : '#'; ?>" class="<?php echo $is_admin ? 'sidebar-link' : 'sidebar-link-disabled'; ?>">
                            <span style="margin-left: 8px;">College Transfer</span>
                            <?php if ($transferRequestCount > 0 && $is_admin): ?>
                                <span class="notification-counter"><?= $transferRequestCount; ?></span>
                            <?php endif; ?>
                        </a>
                    </div>
                </li>
                <li class="sidebar-item has-dropdown">
                    <a href="#" class="sidebar-link-active">
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
                        <?php if ($notification_count > 0): ?>
                            <span class="notification-counter">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-dot" viewBox="0 0 16 16">
                                    <path d="M8 9.5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3" />
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
        <div class="dashboard-container">
            <div class="card-container">
                <div class="card">
                    <h2><?php echo $total_colleges; ?></h2>
                    <p>Total Colleges</p>
                </div>
                <div class="card">
                    <h2><?php echo $total_programs; ?></h2>
                    <p>Total Programs</p>
                </div>
                <div class="card">
                    <h2><?php echo $total_users; ?></h2>
                    <p>Total Users</p>
                </div>
            </div>

            <div class="button-container">
                <div class="filter">
                    <!-- <select id="programLevel" onchange="updateCharts()">
                        <option value="All">All Level</option>
                        <option value="Not Accreditable">Not Accreditable</option>
                        <option value="PSV">PSV</option>
                        <option value="Candidate">Candidate</option>
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                    </select>
                    <select id="year" onchange="updateCharts()">
                        <option value="All">All Years</option>
                    </select> -->
                </div>
                <!-- <div>
                    <button type="button" id="exportPDF">EXPORT <img style="margin-left: 5px;" src="images/export.png"></button>
                </div> -->
            </div>

            <div class="charts-container">
                <div class="chart-wrapper large">
                    <canvas id="collegeChart" height="550"></canvas>
                </div>
                <div class="chart-wrapper-right">
                    <div class="chart-wrapper-pie-chart">
                        <canvas id="programPieChart"></canvas>
                    </div>
                    <div class="recent-programs-container">
                        <h3>Recent Program Levels</h3>
                        <table id="recentProgramsTable">
                            <thead>
                                <tr>
                                    <th>Program Name</th>
                                    <th>Date Received</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>

    <script>
        async function fetchCollegeData(programLevel, year) {
            const response = await fetch(`fetch_college_data_report.php?programLevel=${programLevel}&year=${year}`);
            const data = await response.json();
            return data;
        }

        async function fetchPieChartData(programLevel, year) {
            const response = await fetch(`fetch_pie_chart_data.php?programLevel=${programLevel}&year=${year}`);
            const data = await response.json();
            return data;
        }

        async function fetchRecentPrograms() {
            const response = await fetch('fetch_recent_programs.php');
            const data = await response.json();
            return data;
        }

        // async function fetchYears() {
        //     const response = await fetch('fetch_years.php');
        //     const years = await response.json();
        //     const yearSelect = document.getElementById('year');
        //     years.forEach(year => {
        //         const option = document.createElement('option');
        //         option.value = year;
        //         option.textContent = year;
        //         yearSelect.appendChild(option);
        //     });
        // }

        function fetchProgramLevelHistoryData(campus, college, program) {
            fetch('fetch_program_level_history.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        campus: campus,
                        college: college,
                        program: program
                    })
                })
                .then(response => response.json())
                .then(data => {
                    // Display data in the chart
                    displayProgramLevelHistoryChart(data);
                })
                .catch(error => console.error('Error fetching data:', error));
        }

        // Function to display the chart
        function displayProgramLevelHistoryChart(data) {
            const ctx = document.getElementById('programLevelHistoryCanvas').getContext('2d');
            const chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [{
                        label: 'Program Level History',
                        data: data.values,
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1,
                        fill: false
                    }]
                },
                options: {
                    scales: {
                        x: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        async function updateCharts() {
            const programLevel = "All";
            const year = "All";

            await updateBarChart(programLevel, year);
            updateRecentPrograms();
        }

        async function updateBarChart(programLevel, year) {
            const data = await fetchCollegeData(programLevel, year);

            const campuses = data.map(item => item.college_campus);
            const uniqueCampuses = [...new Set(campuses)];

            const levels = ['Not Accreditable', 'PSV', 'Candidate', '1', '2', '3', '4'];
            const colors = ['#FF6262', '#818181', '#34C759', '#DEFF81', '#C8FFF8', '#AA8CFF', '#FEC269'];

            // Create datasets for each level
            const datasets = levels.map((level, index) => ({
                label: level === '1' || level === '2' || level === '3' || level === '4' ? `Level ${level}` : level,
                data: uniqueCampuses.map(campus => {
                    const campusData = data.find(item => item.college_campus === campus) || {};
                    return campusData[level] || 0;
                }),
                backgroundColor: colors[index],
            }));

            chart.data.labels = uniqueCampuses;
            chart.data.datasets = datasets;
            chart.update();
        }

        async function updatePieChart(programLevel, year) {
            const data = await fetchPieChartData(programLevel, year);

            const labels = data.map(item => item.college_campus);
            const programCounts = data.map(item => item.program_count);
            const collegeCounts = data.map(item => item.college_count);
            const totalPrograms = programCounts.reduce((sum, count) => sum + count, 0);

            pieChart.data.labels = labels;
            pieChart.data.datasets = [{
                data: programCounts,
                backgroundColor: [
                    '#FFD160',
                    '#9CC8E5',
                    '#FF6384',
                    '#FF6347',
                    '#8FBC8F',
                    '#4682B4',
                    '#7B68EE',
                    '#32CD32'
                ],
            }];

            pieChart.options.plugins.tooltip.callbacks = {
                label: function(context) {
                    const label = context.label || '';
                    const value = context.raw || 0;
                    const percentage = ((value / totalPrograms) * 100).toFixed(2);
                    const collegeCount = collegeCounts[context.dataIndex];
                    return ` ${collegeCount} colleges | ${value} programs`;
                }
            };

            pieChart.options.plugins.datalabels = {
                formatter: (value, context) => {
                    return `${value}%`;
                },
                color: '#fff',
                font: {
                    weight: 'bold'
                },
                anchor: 'end',
                align: 'start',
                offset: 5
            };

            pieChart.update();
        }


        $(document).ready(function() {
            const table = $('#programScheduleTable').DataTable({
                serverSide: true,
                ajax: function(data, callback, settings) {
                    const search = data.search.value;
                    const offset = data.start;

                    $.post('analytics_get_program_schedules.php', {
                        action: 'getProgramSchedule',
                        search: search,
                        offset: offset
                    }, function(response) {
                        const schedules = JSON.parse(response);
                        callback({
                            draw: data.draw,
                            recordsTotal: schedules.recordsTotal,
                            recordsFiltered: schedules.recordsFiltered,
                            data: schedules.data.map(schedule => [
                                schedule.program_name,
                                schedule.total_schedule_count,
                                schedule.approved_count,
                                schedule.canceled_count
                            ])
                        });
                    });
                },
                columns: [{
                        title: "Program Name"
                    },
                    {
                        title: "Total Schedules"
                    },
                    {
                        title: "Approved"
                    },
                    {
                        title: "Canceled"
                    }
                ],
                pageLength: 10
            });
        });



        function formatDate(dateString) {
            const options = {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            };
            const date = new Date(dateString);
            return date.toLocaleDateString(undefined, options);
        }

        async function updateRecentPrograms() {
            const data = await fetchRecentPrograms();
            const tableBody = document.getElementById('recentProgramsTable').querySelector('tbody');
            tableBody.innerHTML = '';

            data.forEach(item => {
                const row = document.createElement('tr');
                const programNameCell = document.createElement('td');
                programNameCell.textContent = item.program_name;
                const dateReceivedCell = document.createElement('td');
                dateReceivedCell.textContent = formatDate(item.date_received);
                row.appendChild(programNameCell);
                row.appendChild(dateReceivedCell);
                tableBody.appendChild(row);
            });
        }

        const ctxBar = document.getElementById('collegeChart').getContext('2d');
        const chart = new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: [],
                datasets: []
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.7)',
                        titleFont: {
                            size: 16,
                            weight: 'bold'
                        },
                        bodyFont: {
                            size: 14
                        },
                        cornerRadius: 5
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 14
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(200, 200, 200, 0.2)'
                        },
                        ticks: {
                            font: {
                                size: 14
                            }
                        }
                    }
                }
            }
        });

        const ctxPie = document.getElementById('programPieChart').getContext('2d');
        const pieChart = new Chart(ctxPie, {
            type: 'pie',
            data: {
                labels: [], // The labels for the campuses will be set dynamically
                datasets: []
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top', // Ensure the legend is positioned above the chart
                        labels: {
                            boxWidth: 20, // Adjust the width of the colored box next to each label
                            padding: 10, // Add some padding between the labels
                            font: {
                                size: 14 // Adjust the font size of the labels if needed
                            }
                        },
                        onClick: (e) => e.stopPropagation(), // Prevent the legend click event from affecting the chart
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                return `${label}: ${value} programs`;
                            }
                        }
                    },
                    datalabels: {
                        formatter: (value, context) => {
                            const total = context.chart.data.datasets[0].data.reduce((acc, val) => acc + val, 0);
                            const percentage = ((value / total) * 100).toFixed(2);
                            return `${percentage}%`;
                        },
                        color: '#fff',
                        font: {
                            weight: 'bold'
                        },
                        anchor: 'end',
                        align: 'start',
                        offset: -10
                    }
                },
                layout: {
                    padding: {
                        top: 10 // Adjust the padding above the chart if needed
                    }
                }
            },
            plugins: [ChartDataLabels]
        });



        window.addEventListener('DOMContentLoaded', async () => {
            // await fetchYears();
            updatePieChart();
            updateRecentPrograms();
            fetchProgramLevelHistoryData('Campus1', 'College1', 'Program1');
            await updateCharts();

            // document.getElementById('programLevel').addEventListener('change', async () => {
            //     await updateCharts();
            // });

            // document.getElementById('year').addEventListener('change', async () => {
            //     await updateCharts();
            // });
        });

        // document.getElementById('exportPDF').addEventListener('click', function() {
        //     const charts = document.querySelectorAll('canvas');

        //     const images = [];
        //     let count = 0;

        //     charts.forEach((chart, index) => {
        //         html2canvas(chart).then(canvas => {
        //             images.push({
        //                 data: canvas.toDataURL('image/png')
        //             });
        //             count++;
        //             if (count === charts.length) {
        //                 console.log('All charts captured, sending to server');
        //                 sendImagesToServer(images);
        //             }
        //         }).catch(err => console.error('Error capturing canvas:', err));
        //     });
        // });

        function sendImagesToServer(images) {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'reports_dashboard_pdf.php', true);
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.onreadystatechange = function() {
                if (xhr.readyState == 4) {
                    if (xhr.status == 200) {
                        console.log('PDF generated:', xhr.responseText);
                        downloadFile(xhr.responseText);
                    } else {
                        console.error('Failed to generate PDF:', xhr.statusText);
                    }
                }
            };
            xhr.send(JSON.stringify(images));
        }


        function downloadFile(fileName) {
            const link = document.createElement('a');
            link.href = 'reports_dashboard_download.php?file=' + encodeURIComponent(fileName);
            link.download = fileName;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
</body>

</html>