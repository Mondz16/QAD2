<?php

include 'connection.php';

// Get and sanitize the college name and college code from the GET parameters
$college_name = urldecode($_GET['college']);
$college_code = htmlspecialchars($_GET['college_code']); // Added to get college_code

// Update the SQL query to include the manually_unlocked column from the schedule table
$sql = "SELECT s.id, p.program_name, s.level_applied, s.schedule_date, s.schedule_time, s.schedule_status, s.manually_unlocked
        FROM schedule s
        JOIN program p ON s.program_id = p.id
        JOIN college c ON s.college_code = c.code
        WHERE c.college_name = ? 
        AND s.schedule_status NOT IN ('passed', 'failed')
        ORDER BY s.schedule_date, s.schedule_time";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $college_name);
$stmt->execute();
$result = $stmt->get_result();

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link href="css/schedule_college_pagestyle.css" rel="stylesheet">
    <link rel="stylesheet" href="css/navbar.css">
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
            margin: 20px 50px;
            font-size: 17px;
            font-weight: 500;
        }

        .hairpop-up {
            height: 15px;
            background: linear-gradient(275.52deg, #973939 0.28%, #DC7171 100%);
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
            border-color: #FF7A7A !important; /* Enforce the custom color */
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

        input[type="date"], input[type="time"] {
            cursor: pointer;
        }

        /* Ensure the icon itself is also covered */
        input[type="date"]::-webkit-calendar-picker-indicator,
        input[type="time"]::-webkit-calendar-picker-indicator {
            cursor: pointer;
        }

        .view-user-modal {
        margin: 5% auto !important;
        }
    </style>
</head>

<body>
    <div class="wrapper">
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
    </div>
    <div style="height: 1px; width: 100%; background: #E5E5E5"></div>
    <div class="container d-flex align-items-center mt-4">
        <a class="btn-back" href="schedule.php">&lt; BACK</a>
        <h2 class="mt-4 mb-4">SCHEDULE DETAILS FOR <?php echo strtoupper($college_name) ?></h2>
    </div>
    <div class="container">
        <div class="filter-container text-end">
            <div class="dropdown">
                <button class="dropbtn">FILTER
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-funnel" viewBox="0 0 16 16">
                        <path d="M1.5 1.5A.5.5 0 0 1 2 1h12a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.128.334L10 8.692V13.5a.5.5 0 0 1-.342.474l-3 1A.5.5 0 0 1 6 14.5V8.692L1.628 3.834A.5.5 0 0 1 1.5 3.5zm1 .5v1.308l4.372 4.858A.5.5 0 0 1 7 8.5v5.306l2-.666V8.5a.5.5 0 0 1 .128-.334L13.5 3.308V2z" />
                    </svg>
                </button>
                <div class="dropdown-content">
                    <a href="#" onclick="filterTable('all')">All</a>
                    <a href="#" onclick="filterTable('pending')">Pending</a>
                    <a href="#" onclick="filterTable('approved')">Approved</a>
                    <a href="#" onclick="filterTable('done')">Done</a>
                    <a href="#" onclick="filterTable('cancelled')">Cancelled</a>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>PROGRAM NAME</th>
                        <th>LEVEL APPLIED</th>
                        <th>DATE</th>
                        <th>TIME</th>
                        <th>STATUS</th>
                        <th>ACTIONS</th>
                    </tr>
                </thead>
                <tbody>

                <?php
                date_default_timezone_set('Asia/Manila');

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $schedule_date = date("F-d-Y", strtotime($row['schedule_date']));
                        $schedule_time = date("h:i A", strtotime($row['schedule_time']));

                        // Create DateTime objects with the Asia/Manila timezone
                        $scheduleDateTime = new DateTime($row['schedule_date'] . ' ' . $row['schedule_time'], new DateTimeZone('Asia/Manila'));
                        $currentDateTime = new DateTime('now', new DateTimeZone('Asia/Manila'));

                        // Create date-only and time-only objects for the current and schedule dates
                        $currentDate = new DateTime($currentDateTime->format('Y-m-d'), new DateTimeZone('Asia/Manila'));
                        $scheduleDate = new DateTime($scheduleDateTime->format('Y-m-d'), new DateTimeZone('Asia/Manila'));
                        $currentTime = $currentDateTime->format('H:i');  // Get the current time in 24-hour format

                        // Set a grace period (24 hours after the schedule date and time)
                        $gracePeriodEnd = clone $scheduleDateTime;
                        $gracePeriodEnd->modify('+24 hours');

                        // Automatically mark as done if the current date matches the schedule date and time is 5 PM or later, 
                        // or if the current date is after the schedule date, but only if it hasn't been manually unlocked
                        if (($currentDate == $scheduleDate && $currentTime >= '17:00') || $currentDate > $scheduleDate) {
                            
                            // Check if the grace period has passed
                            if ($currentDateTime > $gracePeriodEnd) {
                                // Reset the manually_unlocked flag if the grace period has passed and it was manually unlocked
                                if ($row['manually_unlocked'] == 1) {
                                    $reset_sql = "UPDATE schedule SET manually_unlocked = 0 WHERE id = ?";
                                    $reset_stmt = $conn->prepare($reset_sql);
                                    $reset_stmt->bind_param("i", $row['id']);
                                    $reset_stmt->execute();
                                    $reset_stmt->close();
                                    
                                    // Update the flag in the current row so it doesn't need to be done again in this iteration
                                    $row['manually_unlocked'] = 0;
                                }
                            }

                            // Automatically mark as done if it hasn't been manually unlocked or if the grace period has passed
                            if ($row['schedule_status'] === 'approved' && $row['schedule_status'] !== 'cancelled' && $row['schedule_status'] !== 'done' && $row['manually_unlocked'] == 0) {
                                // Update the schedule status to "done" in the database
                                $update_sql = "UPDATE schedule SET schedule_status = 'done' WHERE id = ?";
                                $update_stmt = $conn->prepare($update_sql);
                                $update_stmt->bind_param("i", $row['id']);
                                $update_stmt->execute();
                                $update_stmt->close();

                                // Update the status in the current row data
                                $row['schedule_status'] = 'done';
                            }
                        }                        

                        // Start displaying the table row
                        echo "<tr class='schedule-row' data-status='" . htmlspecialchars($row['schedule_status']) . "'>";
                        echo "<td>" . htmlspecialchars($row['program_name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['level_applied']) . "</td>";
                        echo "<td>" . htmlspecialchars($schedule_date) . "</td>";
                        echo "<td>" . htmlspecialchars($schedule_time) . "</td>";

                        // Schedule status column
                        if ($row['schedule_status'] !== 'pending') {
                            echo "<td>" . htmlspecialchars($row['schedule_status']) . "</td>";
                        } else {
                            echo "<td>waiting for approval</td>";
                        }

                        echo "<td>";

                        // Always show the "View Team" button
                        echo "<button class='button view-team' onclick='openTeamModal(" . $row['id'] . ")'>
                                    <svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor' class='bi bi-people' viewBox='0 0 16 16'>
                                        <path d='M15 14s1 0 1-1-1-4-5-4-5 3-5 4 1 1 1 1zm-7.978-1L7 12.996c.001-.264.167-1.03.76-1.72C8.312 10.629 9.282 10 11 10c1.717 0 2.687.63 3.24 1.276.593.69.758 1.457.76 1.72l-.008.002-.014.002zM11 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4m3-2a3 3 0 1 1-6 0 3 3 0 0 1 6 0M6.936 9.28a6 6 0 0 0-1.23-.247A7 7 0 0 0 5 9c-4 0-5 3-5 4q0 1 1 1h4.216A2.24 2.24 0 0 1 5 13c0-1.01.377-2.042 1.09-2.904.243-.294.526-.569.846-.816M4.92 10A5.5 5.5 0 0 0 4 13H1c0-.26.164-1.03.76-1.724.545-.636 1.492-1.256 3.16-1.275ZM1.5 5.5a3 3 0 1 1 6 0 3 3 0 0 1-6 0m3-2a2 2 0 1 0 0 4 2 2 0 0 0 0-4'/>
                                    </svg>
                                </button>";

                        // Display the "UNLOCK" button if the schedule is marked as "done" and it's still within the 24-hour grace period
                        if ($row['schedule_status'] === 'done' && $currentDateTime <= $gracePeriodEnd) {
                            echo "<button class='button unlock mt-lg-0 mt-1' onclick='unlockSchedule(" . $row['id'] . ")'>UNLOCK</button>";
                        }

                        // Other buttons (Approve, Reschedule, Cancel) if the schedule is not cancelled, approved, done, or finished
                        if ($row['schedule_status'] !== 'cancelled' && $row['schedule_status'] !== 'approved' && $row['schedule_status'] !== 'done' && $row['schedule_status'] !== 'finished') {
                            echo "<button class='button approve mt-lg-0 mt-1' onclick='openApproveModal(" . $row['id'] . ")'>APPROVE</button>";
                            echo "<button class='button reschedule mt-lg-0 mt-1' onclick='openRescheduleModal(" . $row['id'] . ")'>RESCHEDULE</button>";
                            echo "<button class='button cancel mt-lg-0 mt-1' onclick='openCancelModal(" . $row['id'] . ")'>CANCEL</button>";
                        }

                        echo "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='6'>No schedules found for this college</td></tr>";
                }

                $stmt->close();
                $conn->close();
                ?>

                </tbody>
            </table>
        </div>

        <!-- Cancel Modal -->
        <div id="cancelModal" class="modal">
            <div class="modal-content">
                <form id="cancelForm" action="schedule_cancel_process.php" method="post">
                    <input type="hidden" name="schedule_id" id="cancel_schedule_id">
                    <input type="hidden" name="college" value="<?php echo htmlspecialchars($_GET['college']); ?>">
                    <input type="hidden" name="college_code" value="<?php echo htmlspecialchars($_GET['college_code']); ?>">
                    <h2>Are you sure you want to cancel this schedule?</h2>
                    <div class="form-group">
                        <textarea id="cancel_reason" name="cancel_reason" rows="5" cols="52" placeholder="Enter reason for schedule cancellation" required></textarea>
                    </div>
                    <div class="modal-buttons">
                        <button class="no-btn" type="button" onclick="closeCancelModal()">NO</button>
                        <button class="yes-btn rejection" type="submit">YES</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Approve Modal -->
        <div id="approveModal" class="modal">
            <div class="modal-content">
                <h2>Are you sure you want to approve this schedule?</h2>
                <div class="modal-buttons">
                    <button class="no-btn" onclick="closeApproveModal()">NO</button>
                    <button class="yes-btn" id="confirmApproveBtn">YES</button>
                </div>
            </div>
        </div>

        <!-- Reschedule Modal -->
        <div id="rescheduleModal" class="modal">
            <div class="modal-content">
                <form id="rescheduleForm" action="schedule_update_process.php" method="post">
                    <input type="hidden" name="schedule_id" id="schedule_id">
                    <input type="hidden" name="college" value="<?php echo htmlspecialchars($_GET['college']); ?>">
                    <input type="hidden" name="college_code" value="<?php echo htmlspecialchars($_GET['college_code']); ?>">
                    <div class="form-group">
                        <label for="new_date">NEW DATE:</label>
                        <input type="date" id="new_date" name="new_date" required style="cursor: pointer;" onclick="openDatePicker('new_date')">
                    </div>
                    <div class="form-group">
                        <label for="new_time">NEW TIME:</label>
                        <input type="time" id="new_time" name="new_time" required style="cursor: pointer;" onclick="openDatePicker('new_time')">
                    </div>
                    <div class="form-group">
                        <label for="new_zoom">NEW ZOOM:</label>
                        <input type="text" id="new_zoom" name="new_zoom">
                    </div>
                    <div class="form-group">
                        <textarea id="reason" name="reason" rows="5" cols="52" placeholder="Enter reason for reschedule" required></textarea>
                    </div>
                    <div class="modal-buttons">
                        <button type="button" class="no-btn" onclick="closeRescheduleModal()">CANCEL</button>
                        <button type="submit" class="yes-btn">SAVE CHANGES</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Team Modal -->
        <div id="teamModal" class="modal">
            <div class="modal-content">
                <div class="header">
                    <h2>Team</h2>
                    <span class="close" onclick="closeTeamModal()">&times;</span>
                </div>
                <div id="teamContent"></div>
            </div>
        </div>
    </div>

    <!-- View User Modal -->
    <div id="viewUserModal" class="modal">
        <div class="modal-content view-user-modal">
            <div class="view-header">
                <h2>User Details</h2>
                <span class="close" onclick="document.getElementById('viewUserModal').style.display='none'">&times;</span>
            </div>
            <div id="viewUserContent"></div>
        </div>
    </div>

    <!-- Change User Modal -->
    <div id="changeUserModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('changeUserModal').style.display='none'">&times;</span>
            <div id="changeUserContent"></div>
        </div>
    </div>

    <form id="approveForm" action="schedule_approve_process.php" method="post" style="display: none;">
        <input type="hidden" name="schedule_id" id="approveScheduleId">
        <input type="hidden" name="college" value="<?php echo htmlspecialchars($_GET['college']); ?>">
        <input type="hidden" name="college_code" value="<?php echo htmlspecialchars($_GET['college_code']); ?>">
    </form>

    </div>
    </div>

    <div id="errorPopup" class="popup" style="display: none;">
        <div class="popup-content">
            <div style="height: 50px; width: 0px;"></div>
            <img class="Error" src="images/Error.png" height="100">
            <div style="height: 20px; width: 0px;"></div>
            <div class="popup-text">A schedule for the selected date already exists.</div>
            <div style="height: 50px; width: 0px;"></div>
            <a href="#" class="okay" id="closeErrorPopup">Okay</a>
            <div style="height: 100px; width: 0px;"></div>
            <div class="hairpop-up"></div>
        </div>
    </div>

    <div id="loadingSpinner" class="loading-spinner spinner-hidden">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous">

    </script>
    <script>
        let cancelScheduleId;
        let approveScheduleId;

        function openRescheduleModal(scheduleId) {
            document.getElementById('schedule_id').value = scheduleId;
            document.getElementById('rescheduleModal').style.display = 'block';
        }

        function closeRescheduleModal() {
            document.getElementById('rescheduleModal').style.display = 'none';
        }

        document.getElementById('new_date').addEventListener('change', function() {
    var newDate = this.value;
    var scheduleId = document.getElementById('schedule_id').value;

    if (newDate) {
        // Perform an AJAX request to check if the new date conflicts with an existing one
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'check_schedule.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                var response = JSON.parse(xhr.responseText);

                if (response.status === 'exists') {
                    // Show the error modal if a conflict is found
                    document.getElementById('errorPopup').style.display = 'block';
                }
            }
        };
        xhr.send('date=' + encodeURIComponent(newDate) + '&exclude_schedule_id=' + encodeURIComponent(scheduleId));
    }
});

document.getElementById('rescheduleForm').addEventListener('submit', function(event) {
    event.preventDefault(); // Prevent the form from submitting immediately

    var newDate = document.getElementById('new_date').value;
    var newTime = document.getElementById('new_time').value;
    var scheduleId = document.getElementById('schedule_id').value;

    if (newDate && newTime) {
        // Perform an AJAX request to check if the new date conflicts with an existing one
        var xhr = new XMLHttpRequest();
        xhr.open('POST', 'check_schedule.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                var response = JSON.parse(xhr.responseText);

                if (response.status === 'exists') {
                    // Show the error modal if a conflict is found
                    document.getElementById('errorPopup').style.display = 'block';
                } else {
                    // No conflict, submit the form
                    document.getElementById('rescheduleForm').submit();
                }
            }
        };
        xhr.send('date=' + encodeURIComponent(newDate) + '&exclude_schedule_id=' + encodeURIComponent(scheduleId));
    }
});

// Event listener for closing the error popup
document.getElementById('closeErrorPopup').addEventListener('click', function() {
    document.getElementById('errorPopup').style.display = 'none';
});



        function openTeamModal(scheduleId) {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'fetch_team.php?schedule_id=' + scheduleId, true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    document.getElementById('teamContent').innerHTML = xhr.responseText;
                    document.getElementById('teamModal').style.display = 'block';
                }
            };
            xhr.send();
        }

        function closeTeamModal() {
            document.getElementById('teamModal').style.display = 'none';
        }

        function filterTable(status) {
            var rows = document.querySelectorAll('.schedule-row');
            rows.forEach(function(row) {
                if (status === 'all') {
                    row.style.display = '';
                } else {
                    if (row.getAttribute('data-status') === status) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                }
            });
        }

        function openCancelModal(scheduleId) {
            cancelScheduleId = scheduleId;
            document.getElementById('cancel_schedule_id').value = scheduleId;
            document.getElementById('cancelModal').style.display = 'block';
        }

        function closeCancelModal() {
            document.getElementById('cancelModal').style.display = 'none';
        }

        function openApproveModal(scheduleId) {
            approveScheduleId = scheduleId;
            document.getElementById('approveScheduleId').value = scheduleId;
            document.getElementById('approveModal').style.display = 'block';
        }

        function closeApproveModal() {
            document.getElementById('approveModal').style.display = 'none';
        }

        document.getElementById('confirmApproveBtn').addEventListener('click', function() {
            document.getElementById('approveForm').submit();
        });

        function viewUser(userId) {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'view_user.php?user_id=' + userId, true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    document.getElementById('viewUserContent').innerHTML = xhr.responseText;
                    document.getElementById('viewUserModal').style.display = 'block';
                }
            };
            xhr.send();
        }

        function changeUser(teamId) {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'change_user.php?team_id=' + teamId + '&college_code=' + encodeURIComponent('<?php echo $_GET["college_code"]; ?>'), true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    document.getElementById('changeUserContent').innerHTML = xhr.responseText;
                    document.getElementById('changeUserModal').style.display = 'block';
                }
            };
            xhr.send();
        }

        document.addEventListener('DOMContentLoaded', function() {
            const loadingSpinner = document.getElementById('loadingSpinner');

            // For Cancel Form
            document.getElementById('cancelForm').addEventListener('submit', function() {
                loadingSpinner.classList.remove('spinner-hidden');
            });

            // For Approve Form (triggered from Approve Modal)
            document.getElementById('approveForm').addEventListener('submit', function() {
                loadingSpinner.classList.remove('spinner-hidden');
            });

            // For Reschedule Form
            document.getElementById('rescheduleForm').addEventListener('submit', function() {
                loadingSpinner.classList.remove('spinner-hidden');
            });
            
            // For the hidden Approve Form (if the user is triggering it manually)
            document.getElementById('confirmApproveBtn').addEventListener('click', function() {
                loadingSpinner.classList.remove('spinner-hidden');
                document.getElementById('approveForm').submit();
            });
        });

        function openDatePicker(id) {
            document.getElementById(id).showPicker();
        }

        function unlockSchedule(scheduleId) {
    if (confirm("Are you sure you want to unlock this schedule?")) {
        // Send an AJAX request to unlock the schedule
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "unlock_schedule.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

        // Handle the response
        xhr.onreadystatechange = function () {
            if (xhr.readyState == 4 && xhr.status == 200) {
                // Success, notify the user
                alert(xhr.responseText);
                location.reload();  // Reload the page to show the updated status
            } else if (xhr.readyState == 4) {
                // Handle error responses
                alert("An error occurred: " + xhr.responseText);
            }
        };

        // Send the schedule ID to the server
        xhr.send("id=" + scheduleId);
    }
}

    </script>
</body>

</html>