<?php
include 'connection.php';
session_start();


$user_id = $_SESSION['user_id'];
$is_admin = false;

// Check user type and redirect accordingly
if ($user_id === 'admin') {
    $is_admin = true;
    // If current page is not admin.php, redirect
    if (basename($_SERVER['PHP_SELF']) !== 'internal_assigned_schedule.php') {
        header("Location: admin_sidebar.php");
        exit();
    }
} else {
    $user_type_code = substr($user_id, 3, 2);

    if ($user_type_code === '11') {
        // Internal user
        if (basename($_SERVER['PHP_SELF']) !== 'internal_assigned_schedule.php') {
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
// Fetch colleges for the dropdown
$college_query = "SELECT code, college_name FROM college";
$college_result = $conn->query($college_query);

// Fetch distinct years from the schedule table for filtering
$year_query = "SELECT DISTINCT YEAR(schedule_date) as year FROM schedule";
$year_result = $conn->query($year_query);

// Fetch distinct status for the status dropdown
$status_options = ['pending', 'approved', 'cancelled', 'finished', 'failed'];

// Function to fetch and display schedules (used for both AJAX and initial load)
function display_schedules($conn, $user_id, $selected_college = '', $selected_year = '', $selected_status = '')
{
    $schedule_query = "
        SELECT s.*, c.college_name, p.program_name
        FROM schedule s
        JOIN team t ON s.id = t.schedule_id
        JOIN college c ON s.college_code = c.code
        JOIN program p ON s.program_id = p.id
        WHERE t.internal_users_id = ?";

    // Apply filtering by college
    if (!empty($selected_college)) {
        $schedule_query .= " AND s.college_code = ?";
    }

    // Apply filtering by year
    if (!empty($selected_year)) {
        $schedule_query .= " AND YEAR(s.schedule_date) = ?";
    }

    // Apply filtering by status
    if (!empty($selected_status)) {
        $schedule_query .= " AND s.schedule_status = ?";
    }

    $stmt = $conn->prepare($schedule_query);

    // Bind the parameters dynamically based on the filters
    if (!empty($selected_college) && !empty($selected_year) && !empty($selected_status)) {
        $stmt->bind_param("ssss", $user_id, $selected_college, $selected_year, $selected_status);
    } elseif (!empty($selected_college) && !empty($selected_year)) {
        $stmt->bind_param("sss", $user_id, $selected_college, $selected_year);
    } elseif (!empty($selected_college)) {
        $stmt->bind_param("ss", $user_id, $selected_college);
    } elseif (!empty($selected_year)) {
        $stmt->bind_param("ss", $user_id, $selected_year);
    } elseif (!empty($selected_status)) {
        $stmt->bind_param("ss", $user_id, $selected_status);
    } else {
        $stmt->bind_param("s", $user_id);
    }

    $stmt->execute();
    $schedule_result = $stmt->get_result();

    $output = "";
    if ($schedule_result->num_rows > 0) {
        while ($row = $schedule_result->fetch_assoc()) {
            $schedule_date = date("F d, Y", strtotime($row['schedule_date'])); // Format as MM-DD-YYYY
            $schedule_time = date("g:i a", strtotime($row['schedule_time'])); // Format as 11:00am
            $output .= "<tr>
                <td>{$schedule_date}</td>
                <td>{$schedule_time}</td>
                <td>{$row['college_name']}</td>
                <td>{$row['program_name']}</td>
                <td>{$row['schedule_status']}</td>
            </tr>";
        }
    } else {
        $output .= "<tr><td colspan='5'>No schedules found for the selected filters.</td></tr>";
    }

    $stmt->close();
    return $output;
}

// Handle AJAX request
if (isset($_POST['ajax']) && $_POST['ajax'] == 1) {
    $selected_college = $_POST['college'];
    $selected_year = $_POST['year'];
    $selected_status = $_POST['status'];
    echo display_schedules($conn, $user_id, $selected_college, $selected_year, $selected_status);
    exit();
}

// PDF export function
if (isset($_POST['export_pdf'])) {
    // Include FPDF or TCPDF library
    require('mc_table.php'); // Assuming you have FPDF installed in the project

    $pdf = new PDF_MC_Table();
    $pdf->AddPage();
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, 'Schedule Report', 1, 1, 'C');

    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(30, 10, 'Date', 1);
    $pdf->Cell(20, 10, 'Time', 1);
    $pdf->Cell(50, 10, 'College', 1);
    $pdf->Cell(60, 10, 'Program', 1);
    $pdf->Cell(30, 10, 'Status', 1);
    $pdf->Ln();

    // Function to wrap text to a specified length
    function wrapText($text, $length) {
        return wordwrap($text, $length, "\n", true);
    }

    // Get the data to export
    $schedules = display_schedules($conn, $user_id, $_POST['college'], $_POST['year'], $_POST['status']);
    $schedules_array = explode('</tr>', $schedules);

    foreach ($schedules_array as $row) {
        $row_data = strip_tags($row);
        $cells = explode("\t", $row_data);

        foreach ($cells as $cell) {
            $trimmedCell = trim($cell);
            $data_cell = explode("\n", $trimmedCell);
            if (count($data_cell) < 5) {
                continue; // Skip rows that don't have enough data
            }

            // Prepare wrapped text for Time and College
            $wrappedProgram = wrapText(trim($data_cell[3]), 35);
            $wrappedCollege = wrapText(trim($data_cell[2]), 45);

            // Calculate the number of lines for each wrapped text
            $linesTime = count(explode("\n", $wrappedProgram));
            $linesCollege = count(explode("\n", $wrappedCollege));

            // Calculate the maximum height for the row
            $rowHeight = max($linesTime, $linesCollege, 1) * 5; // 10 is the cell height

            // // Add data to the PDF
            // $pdf->Cell(30, $rowHeight, trim($data_cell[0]), 1); // Date
            // $pdf->Cell(20, $rowHeight, trim($data_cell[1]), 1); // Time
            // $pdf->MultiCell(50, $rowHeight / 2, $wrappedCollege, 1, 'C'); // College
            // $pdf->MultiCell(60, 5, $wrappedProgram, 1); // Program
            // $pdf->Cell(30, $rowHeight, trim($data_cell[4]), 1); // Status
            
            $pdf->SetWidths(array(30,20,50,60, 30));
            $pdf->Row(array(trim($data_cell[0]),trim($data_cell[1]),trim($data_cell[2]),trim($data_cell[3]),trim($data_cell[4])));

            $pdf->Ln($rowHeight); // Move to the next line after the row height
        }
    }

    // Output PDF
    $fileName = $user_id . '_schedules_report.pdf';
    $pdf->Output('d', $fileName);
    exit();
}

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
            border-collapse: collapse;
        }

        table,
        th,
        td {
            border: 1px solid black;
        }

        th,
        td {
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
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
            <h2>View Assigned Schedules</h2>

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
                
                <form method="post" action="" class="button-container">
                    <input type="hidden" name="college" id="hidden-college">
                    <input type="hidden" name="year" id="hidden-year">
                    <input type="hidden" name="status" id="hidden-status">
                    <button type="submit" name="export_pdf" class="export-btn">Export as PDF</button>
                </form>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Schedule Date</th>
                        <th>Schedule Time</th>
                        <th>College</th>
                        <th>Program</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="schedule-table">
                    <?php echo display_schedules($conn, $user_id); ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Function to fetch and update the schedule table
        function fetchSchedules() {
            const college = document.getElementById('college').value;
            const year = document.getElementById('year').value;
            const status = document.getElementById('status').value;

            // Send an AJAX request to fetch schedules based on filters
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "", true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    document.getElementById('schedule-table').innerHTML = xhr.responseText;
                }
            };
            xhr.send(`ajax=1&college=${college}&year=${year}&status=${status}`);
        }

        // Add event listeners to the dropdowns to trigger the fetch on change
        document.getElementById('college').addEventListener('change', fetchSchedules);
        document.getElementById('year').addEventListener('change', fetchSchedules);
        document.getElementById('status').addEventListener('change', fetchSchedules);

        // Set the hidden fields for PDF export
        document.querySelector('.export-btn').addEventListener('click', function() {
            document.getElementById('hidden-college').value = document.getElementById('college').value;
            document.getElementById('hidden-year').value = document.getElementById('year').value;
            document.getElementById('hidden-status').value = document.getElementById('status').value;
        });
    </script>
</body>

</html>