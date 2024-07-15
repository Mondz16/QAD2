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

    $user_type = "";
    $sql = "SELECT * FROM internal_users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $id); // Change "i" to "s" since user_id is a string
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        $sql = "SELECT * FROM external_users WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user_type = "external";
    } else {
        $user_type = "internal";
    }

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();

        if ($action == "approve") {
            if ($user_type == "internal") {
                $sql_update_internal = "UPDATE internal_users SET status = 'approved' WHERE user_id = ?";
                $stmt_update_internal = $conn->prepare($sql_update_internal);
                $stmt_update_internal->bind_param("s", $id);
                $stmt_update_internal->execute();
                $stmt_update_internal->close();

                echo "User approved with ID: " . $id;
            } elseif ($user_type == "external") {
                $sql_update_external = "UPDATE external_users SET status = 'approved' WHERE user_id = ?";
                $stmt_update_external = $conn->prepare($sql_update_external);
                $stmt_update_external->bind_param("s", $id);
                $stmt_update_external->execute();
                $stmt_update_external->close();

                echo "User approved with ID: " . $id;
            }
        } else if ($action == "deny") {
            if ($user_type == "internal") {
                $sql_delete_internal = "DELETE FROM internal_users WHERE user_id = ?";
                $stmt_delete_internal = $conn->prepare($sql_delete_internal);
                $stmt_delete_internal->bind_param("s", $id);
                $stmt_delete_internal->execute();
                $stmt_delete_internal->close();
            } else if ($user_type == "external") {
                $sql_delete_external = "DELETE FROM external_users WHERE user_id = ?";
                $stmt_delete_external = $conn->prepare($sql_delete_external);
                $stmt_delete_external->bind_param("s", $id);
                $stmt_delete_external->execute();
                $stmt_delete_external->close();
            }

            echo "User registration rejected.";
        }

        echo '<br><button onclick="window.location.href=\'registration.php\'">OK</button>';
    } else {
        echo "Invalid registration ID.";
    }
}

$conn->close();
?>
