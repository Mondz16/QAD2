<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "scheduler";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$college = $_GET['college'];

$sql = "SELECT * FROM schedule WHERE college = '$college' AND status = 'pending'";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>Title</th><th>Start</th><th>End</th><th>Location</th><th>Actions</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['title'] . "</td>";
        echo "<td>" . $row['start'] . "</td>";
        echo "<td>" . $row['end'] . "</td>";
        echo "<td>" . $row['location'] . "</td>";
        echo "<td>
            <a class='action-btn' href='edit_schedule.php?id=" . $row['id'] . "'>Edit</a>
            <a class='action-btn cancel' href='cancel_schedule.php?id=" . $row['id'] . "'>Cancel</a>
            </td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No pending schedules found.";
}
$conn->close();
?>
