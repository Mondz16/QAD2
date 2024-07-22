<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $type = $_POST['type'];
    $first_name = $_POST['first_name'];
    $middle_initial = $_POST['middle_initial'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        echo "Passwords do not match!";
        exit;
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    include 'connection.php';

    // Function to generate unique user_id
    function generate_unique_number($conn, $table) {
        $sql_count_users = "SELECT COUNT(*) AS count FROM $table";
        $result_count_users = $conn->query($sql_count_users);
        $count_users = $result_count_users->fetch_assoc()['count'];

        $unique_number = str_pad($count_users + 1, 4, "0", STR_PAD_LEFT);
        return $unique_number;
    }

    $registration_success = false;
    $user_id = '';

    if ($type == 'internal') {
        $college_id = $_POST['college'];

        // Fetch college details based on college_id
        $stmt_college = $conn->prepare("SELECT college_code, college_name FROM college WHERE id = ?");
        $stmt_college->bind_param("i", $college_id);
        $stmt_college->execute();
        $result_college = $stmt_college->get_result();

        if ($result_college->num_rows > 0) {
            $row_college = $result_college->fetch_assoc();
            $college_code = $row_college['college_code'];
            $college = $row_college['college_name'];
        } else {
            echo "Invalid college selected.";
            exit;
        }

        $table = "internal_users"; // Table to insert into
        $unique_number = generate_unique_number($conn, $table);
        $user_id = $college_code . "-11-" . $unique_number;

        // Insert into internal_users table
        $stmt_internal = $conn->prepare("INSERT INTO $table (user_id, college_id, first_name, middle_initial, last_name, email, password, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
        $stmt_internal->bind_param("sisssss", $user_id, $college_id, $first_name, $middle_initial, $last_name, $email, $hashed_password);
        if ($stmt_internal->execute()) {
            $registration_success = true;
        } else {
            echo "Error: " . $stmt_internal->error;
        }
        $stmt_internal->close();
    } elseif ($type == 'external') {
        $company_id = $_POST['company'];

        // Fetch company details based on company_id
        $stmt_company = $conn->prepare("SELECT company_code, company_name FROM company WHERE id = ?");
        $stmt_company->bind_param("i", $company_id);
        $stmt_company->execute();
        $result_company = $stmt_company->get_result();

        if ($result_company->num_rows > 0) {
            $row_company = $result_company->fetch_assoc();
            $company_code = $row_company['company_code'];
            $company_name = $row_company['company_name'];
        } else {
            echo "Invalid company selected.";
            exit;
        }

        $table = "external_users"; // Table to insert into
        $unique_number = generate_unique_number($conn, $table);
        $user_id = $company_code . "-22-" . $unique_number;

        // Insert into external_users table
        $stmt_external = $conn->prepare("INSERT INTO $table (user_id, company_id, first_name, middle_initial, last_name, email, password, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
        $stmt_external->bind_param("sisssss", $user_id, $company_id, $first_name, $middle_initial, $last_name, $email, $hashed_password);
        if ($stmt_external->execute()) {
            $registration_success = true;
        } else {
            echo "Error: " . $stmt_external->error;
        }
        $stmt_external->close();
    } else {
        echo "Invalid registration type.";
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
            <div class="message">
                <?php
                if ($registration_success) {
                    echo "<p class='success'>Registration successful and pending for approval. Your User ID: " . htmlspecialchars($user_id) . "</p>";
                } else {
                    echo "<p class='error'>Registration failed. Please try again.</p>";
                }
                ?>
            </div>
            <button class="button-primary" onclick="window.location.href='login.php'">OK</button>
        </div>
    </body>
    </html>
    <?php
}
?>
