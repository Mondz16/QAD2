<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

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

$message = "";
$message_class = "";

if (isset($_FILES['excel_file']['name'])) {
    $fileName = $_FILES['excel_file']['tmp_name'];

    if ($_FILES['excel_file']['size'] > 0) {
        $spreadsheet = IOFactory::load($fileName);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();

        for ($row = 2; $row <= $highestRow; $row++) {
            // Extracting cell values
            $area_name = $sheet->getCellByColumnAndRow(1, $row)->getValue();
            $area_parameters = $sheet->getCellByColumnAndRow(2, $row)->getValue();

            // Check if the area already exists
            $sql_check_area = "SELECT id FROM area WHERE area_name = ?";
            $stmt_check_area = $conn->prepare($sql_check_area);
            $stmt_check_area->bind_param("s", $area_name);
            $stmt_check_area->execute();
            $result_check_area = $stmt_check_area->get_result();

            if ($result_check_area->num_rows > 0) {
                // Area already exists, skip the insert
                continue; // Optionally, you could log this or notify the user
            }

            // Insert new area
            $sql_insert_area = "INSERT INTO area (area_name, area_parameters) VALUES (?, ?)";
            $stmt_insert_area = $conn->prepare($sql_insert_area);
            $stmt_insert_area->bind_param("ss", $area_name, $area_parameters);
            if (!$stmt_insert_area->execute()) {
                // Capture error if execution fails
                $message = "Error inserting area: " . $stmt_insert_area->error;
                $message_class = "error";
                break; // Stop further processing on error
            }
        }

        if ($message_class !== "error") {
            $message = "Data imported successfully!";
            $message_class = "success";
        }
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
    <title>Data Import</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600&display=swap">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Quicksand", sans-serif;
        }
        body {
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }
        .popup-content {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
        }
        .message {
            margin: 20px 0;
            font-size: 18px;
        }
        .success {
            color: green;
        }
        .error {
            color: red;
        }
        .btn-hover {
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
    <?php
        $image_src = ($message_class === "success") ? "images/Success.png" : "images/Error.png";
    ?>
    <img src="<?php echo $image_src; ?>" height="100" alt="<?php echo ucfirst($message_class); ?>">
    <div class="message <?php echo $message_class; ?>">
        <?php echo $message; ?>
    </div>
    <a href="area.php" class="btn-hover">OKAY</a>
</div>
</body>
</html>
