<?php
$servername = "localhost";
$username = "root";
$password = "";

$conn = new mysqli($servername, $username, $password);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$sql = "CREATE DATABASE IF NOT EXISTS qadDB";
if (!$conn->query($sql)) {
    die("Error creating database: " . $conn->error);
}

$conn->close();

include 'connection.php';

$sql = "CREATE TABLE IF NOT EXISTS company (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_code VARCHAR(255) NOT NULL,
    company_name VARCHAR(255) NOT NULL
)";

if ($conn->query($sql) === TRUE) {
    echo "Table company created successfully<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

$sql = "CREATE TABLE IF NOT EXISTS college (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    college_code VARCHAR(255) NOT NULL,
    college_name VARCHAR(255) NOT NULL
)";

if ($conn->query($sql) === TRUE) {
    echo "Table college created successfully<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

$sql = "CREATE TABLE IF NOT EXISTS program (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    college_id INT(6) UNSIGNED,
    program VARCHAR(255) NOT NULL,
    level VARCHAR(255) NOT NULL,
    date_received DATE NOT NULL,
    FOREIGN KEY (college_id) REFERENCES college(id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table program created successfully<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

$sql = "CREATE TABLE IF NOT EXISTS users (
    user_id VARCHAR(255) PRIMARY KEY,
    role VARCHAR(255) NOT NULL,
    first_name VARCHAR(255) NOT NULL,
    middle_initial VARCHAR(255) NOT NULL,
    last_name VARCHAR(255) NOT NULL,
    usep_email VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL
)";

if ($conn->query($sql) === TRUE) {
    echo "Table users created successfully<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

$sql = "CREATE TABLE IF NOT EXISTS internal_users (
    user_id VARCHAR(255) PRIMARY KEY,
    type VARCHAR(255) NOT NULL,
    first_name VARCHAR(255) NOT NULL,
    middle_initial VARCHAR(255) NOT NULL,
    last_name VARCHAR(255) NOT NULL,
    usep_email VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    college VARCHAR(255) NOT NULL,
    status ENUM('pending', 'approved', 'denied') NOT NULL DEFAULT 'pending'
)";

if ($conn->query($sql) === TRUE) {
    echo "Table internal_users created successfully<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

$sql = "CREATE TABLE IF NOT EXISTS external_users (
    user_id VARCHAR(255) PRIMARY KEY,
    type VARCHAR(255) NOT NULL,
    first_name VARCHAR(255) NOT NULL,
    middle_initial VARCHAR(255) NOT NULL,
    last_name VARCHAR(255) NOT NULL,
    usep_email VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    company VARCHAR(255) NOT NULL,
    status ENUM('pending', 'approved') NOT NULL DEFAULT 'pending'
)";

if ($conn->query($sql) === TRUE) {
    echo "Table external_users created successfully<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

$sql = "CREATE TABLE IF NOT EXISTS schedule (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    college VARCHAR(255) NOT NULL,
    program VARCHAR(255) NOT NULL,
    level_applied INT(6) NOT NULL,
    schedule_date DATE NOT NULL,
    schedule_time TIME NOT NULL
)";

if ($conn->query($sql) === TRUE) {
    echo "Table schedule created successfully<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

$sql = "CREATE TABLE IF NOT EXISTS team (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    schedule_id INT(6) UNSIGNED,
    fname VARCHAR(255) NOT NULL,
    mi VARCHAR(10) NOT NULL,
    lname VARCHAR(255) NOT NULL,
    role VARCHAR(255) NOT NULL,
    status ENUM('pending', 'accept', 'decline') NOT NULL DEFAULT 'pending',
    FOREIGN KEY (schedule_id) REFERENCES schedule(id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table team_members created successfully<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

$sql = "CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Table team_members created successfully<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

$sql_check_admin = "SELECT * FROM users WHERE user_id = 'admin'";
$result_check_admin = $conn->query($sql_check_admin);

if ($result_check_admin->num_rows === 0) {
    $hashed_password = password_hash("admin", PASSWORD_DEFAULT);

    $sql_create_admin = "INSERT INTO users (user_id, role, first_name, middle_initial, last_name, usep_email, password) 
                         VALUES ('admin', 'admin', 'Vience', 'A.', 'Banzali', 'admin@example.com', '$hashed_password')";

    if ($conn->query($sql_create_admin) === TRUE) {
        echo "Admin account created successfully<br>";
    } else {
        echo "Error creating admin account: " . $conn->error . "<br>";
    }
}
echo "Setup is successful!";
?>