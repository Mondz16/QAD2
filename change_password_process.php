<?php
include 'connection.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$current_password = $_POST['currentPassword'];
$new_password = $_POST['newPassword'];
$confirm_password = $_POST['confirmPassword'];

// Determine user role and appropriate table
if ($user_id === 'admin') {
    $table = 'admin';
} else {
    $user_type_code = substr($user_id, 3, 2);
    if ($user_type_code === '11') {
        $table = 'internal_users';
    } elseif ($user_type_code === '22') {
        $table = 'external_users';
    } else {
        header("Location: login.php");
        exit();
    }
}

// Fetch current password from the database
$sql = "SELECT password FROM $table WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user_id);
$stmt->execute();
$stmt->bind_result($db_password);
$stmt->fetch();
$stmt->close();

// Verify current password
if (!password_verify($current_password, $db_password)) {
    $message = "Current password is incorrect.";
    $status = "error";
} elseif ($new_password !== $confirm_password) {
    $message = "New password and confirm password do not match.";
    $status = "error";
} else {
    // Hash the new password
    $new_password_hashed = password_hash($new_password, PASSWORD_DEFAULT);

    // Update the password in the database
    $sql_update = "UPDATE $table SET password = ? WHERE user_id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("ss", $new_password_hashed, $user_id);

    if ($stmt_update->execute()) {
        $message = "Password updated successfully.";
        $status = "success";
    } else {
        $message = "Error updating password.";
        $status = "error";
    }

    $stmt_update->close();
}

$conn->close();

// HTML template
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Operation Result</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600&display=swap">
    <link rel="stylesheet" href="index.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Quicksand", sans-serif;
        }
        body {
            background-color: #f9f9f9;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }
        h2 {
            font-size: 24px;
            color: #292D32;
            margin-bottom: 20px;
        }

        .message {
            margin-bottom: 20px;
            font-size: 18px;
        }
        .success {
            color: green;
        }

        .error {
            color: red;
        }
        .btn-hover{
            border: 1px solid #AFAFAF;
            text-decoration: none;
            color: black;
            border-radius: 10px;
            padding: 20px 50px;
            font-size: 1rem;
            font-weight: bold;
            text-transform: uppercase;
        }
        .btn-hover:hover {
            background-color: #AFAFAF;
        }
    </style>
</head>
<body>
    <div class="popup-content">
        <div style='height: 50px; width: 0px;'></div>
        <img src="images/<?php echo ucfirst($status); ?>.png" height="100" alt="<?php echo ucfirst($status); ?>">
        <div style="height: 25px; width: 0px;"></div>
        <div class="message <?php echo $status; ?>">
            <?php echo $message; ?>
        </div>
        <div style="height: 50px; width: 0px;"></div>
        <a href="internal.php" class="btn-hover">OKAY</a>
        <div style='height: 100px; width: 0px;'></div>
        <div class='hairpop-up'></div>
    </div>
</body>
</html>