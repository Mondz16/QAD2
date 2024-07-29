<?php
// Database connection
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

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
    <link rel="stylesheet" type="text/css" href="reports_dashboard_styles.css">
</head>
<body>
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

        <div class="filter">
            <select id="programLevel" onchange="updateCharts()">
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
            </select>
        </div>

        <div class="charts-container">
            <div class="chart-wrapper large">
                <canvas id="collegeChart" height="650"></canvas>
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

        async function fetchYears() {
            const response = await fetch('fetch_years.php');
            const years = await response.json();
            const yearSelect = document.getElementById('year');
            years.forEach(year => {
                const option = document.createElement('option');
                option.value = year;
                option.textContent = year;
                yearSelect.appendChild(option);
            });
        }

        async function updateCharts() {
            const programLevel = document.getElementById('programLevel').value;
            const year = document.getElementById('year').value;
            
            await updateBarChart(programLevel, year);
            await updatePieChart(programLevel, year);
            updateRecentPrograms();
        }

        async function updateBarChart(programLevel, year) {
            const data = await fetchCollegeData(programLevel, year);

            const labels = data.map(item => item.college_campus);

            let datasets = [];
            if (programLevel === 'All') {
                datasets = [{
                        label: 'Not Accreditable',
                        data: data.map(item => item['Not Accreditable'] || 0),
                        backgroundColor: '#FFD700',
                    },
                    {
                        label: 'PSV',
                        data: data.map(item => item['PSV'] || 0),
                        backgroundColor: '#8FBC8F',
                    },
                    {
                        label: 'Candidate',
                        data: data.map(item => item['Candidate'] || 0),
                        backgroundColor: '#FF6347',
                    },
                    {
                        label: 'Level 1',
                        data: data.map(item => item['1'] || 0),
                        backgroundColor: '#4682B4',
                    },
                    {
                        label: 'Level 2',
                        data: data.map(item => item['2'] || 0),
                        backgroundColor: '#7B68EE',
                    },
                    {
                        label: 'Level 3',
                        data: data.map(item => item['3'] || 0),
                        backgroundColor: '#FF69B4',
                    },
                    {
                        label: 'Level 4',
                        data: data.map(item => item['4'] || 0),
                        backgroundColor: '#32CD32',
                    }
                ];
            } else {
                datasets = [{
                    label: programLevel == "Not Accreditable" || programLevel == "PSV" || programLevel == "Candidate" ? programLevel : `Level ${programLevel}`,
                    data: data.map(item => item.program_count || 0),
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                }];
            }

            chart.data.labels = labels;
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
                    '#FF6384',
                    '#36A2EB',
                    '#FFCE56',
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
                        stacked: true,
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
                        stacked: true,
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
                labels: [],
                datasets: []
            },
            options: {
                responsive: true,
                plugins: {
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
                }
            },
            plugins: [ChartDataLabels]
        });

        
        window.addEventListener('DOMContentLoaded', async () => {
            await fetchYears();
            updateCharts();
            updatePieChart();
            updateRecentPrograms();

            document.getElementById('programLevel').addEventListener('change', async () => {
                await updateCharts();
            });

            document.getElementById('year').addEventListener('change', async () => {
                await updateCharts();
            });
        });
    </script>
</body>
</html>
