<?php
$servername = "localhost";
$username = "root";
$password = "";

// Create connection
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Select database
$conn->select_db('qadDB');

// Modify team table
$sql = "ALTER TABLE team MODIFY COLUMN status ENUM('pending', 'accepted', 'declined', 'finished') NOT NULL DEFAULT 'pending';";
if ($conn->query($sql) === TRUE) {
    echo "Table team modified successfully<br>";
} else {
    echo "Error modifying table team: " . $conn->error . "<br>";
}

// Add profile_picture column to internal_users table
$sql = "ALTER TABLE internal_users ADD COLUMN profile_picture VARCHAR(255) NOT NULL;";
if ($conn->query($sql) === TRUE) {
    echo "Column profile_picture added to internal_users table successfully<br>";
} else {
    echo "Error adding column to internal_users table: " . $conn->error . "<br>";
}

// Add profile_picture column to external_users table
$sql = "ALTER TABLE external_users ADD COLUMN profile_picture VARCHAR(255) NOT NULL;";
if ($conn->query($sql) === TRUE) {
    echo "Column profile_picture added to external_users table successfully<br>";
} else {
    echo "Error adding column to external_users table: " . $conn->error . "<br>";
}

// Modify code column in college table
$sql = "ALTER TABLE college MODIFY COLUMN code VARCHAR(2) PRIMARY KEY;";
if ($conn->query($sql) === TRUE) {
    echo "Column code in college table modified successfully<br>";
} else {
    echo "Error modifying column in college table: " . $conn->error . "<br>";
}

$conn->close();
?>
