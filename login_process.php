<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_POST['user_id'];
    $password = $_POST['password'];

    $servername = "localhost";
    $db_username = "root";
    $db_password = "";
    $dbname = "qadDB";

    $conn = new mysqli($servername, $db_username, $db_password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Check admin table
    $stmt = $conn->prepare("SELECT * FROM admin WHERE user_id = ?");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $result_admin = $stmt->get_result();

    if ($result_admin->num_rows == 1) {
        $admin = $result_admin->fetch_assoc();
        if (password_verify($password, $admin['password'])) {
            $_SESSION['user_id'] = $admin['user_id'];
            header("Location: admin.php");
            exit;
        }
    }

    // Function to check user in a specific table
    function check_user($conn, $table, $user_id, $password) {
        $stmt = $conn->prepare("SELECT * FROM $table WHERE user_id = ?");
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            if (password_verify($password, $row['password'])) {
                return $row;
            }
        }
        
        return false;
    }

    // Check internal_users table
    $internal_user = check_user($conn, 'internal_users', $user_id, $password);
    if ($internal_user) {
        if ($internal_user['status'] == 'approved') {
            $_SESSION['user_id'] = $internal_user['user_id'];
            header("Location: internal.php");
            exit;
        } else {
            echo "Internal user status is pending";
            exit;
        }
    }

    // Check external_users table
    $external_user = check_user($conn, 'external_users', $user_id, $password);
    if ($external_user) {
        if ($external_user['status'] == 'approved') {
            $_SESSION['user_id'] = $external_user['user_id'];
            header("Location: external.php");
            exit;
        } else {
            echo "External user status is pending";
            exit;
        }
    }

    // If no match found in any table
    echo "User not found or password incorrect";

    $conn->close();
}
?>
