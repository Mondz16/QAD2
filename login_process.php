<?php
session_start();
//wewew
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

    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->bind_param("s", $user_id);

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        $stored_password = $row['password'];

        if (password_verify($password, $stored_password)) {
            $_SESSION['user_id'] = $row['user_id']; // Correctly set the session variable
            if ($_SESSION['user_id'] === 'admin') {
                header("Location: admin.php");
            } else if (substr($_SESSION['user_id'], 3, 2) === '22') {
                header("Location: external.php");
            } else if (substr($_SESSION['user_id'], 3, 2) === '11') {
                header("Location: internal.php");
            } else {
                header("Location: login.php");
            }
            exit;
        } else {
            echo "Incorrect password";
        }
    } else {
        echo "User not found";
    }

    $stmt->close();
    $conn->close();
}
?>
