<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Details</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Quicksand", sans-serif;
        }

        table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden;
        }

        table,
        th,
        td {
            border: 1px solid #ddd;
            text-align: left;
            padding: 12px;
        }

        th {
            background-color: #f2f2f2;
            font-weight: bold;
            color: #333;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .container {
            max-width: 1280px;
            padding-left: 24px;
            padding-right: 24px;
            width: 100%;
            display: block;
            box-sizing: border-box;
            margin-left: auto;
            margin-right: auto;
        }

        .header {
            height: 58px;
            width: 100%;
            display: flex;
            flex-flow: unset;
            justify-content: space-between;
            align-items: center;
            align-content: unset;
            overflow: unset;
        }

        .headerLeft {
            order: unset;
            flex: unset;
            align-self: unset;
        }

        .USePData {
            height: 100%;
            width: 100%;
            display: flex;
            flex-flow: unset;
            place-content: unset;
            align-items: center;
            overflow: unset;
        }

        .headerLeftText {
            height: 100%;
            width: 100%;
            display: flex;
            flex-direction: column;
            flex-wrap: unset;
            place-content: unset;
            align-items: unset;
            overflow: unset;
            font-size: 18px;
        }

        .headerRight {
            order: unset;
            flex: unset;
            align-self: unset;
        }

        .SDMD {
            height: 100%;
            width: 100%;
            display: flex;
            flex-flow: unset;
            place-content: unset;
            align-items: center;
            overflow: unset;
        }

        .headerRightText {
            height: 100%;
            width: 100%;
            display: flex;
            flex-direction: column;
            flex-wrap: unset;
            place-content: unset;
            align-items: flex-end;
            overflow: unset;
            text-align: right;
        }

        .headerLeftText h1,
        .headerLeftText h2 {
            margin: 0;
            padding: 0;
        }

        .headerRight .btn {
            background-color: #dc3545;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin-left: -500px;
            transition: background-color 0.3s ease;
        }

        .headerRight .btn:hover {
            background-color: #b82c3b;
        }

        .pageHeader {
            display: flex;
            max-width: 1920px;
            align-items: center;
            justify-content: center;
            margin-top: 50px;
            margin-bottom: 30px;
        }

        .pageHeader h2 {
            text-align: center;
            margin: 20px 0;
            color: #333;
        }

        .schedule-table {
            max-width: 1500px;
            padding: 0 24px;
            width: 100%;
            margin: 0 auto;
            box-sizing: border-box;
        }

        .action-btn {
            background-color: #007bff;
            color: white;
            padding: 5px 10px;
            margin-right: 5px;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s ease;
            font-size: 14px;
        }

        .action-btn:hover {
            background-color: #0056b3;
        }

        .action-btn.cancel {
            background-color: #dc3545;
        }

        .action-btn.cancel:hover {
            background-color: #b82c3b;
        }

        /* Modal Styles */
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
            padding-top: 60px;
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 800px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
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

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
        }

        .form-group input {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
        }

        .modal-footer {
            text-align: right;
        }

        .modal-footer button {
            padding: 10px 20px;
            margin-left: 10px;
        }

        .tabs {
            display: flex;
            justify-content: center;
            margin-top: 20px;
        }

        .tab {
            cursor: pointer;
            padding: 10px 20px;
            margin: 0 5px;
            background-color: #f2f2f2;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        .tab.active {
            background-color: #007bff;
            color: white;
        }

        .tab:hover {
            background-color: #d4d4d4;
        }

        .hidden {
            display: none;
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

    <div class="tabs">
        <div class="tab active" onclick="filterTable('all')">All</div>
        <div class="tab" onclick="filterTable('pending')">Pending</div>
        <div class="tab" onclick="filterTable('done')">Done</div>
        <div class="tab" onclick="filterTable('cancelled')">Cancelled</div>
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
                $sql = "SELECT s.id, p.program, s.level_applied, s.schedule_date, s.schedule_time, s.schedule_status
    FROM schedule s
    JOIN program p ON s.program_id = p.id
    JOIN college c ON s.college_id = c.id
    WHERE c.college_name = ?
    ORDER BY s.schedule_date, s.schedule_time";

                $stmt = $conn->prepare($sql);
                $stmt->bind_param("s", $college_name);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $schedule_date = date("F-d-Y", strtotime($row['schedule_date']));
                        $schedule_time = date("h:i A", strtotime($row['schedule_time']));

                        // Check if the current date and time have passed the schedule date and time
                        $scheduleDateTime = strtotime($row['schedule_date'] . ' ' . $row['schedule_time']);
                        $currentDateTime = time();

                        if ($currentDateTime > $scheduleDateTime && $row['schedule_status'] !== 'done' && $row['schedule_status'] !== 'cancelled') {
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
                        echo "<td>" . htmlspecialchars($row['program']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['level_applied']) . "</td>";
                        echo "<td>" . htmlspecialchars($schedule_date) . "</td>";
                        echo "<td>" . htmlspecialchars($schedule_time) . "</td>";
                        echo "<td>" . htmlspecialchars($row['schedule_status']) . "</td>";
                        echo "<td>";
                        echo "<a class='action-btn' href='#' onclick='openTeamModal(" . $row['id'] . ")'>View Team</a>";
                        if ($row['schedule_status'] !== 'cancelled' && $row['schedule_status'] !== 'done') {
                            echo "<a class='action-btn' href='#' onclick='openRescheduleModal(" . $row['id'] . ")'>Reschedule</a>";
                        }
                        if ($row['schedule_status'] !== 'cancelled' && $row['schedule_status'] !== 'done') {
                            echo "<a class='action-btn cancel' href='schedule_cancel_process.php?schedule_id=" . $row['id'] . "&college=" . $college_name . "'>Cancel</a>";
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

    <script>
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

            document.querySelectorAll('.tab').forEach(function(tab) {
                tab.classList.remove('active');
            });
            document.querySelector('.tab[onclick="filterTable(\'' + status + '\')"]').classList.add('active');
        }
    </script>
</body>

</html>