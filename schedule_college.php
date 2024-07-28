<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Details</title>
    <link rel="stylesheet" href="schedule_college_style.css">
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgb(0, 0, 0);
            background-color: rgba(0, 0, 0, 0.4);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 400px;
            border-radius: 10px;
            text-align: center;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        .modal-buttons {
            display: flex;
            justify-content: space-around;
            margin-top: 20px;
        }

        .modal-buttons button {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .modal-buttons .yes-btn {
            background-color: #d9534f;
            color: white;
        }

        .modal-buttons .no-btn {
            background-color: #5bc0de;
            color: white;
        }

        .modal-buttons .yes-btn:hover {
            background-color: #c9302c;
        }

        .modal-buttons .no-btn:hover {
            background-color: #31b0d5;
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
            </div>
        </div>
        <div style="height: 1px; width: 100%; background: #E5E5E5"></div>
    </div>

    <div class="pageHeader">
        <div class="headerRight">
            <a class="btn" href="schedule.php">Back</a>
        </div>
        <h2>Schedule Details for <?php echo htmlspecialchars($_GET['college']); ?></h2>
    </div>

    <div class="filter-container">
        <div class="dropdown">
            <button class="dropbtn">Filter</button>
            <div class="dropdown-content">
                <a href="#" onclick="filterTable('all')">All</a>
                <a href="#" onclick="filterTable('pending')">Pending</a>
                <a href="#" onclick="filterTable('approved')">Approved</a>
                <a href="#" onclick="filterTable('done')">Done</a>
                <a href="#" onclick="filterTable('cancelled')">Cancelled</a>
            </div>
        </div>
    </div>

    <div class="schedule-table">
        <table>
            <thead>
                <tr>
                    <th>Program</th>
                    <th>Level Applied</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                include 'connection.php';

                $college_name = urldecode($_GET['college']);
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
                        echo "<a class='action-btn' href='#' onclick='openTeamModal(" . $row['id'] . ")'>View Team</a>";
                        if ($row['schedule_status'] !== 'cancelled' && $row['schedule_status'] !== 'approved' && $row['schedule_status'] !== 'done') {
                            echo "<a class='action-btn approve' href='#' onclick='openApproveModal(" . $row['id'] . ")'>Approve</a>";
                            echo "<a class='action-btn reschedule' href='#' onclick='openRescheduleModal(" . $row['id'] . ")'>Reschedule</a>";
                            echo "<a class='action-btn cancel' href='#' onclick='openCancelModal(" . $row['id'] . ")'>Cancel</a>";
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

    <form id="approveForm" action="schedule_approve_process.php" method="post" style="display: none;">
        <input type="hidden" name="schedule_id" id="approveScheduleId">
        <input type="hidden" name="college" value="<?php echo htmlspecialchars($_GET['college']); ?>">
    </form>

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
            xhr.onreadystatechange = function () {
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
            rows.forEach(function (row) {
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

            document.querySelectorAll('.tab').forEach(function (tab) {
                tab.classList.remove('active');
            });
            document.querySelector('.tab[onclick="filterTable(\'' + status + '\')"]').classList.add('active');
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

        document.getElementById('confirmApproveBtn').addEventListener('click', function () {
            document.getElementById('approveForm').submit();
        });
    </script>
</body>

</html>
