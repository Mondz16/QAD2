<?php
include 'connection.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Check user type and redirect accordingly
if ($user_id === 'admin') {
    // If current page is not admin.php, redirect
    if (basename($_SERVER['PHP_SELF']) !== 'program_timeline.php') {
        header("Location: program_timeline.php");
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

// Fetch colleges
$colleges = [];
$sql = "SELECT code, college_name FROM college";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $colleges[] = $row;
    }
}

// Handle AJAX requests
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['college_code'])) {
        // Fetch distinct programs for a specific college
        $college_code = $_POST['college_code'];
        
        $sql = "SELECT DISTINCT program_name FROM program WHERE college_code = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $college_code);
        $stmt->execute();
        $result = $stmt->get_result();

        $options = "";

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $options .= "<div class='select-item' data-value='" . htmlspecialchars($row['program_name']) . "'>" . htmlspecialchars($row['program_name']) . "</div>";
            }
        } else {
            $options .= "<div class='select-item'>No programs available</div>";
        }

        echo $options;
        $stmt->close();
        exit;  // Exit to prevent further HTML output
    }

    if (isset($_POST['program_names'])) {
        // Fetch program level history for specific programs
        $program_names = json_decode($_POST['program_names'], true);
        $events = []; // Array to store events for the timeline

        foreach ($program_names as $program_name) {
            $sql = "SELECT plh.program_level, plh.date_received 
                    FROM program_level_history plh
                    JOIN program p ON plh.program_id = p.id
                    WHERE p.program_name = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $program_name);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $programEvents = [];
                while ($row = $result->fetch_assoc()) {
                    $programEvents[] = [
                        'label' => $row['program_level'],
                        'date' => $row['date_received']
                    ];
                }
                $events[$program_name] = $programEvents; // Store events per program
            }
            $stmt->close();
        }

        // Return events data as JSON
        echo json_encode($events);
        exit;  // Exit to prevent further HTML output
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Internal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="index.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-moment"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <style>
        .program-history {
            margin-bottom: 20px;
        }
        .custom-select {
            width: 300px;
            position: relative;
            display: inline-block;
        }
        .select-items {
            position: absolute;
            background-color: #f9f9f9;
            top: 100%;
            left: 0;
            right: 0;
            z-index: 99;
            border: 1px solid #ccc;
            border-radius: 8px;
            max-height: 150px;
            overflow-y: auto;
            display: none;
        }
        .select-item {
            padding: 10px;
            cursor: pointer;
            font-weight: bold;
        }
        .select-item:hover {
            background: linear-gradient(275.52deg, #973939 0.28%, #DC7171 100%);
            color: white;
        }
        .same-as-selected {
            background: linear-gradient(275.52deg, #973939 0.28%, #DC7171 100%);
            color: white;
        }

        /* Styles for legends */
        .legend-container {
            display: flex;
            justify-content: flex-end; /* Align items to the right */
            align-items: center;
            margin-bottom: 10px;
            position: relative; /* Needed for positioning the tooltip */
        }

        .legend-lines {
            display: flex;
            gap: 5px; /* Space between lines */
            margin-left: 10px; /* Space between legend text and lines */
        }

        .legend-line,
        .legend-text,
        .info-icon {
            position: relative;
            height: 20px; /* Height of the line, same as the LEGENDS text */
            border-radius: 5px; /* Rounded corners */
            cursor: pointer;
        }

        .legend-line,
        .legend-text {
            width: 50px;
        }
        .legend-text {
            width: auto;
            margin-right: 5px;
        }

        .red-line {
            background-color: #FF7B7A; /* Color for 'Not Accreditable' */
        }

        .green-line {
            background-color: #76FA97; /* Color for 'Candidate' */
        }

        .grey-line {
            background-color: #CCCCCC; /* Color for 'PSV' */
        }

        .yellow-line {
            background-color: #FDC879; /* Color for Levels 1-4 */
        }

        .info-icon {
            background: transparent;
        }

        .info-icon img {
            width: 20px;
            height: 20px;
        }

        /* Tooltip styles for entire legend */
        .legend-tooltip {
            visibility: hidden;
            width: 350px;
            color: #fff;
            text-align: left;
            border-radius: 20px;
            padding: 10px;
            position: absolute;
            z-index: 1;
            top: 150%; /* Position above the element */
            right: 0;
            opacity: 0;
            transition: opacity 0.3s;
            background-color: #FFFFFF;
            border: 1px solid #575757;
        }

        /* Show tooltip on hover over the legend-container */
        .legend-container:hover .legend-tooltip {
            visibility: visible;
            opacity: 1;
        }

        /* Tooltip content lines */
        .tooltip-line, .tooltip-line1 {
            display: flex;
            align-items: center;
            color: black;
            padding: 10px;
        }

        .tooltip-line {
            margin-bottom: 10px;
        }

        .tooltip-color {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 80px;
            height: 40px;
            border-radius: 5px;
            margin-right: 5px;
            color: #000;
            font-weight: bold;
        }

        .red-tooltip { background-color: #FF7B7A; }
        .green-tooltip { background-color: #76FA97; }
        .grey-tooltip { background-color: #CCCCCC; }
        .yellow-tooltip { background-color: #FDC879; }

    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
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
                <li class="sidebar-item">
                    <a href="area.php" class="sidebar-link">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-mortarboard" viewBox="0 0 16 16">
                            <path d="M8.211 2.047a.5.5 0 0 0-.422 0l-7.5 3.5a.5.5 0 0 0 .025.916l7.5 3a.5.5 0 0 0 .372 0L14 7.14V13a1 1 0 0 0-1 1v2h3v-2a1 1 0 0 0-1-1V6.739l.686-.275a.5.5 0 0 0 .025-.916zM8 8.46 1.758 5.965 8 3.052l6.242 2.913z" />
                            <path d="M4.166 9.032a.5.5 0 0 0-.656.327l-.5 1.7a.5.5 0 0 0 .294.605l4.5 1.8a.5.5 0 0 0 .372 0l4.5-1.8a.5.5 0 0 0 .294-.605l-.5-1.7a.5.5 0 0 0-.656-.327L8 10.466zm-.068 1.873.22-.748 3.496 1.311a.5.5 0 0 0 .352 0l3.496-1.311.22.748L8 12.46z" />
                        </svg>
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
                            <a href="program_timeline.php" class="sidebar-link-active"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-bar-chart-steps me-2" viewBox="0 0 16 16">
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

        <div class="main bg-white">
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
                                    <span class="datausep">USeP.</span></h>
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
        <div class="container">
            <p style="text-align: center; font-size: 30px"><strong>PROGRAM LEVEL HISTORY TIMELINE</strong></p>
            <div style="height: 30px;"></div>
            <div class="college-program">
                <div class="college-program-history">
                    <select id="collegeSelect" onchange="loadPrograms(this.value)">
                    <option value="">SELECT COLLEGE</option>
                    <?php
                    foreach ($colleges as $college) {
                        echo "<option value='" . $college['code'] . "'>" . htmlspecialchars($college['college_name']) . "</option>";
                    }
                    ?>
                </select>
                </div>
                <div class="college-program-history">
                    <div class="select-selected">SELECT PROGRAM/S</div>
                    <div class="select-items">
                        <!-- Options will be populated based on selected college -->
                    </div>
                    <div class="custom-select">
                    </div>
                </div>
            </div>
            <div class="legend-container">
                <div class="legend-text">
                    <p><strong>LEGENDS</strong></p>
                </div>
                <div class="legend-lines">
                    <div class="legend-line red-line"></div>
                    <div class="legend-line green-line"></div>
                    <div class="legend-line grey-line"></div>
                    <div class="legend-line yellow-line"></div>
                    <div class="info-icon">
                        <img src="images/info-circle.png" alt="Info">
                    </div>
                </div>
                <div class="legend-tooltip">
                    <div class="tooltip-line">
                        <div class="tooltip-color red-tooltip" style="margin-right: 30px;">NA</div><strong>NOT ACCREDITABLE</strong>
                    </div>
                    <div class="tooltip-line">
                        <div class="tooltip-color green-tooltip" style="margin-right: 30px;">CAN</div><strong>CANDIDATE</strong>
                    </div>
                    <div class="tooltip-line">
                        <div class="tooltip-color grey-tooltip" style="margin-right: 30px;">PSV</div><strong>PRE-SURVEY VISIT</strong>
                    </div>
                    <div class="tooltip-line1">
                        <div class="tooltip-color yellow-tooltip" style="margin-right: 30px;">LVL 1-4</div><strong>LEVEL ACCREDITED</strong>
                    </div>
                </div>
            </div>
            <div class="orientation5" id="chartContainer">
                <p style="text-align: center; font-size: 20px"><strong>PLEASE SELECT COLLEGE AND PROGRAM/S</strong></p>
            </div>
        </div>
        <button class="export-button" id="exportPdfButton"><span style="font-weight: bold; color: #575757; font-size: 16px; cursor: pointer;">EXPORT</span><img style="margin-left: 5px;" src="images/export.png"></button>
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
        function loadPrograms(collegeCode) {
            if (collegeCode === "") {
                document.querySelector('.select-items').innerHTML = "<div>Select programs</div>";
                document.getElementById('programHistory').innerHTML = "";
                return;
            }
            
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "", true);  // Send POST request to the same page
            xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function() {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    document.querySelector('.select-items').innerHTML = xhr.responseText;
                    setupCustomSelect();
                }
            };
            xhr.send("college_code=" + encodeURIComponent(collegeCode));
        }

        function setupCustomSelect() {
    const selectItems = document.querySelector('.select-items');
    const selectedDiv = document.querySelector('.select-selected');
    const items = selectItems.getElementsByClassName('select-item');
    const maxSelection = 5; // Maximum number of selectable programs

    Array.from(items).forEach(function(item) {
        item.addEventListener('click', function(e) {
            e.stopPropagation();
            item.classList.toggle('same-as-selected');

            // Get selected values
            let selectedValues = Array.from(selectItems.getElementsByClassName('same-as-selected')).map(function(selectedItem) {
                return selectedItem.dataset.value;
            });

            // Limit selection to maxSelection
            if (selectedValues.length > maxSelection) {
                alert(`You can only select up to ${maxSelection} programs.`);
                item.classList.remove('same-as-selected');
                return;
            }

            // Update display
            if (selectedValues.length > 0) {
                const displayedText = selectedValues.length > 1 ? `${selectedValues[0]} and ${selectedValues.length - 1} more` : selectedValues[0];
                selectedDiv.textContent = displayedText;
            } else {
                selectedDiv.textContent = 'Select programs';
            }

            loadProgramHistories(selectedValues);
        });
    });

    // Toggle dropdown
    selectedDiv.addEventListener('click', function(e) {
        e.stopPropagation();
        closeAllSelect();
        selectItems.style.display = selectItems.style.display === 'block' ? 'none' : 'block';
    });
}


        function loadProgramHistories(selectedPrograms) {
    if (selectedPrograms.length === 0) {
        document.getElementById('chartContainer').innerHTML = "";
        return;
    }

    var xhr = new XMLHttpRequest();
    xhr.open("POST", "", true);  // Send POST request to the same page
    xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function() {
        if (xhr.readyState == 4 && xhr.status == 200) {
            try {
                const allEvents = JSON.parse(xhr.responseText);
                renderTimelineCharts(allEvents, selectedPrograms);
            } catch (e) {
                console.error("Failed to parse JSON response", e);
                document.getElementById('programHistory').innerHTML = xhr.responseText;
            }
        }
    };
    xhr.send("program_names=" + encodeURIComponent(JSON.stringify(selectedPrograms)));
}

function renderTimelineCharts(eventsGroupedByProgram, selectedPrograms) {
    const chartContainer = document.getElementById('chartContainer');
    
    // Clear previous charts
    chartContainer.innerHTML = '';

    Object.keys(eventsGroupedByProgram).forEach((programName, programIndex) => {
        const events = eventsGroupedByProgram[programName];

        // Create a container div for each program
        const programContainer = document.createElement('div');
        programContainer.classList.add('orientation4');
        
        // Create a label for each program
        const programLabel = document.createElement('h3');
        programLabel.textContent = `${programName}`;
        programContainer.appendChild(programLabel);

        // Create a new canvas element for each program
        const canvas = document.createElement('canvas');
        canvas.id = `timelineChart${programIndex}`;
        canvas.style.height = '200px';
        canvas.style.width = '100%';
        programContainer.appendChild(canvas);

        // Append the program container to the chart container
        chartContainer.appendChild(programContainer);

        // Create a chart for each canvas
        if (events.length === 0) {
            return;
        }

        // Convert date strings to Date objects
        events.forEach(event => {
            event.x = new Date(event.date);
            event.y = 0;
            event.label = `${event.label}; ${new Date(event.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}`;
        });

        // Determine the timeline range
        const dates = events.map(event => event.x);
        const minDate = new Date(Math.min.apply(null, dates));
        const maxDate = new Date(Math.max.apply(null, dates));
        minDate.setFullYear(minDate.getFullYear() - 1);
        maxDate.setFullYear(maxDate.getFullYear() + 6);

        // Prepare the dataset for Chart.js
        const dataset = {
            datasets: [{
                label: 'Program Timeline',
                data: events,
                backgroundColor: 'blue',
                pointRadius: 0, // Hide the scatter points
                pointHoverRadius: 0,
                showLine: false
            }]
        };

        // Get the context of the new canvas element
        const ctx = canvas.getContext('2d');

        // Define colors for each level
        const levelColors = {
            'Not Accreditable': '#FF7B7A',  // Red
            'Candidate': '#76FA97',        // Green
            'PSV': '#CCCCCC',              // Grey
            '1': '#FDC879',                // Yellow
            '2': '#FDC879',                // Yellow
            '3': '#FDC879',                // Yellow
            '4': '#FDC879'                 // Yellow
        };

        // Define abbreviations for each level
        const levelAbbreviations = {
            'Not Accreditable': 'NA',
            'Candidate': 'CAN',
            'PSV': 'PSV',
            '1': 'LVL 1',
            '2': 'LVL 2',
            '3': 'LVL 3',
            '4': 'LVL 4'
        };

        // Function to draw rounded rectangles with border
        function drawRoundedRectWithBorder(ctx, x, y, width, height, radius, borderColor) {
            ctx.beginPath();
            ctx.moveTo(x + radius, y);
            ctx.lineTo(x + width - radius, y);
            ctx.quadraticCurveTo(x + width, y, x + width, y + radius);
            ctx.lineTo(x + width, y + height - radius);
            ctx.quadraticCurveTo(x + width, y + height, x + width - radius, y + height);
            ctx.lineTo(x + radius, y + height);
            ctx.quadraticCurveTo(x, y + height, x, y + height - radius);
            ctx.lineTo(x, y + radius);
            ctx.quadraticCurveTo(x, y, x + radius, y);
            ctx.closePath();

            // Fill the rectangle
            ctx.fill();

            // Draw the border
            ctx.strokeStyle = borderColor;
            ctx.lineWidth = 1;
            ctx.stroke();
        }

        // Define a plugin to draw vertical lines with labels and borders
        const verticalLinePlugin = {
            id: 'verticalLinePlugin',
            beforeDraw: chart => {
                const { ctx, chartArea: { top, bottom }, scales: { x, y } } = chart;
                ctx.save();

                events.forEach(event => {
                    const level = event.label.split(';')[0];  // Extract the level from the label
                    ctx.fillStyle = levelColors[level] || 'black';  // Use black as default color if not found

                    const xPosition = x.getPixelForValue(event.x);
                    const lineWidth = 60;
                    const lineHeight = bottom - y.getPixelForValue(0);
                    const lineTop = y.getPixelForValue(0);

                    // Define margins
                    const marginBetweenLines = 10; // Space between the first and second vertical lines
                    const marginBelowSecondLine = 5; // Space below the second vertical line

                    // Calculate the offset for the first rectangle
                    const offset = lineHeight / 2 + marginBetweenLines; // Increase offset to move the first rectangle up

                    // Draw the first rectangle for the level, adjusted upward, with border
                    ctx.fillStyle = levelColors[level] || 'black';
                    drawRoundedRectWithBorder(ctx, xPosition - lineWidth / 2, lineTop - offset, lineWidth, 70, 5, '#AFAFAF');

                    // Draw the level abbreviation inside the first rectangle
                    ctx.fillStyle = 'black';  // Text color
                    ctx.font = 'bold 16px Arial';  // Bold font style
                    ctx.textAlign = 'center'; // Center the text
                    ctx.textBaseline = 'middle';
                    ctx.fillText(levelAbbreviations[level] || '', xPosition, lineTop - offset + 35); // Centered at 35 pixels

                    const secondRectOffset = -5; // Increase or decrease this value to move the rectangle

                    // Draw the second rectangle for the date with margin below, with border
                    const dateHeight = lineHeight * 0.6; // Increase the height of the second vertical line
                    ctx.fillStyle = '#FFFFFF';  // White background
                    drawRoundedRectWithBorder(ctx, xPosition - lineWidth / 2, lineTop + lineHeight / 2 - marginBelowSecondLine + secondRectOffset, lineWidth, dateHeight - marginBelowSecondLine, 5, '#AFAFAF');

                    // Draw the date inside the second rectangle, centered vertically
                    ctx.fillStyle = 'black';  // Text color
                    ctx.font = 'bold 14px Arial';  // Bold font style for date
                    ctx.textBaseline = 'middle'; // Center the text vertically
                    ctx.fillText(
                        new Date(event.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }), 
                        xPosition, 
                        lineTop + lineHeight / 2 - marginBelowSecondLine + secondRectOffset + (dateHeight - marginBelowSecondLine) / 2
                    );
                });

                ctx.restore();
            }
        };

        // Render the chart on the new canvas
        new Chart(ctx, {
            type: 'scatter',
            data: dataset,
            options: {
                scales: {
                    x: {
                        type: 'time',
                        time: {
                            unit: 'year', // Set unit to year
                            displayFormats: {
                                year: 'YYYY' // Format the year
                            }
                        },
                        min: minDate,
                        max: maxDate,
                        title: {
                            display: true,
                            text: 'Year'
                        }
                    },
                    y: {
                        display: false,
                        min: -1,
                        max: 1
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        enabled: false // Disable default tooltips
                    },
                    datalabels: {
                        display: false // Hide default data labels
                    }
                }
            },
            plugins: [verticalLinePlugin] // Add the vertical line plugin
        });
    });
}








        function closeAllSelect() {
            var selectItems = document.querySelector('.select-items');
            selectItems.style.display = 'none';
        }

        document.addEventListener('click', closeAllSelect);

        document.getElementById('exportPdfButton').addEventListener('click', function() {
    const charts = document.querySelectorAll('canvas');
    const programNames = document.querySelectorAll('#chartContainer h3');
    
    const images = [];
    let count = 0;
    
    charts.forEach((chart, index) => {
        html2canvas(chart).then(canvas => {
            images.push({
                name: programNames[index].innerText,
                data: canvas.toDataURL('image/png')
            });
            count++;
            if (count === charts.length) {
                console.log('All charts captured, sending to server');
                sendImagesToServer(images);
            }
        }).catch(err => console.error('Error capturing canvas:', err));
    });
});

function sendImagesToServer(images) {
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'program_timeline_pdf.php', true);
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
    link.href = 'program_timeline_download.php?file=' + encodeURIComponent(fileName);
    link.download = 'program_history.pdf';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

    </script>
</body>
</html>
