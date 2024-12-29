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
    if (basename($_SERVER['PHP_SELF']) !== 'program_level.php') {
        header("Location: program_level.php");
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

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Schedule</title>
    <!-- Include Select2 CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link href="css/pagestyle.css" rel="stylesheet">
    <link rel="stylesheet" href="css/sidebar_updated.css">
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="index.css">
    <link rel="stylesheet" href="css/form_style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <style>
        .popup {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .popup-content {
            background-color: #fff;
            margin: 10% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
            text-align: center;
            border-radius: 10px;
            position: relative;
        }

        .popup-image {
            max-width: 100%;
            height: auto;
        }

        .popup-text {
            margin: 20px 25px;
            font-size: 17px;
            font-weight: 500;
        }

        .hairpop-up {
            height: 15px;
            background: #9B0303;
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            width: 100%;
            border-bottom-left-radius: 10px;
            border-bottom-right-radius: 10px;
        }

        .okay {
            color: black;
            text-decoration: none;
            white-space: unset;
            font-size: 1rem;
            font-weight: bold;
            text-transform: uppercase;
            border: 1px solid;
            border-radius: 10px;
            cursor: pointer;
            padding: 16px 55px;
            min-width: 120px;
        }

        .okay:hover {
            background-color: #EAEAEA;
        }

        .loading-spinner .spinner-border {
            width: 40px;
            height: 40px;
            border-width: 5px;
            border-color: #B73033 !important;
            /* Enforce the custom color */
            border-right-color: transparent !important;
        }

        #loadingSpinner.spinner-hidden {
            display: none;
        }

        .loading-spinner {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        input[type="date"],
        input[type="time"] {
            cursor: pointer;
        }

        /* Ensure the icon itself is also covered */
        input[type="date"]::-webkit-calendar-picker-indicator,
        input[type="time"]::-webkit-calendar-picker-indicator {
            cursor: pointer;
        }
    </style>
</head>

<body>
    <div class="wrapper">
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
                            <?php if ($totalPendingSchedules > 0): ?>
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
                                <?php if ($totalPendingSchedules > 0): ?>
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
                            <?php if ($assessmentCount > 0): ?>
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
                                <?php if ($assessmentCount > 0): ?>
                                    <span class="notification-counter"><?= $assessmentCount; ?></span>
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
                        <a href="#" class="sidebar-link-active">
                            <span style="margin-left: 8px;">Administrative</span>
                            <?php if ($totalPendingUsers > 0 || $transferRequestCount > 0): ?>
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
                                <?php if ($totalPendingUsers > 0): ?>
                                    <span class="notification-counter"><?= $totalPendingUsers; ?></span>
                                <?php endif; ?>
                            </a>
                            <a href="<?php echo $is_admin ? 'college_transfer.php' : '#'; ?>" class="<?php echo $is_admin ? 'sidebar-link' : 'sidebar-link-disabled'; ?>">
                                <span style="margin-left: 8px;">College Transfer</span>
                                <?php if ($transferRequestCount > 0): ?>
                                    <span class="notification-counter"><?= $transferRequestCount; ?></span>
                                <?php endif; ?>
                            </a>
                            <a href="<?php echo $is_admin ? 'program_level.php' : '#'; ?>" class="<?php echo $is_admin ? 'sidebar-link' : 'sidebar-link-disabled'; ?>">
                                <span style="margin-left: 8px;">Update Program Level</span>
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
            <div class="container d-flex align-items-center mt-4">
                <h2 class="mt-4 mb-4">UPDATE PROGRAM LEVEL</h2>
            </div>
            <div class="container2">
                <div class="form-container">
                    <form id="schedule-form" method="POST" action="add_schedule_process.php">
                        <div class="form-group">
                            <label for="college">COLLEGE:</label>
                            <select id="college" name="college" onchange="fetchPrograms(); fetchTeamLeadersAndMembers();" required class="select2" style="cursor: pointer;">
                                <option value="" disabled selected hidden>Select College</option>
                                <?php
                                include 'connection.php';

                                $sql = "SELECT code, college_name FROM college ORDER BY college_name";
                                $result = $conn->query($sql);

                                while ($row = $result->fetch_assoc()) {
                                    echo "<option value='{$row['code']}'>{$row['college_name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="program">PROGRAM:</label>
                            <select id="program" name="program" onchange="fetchProgramLevelDynamic()" required class="select2" style="cursor: pointer;">
                                <option value="" disabled selected hidden>Select Program</option>
                                <!-- Options will be dynamically populated based on college selection -->
                            </select>
                        </div>
                        <div id="programs-container" class="selected-programs-list">

                        </div>
                        <button type="button" id="add-program-button" class="add-program-input-button" onclick="addProgramInput()" disable>Add Program Level</button>
                    </form>
                </div>
            </div>

            <div id="programModal" class="program-modal">
                <div class="program-modal-content">
                    <h2>Add Program Level</h2>
                    <div id="program-form"></div>
                    <button type="button" class="save-program-btn">Save</button>
                    <button type="button" class="cancel-program-btn">Cancel</button>
                </div>
            </div>

            <div id="loadingSpinner" class="loading-spinner spinner-hidden">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
        let programCount = 0;
        let programsData = [];

        // Core event listeners
        document.getElementById('college').addEventListener('change', function() {
            fetchPrograms();
            clearProgramsOnCollegeChange();
            updateSubmitButtonState();
        });

        document.addEventListener('DOMContentLoaded', function() {
            const addProgramButton = document.getElementById('add-program-button');
            if (addProgramButton) {
                addProgramButton.disabled = true;
                addProgramButton.style.cursor = 'not-allowed';
                addProgramButton.style.opacity = '0.6';
            }
        });

        // Core functions
        function updateSubmitButtonState() {
            const submitButton = document.querySelector('.submit-button');
            if (submitButton) {
                submitButton.disabled = programsData.length === 0;
                if (programCount === 0) {
                    submitButton.classList.add('submit-button-disabled');
                } else {
                    submitButton.classList.remove('submit-button-disabled');
                }
            }
        }

        function clearProgramsOnCollegeChange() {
            const programsContainer = document.getElementById('programs-container');
            const programBlocks = document.querySelectorAll('.program-block');

            programBlocks.forEach((block) => {
                const blockId = block.id.split('-')[2];
                try {
                    $(`#program-${blockId}`).select2('destroy');
                } catch (e) {
                    console.log('Select2 instance not found or already destroyed');
                }
            });

            programsContainer.innerHTML = '';
            programCount = 0;
        }

        // AJAX functions
        function fetchPrograms() {
            const collegeId = document.getElementById('college').value;
            const addProgramButton = document.getElementById('add-program-button');

            if (collegeId) {
                $.ajax({
                    url: 'get_programs.php',
                    type: 'POST',
                    data: {
                        college_id: collegeId
                    },
                    success: function(response) {
                        if (addProgramButton) {
                            addProgramButton.disabled = false;
                            addProgramButton.style.cursor = 'pointer';
                            addProgramButton.style.opacity = '1';
                        }
                        populateProgramDropdown('#program', response);
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', error);
                        if (addProgramButton) {
                            addProgramButton.disabled = true;
                            addProgramButton.style.cursor = 'not-allowed';
                            addProgramButton.style.opacity = '0.6';
                        }
                    }
                });
            } else {
                if (addProgramButton) {
                    addProgramButton.disabled = true;
                    addProgramButton.style.cursor = 'not-allowed';
                    addProgramButton.style.opacity = '0.6';
                }
                clearDropdown('#program');
            }
        }

        // Modal functions
        // Update the showProgramModal function
        function showProgramModal() {
            const modal = document.getElementById('programModal');
            const programForm = document.getElementById('program-form');

            // Get current date and format it for the date input
            const today = new Date().toISOString().split('T')[0];

            programForm.innerHTML = `
        <div class="edit-form">
            <div class="form-group">
                <label>Program Level:</label>
                <input type="text" class="form-control" name="new-program_level" required>
            </div>
            <div class="form-group">
                <label>Date Received:</label>
                <input type="date" class="form-control" name="new-date_received" 
                       value="${today}"
                       onchange="updateModalValidityPeriod()" required>
            </div>
            <div class="form-group">
                <label>Validity Period:</label>
                <input type="date" class="form-control" name="new-year_of_validity" required>
            </div>
        </div>
    `;

            // Set initial validity period
            updateModalValidityPeriod();
            modal.style.display = "block";

            // Update save button handler
            document.querySelector('.save-program-btn').onclick = saveNewProgramLevel;
        }

        function updateModalValidityPeriod() {
            const dateReceived = new Date(document.querySelector('[name="date_received"]').value);
            const validityInput = document.querySelector('[name="year_of_validity"]');

            // Set validity to date_received + 3 years
            const newValidity = new Date(dateReceived);
            newValidity.setFullYear(newValidity.getFullYear() + 3);

            validityInput.value = newValidity.toISOString().split('T')[0];
        }

        function saveNewProgramLevel() {
            const programId = document.getElementById('program').value;
            const level = document.querySelector('[name="new-program_level"]').value;
            const dateReceived = document.querySelector('[name="new-date_received"]').value;
            const yearValidity = document.querySelector('[name="new-year_of_validity"]').value;

            if (!programId || !level || !dateReceived || !yearValidity) {
                alert('Please fill in all fields');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'add');
            formData.append('program_id', programId);
            formData.append('program_level', level);
            formData.append('date_received', dateReceived);
            formData.append('year_of_validity', yearValidity);

            $.ajax({
                url: 'update_program_history.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    try {
                        console.log(response);
                        const data = JSON.parse(response);
                        if (data.success) {
                            document.getElementById('programModal').style.display = "none";
                            fetchProgramLevelDynamic();
                            alert('New program level added successfully');
                        } else {
                            alert('Error adding program level: ' + data.message);
                        }
                    } catch (e) {
                        console.error('Error parsing response:', e);
                        alert('Error adding program level');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                    alert('Error adding program level');
                }
            });
        }

        // Add styles for the modal
        const modalStyles = `
.program-modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
}

.program-modal-content {
    background-color: #fff;
    margin: 10% auto;
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 5px;
    width: 80%;
    max-width: 500px;
}

.program-modal-content h2 {
    margin-bottom: 20px;
    color: #333;
}

.save-program-btn,
.cancel-program-btn {
    padding: 8px 16px;
    margin: 10px 5px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.save-program-btn {
    background-color: #34c759;
    color: white;
}

.cancel-program-btn {
    background-color: #6c757d;
    color: white;
}
`;

        // Add styles to document
        const styleSheet = document.createElement("style");
        styleSheet.textContent = modalStyles;
        document.head.appendChild(styleSheet);

        function populateProgramDropdown(selector, options) {
            console.log(selector);
            const dropdown = $(selector);
            dropdown.empty();
            dropdown.append($('<option>').text('Select Program').attr('value', ''));
            options.forEach(option => {
                dropdown.append($('<option>').text(option.name).attr('value', option.id));
            });
        }

        // Helper Function: Clear Dropdown
        function clearDropdown(selector) {
            const dropdown = $(selector);
            dropdown.empty();
            dropdown.append($('<option>').text('Select Program').attr('value', ''));
        }

        function fetchProgramLevelDynamic() {
            const programId = document.getElementById(`program`).value;
            if (programId) {
                $.ajax({
                    url: 'get_program_level_history.php',
                    type: 'POST',
                    data: {
                        program_id: programId
                    },
                    success: function(response) {
                        const data = JSON.parse(response);
                        console.log(data);
                        updateProgramsList(data);
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', error);
                    }
                });
            }
        }

        // Add these functions to your existing JavaScript

        function updateProgramsList(programData) {
            const container = document.querySelector('.selected-programs-list');
            container.innerHTML = '';

            // Create the program history display
            const historyContent = programData.history.map(history => {
                // Calculate default validity (date_received + years_of_validity + 3 years)
                const dateReceived = new Date(history.date_received);
                const defaultValidity = new Date(dateReceived.setFullYear(
                    dateReceived.getFullYear() + (history.years_of_validity || 0) + 3
                ));

                let showDateReceived = true;
                let levelToShow = `Level ${history.level}`;
                if(history.level == "Not Accreditable" || history.level == "Candidate" || history.level == "No Graduates Yet" || history.level == "N/A" ||  history.level == "PSV" ){
                    levelToShow = history.level;
                    if(history.level != "Candidate" ||history.level != "PSV" ){
                        showDateReceived = false;
                    }
                }

                return `
        <div class="level-history-item" data-history-id="${history.id}">
            <div class="level-info">
                <span class="level-badge">${levelToShow}</span>
                <span class="level-date" ${showDateReceived ? '' : 'hidden'}>Received: ${new Date(history.date_received).toLocaleDateString()}</span>
                ${history.year_of_validity ? 
                    `<span class="validity" ${showDateReceived ? '' : 'hidden'}>Valid until: ${new Date(history.year_of_validity).toLocaleDateString()}</span>` 
                    : ''}
            </div>
            <div class="history-actions" ${showDateReceived ? '' : 'hidden'}>
                <button type="button" class="edit-history-btn" onclick="editHistory(${history.id})">
                    <i class="fas fa-edit"></i> Edit
                </button>
            </div>
            <div class="edit-form" id="edit-form-${history.id}" style="display: none;">
                <div class="form-group">
                    <label>Program Level:</label>
                    <input type="number" class="form-control" name="program_level"
                           value="${history.level}" min="1" max="4">
                </div>
                <div class="form-group">
                    <label>Date Received:</label>
                    <input type="date" class="form-control" name="date_received"
                           value="${history.date_received}" 
                           onchange="updateValidityPeriod(${history.id})">
                </div>
                <div class="form-group">
                    <label>Validity Period:</label>
                    <input type="date" class="form-control" name="year_of_validity"
                           value="${history.year_of_validity || defaultValidity.toISOString().split('T')[0]}">
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-primary" 
                            onclick="saveHistoryChanges(${history.id})">Save</button>
                    <button type="button" class="btn btn-secondary" 
                            onclick="cancelEdit(${history.id})">Cancel</button>
                </div>
            </div>
        </div>
    `
            }).join('');

            const programElement = `
        <div class="program-history-wrapper">
            <h6 class="program-title">Program Levels:</h6>
            <div class="level-history-container">
                ${historyContent || '<p>No level history available</p>'}
            </div>
        </div>
    `;

            container.insertAdjacentHTML('beforeend', programElement);

            // Add the updated CSS
            const style = document.createElement('style');
            style.textContent = `
        .program-history-wrapper {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .program-title {
            margin-bottom: 15px;
            color: #333;
            font-size: 1.2em;
        }
        .level-history-container {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .level-history-item {
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 4px;
            position: relative;
        }
        .level-info {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        .level-badge {
            background-color: #9B0303;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: 500;
        }
        .level-date, .validity {
            color: #666;
            font-size: 0.9em;
        }
        .history-actions {
            position: absolute;
            right: 10px;
            top: 10px;
            display: flex;
            gap: 8px;
        }
        .edit-history-btn, .delete-history-btn {
            padding: 4px 8px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9em;
        }
        .edit-history-btn {
            background-color: #007bff;
            color: white;
        }
        .delete-history-btn {
            background-color: #dc3545;
            color: white;
        }
        .edit-form {
            margin-top: 15px;
            padding: 15px;
            border-top: 1px solid #ddd;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
    `;
            document.head.appendChild(style);
        }

        function editHistory(historyId) {
            // Hide all other edit forms first
            document.querySelectorAll('.edit-form').forEach(form => {
                form.style.display = 'none';
            });

            // Show the selected edit form
            const editForm = document.getElementById(`edit-form-${historyId}`);
            if (editForm) {
                editForm.style.display = 'block';
            }
        }

        function cancelEdit(historyId) {
            const editForm = document.getElementById(`edit-form-${historyId}`);
            if (editForm) {
                editForm.style.display = 'none';
            }
        }

        function saveHistoryChanges(historyId) {
            const editForm = document.getElementById(`edit-form-${historyId}`);
            const formData = new FormData();

            formData.append('action', 'update');
            formData.append('history_id', historyId);
            formData.append('program_level', editForm.querySelector('[name="program_level"]').value);
            formData.append('date_received', editForm.querySelector('[name="date_received"]').value);
            formData.append('year_of_validity', editForm.querySelector('[name="year_of_validity"]').value);

            $.ajax({
                url: 'update_program_history.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    const data = JSON.parse(response);
                    if (data.success) {
                        // Refresh the program history display
                        fetchProgramLevelDynamic();
                        alert('History updated successfully');
                    } else {
                        alert('Error updating history: ' + data.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                    alert('Error updating history');
                }
            });
        }

        function confirmDeleteHistory(historyId) {
            if (confirm('Are you sure you want to delete this history entry? This action cannot be undone.')) {
                deleteHistory(historyId);
            }
        }

        function deleteHistory(historyId) {
            $.ajax({
                url: 'update_program_history.php',
                type: 'POST',
                data: {
                    action: 'delete',
                    history_id: historyId
                },
                success: function(response) {
                    const data = JSON.parse(response);
                    if (data.success) {
                        // Refresh the program history display
                        fetchProgramLevelDynamic(currentProgramId);
                        alert('History deleted successfully');
                    } else {
                        alert('Error deleting history: ' + data.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                    alert('Error deleting history');
                }
            });
        }


        // Event handlers for modal
        document.querySelector('.cancel-program-btn').onclick = () => {
            document.getElementById('programModal').style.display = "none";
        };
        document.getElementById('add-program-button').onclick = showProgramModal;

        // Form submission handler
        document.getElementById('schedule-form').addEventListener('submit', function(event) {
            event.preventDefault();
            const loadingSpinner = document.getElementById('loadingSpinner');
            loadingSpinner.classList.remove('spinner-hidden');
            document.getElementById('schedule-form').submit();
        });
    </script>
</body>

</html>