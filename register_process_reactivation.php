<?php
session_start();

if (isset($_GET['type']) && isset($_GET['email'])) {
    $type = $_GET['type'];
    $email = $_GET['email'];

    include 'connection.php';

    if ($type == 'internal') {
        $stmt_reactivate = $conn->prepare("UPDATE internal_users SET status = 'pending' WHERE email = ? AND status = 'inactive'");
        $stmt_reactivate->bind_param("s", $email);
    } elseif ($type == 'external') {
        $stmt_reactivate = $conn->prepare("UPDATE external_users SET status = 'pending' WHERE email = ? AND status = 'inactive'");
        $stmt_reactivate->bind_param("s", $email);
    } else {
        echo "Invalid type.";
        exit;
    }

    if ($stmt_reactivate->execute()) {
        echo "Your application has been reactivated and is now pending approval. <a href='login.php'>OK</a>";
    } else {
        echo "Error: " . $stmt_reactivate->error;
    }

    $stmt_reactivate->close();
    $conn->close();
} else {
    echo "Invalid request.";
}
?>
