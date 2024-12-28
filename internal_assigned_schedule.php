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
$status_options = ['pending', 'cancelled', 'finished', 'passed', 'failed'];

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
        /* Modern DataTable Styling */
        .dataTables_wrapper {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
        }

        /* Table Header Styling */
        table.dataTable thead th {
            background-color: #B73033 !important;
            color: white !important;
            font-weight: 600;
            padding: 16px;
            border: 1px solid #E5E5E5;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Table Body Styling */
        table.dataTable tbody td {
            padding: 16px;
            border: 1px solid #E5E5E5;
            color: #333;
            font-size: 14px;
            vertical-align: middle;
        }

        /* Stripe Effect */
        table.dataTable tbody tr:nth-child(even) {
            background-color: #fafafa;
        }

        table.dataTable tbody tr:hover {
            background-color: #f5f5f5;
            transition: background-color 0.2s ease;
        }

        /* Export Button Styling */
        .dt-buttons .btn-primary {
            background-color: #B73033;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 500;
            font-size: 14px;
            margin-bottom: 20px;
            transition: background-color 0.2s ease;
            color: white;
        }

        .dt-buttons .btn-primary:hover {
            background-color: #9c292b;
        }

        /* Filter Inputs Styling */
        .dataTables_filter input {
            border: 1px solid #E5E5E5;
            border-radius: 6px;
            padding: 8px 12px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s ease;
        }

        .dataTables_filter input:focus {
            border-color: #B73033;
            box-shadow: 0 0 0 3px rgba(183, 48, 51, 0.1);
        }

        /* Filter Container Styling */
        .filter-container {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
        }

        .filter-container label {
            font-weight: 500;
            color: #333;
            margin-right: 8px;
        }

        .filter-container select {
            border: 1px solid #E5E5E5;
            border-radius: 6px;
            padding: 8px 32px 8px 12px;
            font-size: 14px;
            margin-right: 20px;
            color: #333;
            background: white;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23333333' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 8px center;
            background-size: 16px;
            appearance: none;
            cursor: pointer;
            outline: none;
            min-width: 160px;
        }

        .filter-container select:focus {
            border-color: #B73033;
            box-shadow: 0 0 0 3px rgba(183, 48, 51, 0.1);
        }

        /* Status Column Badge Styling */
        .status-badge {
            padding: 6px 12px;
            border-radius: 16px;
            font-size: 13px;
            font-weight: 500;
            display: inline-block;
            text-transform: capitalize;
        }

        .status-approved {
            background-color: rgb(209, 209, 209);
            color:rgb(255, 255, 255);
        }

        .status-passed {
            background-color: #e8f5e9;
            color: #2e7d32;
        }

        .status-pending {
            background-color: #fff3e0;
            color: #ef6c00;
        }
        
        .status-failed {
            background-color: #ffebee;
            color: #c62828;
        }

        .status-cancelled {
            background-color: #ffebee;
            color: #c62828;
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
                            <?php if ($assessment_count > 0): ?>
                                <span class="notification-counter"><?php echo $assessment_count; ?></span>
                            <?php endif; ?>
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
                    data: 'schedule_status',
                    render: function(data, type, row) {
                        if (type === 'display') {
                            const status = data === 'finished' ? 'approved' : data;
                            const statusClass = `status-${status}`;
                            return `<span class="status-badge ${statusClass}">${status}</span>`;
                        }
                        return data;
                    }
                }
            ]
        });

        $('#college, #year, #status').change(function() {
            table.draw();
        });
    </script>
</body>

</html>