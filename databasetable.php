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

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS qadDB";
if (!$conn->query($sql)) {
    die("Error creating database: " . $conn->error);
}

$conn->close();

// Reconnect to the newly created database
include 'connection.php';

// Create company table
$sql = "CREATE TABLE IF NOT EXISTS company (
    code VARCHAR(2) PRIMARY KEY,
    company_name VARCHAR(100) NOT NULL,
    company_email VARCHAR(255) NOT NULL
)";

if ($conn->query($sql) === TRUE) {
    echo "Table company created successfully<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// Create college table
$sql = "CREATE TABLE IF NOT EXISTS college (
    code VARCHAR(2) PRIMARY KEY,
    college_name VARCHAR(100) NOT NULL,
    college_campus VARCHAR(20) NOT NULL,
    college_email VARCHAR(255) NOT NULL
)";

if ($conn->query($sql) === TRUE) {
    echo "Table college created successfully<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// Create program table
$sql = "CREATE TABLE IF NOT EXISTS program (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    college_code VARCHAR(2),
    program_name VARCHAR(255) NOT NULL,
    program_level VARCHAR(255) NOT NULL,
    date_received DATE NOT NULL,
    FOREIGN KEY (college_code) REFERENCES college(code)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table program created successfully<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// Create admin table
$sql = "CREATE TABLE IF NOT EXISTS admin (
    user_id VARCHAR(255) PRIMARY KEY,
    password VARCHAR(255) NOT NULL
)";

if ($conn->query($sql) === TRUE) {
    echo "Table admin created successfully<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// Create internal_users table
$sql = "CREATE TABLE IF NOT EXISTS internal_users (
    user_id VARCHAR(10) PRIMARY KEY,
    college_code VARCHAR(2),
    first_name VARCHAR(50) NOT NULL,
    middle_initial VARCHAR(1) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password VARCHAR(128) NOT NULL,
    status ENUM('pending', 'active', 'inactive') NOT NULL DEFAULT 'pending',
    date_added TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (college_code) REFERENCES college(code)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table internal_users created successfully<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// Create external_users table
$sql = "CREATE TABLE IF NOT EXISTS external_users (
    user_id VARCHAR(10) PRIMARY KEY,
    company_code VARCHAR(2),
    first_name VARCHAR(50) NOT NULL,
    middle_initial VARCHAR(1) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password VARCHAR(128) NOT NULL,
    status ENUM('pending', 'active', 'inactive') NOT NULL DEFAULT 'pending',
    date_added TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_code) REFERENCES company(code)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table external_users created successfully<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// Create schedule table
$sql = "CREATE TABLE IF NOT EXISTS schedule (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    college_code VARCHAR(2),
    program_id INT(6) UNSIGNED,
<<<<<<< Updated upstream
    level_applied VARCHAR(10) NOT NULL,
=======
    level_applied INT(6) NOT NULL,
>>>>>>> Stashed changes
    schedule_date DATE NOT NULL,
    schedule_time TIME NOT NULL,
    schedule_status ENUM('pending', 'approved', 'cancelled') NOT NULL DEFAULT 'pending',
    status_date DATETIME NOT NULL,
    FOREIGN KEY (college_code) REFERENCES college(code),
    FOREIGN KEY (program_id) REFERENCES program(id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table schedule created successfully<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// Create team table
$sql = "CREATE TABLE IF NOT EXISTS team (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    schedule_id INT(6) UNSIGNED,
    internal_users_id VARCHAR(10) NOT NULL,
    role VARCHAR(11) NOT NULL,
    area VARCHAR(255) NOT NULL,
    status ENUM('pending', 'accepted', 'declined') NOT NULL DEFAULT 'pending',
    FOREIGN KEY (schedule_id) REFERENCES schedule(id),
    FOREIGN KEY (internal_users_id) REFERENCES internal_users(user_id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table team created successfully<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// Create assessment table
$sql = "CREATE TABLE IF NOT EXISTS assessment (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    team_id INT(6) UNSIGNED,
    result ENUM('Ready', 'Needs improvement', 'Revisit') NOT NULL,
    area_evaluated VARCHAR(255) NOT NULL,
    findings VARCHAR(255) NOT NULL,
    recommendations VARCHAR(255) NOT NULL,
    evaluator VARCHAR(255) NOT NULL,
    evaluator_signature VARCHAR(255) NOT NULL,
    assessment_file VARCHAR(255) NOT NULL,
    FOREIGN KEY (team_id) REFERENCES team(id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table assessment created successfully<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// Create summary table
$sql = "CREATE TABLE IF NOT EXISTS summary (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    team_id INT(6) UNSIGNED,
    areas VARCHAR(255) NOT NULL,
    results VARCHAR(10) NOT NULL,
    evaluator VARCHAR(255) NOT NULL,
    evaluator_signature VARCHAR(255) NOT NULL,
    summary_file VARCHAR(255) NOT NULL,
    FOREIGN KEY (team_id) REFERENCES team(id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table summary created successfully<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// Create admin account if it doesn't exist
$sql_check_admin = "SELECT * FROM admin WHERE user_id = 'admin'";
$result_check_admin = $conn->query($sql_check_admin);

if ($result_check_admin->num_rows === 0) {
    $hashed_password = password_hash("admin", PASSWORD_DEFAULT);

    $sql_create_admin = "INSERT INTO admin (user_id, password) 
                         VALUES ('admin', '$hashed_password')";

    if ($conn->query($sql_create_admin) === TRUE) {
        echo "Admin account created successfully<br>";
    } else {
        echo "Error creating admin account: " . $conn->error . "<br>";
    }
}

echo "Setup is successful!";
?>
