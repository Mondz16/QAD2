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

function displayRegistrations($conn, $tableName, $title) {
    $sql = "SELECT * FROM $tableName";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        echo "<h2>$title</h2>";
        echo "<table border='1'>
            <tr>
                <th>ID</th>
                <th>Internal/External</th>
                <th>First Name</th>
                <th>Middle Initial</th>
                <th>Last Name</th>
                <th>Email</th>
                <th>College/Company</th>
                <th>Actions</th>
            </tr>";

        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                <td>{$row['id']}</td>
                <td>{$row['type']}</td>
                <td>{$row['first_name']}</td>
                <td>{$row['middle_initial']}</td>
                <td>{$row['last_name']}</td>
                <td>{$row['usep_email']}</td>
                <td>" . (isset($row['college']) ? $row['college'] : $row['company']) . "</td>
                <td>
                    <form action='registration_approval.php' method='post' style='display:inline;'>
                        <input type='hidden' name='id' value='{$row['id']}'>
                        <input type='hidden' name='action' value='approve'>
                        <input type='submit' value='Approve'>
                    </form>
                    <form action='registration_approval.php' method='post' style='display:inline;'>
                        <input type='hidden' name='id' value='{$row['id']}'>
                        <input type='hidden' name='action' value='reject'>
                        <input type='submit' value='Reject'>
                    </form>
                </td>
            </tr>";
        }

        echo "</table>";
    } else {
        echo "<p>No pending registrations.</p>";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Registrations</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="admin-content">
    <?php
    displayRegistrations($conn, 'internal_pending_registrations', 'Internal Pending Registrations');
    displayRegistrations($conn, 'external_pending_registrations', 'External Pending Registrations');
    $conn->close();
    ?>
</div>
<div>
    <button onclick="window.location.href='admin.php'">Back to Admin Panel</button>
</div>
</body>
</html>