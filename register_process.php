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

    if ($type == 'internal') {
        $college_id = $_POST['college'];

        $sql_college = "SELECT college_name FROM college WHERE id = ?";
        $stmt_college = $conn->prepare($sql_college);
        $stmt_college->bind_param("i", $college_id);
        $stmt_college->execute();
        $result_college = $stmt_college->get_result();

        if ($result_college->num_rows > 0) {
            $row_college = $result_college->fetch_assoc();
            $college = $row_college['college_name'];
        } else {
            echo "Invalid college selected.";
            exit;
        }

        $stmt_internal = $conn->prepare("INSERT INTO internal_pending_registrations (type, first_name, middle_initial, last_name, usep_email, password, college) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt_internal->bind_param("sssssss", $type, $first_name, $middle_initial, $last_name, $email, $hashed_password, $college);
        if ($stmt_internal->execute()) {
            echo "Registration details submitted for internal approval. <a href='login.php'>OK</a>";
        } else {
            echo "Error: " . $stmt_internal->error;
        }
        $stmt_internal->close();
    } elseif ($type == 'external') {
        $company_id = $_POST['company'];

        $sql_company = "SELECT company_name FROM company WHERE id = ?";
        $stmt_company = $conn->prepare($sql_company);
        $stmt_company->bind_param("i", $company_id);
        $stmt_company->execute();
        $result_company = $stmt_company->get_result();

        if ($result_company->num_rows > 0) {
            $row_company = $result_company->fetch_assoc();
            $company_name = $row_company['company_name'];
        } else {
            echo "Invalid company selected.";
            exit;
        }

        $stmt_external = $conn->prepare("INSERT INTO external_pending_registrations (type, first_name, middle_initial, last_name, usep_email, password, company) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt_external->bind_param("sssssss", $type, $first_name, $middle_initial, $last_name, $email, $hashed_password, $company_name);
        if ($stmt_external->execute()) {
            echo "Registration details submitted for external approval. <a href='login.php'>OK</a>";
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