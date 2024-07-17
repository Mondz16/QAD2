<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule List</title>
    <style>
        table {
            width: 50%;
            margin-top: 20px;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid black;
            text-align: left;
            padding: 8px;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <h2>Schedule List</h2>
    <table>
        <tr>
            <th>College</th>
            <th>Total Schedules</th>
        </tr>
        <?php
        include 'connection.php';

        $sql = "SELECT c.college_name, COUNT(s.id) AS total_schedules 
                FROM college c 
                LEFT JOIN schedule s ON c.id = s.college_id 
                GROUP BY c.college_name 
                ORDER BY c.college_name";

        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td><a href='schedule_college.php?college=" . urlencode($row["college_name"]) . "'>" . $row["college_name"] . "</a></td>";
                echo "<td>" . $row["total_schedules"] . "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='2'>No colleges found</td></tr>";
        }

        $conn->close();
        ?>
    </table><br>
    <button onclick="location.href='add_schedule.php'">Add</button><br><br>
    <button onclick="location.href='admin.php'">Back</button>
</body>
</html>
