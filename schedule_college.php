<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link href="css/schedule_college_pagestyle.css" rel="stylesheet">
</head>

<body>
    <div class="wrapper">
        <div class="row top-bar"></div>
        <div class="row header mb-3">
            <div class="col-6 col-md-2 mx-auto d-flex align-items-center justify-content-end">
                <img src="images/USePLogo.png" alt="USeP Logo">
            </div>
            <div class="col-6 col-md-4 d-flex align-items-start">
                <div class="vertical-line"></div>
                <div class="divider"></div>
                <div class="text">
                    <span class="one">One</span>
                    <span class="datausep">Data.</span>
                    <span class="one">One</span>
                    <span class="datausep">USeP.</span><br>
                    <span>Quality Assurance Division</span>
                </div>
            </div>
            <div class="col-md-4 d-none d-md-flex align-items-center justify-content-end">
            </div>
            <div class="col-md-2 d-none d-md-flex align-items-center justify-content-start">
            </div>
        </div>
        <div class="container d-flex align-items-center mt-4">
            <a class="btn-back" href="schedule.php">&lt; BACK</a>
            <h2 class="mt-4 mb-4">SCHEDULE DETAILS FOR COLLEGE OF EDUCATION</h2>
        </div>
        <div class="container">
            <div class="filter-container text-end">
                <div class="dropdown">
                    <button class="dropbtn">FILTER</button>
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
                        include 'connection.php';

                        $college_name = urldecode($_GET['college']);
                        $college_code = htmlspecialchars($_GET['college_code']); // Added to get college_code
                        $sql = "SELECT s.id, p.program_name, s.level_applied, s.schedule_date, s.schedule_time, s.schedule_status
                                FROM schedule s
                                JOIN program p ON s.program_id = p.id
                                JOIN college c ON s.college_code = c.code
                                WHERE c.college_name = ?
                                ORDER BY s.schedule_date, s.schedule_time";

                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("s", $college_name);
                        $stmt->execute();
                        $result = $stmt->get_result();

                        // Set the timezone to Asia/Manila
                        date_default_timezone_set('Asia/Manila');

                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                $schedule_date = date("F-d-Y", strtotime($row['schedule_date']));
                                $schedule_time = date("h:i A", strtotime($row['schedule_time']));

                                // Create DateTime objects with the Asia/Manila timezone
                                $scheduleDateTime = new DateTime($row['schedule_date'] . ' ' . $row['schedule_time'], new DateTimeZone('Asia/Manila'));
                                $currentDateTime = new DateTime('now', new DateTimeZone('Asia/Manila'));

                                if ($currentDateTime > $scheduleDateTime && $row['schedule_status'] === 'approved' && $row['schedule_status'] !== 'cancelled') {
                                    // Update the schedule status to "done" in the database
                                    $update_sql = "UPDATE schedule SET schedule_status = 'done' WHERE id = ?";
                                    $update_stmt = $conn->prepare($update_sql);
                                    $update_stmt->bind_param("i", $row['id']);
                                    $update_stmt->execute();
                                    $update_stmt->close();

                                    // Update the status in the current row data
                                    $row['schedule_status'] = 'done';
                                }

                                echo "<tr class='schedule-row' data-status='" . htmlspecialchars($row['schedule_status']) . "'>";
                                echo "<td>" . htmlspecialchars($row['program_name']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['level_applied']) . "</td>";
                                echo "<td>" . htmlspecialchars($schedule_date) . "</td>";
                                echo "<td>" . htmlspecialchars($schedule_time) . "</td>";
                                if ($row['schedule_status'] !== 'pending') {
                                    echo "<td>" . htmlspecialchars($row['schedule_status']) . "</td>";
                                } else {
                                    echo "<td>waiting for approval</td>";
                                }
                                echo "<td>";
                                echo "<button class='button view-team' onclick='openTeamModal(" . $row['id'] . ")'>VIEW TEAM</button>";
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
                    <span class="close" onclick="closeCancelModal()">&times;</span>
                    <form id="cancelForm" action="schedule_cancel_process.php" method="post">
                        <input type="hidden" name="schedule_id" id="cancel_schedule_id">
                        <input type="hidden" name="college" value="<?php echo htmlspecialchars($_GET['college']); ?>">
                        <h2>Are you sure you want to cancel this schedule?</h2>
                        <div class="form-group">
                            <label for="cancel_reason">Reason:</label>
                            <textarea id="cancel_reason" name="cancel_reason" rows="5" cols="52" required></textarea>
                        </div>
                        <div class="modal-buttons">
                            <button class="yes-btn" type="submit">Yes</button>
                            <button class="no-btn" type="button" onclick="closeCancelModal()">No</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Approve Modal -->
            <div id="approveModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeApproveModal()">&times;</span>
                    <h2>Are you sure you want to approve this schedule?</h2>
                    <div class="modal-buttons">
                        <button class="yes-btn" id="confirmApproveBtn">Yes</button>
                        <button class="no-btn" onclick="closeApproveModal()">No</button>
                    </div>
                </div>
            </div>

            <!-- Reschedule Modal -->
            <div id="rescheduleModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeRescheduleModal()">&times;</span>
                    <form action="schedule_update_process.php" method="post">
                        <input type="hidden" name="schedule_id" id="schedule_id">
                        <input type="hidden" name="college" value="<?php echo htmlspecialchars($_GET['college']); ?>">
                        <div class="form-group">
                            <label for="new_date">New Date:</label>
                            <input type="date" id="new_date" name="new_date" required>
                        </div>
                        <div class="form-group">
                            <label for="new_time">New Time:</label>
                            <input type="time" id="new_time" name="new_time" required>
                        </div>
                        <div class="form-group">
                            <label for="reason">Reason:</label>
                            <textarea id="reason" name="reason" rows="5" cols="52" required></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" onclick="closeRescheduleModal()">Cancel</button>
                            <button type="submit">Save changes</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Team Modal -->
            <div id="teamModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeTeamModal()">&times;</span>
                    <h2>Team</h2>
                    <div id="teamContent"></div>
                    <div class="modal-footer">
                    </div>
                </div>
            </div>

            <!-- View User Modal -->
            <div id="viewUserModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="document.getElementById('viewUserModal').style.display='none'">&times;</span>
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
            </form>

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
    </script>
</body>

</html>