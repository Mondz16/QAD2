<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Details</title>
    <style>
        table {
            width: 100%;
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
    <h2>Schedule Details for <?php echo htmlspecialchars($_GET['college']); ?></h2>
    <table>
        <tr>
            <th>Program</th>
            <th>Level Applied</th>
            <th>Schedule Date</th>
            <th>Schedule Time</th>
        </tr>
        <?php
        include 'connection.php';

        $college_name = urldecode($_GET['college']);
        $sql = "SELECT s.id, p.program, s.level_applied, s.schedule_date, s.schedule_time
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
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['program']) . "</td>";
                echo "<td>" . htmlspecialchars($row['level_applied']) . "</td>";
                echo "<td>" . htmlspecialchars($row['schedule_date']) . "</td>";
                echo "<td>" . htmlspecialchars($row['schedule_time']) . "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='4'>No schedules found for this college</td></tr>";
        }

        $stmt->close();
        $conn->close();
        ?>
    </table><br>
    <button onclick="location.href='schedule.php'">Back</button>
</body>
</html>