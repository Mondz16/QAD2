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

// Fetch current password from the database
$sql = "SELECT password FROM internal_users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user_id);
$stmt->execute();
$stmt->bind_result($db_password);
$stmt->fetch();
$stmt->close();

// Verify current password
if (!password_verify($current_password, $db_password)) {
    echo "Current password is incorrect.";
    exit();
}

// Verify new password and confirm password match
if ($new_password !== $confirm_password) {
    echo "New password and confirm password do not match.";
    exit();
}

// Hash the new password
$new_password_hashed = password_hash($new_password, PASSWORD_DEFAULT);

// Update the password in the database
$sql_update = "UPDATE internal_users SET password = ? WHERE user_id = ?";
$stmt_update = $conn->prepare($sql_update);
$stmt_update->bind_param("ss", $new_password_hashed, $user_id);

if ($stmt_update->execute()) {
    echo "Password updated successfully.";
} else {
    echo "Error updating password.";
}

$stmt_update->close();
$conn->close();
?>
