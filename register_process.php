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
    $college_code = isset($_POST['college']) ? $_POST['college'] : null;
    $company_code = isset($_POST['company']) ? $_POST['company'] : null;

    if ($password !== $confirm_password) {
        echo "Passwords do not match!";
        exit;
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    include 'connection.php';

    function check_existing_user($conn, $first_name, $middle_initial, $last_name) {
        $stmt = $conn->prepare("SELECT status FROM internal_users WHERE first_name = ? AND middle_initial = ? AND last_name = ? UNION SELECT status FROM external_users WHERE first_name = ? AND middle_initial = ? AND last_name = ?");
        $stmt->bind_param("ssssss", $first_name, $middle_initial, $last_name, $first_name, $middle_initial, $last_name);
        $stmt->execute();
        return $stmt->get_result();
    }

    $result_existing = check_existing_user($conn, $first_name, $middle_initial, $last_name);

    if ($result_existing->num_rows > 0) {
        $row_existing = $result_existing->fetch_assoc();
        $status = $row_existing['status'];
        if ($status == 'inactive') {
            echo "<script>
                    if (confirm('This information is already registered in the system but inactive. Would you like to apply again?')) {
                        window.location.href = 'register_process_reactivation.php?type=$type&email=$email';
                    } else {
                        window.location.href = 'register.php';
                    }
                  </script>";
        } elseif ($status == 'pending') {
            echo "<script>alert('This information is already registered in the system but pending. Please wait for the admin to approve.');
                  window.location.href = 'register.php';
                  </script>";
        } elseif ($status == 'active') {
            echo "<script>alert('This information is already registered in the system and active.');
                  window.location.href = 'login.php';
                  </script>";
        }
        exit;
    }

    function generate_unique_number($conn, $table) {
        $sql_count_users = "SELECT COUNT(*) AS count FROM $table";
        $result_count_users = $conn->query($sql_count_users);
        $count_users = $result_count_users->fetch_assoc()['count'];

        $unique_number = str_pad($count_users + 1, 4, "0", STR_PAD_LEFT);
        return $unique_number;
    }

    if ($type == 'internal') {
        // Fetch college details based on college_id
        $stmt_college = $conn->prepare("SELECT code, college_name FROM college WHERE code = ?");
        $stmt_college->bind_param("i", $college_code);
        $stmt_college->execute();
        $result_college = $stmt_college->get_result();

        if ($result_college->num_rows > 0) {
            $row_college = $result_college->fetch_assoc();
            $college = $row_college['college_name'];
        } else {
            echo "Invalid college selected.";
            exit;
        }

        $table = "internal_users"; // Table to insert into
        $unique_number = generate_unique_number($conn, $table);
        $user_id = $college_code . "-11-" . $unique_number;

        // Insert into internal_users table
        $stmt_internal = $conn->prepare("INSERT INTO $table (user_id, college_code, first_name, middle_initial, last_name, email, password, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
        $stmt_internal->bind_param("sssssss", $user_id, $college_code, $first_name, $middle_initial, $last_name, $email, $hashed_password);
        if ($stmt_internal->execute()) {
            echo "Registration successful and pending for internal approval. Your User ID: " . $user_id . " <a href='login.php'>OK</a>";
        } else {
            echo "Error: " . $stmt_internal->error;
        }
        $stmt_internal->close();
    } elseif ($type == 'external') {
        // Fetch company details based on company_id
        $stmt_company = $conn->prepare("SELECT code, company_name FROM company WHERE code = ?");
        $stmt_company->bind_param("i", $company_code);
        $stmt_company->execute();
        $result_company = $stmt_company->get_result();

        if ($result_company->num_rows > 0) {
            $row_company = $result_company->fetch_assoc();
            $company_name = $row_company['company_name'];
        } else {
            echo "Invalid company selected.";
            exit;
        }

        $table = "external_users"; // Table to insert into
        $unique_number = generate_unique_number($conn, $table);
        $user_id = $company_code . "-22-" . $unique_number;

        // Insert into external_users table
        $stmt_external = $conn->prepare("INSERT INTO $table (user_id, company_code, first_name, middle_initial, last_name, email, password, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
        $stmt_external->bind_param("sisssss", $user_id, $company_code, $first_name, $middle_initial, $last_name, $email, $hashed_password);
        if ($stmt_external->execute()) {
            echo "Registration successful and pending for external approval. Your User ID: " . $user_id . " <a href='login.php'>OK</a>";
        } else {
            echo "Error: " . $stmt_external->error;
        }
        $stmt_external->close();
    } else {
        echo "Invalid registration type.";
    }

    $conn->close();
}
?>
