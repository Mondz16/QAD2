<?php
include 'connection.php';

// Check if schedule_id is provided via GET parameter
if (isset($_GET['schedule_id'])) {
    $schedule_id = mysqli_real_escape_string($conn, $_GET['schedule_id']);

    // Query to retrieve schedule details and college information
    $sql = "SELECT s.program, s.schedule_date, s.schedule_time, s.college,
                   t.fname AS leader_fname, t.mi AS leader_mi, t.lname AS leader_lname,
                   tm.fname AS member_fname, tm.mi AS member_mi, tm.lname AS member_lname
            FROM schedule s
            LEFT JOIN team t ON s.id = t.schedule_id AND t.role = 'team leader'
            LEFT JOIN team tm ON s.id = tm.schedule_id AND tm.role = 'team member'
            WHERE s.id = '$schedule_id'";

    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $program = $row['program'];
        $schedule_date = $row['schedule_date'];
        $schedule_time = $row['schedule_time'];
        $college = $row['college'];

        // Extract team leader details
        $team_leader = $row['leader_fname'] . " " . $row['leader_mi'] . " " . $row['leader_lname'];

        // Extract team members details
        $team_members = [];
        if ($row['member_fname'] != null) {
            do {
                $team_members[] = $row['member_fname'] . " " . $row['member_mi'] . " " . $row['member_lname'];
            } while ($row = $result->fetch_assoc());
        }

        // Display the schedule details and form for rescheduling
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Reschedule Schedule ID: <?php echo $schedule_id; ?></title>
        </head>
        <body>
            <h2>Reschedule Schedule ID: <?php echo $schedule_id; ?></h2>
            <p><strong>Program:</strong> <?php echo $program; ?></p>
            <p><strong>Date:</strong> <?php echo $schedule_date; ?></p>
            <p><strong>Time:</strong> <?php echo $schedule_time; ?></p>
            <p><strong>College:</strong> <?php echo $college; ?></p>
            <p><strong>Team Leader:</strong> <?php echo $team_leader; ?></p>
            <p><strong>Team Members:</strong><br>
            <?php 
            foreach ($team_members as $member) {
                echo "- " . $member . "<br>";
            }
            ?>
            </p>
            <form action="schedule_update.php" method="post">
                <input type="hidden" name="schedule_id" value="<?php echo $schedule_id; ?>">
                <label for="schedule_date">New Date:</label>
                <input type="date" id="schedule_date" name="schedule_date" value="<?php echo $schedule_date; ?>"><br><br>
                <label for="schedule_time">New Time:</label>
                <input type="time" id="schedule_time" name="schedule_time" value="<?php echo $schedule_time; ?>"><br><br>
                <input type="submit" value="Update Schedule">
            </form>
            <br>
            <a href="schedule_college.php?college=<?php echo urlencode($college); ?>">Cancel</a>
        </body>
        </html>
        <?php
    } else {
        echo "No schedule found with ID: $schedule_id";
    }
} else {
    echo "Error: Schedule ID parameter not specified";
}

$conn->close();
?>
