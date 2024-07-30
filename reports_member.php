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
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.3/css/jquery.dataTables.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 80%;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
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
            <div class="charts-left">
                <div class="chart-header"><h3>Schedules Over Time</h3></div>
                <canvas id="scheduleChart" height="600" width="800px"></canvas>
            </div>
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

    <script>
        $(document).ready(function() {
            const ctx = document.getElementById('scheduleChart').getContext('2d');
            const schedules = <?= json_encode($schedules) ?>;
            const labels = schedules.map(s => s.schedule_date);
            const dataPoints = schedules.map(s => s.user_count);

            const scheduleChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Schedules Over Time',
                        data: dataPoints,
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1,
                        fill: false,
                        pointHoverRadius: 7,
                        pointHoverBorderColor: 'rgba(220,220,220,1)'
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    },
                    tooltips: {
                        callbacks: {
                            label: function(tooltipItem) {
                                return 'Users attended: ' + tooltipItem.yLabel;
                            }
                        }
                    }
                }
            });

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
