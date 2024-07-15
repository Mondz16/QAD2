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

    // Function to check user in a specific table
    function check_user($conn, $table, $user_id, $password) {
        $stmt = $conn->prepare("SELECT * FROM $table WHERE user_id = ? AND status = 'approved'");
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

    // Check users table first
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role'] = $user['role'];
            
            if ($user['role'] == 'admin') {
                header("Location: admin.php");
                exit;
            } elseif ($user['role'] == 'internal') {
                $internal_user = check_user($conn, 'internal_users', $user_id, $password);
                if ($internal_user) {
                    header("Location: internal.php");
                    exit;
                } else {
                    echo "Internal user not found or status not approved";
                }
            } elseif ($user['role'] == 'external') {
                $external_user = check_user($conn, 'external_users', $user_id, $password);
                if ($external_user) {
                    header("Location: external.php");
                    exit;
                } else {
                    echo "External user not found or status not approved";
                }
            }
        } else {
            echo "Invalid password";
        }
    } else {
        echo "User not found";
    }

    $conn->close();
}
?>
