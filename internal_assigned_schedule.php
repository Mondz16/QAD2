<?php
include 'connection.php';
session_start();

$user_id = $_SESSION['user_id'];
$is_admin = false;

// Check user type and redirect accordingly
if ($user_id === 'admin') {
    $is_admin = true;
    if (basename($_SERVER['PHP_SELF']) !== 'internal_assigned_schedule.php') {
        header("Location: admin_sidebar.php");
        exit();
    }
} else {
    $user_type_code = substr($user_id, 3, 2);

    if ($user_type_code === '11') {
        if (basename($_SERVER['PHP_SELF']) !== 'internal_assigned_schedule.php') {
            header("Location: internal.php");
            exit();
        }
    } elseif ($user_type_code === '22') {
        if (basename($_SERVER['PHP_SELF']) !== 'external.php') {
            header("Location: external.php");
            exit();
        }
    } else {
        header("Location: login.php");
        exit();
    }
}

// Fetch colleges for the dropdown
$college_query = "SELECT code, college_name FROM college";
$college_result = $conn->query($college_query);

// Fetch distinct years from the schedule table for filtering
$year_query = "SELECT DISTINCT YEAR(schedule_date) as year FROM schedule";
$year_result = $conn->query($year_query);

// Fetch distinct statuses for the status dropdown
$status_options = ['pending', 'cancelled', 'finished'];

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assigned Schedules</title>
    <link rel="stylesheet" href="index.css">
    <link rel="stylesheet" href="reports_dashboard_styles.css">
    <link rel="stylesheet" href="pagestyle.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.5/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.5/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <style>
        .edit-icon {
            display: inline-block;
            margin-left: 10px;
            cursor: pointer;
        }

        .edit-icon i {
            font-size: 14px;
            color: #000;
        }

        .report-box {
            padding: 20px;
            border: 1px solid #ddd;
            margin-top: 20px;
        }

        .report-box h3 {
            margin-bottom: 10px;
        }

        .report-box p {
            font-size: 18px;
            font-weight: bold;
        }

        table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
            margin-top: 10px;
        }

        table th,
        table td {
            padding: 10px;
            text-align: left;
            border: solid #E5E5E5 1px;
        }

        table thead th {
            background-color: #B73033;
            color: #fff;
        }

        .filter-container {
            margin-bottom: 20px;
        }

        .filter-container select {
            padding: 5px;
            margin-right: 10px;
        }

        .filter-container button {
            padding: 5px 10px;
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
        <div style="height: 24px; width: 0px;"></div>

        <div class="dashboard-container">
            <div style="height: 24px; width: 0px;"></div>
            <div class="filter-container">
                <label for="college">College:</label>
                <select name="college" id="college">
                    <option value="">All Colleges</option>
                    <?php while ($college_row = $college_result->fetch_assoc()) { ?>
                        <option value="<?php echo $college_row['code']; ?>"><?php echo $college_row['college_name']; ?></option>
                    <?php } ?>
                </select>

                <label for="year">Year:</label>
                <select name="year" id="year">
                    <option value="">All Years</option>
                    <?php while ($year_row = $year_result->fetch_assoc()) { ?>
                        <option value="<?php echo $year_row['year']; ?>"><?php echo $year_row['year']; ?></option>
                    <?php } ?>
                </select>

                <label for="status">Status:</label>
                <select name="status" id="status">
                    <option value="">All Status</option>
                    <?php foreach ($status_options as $status) { ?>
                        <option value="<?php echo $status; ?>"><?php echo ucfirst($status); ?></option>
                    <?php } ?>
                </select>
            </div>

            <table id="scheduleTable" class="display nowrap" style="width:100%">
                <thead>
                    <tr>
                        <th>Schedule Date</th>
                        <th>Schedule Time</th>
                        <th>College</th>
                        <th>Program</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            const table = $('#scheduleTable').DataTable({
                dom: 'Bfrtip',
                buttons: [{
                    extend: 'pdfHtml5',
                    text: 'Export to PDF',
                    className: 'btn btn-primary',
                    title: 'Schedule Report',
                    filename: function() {
                        // Generate a dynamic filename with the current date and time
                        const now = new Date();
                        const formattedDate = now.toISOString().split('T')[0]; // YYYY-MM-DD
                        const formattedTime = now
                            .toLocaleTimeString('en-US', {
                                hour12: false
                            })
                            .replace(/:/g, '-'); // HH-MM-SS
                        return `Schedule_Report_${formattedDate}_${formattedTime}`;
                    },
                    exportOptions: {
                        columns: ':visible',
                        modifier: {
                            search: 'applied',
                            order: 'applied'
                        }
                    },
                    customize: function(doc) {
                        doc.styles.tableHeader.fillColor = '#B73033';
                        doc.styles.tableHeader.color = 'white';
                    }
                }],
                serverSide: true,
                ajax: {
                    url: 'fetch_schedules.php',
                    type: 'POST',
                    data: function(d) {
                        d.college = $('#college').val();
                        d.year = $('#year').val();
                        d.status = $('#status').val();
                    }
                },
                columns: [{
                        data: 'schedule_date'
                    },
                    {
                        data: 'schedule_time'
                    },
                    {
                        data: 'college_name'
                    },
                    {
                        data: 'program_name'
                    },
                    {
                        data: 'schedule_status'
                    }
                ]
            });

            $('#college, #year, #status').change(function() {
                table.draw();
            });
        });
    </script>
</body>

</html>