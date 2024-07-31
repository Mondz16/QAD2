<?php
session_start();

if (isset($_GET['type']) && isset($_GET['user_id'])) {
    $type = $_GET['type'];
    $user_id = $_GET['user_id'];

    $servername = "localhost";
    $db_username = "root";
    $db_password = "";
    $dbname = "qadDB";

    $conn = new mysqli($servername, $db_username, $db_password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    if ($type == 'internal') {
        $stmt_reactivate = $conn->prepare("UPDATE internal_users SET status = 'pending' WHERE user_id = ? AND status = 'inactive'");
        $stmt_reactivate->bind_param("s", $user_id);
    } elseif ($type == 'external') {
        $stmt_reactivate = $conn->prepare("UPDATE external_users SET status = 'pending' WHERE user_id = ? AND status = 'inactive'");
        $stmt_reactivate->bind_param("s", $user_id);
    } else {
        header("Location: login.php");
        exit;
    }

    $stmt_reactivate->execute();
    $stmt_reactivate->close();
    $conn->close();

    header("Location: login.php");
    exit;
} else {
    header("Location: login.php");
    exit;
}
?>
