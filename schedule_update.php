<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reschedule</title>
</head>
<body>
    <?php
    include 'connection.php';

    if (isset($_GET['schedule_id'])) {
        $schedule_id = intval($_GET['schedule_id']);

        $sql = "SELECT s.schedule_date, s.schedule_time, p.program 
                FROM schedule s
                JOIN program p ON s.program_id = p.id
                WHERE s.id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $schedule_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            ?>
            <h2>Reschedule for <?php echo htmlspecialchars($row['program']); ?></h2>
            <form action="schedule_update_process.php" method="post">
                <input type="hidden" name="schedule_id" value="<?php echo $schedule_id; ?>">
                <label for="schedule_date">New Schedule Date:</label>
                <input type="date" id="schedule_date" name="schedule_date" value="<?php echo htmlspecialchars($row['schedule_date']); ?>" required>
                <br>
                <label for="schedule_time">New Schedule Time:</label>
                <input type="time" id="schedule_time" name="schedule_time" value="<?php echo htmlspecialchars($row['schedule_time']); ?>" required>
                <br>
                <input type="submit" value="Reschedule">
            </form>
            <?php
        } else {
            echo "Schedule not found.";
        }
        $stmt->close();
    } else {
        echo "Invalid request.";
    }

    $conn->close();
    ?>
</body>
</html>
