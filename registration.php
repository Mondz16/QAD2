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
    $sql = "";
    if ($tableName === 'internal_users') {
        $sql = "SELECT i.user_id, i.first_name, i.middle_initial, i.last_name, i.email, c.college_name
                FROM internal_users i
                LEFT JOIN college c ON i.college_id = c.id
                WHERE i.status = 'pending'";
    } elseif ($tableName === 'external_users') {
        $sql = "SELECT e.user_id, e.first_name, e.middle_initial, e.last_name, e.email, e.company
                FROM external_users e
                LEFT JOIN company c ON e.company_id = c.id
                WHERE e.status = 'pending'";
    }

    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        echo "<h2>$title</h2>";
        echo "<table border='1'>
            <tr>
                <th>ID</th>
                <th>First Name</th>
                <th>Middle Initial</th>
                <th>Last Name</th>
                <th>Email</th>";
        
        // Additional columns based on table type
        if ($tableName === 'internal_users') {
            echo "<th>College</th>";
        } elseif ($tableName === 'external_users') {
            echo "<th>Company</th>";
        }

        echo "<th>Actions</th>
            </tr>";

        while ($row = $result->fetch_assoc()) {
            echo "<tr>
                <td>{$row['user_id']}</td>
                <td>{$row['first_name']}</td>
                <td>{$row['middle_initial']}</td>
                <td>{$row['last_name']}</td>
                <td>{$row['email']}</td>";
            
            // Display additional column data based on table type
            if ($tableName === 'internal_users') {
                echo "<td>{$row['college_name']}</td>";
            } elseif ($tableName === 'external_users') {
                echo "<td>{$row['company']}</td>";
            }

            echo "<td>
                    <form action='registration_approval.php' method='post' style='display:inline;'>
                        <input type='hidden' name='id' value='{$row['user_id']}'>
                        <input type='hidden' name='action' value='approve'>
                        <input type='submit' value='Approve'>
                    </form>
                    <form action='registration_approval.php' method='post' style='display:inline;'>
                        <input type='hidden' name='id' value='{$row['user_id']}'>
                        <input type='hidden' name='action' value='deny'>
                        <input type='submit' value='Deny'>
                    </form>
                </td>
            </tr>";
        }

        echo "</table>";
    } else {
        echo "<p>No pending registrations for $title.</p>";
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
    displayRegistrations($conn, 'internal_users', 'Internal Pending Registrations');
    displayRegistrations($conn, 'external_users', 'External Pending Registrations');
    $conn->close();
    ?>
</div>
<div>
    <button onclick="window.location.href='admin.php'">Back to Admin Panel</button>
</div>
</body>
</html>
