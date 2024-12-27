<?php
include 'connection.php';

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
    company_name VARCHAR(60) NOT NULL,
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
    college_campus VARCHAR(20) NOT NULL,
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
    program_name VARCHAR(255) NOT NULL,
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
    program_level VARCHAR(20) NOT NULL,
    date_received DATE NOT NULL,
    year_of_validity DATE NULL,
    FOREIGN KEY (program_id) REFERENCES program(id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table program_level_history created successfully<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// Create the area table
$sql = "CREATE TABLE IF NOT EXISTS area (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    area_name VARCHAR(50) NOT NULL
)";

if ($conn->query($sql) === TRUE) {
    echo "Table area created successfully<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// Create the parameters table
$sql = "CREATE TABLE IF NOT EXISTS parameters (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    area_id INT(6) UNSIGNED NOT NULL,
    parameter_name VARCHAR(80) NOT NULL,
    parameter_description VARCHAR(255) NOT NULL,
    FOREIGN KEY (area_id) REFERENCES area(id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table parameters created successfully<br>";
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
    profile_picture VARCHAR(50) NOT NULL,
    gender VARCHAR(10) NOT NULL,
    password VARCHAR(255) NOT NULL,
    otp VARCHAR(255) NOT NULL,
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
    prefix VARCHAR(20) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    middle_initial VARCHAR(1) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    profile_picture VARCHAR(50) NOT NULL,
    gender VARCHAR(50) NOT NULL,
    status ENUM('pending', 'active', 'inactive', '') NOT NULL,
    e_sign_agreement VARCHAR(50) NOT NULL,
    otp VARCHAR(255) NOT NULL,
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
    prefix VARCHAR(20) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    middle_initial VARCHAR(1) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    profile_picture VARCHAR(50) NOT NULL,
    gender VARCHAR(50) NOT NULL,
    status ENUM('pending', 'active', 'inactive', '') NOT NULL,
    otp VARCHAR(255) NOT NULL,
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
    zoom VARCHAR(50),
    schedule_status ENUM('pending', 'approved', 'cancelled', 'finished', 'failed', 'passed', 'done') NOT NULL DEFAULT 'pending',
    manually_unlocked TINYINT(1) DEFAULT 0,
    unlock_expiration DATETIME NULL,
    status_date DATETIME NOT NULL,
    reschedule_count INT(2) DEFAULT 0,
    FOREIGN KEY (college_code) REFERENCES college(code),
    FOREIGN KEY (program_id) REFERENCES program(id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table schedule created successfully<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// Create or modify the team table to include the area_rating_file column
$sql = "CREATE TABLE IF NOT EXISTS team (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    schedule_id INT(6) UNSIGNED,
    internal_users_id VARCHAR(10) NOT NULL,
    role VARCHAR(11) NOT NULL,
    status ENUM('pending', 'accepted', 'declined', 'finished', 'cancelled') NOT NULL DEFAULT 'pending',
    area_rating_file VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (schedule_id) REFERENCES schedule(id),
    FOREIGN KEY (internal_users_id) REFERENCES internal_users(user_id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table team created/modified successfully<br>";
} else {
    echo "Error creating/modifying table: " . $conn->error . "<br>";
}


// Create team_areas table
$sql = "CREATE TABLE IF NOT EXISTS team_areas (
    team_id INT(6) UNSIGNED,
    area_id INT(6) UNSIGNED,
    rating DECIMAL(4,2) UNSIGNED,
    FOREIGN KEY (team_id) REFERENCES team(id),
    FOREIGN KEY (area_id) REFERENCES area(id),
    PRIMARY KEY (team_id, area_id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table team_areas created successfully<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// Create assessment table
$sql = "CREATE TABLE IF NOT EXISTS assessment (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    team_id INT(6) UNSIGNED,
    result VARCHAR(80) NOT NULL,
    area_evaluated VARCHAR(80) NOT NULL,
    findings VARCHAR(100) NOT NULL,
    recommendations VARCHAR(100) NOT NULL,
    evaluator VARCHAR(50) NOT NULL,
    evaluator_signature VARCHAR(50) NOT NULL,
    assessment_file VARCHAR(50) NOT NULL,
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
    team_leader_signature VARCHAR(50) NOT NULL,
    approved_assessment_file VARCHAR(50) NOT NULL,
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
    evaluator VARCHAR(50) NOT NULL,
    evaluator_signature VARCHAR(50) NOT NULL,
    summary_file VARCHAR(50) NOT NULL,
    summary_compilation_file VARCHAR(50) NOT NULL,
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
    qad VARCHAR(50) NOT NULL,
    qad_signature VARCHAR(50) NOT NULL,
    approved_summary_file VARCHAR(50) NOT NULL,
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
    room_number VARCHAR(3) NOT NULL,
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
    orientation_link VARCHAR(50) NOT NULL,
    link_passcode VARCHAR(50) NOT NULL,
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
    area VARCHAR(80) NOT NULL,
    comments VARCHAR(100) NOT NULL,
    remarks VARCHAR(100) NOT NULL,
    udas_assessment_file VARCHAR(50) NOT NULL,
    submission_date VARCHAR(10) NOT NULL,
    qad_officer VARCHAR(50) NOT NULL,
    qad_officer_signature VARCHAR(50) NOT NULL,
    qad_director VARCHAR(50) NOT NULL,
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
    internal_accreditor VARCHAR(50) NOT NULL,
    internal_accreditor_signature VARCHAR(50) NOT NULL,
    NDA_file VARCHAR(50) NOT NULL,
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
    NDA_compilation_file VARCHAR(50) NOT NULL,
    FOREIGN KEY (team_id) REFERENCES team(id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table NDA_compilation created successfully<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// SQL to create accreditation_standard table
$sql = "CREATE TABLE IF NOT EXISTS accreditation_standard (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    Level VARCHAR(3) NOT NULL,
    Standard DECIMAL(3, 2) NOT NULL
)";

if ($conn->query($sql) === TRUE) {
    echo "Table accreditation_standard created successfully<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// Insert data into accreditation_standard table
$sql = "INSERT INTO accreditation_standard (Level, Standard) VALUES
    ('PSV', 1.00),
    ('1', 3.00),
    ('2', 3.50),
    ('3', 4.00),
    ('4', 4.50)";

// Execute the insert query
if ($conn->query($sql) === TRUE) {
    echo "Data inserted successfully into accreditation_standard table<br>";
} else {
    echo "Error inserting data: " . $conn->error . "<br>";
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
