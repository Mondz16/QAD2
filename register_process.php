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

    function generate_unique_number($conn) {
        $sql_count_users = "SELECT COUNT(*) AS count FROM users";
        $result_count_users = $conn->query($sql_count_users);
        $count_users = $result_count_users->fetch_assoc()['count'];

        $unique_number = str_pad($count_users + 1, 4, "0", STR_PAD_LEFT);
        return $unique_number;
    }

    if ($type == 'internal') {
        $college_id = $_POST['college'];

        $sql_college = "SELECT college_code, college_name FROM college WHERE id = ?";
        $stmt_college = $conn->prepare($sql_college);
        $stmt_college->bind_param("i", $college_id);
        $stmt_college->execute();
        $result_college = $stmt_college->get_result();

        if ($result_college->num_rows > 0) {
            $row_college = $result_college->fetch_assoc();
            $college_code = $row_college['college_code'];
            $college_name = $row_college['college_name'];
        } else {
            echo "Invalid college selected.";
            exit;
        }

        $unique_number = generate_unique_number($conn);
        $user_id = $college_code . "-11-" . $unique_number;

        $stmt_internal = $conn->prepare("INSERT INTO internal_users (user_id, type, first_name, middle_initial, last_name, usep_email, password, college, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
        $stmt_internal->bind_param("ssssssss", $user_id, $type, $first_name, $middle_initial, $last_name, $email, $hashed_password, $college_name);
        if ($stmt_internal->execute()) {
            $stmt_user = $conn->prepare("INSERT INTO users (user_id, role, first_name, middle_initial, last_name, usep_email, password) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt_user->bind_param("sssssss", $user_id, $type, $first_name, $middle_initial, $last_name, $email, $hashed_password);
            $stmt_user->execute();
            $stmt_user->close();
            echo "Registration successful and pending for internal approval. Your User ID: " . $user_id . " <a href='login.php'>OK</a>";
        } else {
            echo "Error: " . $stmt_internal->error;
        }
        $stmt_internal->close();
    } elseif ($type == 'external') {
        $company_id = $_POST['company'];

        $sql_company = "SELECT company_code, company_name FROM company WHERE id = ?";
        $stmt_company = $conn->prepare($sql_company);
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

        $unique_number = generate_unique_number($conn);
        $user_id = $company_code . "-22-" . $unique_number;

        $stmt_external = $conn->prepare("INSERT INTO external_users (user_id, type, first_name, middle_initial, last_name, usep_email, password, company, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
        $stmt_external->bind_param("ssssssss", $user_id, $type, $first_name, $middle_initial, $last_name, $email, $hashed_password, $company_name);
        if ($stmt_external->execute()) {
            $stmt_user = $conn->prepare("INSERT INTO users (user_id, role, first_name, middle_initial, last_name, usep_email, password) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt_user->bind_param("sssssss", $user_id, $type, $first_name, $middle_initial, $last_name, $email, $hashed_password);
            $stmt_user->execute();
            $stmt_user->close();
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