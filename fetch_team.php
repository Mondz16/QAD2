<?php
include 'connection.php';

$schedule_id = intval($_GET['schedule_id']);

$sql = "SELECT iu.first_name, iu.middle_initial, iu.last_name, t.role, t.status
        FROM team t
        JOIN internal_users iu ON t.internal_users_id = iu.user_id
        WHERE t.schedule_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $schedule_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "
    <style>
        .modal-table {
            width: 100%;
            border-collapse: collapse;
        }

        .modal-table th, .modal-table td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
        }

        .modal-table th {
            background-color: #f2f2f2;
        }
    </style>
    <table class='modal-table'>
        <tr>
            <th>First Name</th>
            <th>Middle Initial</th>
            <th>Last Name</th>
            <th>Role</th>
            <th>Status</th>
        </tr>";
    while ($row = $result->fetch_assoc()) {
        $status = htmlspecialchars($row['status']);
        $bgColor = '';
        switch ($status) {
            case 'pending':
                $bgColor = 'background-color: orange;';
                break;
            case 'accepted':
                $bgColor = 'background-color: #1fd655;';
                break;
            case 'declined':
                $bgColor = 'background-color: red;';
                break;
        }
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['first_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['middle_initial']) . "</td>";
        echo "<td>" . htmlspecialchars($row['last_name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['role']) . "</td>";
        echo "<td style='$bgColor color: white;'>" . $status . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No team members found for this schedule.";
}

$stmt->close();
$conn->close();
