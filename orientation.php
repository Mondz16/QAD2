<?php
session_start();

$servername = "localhost";
$db_username = "root";
$db_password = "";
$dbname = "qadDB";

$conn = new mysqli($servername, $db_username, $db_password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function displayOrientationDetails($conn, $orientationType, $title)
{
    $sql = "SELECT o.id, o.orientation_date, o.orientation_time, 
            IF(o.orientation_type = 'online', ol.orientation_link, f2f.college_building) AS location,
            IF(o.orientation_type = 'online', ol.link_passcode, f2f.room_number) AS additional_info,
            o.schedule_id
            FROM orientation o
            LEFT JOIN online ol ON o.id = ol.orientation_id
            LEFT JOIN face_to_face f2f ON o.id = f2f.orientation_id
            WHERE o.orientation_type = '$orientationType' AND o.orientation_status = 'pending'";

    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        echo "<h2 class='table-title'>$title</h2>";
        echo "<table class='data-table'>
            <tr>
                <th>ID</th>
                <th>Date</th>
                <th>Time</th>";

        if ($orientationType === 'online') {
            echo "<th>Link</th>
                  <th>Passcode</th>";
        } elseif ($orientationType === 'face_to_face') {
            echo "<th>Building</th>
                  <th>Room Number</th>";
        }

        echo "<th>Actions</th>
            </tr>";

        while ($row = $result->fetch_assoc()) {
            $formatted_date = date("F j, Y", strtotime($row['orientation_date']));
            $formatted_time = date("g:i A", strtotime($row['orientation_time']));
            echo "<tr>
                <td>{$row['id']}</td>
                <td>{$formatted_date}</td>
                <td>{$formatted_time}</td>";

            if ($orientationType === 'online') {
                echo "<td>{$row['location']}</td>
                      <td>{$row['additional_info']}</td>";
            } elseif ($orientationType === 'face_to_face') {
                echo "<td>{$row['location']}</td>
                      <td>{$row['additional_info']}</td>";
            }

            echo "<td class='action-buttons'>
                    <button class='btn approve' onclick='openApproveModal({$row['id']})'>Approve</button>
                    <button class='btn deny' onclick='openDenyModal({$row['id']})'>Deny</button>
                    <button class='btn view' onclick='viewSchedule({$row['schedule_id']})'>View Schedule</button>
                </td>
            </tr>";
        }

        echo "</table>";
    } else {
        echo "<p>No pending orientations for $title.</p>";
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Orientations</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600&display=swap">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Quicksand", sans-serif;
        }

        body {
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

        .admin-content {
            max-width: 1200px;
            width: 100%;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }

        .tab-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .tabheader {
            text-align: center;
            margin-bottom: 20px;
        }

        .tabs {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .tab {
            flex: 1;
            text-align: center;
            padding: 10px;
            cursor: pointer;
            background-color: #f2f2f2;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
        }

        .tab.active {
            background-color: #fff;
            font-weight: bold;
            border-bottom: 2px solid #973939;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .table-title {
            font-size: 24px;
            color: #973939;
            margin-bottom: 20px;
            text-align: center;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .data-table th,
        .data-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .data-table th {
            background-color: #f2f2f2;
        }

        .data-table tr:hover {
            background-color: #f1f1f1;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }

        .btn.approve {
            background-color: #2cb84f;
            color: white;
        }

        .btn.deny {
            background-color: #e74c3c;
            color: white;
        }

        .btn.view {
            background-color: #3498db;
            color: white;
        }

        .btn:hover {
            opacity: 0.9;
        }

        .back-button {
            background-color: #e74c3c;
            color: white;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 4px;
            font-size: 16px;
        }

        .back-button:hover {
            opacity: 0.9;
        }

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

        .modal-buttons textarea {
            width: 100%;
            padding: 10px;
            margin-top: 20px;
            border-radius: 5px;
            border: 1px solid #ddd;
            resize: none;
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <div class="hair" style="height: 15px; background: linear-gradient(275.52deg, #973939 0.28%, #DC7171 100%);"></div>

        <div class="container">
            <div class="header">
                <div class="headerLeft">
                    <div class=USePData>
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
    <div class="tab-container">
        <div class="admin-content">
            <h1 class="tabheader">Pending Orientations</h1>
            <div class="tabs">
                <div class="tab active" data-tab="online">Online</div>
                <div class="tab" data-tab="face_to_face">Face to Face</div>
            </div>
            <div class="tab-content active" id="online">
                <?php displayOrientationDetails($conn, 'online', 'Online Pending Orientations'); ?>
            </div>
            <div class="tab-content" id="face_to_face">
                <?php displayOrientationDetails($conn, 'face_to_face', 'Face to Face Pending Orientations'); ?>
            </div>
        </div>
        <div>
            <button class="back-button" onclick="window.location.href='admin.php'">Back to Admin Panel</button>
        </div>
    </div>

    <!-- Approve Modal -->
    <div id="approveModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeApproveModal()">&times;</span>
            <h2>Are you sure you want to approve this orientation request?</h2>
            <div class="modal-buttons">
                <button class="yes-btn" id="confirmApproveBtn">Yes</button>
                <button class="no-btn" onclick="closeApproveModal()">No</button>
            </div>
        </div>
    </div>

    <!-- Deny Modal -->
    <div id="denyModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeDenyModal()">&times;</span>
            <h2>Are you sure you want to deny this orientation request?</h2>
            <textarea rows="3" cols="52" id="denyReason" placeholder="Enter reason for denial"></textarea>
            <div class="modal-buttons">
                <button class="yes-btn" id="confirmDenyBtn">Yes</button>
                <button class="no-btn" onclick="closeDenyModal()">No</button>
            </div>
        </div>
    </div>

    <!-- Schedule Modal -->
    <div id="scheduleModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeScheduleModal()">&times;</span>
            <h2>Schedule Details</h2>
            <div id="scheduleContent"></div>
            <div class="modal-footer">
                <button class="close-btn" onclick="closeScheduleModal()">Close</button>
            </div>
        </div>
    </div>

    <form id="approveForm" action="orientation_approval.php" method="post" style="display: none;">
        <input type="hidden" name="id" id="approveOrientationId">
        <input type="hidden" name="action" value="approve">
    </form>

    <form id="denyForm" action="orientation_approval.php" method="post" style="display: none;">
        <input type="hidden" name="id" id="denyOrientationId">
        <input type="hidden" name="action" value="deny">
        <input type="hidden" name="reason" id="denyReasonInput">
    </form>

    <script>
        let approveOrientationId;
        let denyOrientationId;

        function openApproveModal(id) {
            approveOrientationId = id;
            document.getElementById('approveModal').style.display = 'block';
        }

        function closeApproveModal() {
            document.getElementById('approveModal').style.display = 'none';
        }

        function openDenyModal(id) {
            denyOrientationId = id;
            document.getElementById('denyModal').style.display = 'block';
        }

        function closeDenyModal() {
            document.getElementById('denyModal').style.display = 'none';
        }

        function viewSchedule(scheduleId) {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', 'get_schedule.php?schedule_id=' + scheduleId, true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    document.getElementById('scheduleContent').innerHTML = xhr.responseText;
                    document.getElementById('scheduleModal').style.display = 'block';
                }
            };
            xhr.send();
        }

        function closeScheduleModal() {
            document.getElementById('scheduleModal').style.display = 'none';
        }

        document.getElementById('confirmApproveBtn').addEventListener('click', function() {
            document.getElementById('approveOrientationId').value = approveOrientationId;
            document.getElementById('approveForm').submit();
        });

        document.getElementById('confirmDenyBtn').addEventListener('click', function() {
            const reason = document.getElementById('denyReason').value;
            if (reason.trim() === "") {
                alert("Please provide a reason for denial.");
                return;
            }
            document.getElementById('denyOrientationId').value = denyOrientationId;
            document.getElementById('denyReasonInput').value = reason;
            document.getElementById('denyForm').submit();
        });

        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('.tab');
            const tabContents = document.querySelectorAll('.tab-content');

            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    const target = tab.getAttribute('data-tab');

                    tabs.forEach(t => t.classList.remove('active'));
                    tab.classList.add('active');

                    tabContents.forEach(content => {
                        if (content.id === target) {
                            content.classList.add('active');
                        } else {
                            content.classList.remove('active');
                        }
                    });
                });
            });
        });
    </script>
</body>

</html>

<?php
$conn->close();
?>
