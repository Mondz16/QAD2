<?php
session_start();
include 'connection.php'; // Include your database connection file

function resetPassword($user_id, $email) {
    global $conn;
    $new_password = password_hash($user_id, PASSWORD_DEFAULT);
    $tables = ['internal_users', 'external_users'];

    foreach ($tables as $table) {
        $query = "UPDATE $table SET password=? WHERE user_id=? AND email=?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('sss', $new_password, $user_id, $email);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            return true;
        }
    }
    return false;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_POST['user_id'];
    $email = $_POST['email'];

    if (empty($user_id) || empty($email)) {
        $_SESSION['error'] = "User ID and Email are required.";
    } else {
        if (resetPassword($user_id, $email)) {
            $_SESSION['success'] = "Password has been reset. Your new password is your User ID.";
        } else {
            $_SESSION['error'] = "No matching user found.";
        }
    }

    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="index.css">
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <h2>Forgot Password</h2>

            <?php
            if (isset($_SESSION['error'])) {
                echo "<div class='error'>{$_SESSION['error']}</div>";
                unset($_SESSION['error']);
            }

            if (isset($_SESSION['success'])) {
                echo "<div class='success'>{$_SESSION['success']}</div>";
                unset($_SESSION['success']);
            }
            ?>

            <form method="POST" action="forgot_password.php">
                <div class="input-group">
                    <label for="user_id">User ID</label>
                    <input type="text" name="user_id" id="user_id" required>
                </div>
                <div class="input-group">
                    <label for="email">Email</label>
                    <input type="email" name="email" id="email" required>
                </div>
                <button type="submit">Reset Password</button>
            </form>
        </div>
    </div>
</body>
</html>
