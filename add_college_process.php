<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OPERATION RESULT</title>
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
            background-color: rgba(0, 0, 0, 0.5);
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
            <?php
            $servername = "localhost";
            $username = "root";
            $password = "";
            $dbname = "qadDB";

            $conn = new mysqli($servername, $username, $password, $dbname);

            if ($conn->connect_error) {
                die("
                <div class='popup-content'>
                    <div style='height: 50px; width: 0px;'></div>
                    <img class='Error' src='images/Error.png' height='100'>
                    <div style='height: 25px; width: 0px;'></div>
                    <p class='mb-4 mt-4  text-muted error'>Connection failed: " . $conn->connect_error . "</p>
                    <div style='height: 50px; width: 0px;'></div>
                    <a href='college.php'class='btn-hover'>OKAY</a>
                    <div style='height: 100px; width: 0px;'></div>
                    <div class='hairpop-up'></div>
                </div>");
            }

            $college_name = $_POST['college_name'];
            $college_campus = $_POST['college_campus'];
            $college_email = $_POST['college_email'];

            if (isset($_POST['programs']))
                $programs = $_POST['programs'];

            if (isset($_POST['levels']))
                $levels = $_POST['levels'];

            if (isset($_POST['dates_received']))
                $dates_received = $_POST['dates_received'];

            if (isset($_POST['years_of_validity']))
                $years_of_validity = $_POST['years_of_validity'];

            // Function to get the next code
            function getNextCode($table, $conn)
            {
                $sql_code = "SELECT MAX(CAST(code AS UNSIGNED)) AS max_code FROM $table";
                $result_code = $conn->query($sql_code);
                $row_code = $result_code->fetch_assoc();
                $max_code = $row_code['max_code'];

                if ($max_code) {
                    $next_code = str_pad(intval($max_code) + 1, 2, '0', STR_PAD_LEFT);
                } else {
                    $next_code = '01';
                }

                if (intval($next_code) > 15) {
                    die("
                    <div class='popup-content'>
                        <div style='height: 50px; width: 0px;'></div>
                        <img class='Error' src='images/Error.png' height='100'>
                        <div style='height: 25px; width: 0px;'></div>
                        <p class='mb-4 mt-4  text-muted error'>All codes from 01 to 15 have been used for $table.</p>
                        <div style='height: 50px; width: 0px;'></div>
                        <a href='college.php'class='btn-hover'>OKAY</a>
                        <div style='height: 100px; width: 0px;'></div>
                        <div class='hairpop-up'></div>
                    </div>");
                }

                return $next_code;
            }

            $program_success = true;

            // Check if the college_name already exists
            $sql_check = "SELECT code FROM college WHERE college_name = ?";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->bind_param("s", $college_name);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();

            if ($result_check->num_rows > 0) {
                // College name exists, use its college_code
                $row_check = $result_check->fetch_assoc();
                $college_code = $row_check['code'];
                echo "
                <div class='popup-content'>
                    <div style='height: 50px; width: 0px;'></div>
                    <img class='Success' src='images/Success.png' height='100'>
                    <div style='height: 20px; width: 0px;'></div>
                    <p class='mb-4 mt-4  text-muted success'>College already exists.<br>Program(s) added successfully.</p>
                    <div style='height: 50px; width: 0px;'></div>
                    <a href='college.php'class='btn-hover rounded-3 px-5 py-3'>OKAY</a>
                    <div style='height: 100px; width: 0px;'></div>
                    <div class='hairpop-up'></div>
                </div>";
            } else {
                // College name does not exist, insert it into the table
                $college_code = getNextCode('college', $conn);

                $sql_insert_college = "INSERT INTO college (code, college_name, college_campus, college_email) VALUES (?, ?, ?, ?)";
                $stmt_insert_college = $conn->prepare($sql_insert_college);
                $stmt_insert_college->bind_param("ssss", $college_code, $college_name, $college_campus, $college_email);

                if ($stmt_insert_college->execute()) {
                    echo "
                    <div class='popup-content'>
                        <div style='height: 50px; width: 0px;'></div>
                        <img class='Success' src='images/Success.png' height='100'>
                        <div style='height: 20px; width: 0px;'></div>
                        <p class='mb-4 mt-4  text-muted success'>College added successfully.</p>
                        <div style='height: 50px; width: 0px;'></div>
                        <a href='college.php'class='btn-hover'>OKAY</a>
                        <div style='height: 100px; width: 0px;'></div>
                        <div class='hairpop-up'></div>
                    </div>";
                } else {
                    echo "
                    <div class='popup-content'>
                        <div style='height: 50px; width: 0px;'></div>
                        <img class='Error' src='images/Error.png' height='100'>
                        <div style='height: 25px; width: 0px;'></div>
                        <p class='mb-4 mt-4  text-muted error'>Error adding college: " . $conn->error . "</p>
                        <div style='height: 50px; width: 0px;'></div>
                        <a href='college.php'class='btn-hover'>OKAY</a>
                        <div style='height: 100px; width: 0px;'></div>
                        <div class='hairpop-up'></div>
                    </div>";
                    $stmt_insert_college->close();
                    $conn->close();
                    exit;
                }
                $stmt_insert_college->close();
            }

            // Add programs
            if (isset($_POST['programs'])) {
                for ($i = 0; $i < count($programs); $i++) {
                    $program = $programs[$i];
                    $level = $levels[$i];
                    $date_received = $dates_received[$i];
                    $year_of_validity = $years_of_validity[$i];

                    // Check if the program already exists for the college
                    $sql_check_program = "SELECT id, program_level_id FROM program WHERE college_code = ? AND program_name = ?";
                    $stmt_check_program = $conn->prepare($sql_check_program);
                    $stmt_check_program->bind_param("ss", $college_code, $program);
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

                            if ($existing_program_level !== $level) {
                                // Insert new program level history
                                $sql_insert_program_level_history = "INSERT INTO program_level_history (program_id, program_level, date_received, year_of_validity) VALUES (?, ?, ?, ?)";
                                $stmt_insert_program_level_history = $conn->prepare($sql_insert_program_level_history);
                                $stmt_insert_program_level_history->bind_param("isss", $program_id, $level, $date_received, $year_of_validity);

                                if (!$stmt_insert_program_level_history->execute()) {
                                    echo "<p class='error'>Error adding program level history for '$program': " . $conn->error . "</p>";
                                    $program_success = false;
                                } else {
                                    // Update the program with the new program level ID
                                    $program_level_id = $conn->insert_id;
                                    $sql_update_program = "UPDATE program SET program_level_id = ? WHERE id = ?";
                                    $stmt_update_program = $conn->prepare($sql_update_program);
                                    $stmt_update_program->bind_param("ii", $program_level_id, $program_id);

                                    if (!$stmt_update_program->execute()) {
                                        echo "<p class='error'>Error updating program with level ID for '$program': " . $conn->error . "</p>";
                                        $program_success = false;
                                    }
                                }
                            }
                        }
                    } else {
                        // Insert new program
                        $sql_insert_program = "INSERT INTO program (program_name, college_code) VALUES (?, ?)";
                        $stmt_insert_program = $conn->prepare($sql_insert_program);
                        $stmt_insert_program->bind_param("ss", $program, $college_code);

                        if (!$stmt_insert_program->execute()) {
                            echo "<p class='error'>Error adding program '$program': " . $conn->error . "</p>";
                            $program_success = false;
                            continue; // Skip to the next program if insertion fails
                        }

                        // Get the last inserted program ID
                        $program_id = $conn->insert_id;

                        // Insert into program level history
                        $sql_program_level_history = "INSERT INTO program_level_history (program_id, program_level, date_received, year_of_validity) VALUES (?, ?, ?, ?)";
                        $stmt_program_level_history = $conn->prepare($sql_program_level_history);
                        $stmt_program_level_history->bind_param("isss", $program_id, $level, $date_received, $year_of_validity);

                        if (!$stmt_program_level_history->execute()) {
                            echo "
                                <div class='popup-content'>
                                    <div style='height: 50px; width: 0px;'></div>
                                    <img class='Error' src='images/Error.png' height='100'>
                                    <div style='height: 25px; width: 0px;'></div>
                                    <p mb-4 mt-4  text-muted class='error'>Error adding program level history for '$program': " . $conn->error . "</p>
                                    <div style='height: 50px; width: 0px;'></div>
                                    <a href='college.php'class='btn-hover'>OKAY</a>
                                    <div style='height: 100px; width: 0px;'></div>
                                    <div class='hairpop-up'></div>
                                </div>";
                            $program_success = false;
                            continue; // Skip to the next program if insertion fails
                        }

                        // Get the last inserted program level history ID
                        $program_level_id = $conn->insert_id;

                        // Update the program table with the program_level_id
                        $sql_update_program = "UPDATE program SET program_level_id = ? WHERE id = ?";
                        $stmt_update_program = $conn->prepare($sql_update_program);
                        $stmt_update_program->bind_param("ii", $program_level_id, $program_id);

                        if (!$stmt_update_program->execute()) {
                            echo "
                                <div class='popup-content'>
                                    <div style='height: 50px; width: 0px;'></div>
                                    <img class='Error' src='images/Error.png' height='100'>
                                    <div style='height: 25px; width: 0px;'></div>
                                    <p class='mb-4 mt-4  text-muted error'>Error updating program with level ID for '$program': " . $conn->error . "</p>
                                    <div style='height: 50px; width: 0px;'></div>
                                    <a href='college.php'class='btn-hover'>OKAY</a>
                                    <div style='height: 100px; width: 0px;'></div>
                                    <div class='hairpop-up'></div>
                                </div>";
                            $program_success = false;
                        }
                    }
                }

                if (!$program_success) {
                    echo "
                    <div class='popup-content'>
                        <div style='height: 50px; width: 0px;'></div>
                        <img class='Error' src='images/Error.png' height='100'>
                        <div style='height: 25px; width: 0px;'></div>
                        <p class='mb-4 mt-4  text-muted error'>Some programs could not be added.</p>
                        <div style='height: 50px; width: 0px;'></div>
                        <a href='college.php'class='btn-hover'>OKAY</a>
                        <div style='height: 100px; width: 0px;'></div>
                        <div class='hairpop-up'></div>
                    </div>";
                }

                $stmt_program->close();
            }

            $conn->close();
            ?>
        </div>
        </div>
    </body>

</html>
