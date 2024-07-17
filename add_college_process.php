<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "qadDB";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$college_name = $_POST['college_name'];
$college_email = $_POST['college_email'];
$programs = $_POST['programs'];
$levels = $_POST['levels'];
$dates_received = $_POST['dates_received'];

$sql_code = "SELECT MAX(college_code) AS max_code FROM college";
$result = $conn->query($sql_code);
$row = $result->fetch_assoc();
$max_code = $row['max_code'];

if ($max_code) {
    $next_code = str_pad(intval($max_code) + 1, 2, '0', STR_PAD_LEFT);
} else {
    $next_code = '01';
}

if (intval($next_code) > 15) {
    die("All college codes from 01 to 15 have been used.");
}

$sql = "INSERT INTO college (college_code, college_name, college_email) VALUES (?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sss", $next_code, $college_name, $college_email);

if ($stmt->execute()) {
    echo "College added successfully with code $next_code.<br>";

    $college_id = $stmt->insert_id;

    $sql_program = "INSERT INTO program (program, college_id, level, date_received) VALUES (?, ?, ?, ?)";
    $stmt_program = $conn->prepare($sql_program);

    for ($i = 0; $i < count($programs); $i++) {
        $program = $programs[$i];
        $level = $levels[$i];
        $date_received = $dates_received[$i];
        $stmt_program->bind_param("siss", $program, $college_id, $level, $date_received);
        
        if ($stmt_program->execute()) {
            echo "Program '$program' added successfully.<br>";
        } else {
            echo "Error adding program '$program': " . $conn->error . "<br>";
        }
    }
    
    $stmt_program->close();

} else {
    echo "Error adding college: " . $conn->error . "<br>";
}

echo '<br><button onclick="window.location.href=\'add_college.php\'">OK</button>';

$stmt->close();
$conn->close();
?>
