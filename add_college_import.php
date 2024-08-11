<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "qadDB";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to get the next college code
function getNextCollegeCode($conn)
{
    $sql_code = "SELECT MAX(CAST(code AS UNSIGNED)) AS max_code FROM college";
    $result_code = $conn->query($sql_code);
    $row_code = $result_code->fetch_assoc();
    $max_code = $row_code['max_code'];

    if ($max_code) {
        $next_code = str_pad(intval($max_code) + 1, 2, '0', STR_PAD_LEFT);
    } else {
        $next_code = '01';
    }

    if (intval($next_code) > 99) {
        die("<p class='error'>All codes from 01 to 99 have been used for colleges.</p>");
    }

    return $next_code;
}

// Function to convert Excel date to YYYY-MM-DD format
function excelDateToDate($excelDate)
{
    if (is_numeric($excelDate)) {
        $unixDate = ($excelDate - 25569) * 86400; // Convert Excel date to Unix timestamp
        return gmdate("Y-m-d", $unixDate);
    } else {
        return date("Y-m-d", strtotime($excelDate));
    }
}

$message = "";
$message_class = "";

if (isset($_FILES['excel_file']['name'])) {
    $fileName = $_FILES['excel_file']['tmp_name'];

    if ($_FILES['excel_file']['size'] > 0) {
        $spreadsheet = IOFactory::load($fileName);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        for ($row = 2; $row <= $highestRow; $row++) {
            // Extracting cell values
            $college_name = $sheet->getCellByColumnAndRow(1, $row)->getValue();
            $college_campus = $sheet->getCellByColumnAndRow(2, $row)->getValue();
            $college_email = $sheet->getCellByColumnAndRow(3, $row)->getValue();
            $program_name = $sheet->getCellByColumnAndRow(4, $row)->getValue();
            $program_level = $sheet->getCellByColumnAndRow(5, $row)->getValue();
            $date_received = $sheet->getCellByColumnAndRow(6, $row)->getValue();
            $date_received = excelDateToDate($date_received); // Convert date to YYYY-MM-DD format

            // Set program_level to 'N/A' if it is blank
            if (empty($program_level)) {
                $program_level = 'N/A';
            }

            // Check if the college already exists
            $sql_check = "SELECT code FROM college WHERE college_name = ?";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->bind_param("s", $college_name);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();

            if ($result_check->num_rows > 0) {
                // College name exists, use its college_code
                $row_check = $result_check->fetch_assoc();
                $college_code = $row_check['code'];
            } else {
                // College name does not exist, insert it into the table
                $college_code = getNextCollegeCode($conn);

                $sql_insert_college = "INSERT INTO college (code, college_name, college_campus, college_email) VALUES (?, ?, ?, ?)";
                $stmt_insert_college = $conn->prepare($sql_insert_college);
                $stmt_insert_college->bind_param("ssss", $college_code, $college_name, $college_campus, $college_email);
                $stmt_insert_college->execute();
            }
            // Insert Program into the program table
            $sql_insert_program = "INSERT INTO program (college_code, program_name) VALUES (?, ?)";
            $stmt_insert_program = $conn->prepare($sql_insert_program);
            $stmt_insert_program->bind_param("ss", $college_code, $program_name);
            $stmt_insert_program->execute();

            // Get the last inserted program ID
            $program_id = $conn->insert_id;

            // Insert Program Level History into the program_level_history table
            $sql_insert_program_level_history = "INSERT INTO program_level_history (program_id, program_level, date_received, year_of_validity) VALUES (?, ?, ?, ?)";
            $stmt_insert_program_level_history = $conn->prepare($sql_insert_program_level_history);
            $stmt_insert_program_level_history->bind_param("isss", $program_id, $program_level, $date_received, $year_of_validity);
            $stmt_insert_program_level_history->execute();

            // Get the last inserted program level history ID
            $program_level_id = $conn->insert_id;

            // Update the program table with the program_level_id
            $sql_update_program = "UPDATE program SET program_level_id = ? WHERE id = ?";
            $stmt_update_program = $conn->prepare($sql_update_program);
            $stmt_update_program->bind_param("ii", $program_level_id, $program_id);
            $stmt_update_program->execute();
        }

        $message = "Data imported successfully!";
        $message_class = "success";
    } else {
        $message = "File is empty.";
        $message_class = "error";
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Operation Result</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600&display=swap">
    <link rel="stylesheet" href="index.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Quicksand", sans-serif;
        }
        body {
            background-color: #f9f9f9;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }
        h2 {
            font-size: 24px;
            color: #292D32;
            margin-bottom: 20px;
        }

        .message {
            margin-bottom: 20px;
            font-size: 18px;
        }
        .success {
            color: green;
        }

        .error {
            color: red;
        }
        .btn-hover{
            border: 1px solid #AFAFAF;
            text-decoration: none;
            color: black;
            border-radius: 10px;
            padding: 20px 50px;
            font-size: 1rem;
            font-weight: bold;
            text-transform: uppercase;
        }
        .btn-hover:hover {
            background-color: #AFAFAF;
        }
    </style>
</head>

<body>
<div class="popup-content">
    <div style='height: 50px; width: 0px;'></div>
    <?php
        $image_src = ($message_class === "success") ? "images/Success.png" : "images/Error.png";
        ?>
        <img src="<?php echo $image_src; ?>" height="100" alt="<?php echo ucfirst($message_class); ?>">
    <div style="height: 25px; width: 0px;"></div>
    <div class="message <?php echo $message_class; ?>">
        <?php echo $message; ?>
    </div>
    <div style="height: 50px; width: 0px;"></div>
    <a href="college.php"class="btn-hover">OKAY</a>
    <div style='height: 100px; width: 0px;'></div>
    <div class='hairpop-up'></div>
</div>
</body>

</html>