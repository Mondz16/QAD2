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
        <div class="message">
            <?php
            $servername = "localhost";
            $username = "root";
            $password = "";
            $dbname = "qadDB";

            $conn = new mysqli($servername, $username, $password, $dbname);

            if ($conn->connect_error) {
                die("<p class='error'>Connection failed: " . $conn->connect_error . "</p>");
            }

            $college_name = $_POST['college_name'];
            $college_campus = $_POST['college_campus'];
            $college_email = $_POST['college_email'];
            $programs = $_POST['programs'];
            $levels = $_POST['levels'];
            $dates_received = $_POST['dates_received'];

            // Check if the college_name already exists
            $sql_check = "SELECT code FROM college WHERE college_name = ?";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->bind_param("s", $college_name);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();

            $program_success = true;

            if ($result_check->num_rows > 0) {
                // College name exists, use its college_id and college_code
                $row_check = $result_check->fetch_assoc();
                $college_code = $row_check['code'];
                echo "<p class='success'>College already exists. Program(s) added successfully.</p>";
            } else {
                // College name does not exist, insert it into the table
                $sql_code = "SELECT MAX(code) AS max_code FROM college";
                $result_code = $conn->query($sql_code);
                $row_code = $result_code->fetch_assoc();
                $max_code = $row_code['max_code'];

                if ($max_code) {
                    $next_code = str_pad(intval($max_code) + 1, 2, '0', STR_PAD_LEFT);
                } else {
                    $next_code = '01';
                }

                if (intval($next_code) > 15) {
                    die("<p class='error'>All college codes from 01 to 15 have been used.</p>");
                }

                $sql_insert_college = "INSERT INTO college (code, college_name, college_campus, college_email) VALUES (?, ?, ?, ?)";
                $stmt_insert_college = $conn->prepare($sql_insert_college);
                $stmt_insert_college->bind_param("ssss", $next_code, $college_name, $college_campus, $college_email);

                if ($stmt_insert_college->execute()) {
                    echo "<p class='success'>College and Program(s) added successfully.</p>";
                    $college_id = $stmt_insert_college->insert_id;
                    $college_code = $next_code;
                } else {
                    echo "<p class='error'>Error adding college: " . $conn->error . "</p>";
                    $stmt_insert_college->close();
                    $conn->close();
                    exit;
                }
                $stmt_insert_college->close();
            }

            $sql_program = "INSERT INTO program (program_name, college_code, program_level, date_received) VALUES (?, ?, ?, ?)";
            $stmt_program = $conn->prepare($sql_program);

            for ($i = 0; $i < count($programs); $i++) {
                $program = $programs[$i];
                $level = $levels[$i];
                $date_received = $dates_received[$i];
                $stmt_program->bind_param("siss", $program, $college_id, $level, $date_received);

                if (!$stmt_program->execute()) {
                    echo "<p class='error'>Error adding program '$program': " . $conn->error . "</p>";
                    $program_success = false;
                }
            }

            $stmt_program->close();
            $conn->close();

            if (!$program_success) {
                echo "<p class='error'>Some programs could not be added.</p>";
            }
            ?>
        </div>
        <button class="button-primary" onclick="window.location.href='college.php'">OK</button>
    </div>
</body>

</html>