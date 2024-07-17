<?php
session_start();

$servername = "localhost";
$db_username = "root";
$db_password = "";
$dbname = "qadDB";

$conn = new mysqli($servername, $db_username, $db_password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $action = $_POST['action'];

    $sql = "SELECT * FROM internal_users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    $user_type = "internal";

    if ($result->num_rows == 0) {
        $sql = "SELECT * FROM external_users WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        $user_type = "external";
    }

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();

        if ($action == "approve") {
            if ($user_type == "internal") {
                $college_name = trim($row['college']);

                $sql_college_check = "SELECT * FROM college WHERE college_name = ?";
                $stmt_college_check = $conn->prepare($sql_college_check);
                $stmt_college_check->bind_param("s", $college_name);
                $stmt_college_check->execute();
                $result_college_check = $stmt_college_check->get_result();

                if ($result_college_check->num_rows > 0) {
                    $college_row = $result_college_check->fetch_assoc();

                    $college_code = $college_row['college_code'];

                    $unique_number = generate_unique_number($conn);
                    $user_id = $college_code . "-11-" . $unique_number;

                    $sql_insert_internal = "INSERT INTO internal_users (user_id, type, first_name, middle_initial, last_name, usep_email, password, college)
                                           VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt_insert_internal = $conn->prepare($sql_insert_internal);
                    $stmt_insert_internal->bind_param("ssssssss", $user_id, $row['type'], $row['first_name'], $row['middle_initial'], $row['last_name'], $row['usep_email'], $row['password'], $row['college']);
                    $stmt_insert_internal->execute();
                    $stmt_insert_internal->close();

                    $sql_insert_user = "INSERT INTO users (user_id, role, first_name, middle_initial, last_name, usep_email, password)
                                        VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $stmt_insert_user = $conn->prepare($sql_insert_user);
                    $stmt_insert_user->bind_param("sssssss", $user_id, $row['type'], $row['first_name'], $row['middle_initial'], $row['last_name'], $row['usep_email'], $row['password']);
                    $stmt_insert_user->execute();
                    $stmt_insert_user->close();

                    echo "User approved with ID: " . $user_id;
                } else {
                    echo "No matching college found for registration. Please verify and try again.";
                }
            } elseif ($user_type == "external") {
                $company_name = trim($row['company']);

                $sql_company_check = "SELECT * FROM company WHERE company_name = ?";
                $stmt_company_check = $conn->prepare($sql_company_check);
                $stmt_company_check->bind_param("s", $company_name);
                $stmt_company_check->execute();
                $result_company_check = $stmt_company_check->get_result();

                if ($result_company_check->num_rows > 0) {
                    $company_row = $result_company_check->fetch_assoc();

                    $company_code = $company_row['company_code'];

                    $unique_number = generate_unique_number($conn);
                    $user_id = $company_code . "-22-" . $unique_number;

                    $sql_insert_external = "INSERT INTO external_users (user_id, type, first_name, middle_initial, last_name, usep_email, password, company)
                                           VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt_insert_external = $conn->prepare($sql_insert_external);
                    $stmt_insert_external->bind_param("ssssssss", $user_id, $row['type'], $row['first_name'], $row['middle_initial'], $row['last_name'], $row['usep_email'], $row['password'], $row['company']);
                    $stmt_insert_external->execute();
                    $stmt_insert_external->close();

                    $sql_insert_user = "INSERT INTO users (user_id, role, first_name, middle_initial, last_name, usep_email, password)
                                        VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $stmt_insert_user = $conn->prepare($sql_insert_user);
                    $stmt_insert_user->bind_param("sssssss", $user_id, $row['type'], $row['first_name'], $row['middle_initial'], $row['last_name'], $row['usep_email'], $row['password']);
                    $stmt_insert_user->execute();
                    $stmt_insert_user->close();

                    echo "User approved with ID: " . $user_id;
                } else {
                    echo "No matching company found for registration. Please verify and try again.";
                }
            }
        } else if ($action == "reject") {
            echo "User registration rejected.";
        }

        if ($user_type == "internal") {
            $sql_delete_internal = "DELETE FROM internal_pending_registrations WHERE id = ?";
            $stmt_delete_internal = $conn->prepare($sql_delete_internal);
            $stmt_delete_internal->bind_param("i", $id);
            $stmt_delete_internal->execute();
            $stmt_delete_internal->close();
        } else if ($user_type == "external") {
            $sql_delete_external = "DELETE FROM external_pending_registrations WHERE id = ?";
            $stmt_delete_external = $conn->prepare($sql_delete_external);
            $stmt_delete_external->bind_param("i", $id);
            $stmt_delete_external->execute();
            $stmt_delete_external->close();
        }

        echo '<br><button onclick="window.location.href=\'registration.php\'">OK</button>';
    } else {
        echo "Invalid registration ID.";
    }
}

$conn->close();

function generate_unique_number($conn) {
    $sql_count_users = "SELECT COUNT(*) AS count FROM users";
    $result_count_users = $conn->query($sql_count_users);
    $count_users = $result_count_users->fetch_assoc()['count'];

    $unique_number = str_pad($count_users + 1, 4, "0", STR_PAD_LEFT);
    return $unique_number;
}
?>