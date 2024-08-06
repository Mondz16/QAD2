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
            $year_of_validity = $sheet->getCellByColumnAndRow(7, $row)->getValue();

            // Set program_level to 'N/A' if it is blank
            if (empty($program_level)) {
                $program_level = 'N/A';
            }

            // Check if the college already exists
            $sql_check_college = "SELECT code FROM college WHERE college_name = ?";
            $stmt_check_college = $conn->prepare($sql_check_college);
            $stmt_check_college->bind_param("s", $college_name);
            $stmt_check_college->execute();
            $result_check_college = $stmt_check_college->get_result();

            if ($result_check_college->num_rows > 0) {
                // College name exists, use its college_code
                $row_check_college = $result_check_college->fetch_assoc();
                $college_code = $row_check_college['code'];
            } else {
                // College name does not exist, insert it into the table
                $college_code = getNextCollegeCode($conn);

                $sql_insert_college = "INSERT INTO college (code, college_name, college_campus, college_email) VALUES (?, ?, ?, ?)";
                $stmt_insert_college = $conn->prepare($sql_insert_college);
                $stmt_insert_college->bind_param("ssss", $college_code, $college_name, $college_campus, $college_email);
                $stmt_insert_college->execute();
            }

            // Check if the program already exists for the college
            $sql_check_program = "SELECT id, program_level_id FROM program WHERE college_code = ? AND program_name = ?";
            $stmt_check_program = $conn->prepare($sql_check_program);
            $stmt_check_program->bind_param("ss", $college_code, $program_name);
            $stmt_check_program->execute();
            $result_check_program = $stmt_check_program->get_result();

            if ($result_check_program->num_rows > 0) {
                // Program already exists, use its ID
                $row_check_program = $result_check_program->fetch_assoc();
                $program_id = $row_check_program['id'];
                
                // Check if the program level is different
                $sql_check_program_level = "SELECT program_level FROM program_level_history WHERE id = ?";
                $stmt_check_program_level = $conn->prepare($sql_check_program_level);
                $stmt_check_program_level->bind_param("i", $row_check_program['program_level_id']);
                $stmt_check_program_level->execute();
                $result_check_program_level = $stmt_check_program_level->get_result();

                if ($result_check_program_level->num_rows > 0) {
                    $row_check_program_level = $result_check_program_level->fetch_assoc();
                    $existing_program_level = $row_check_program_level['program_level'];

                    if ($existing_program_level !== $program_level) {
                        // Insert new program level history
                        $sql_insert_program_level_history = "INSERT INTO program_level_history (program_id, program_level, date_received, year_of_validity) VALUES (?, ?, ?, ?)";
                        $stmt_insert_program_level_history = $conn->prepare($sql_insert_program_level_history);
                        $stmt_insert_program_level_history->bind_param("isss", $program_id, $program_level, $date_received, $year_of_validity);
                        $stmt_insert_program_level_history->execute();

                        // Update the program with the new program level ID
                        $program_level_id = $conn->insert_id;
                        $sql_update_program = "UPDATE program SET program_level_id = ? WHERE id = ?";
                        $stmt_update_program = $conn->prepare($sql_update_program);
                        $stmt_update_program->bind_param("ii", $program_level_id, $program_id);
                        $stmt_update_program->execute();
                    }
                }
            } else {
                // Insert new program
                $sql_insert_program = "INSERT INTO program (college_code, program_name) VALUES (?, ?)";
                $stmt_insert_program = $conn->prepare($sql_insert_program);
                $stmt_insert_program->bind_param("ss", $college_code, $program_name);
                $stmt_insert_program->execute();

                // Get the last inserted program ID
                $program_id = $conn->insert_id;

                // Insert program level history
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

        .container {
            max-width: 750px;
            padding: 24px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        h2 {
            font-size: 24px;
            color: #973939;
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

        .button-primary {
            background-color: #2cb84f;
            color: #fff;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 4px;
            margin-top: 10px;
            color: white;
            font-size: 16px;
        }

        .button-primary:hover {
            background-color: #259b42;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>Operation Result</h2>
        <div class="message <?php echo $message_class; ?>">
            <?php echo $message; ?>
        </div>
        <button class="button-primary" onclick="window.location.href='college.php'">OK</button>
    </div>
</body>

</html>
