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
    company_email VARCHAR(100) NOT NULL
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
    college_campus VARCHAR(50) NOT NULL,
    college_email VARCHAR(100) NOT NULL
)";

if ($conn->query($sql) === TRUE) {
    echo "Table college created successfully<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// Create the program table
$sql = "CREATE TABLE IF NOT EXISTS program (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    college_code VARCHAR(2),
    program_name VARCHAR(100) NOT NULL,
    program_level_id INT(6) UNSIGNED,
    FOREIGN KEY (college_code) REFERENCES college(code)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table program created successfully<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// Create the program_level_history table with year_of_validity nullable
$sql = "CREATE TABLE IF NOT EXISTS program_level_history (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    program_id INT(6) UNSIGNED NOT NULL,
    program_level VARCHAR(50) NOT NULL,
    date_received DATE NOT NULL,
    year_of_validity DATE NULL,
    FOREIGN KEY (program_id) REFERENCES program(id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table program_level_history created successfully<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// Create admin table
$sql = "CREATE TABLE IF NOT EXISTS admin (
    user_id VARCHAR(20) PRIMARY KEY,
    prefix VARCHAR(10) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    middle_initial VARCHAR(1) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    profile_picture VARCHAR(100) NOT NULL,
    gender VARCHAR(10) NOT NULL,
    password VARCHAR(255) NOT NULL,
    otp VARCHAR(50) NOT NULL,
    otp_created_at TIMESTAMP,
    date_added TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Table admin created successfully<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// Create internal_users table
$sql = "CREATE TABLE IF NOT EXISTS internal_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(15) UNIQUE,
    college_code VARCHAR(2),
    prefix VARCHAR(50) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    middle_initial VARCHAR(1) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    profile_picture VARCHAR(100) NOT NULL,
    gender VARCHAR(50) NOT NULL,
    status ENUM('pending', 'active', 'inactive', '') NOT NULL,
    e_sign_agreement VARCHAR(50) NOT NULL,
    otp VARCHAR(50) NOT NULL,
    otp_created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
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
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(15) UNIQUE,
    company_code VARCHAR(2),
    prefix VARCHAR(50) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    middle_initial VARCHAR(1) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    profile_picture VARCHAR(100) NOT NULL,
    gender VARCHAR(50) NOT NULL,
    status ENUM('pending', 'active', 'inactive', '') NOT NULL,
    otp VARCHAR(50) NOT NULL,
    otp_created_at TIMESTAMP,
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
    level_applied VARCHAR(10) NOT NULL,
    level_validity INT(1) NOT NULL,
    schedule_date DATE NOT NULL,
    schedule_time TIME NOT NULL,
    zoom VARCHAR(255),
    schedule_status ENUM('pending', 'approved', 'cancelled', 'finished', 'failed', 'passed') NOT NULL DEFAULT 'pending',
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
    area VARCHAR(100) NOT NULL,
    status ENUM('pending', 'accepted', 'declined', 'finished', 'cancelled') NOT NULL DEFAULT 'pending',
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
    area_evaluated VARCHAR(100) NOT NULL,
    findings VARCHAR(500) NOT NULL,
    recommendations VARCHAR(500) NOT NULL,
    evaluator VARCHAR(50) NOT NULL,
    evaluator_signature VARCHAR(255) NOT NULL,
    assessment_file VARCHAR(255) NOT NULL,
    FOREIGN KEY (team_id) REFERENCES team(id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table assessment created successfully<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// Create approved_assessment table
$sql = "CREATE TABLE IF NOT EXISTS approved_assessment (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    assessment_id INT(6) UNSIGNED,
    team_leader VARCHAR(50) NOT NULL,
    team_leader_signature VARCHAR(255) NOT NULL,
    approved_assessment_file VARCHAR(255) NOT NULL,
    FOREIGN KEY (assessment_id) REFERENCES assessment(id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table approved_assessment created successfully<br>";
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

// Create approved_summary table
$sql = "CREATE TABLE IF NOT EXISTS approved_summary (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    summary_id INT(6) UNSIGNED,
    qad VARCHAR(255) NOT NULL,
    qad_signature VARCHAR(255) NOT NULL,
    approved_summary_file VARCHAR(255) NOT NULL,
    FOREIGN KEY (summary_id) REFERENCES summary(id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table approved_summary created successfully<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// Create orientation table
$sql = "CREATE TABLE IF NOT EXISTS orientation (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    schedule_id INT(6) UNSIGNED,
    orientation_date DATE NOT NULL,
    orientation_time TIME NOT NULL,
    orientation_type VARCHAR(50) NOT NULL,
    orientation_status ENUM('pending', 'approved', 'denied', 'finished') NOT NULL DEFAULT 'pending',
    FOREIGN KEY (schedule_id) REFERENCES schedule(id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table orientation created successfully<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// Create face_to_face table
$sql = "CREATE TABLE IF NOT EXISTS face_to_face (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    orientation_id INT(6) UNSIGNED,
    college_building VARCHAR(50) NOT NULL,
    room_number VARCHAR(20) NOT NULL,
    FOREIGN KEY (orientation_id) REFERENCES orientation(id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table face_to_face created successfully<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// Create online table
$sql = "CREATE TABLE IF NOT EXISTS online (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    orientation_id INT(6) UNSIGNED,
    orientation_link VARCHAR(255) NOT NULL,
    link_passcode VARCHAR(255) NOT NULL,
    FOREIGN KEY (orientation_id) REFERENCES orientation(id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table online created successfully<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// Create udas_assessment table
$sql = "CREATE TABLE IF NOT EXISTS udas_assessment (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    schedule_id INT(6) UNSIGNED,
    area VARCHAR(100) NOT NULL,
    comments VARCHAR(500) NOT NULL,
    remarks VARCHAR(500) NOT NULL,
    udas_assessment_file VARCHAR(255) NOT NULL,
    submission_date VARCHAR(255) NOT NULL,
    qad_officer VARCHAR(255) NOT NULL,
    qad_officer_signature VARCHAR(255) NOT NULL,
    qad_director VARCHAR(255) NOT NULL,
    FOREIGN KEY (schedule_id) REFERENCES schedule(id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table udas_assessment created successfully<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

$sql = "CREATE TABLE IF NOT EXISTS NDA (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    team_id INT(6) UNSIGNED,
    date_added DATE NOT NULL,
    internal_accreditor VARCHAR(255) NOT NULL,
    internal_accreditor_signature VARCHAR(255) NOT NULL,
    NDA_file VARCHAR(255) NOT NULL,
    FOREIGN KEY (team_id) REFERENCES team(id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table NDA created successfully<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

$sql = "CREATE TABLE IF NOT EXISTS NDA_compilation (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    team_id INT(6) UNSIGNED,
    NDA_compilation_file VARCHAR(255) NOT NULL,
    FOREIGN KEY (team_id) REFERENCES team(id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table NDA_compilation created successfully<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// Create admin account if it doesn't exist
$sql_check_admin = "SELECT * FROM admin WHERE user_id = 'admin'";
$result_check_admin = $conn->query($sql_check_admin);

if ($result_check_admin->num_rows === 0) {
    $hashed_password = password_hash("admin", PASSWORD_DEFAULT);

    $sql_create_admin = "INSERT INTO admin (user_id, password, profile_picture) 
                         VALUES ('admin', '$hashed_password', 'Profile Pictures/placeholder.jpg')";

    if ($conn->query($sql_create_admin) === TRUE) {
        echo "Admin account created successfully<br>";
    } else {
        echo "Error creating admin account: " . $conn->error . "<br>";
    }
}

echo "Setup is successful!";
?>
