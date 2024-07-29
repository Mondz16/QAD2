<?php
include 'connection.php';

$user_id = $_GET['user_id'];

$sql = "SELECT first_name, middle_initial, last_name, email, profile_picture, status
        FROM internal_users
        WHERE user_id = ?";
        
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo '<h2>User Details</h2>';
    echo '<p>First Name: ' . htmlspecialchars($row['first_name']) . '</p>';
    echo '<p>Middle Initial: ' . htmlspecialchars($row['middle_initial']) . '</p>';
    echo '<p>Last Name: ' . htmlspecialchars($row['last_name']) . '</p>';
    echo '<p>Email: ' . htmlspecialchars($row['email']) . '</p>';
    echo '<p>Status: ' . htmlspecialchars($row['status']) . '</p>';
    echo '<img src="' . htmlspecialchars($row['profile_picture']) . '" alt="Profile Picture">';
} else {
    echo 'User not found.';
}

$stmt->close();
$conn->close();
?>
