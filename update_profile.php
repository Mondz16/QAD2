<?php
include 'connection.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_POST['user_id'];
    $first_name = $_POST['first_name'];
    $middle_initial = $_POST['middle_initial'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $profile_picture = $_FILES['profile_picture'];
    $existing_profile_picture = $_POST['existing_profile_picture'];

    // Handle file upload if a new file is provided
    if ($profile_picture['error'] == UPLOAD_ERR_OK) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true); // Create directory if it does not exist
        }
        $profile_picture_path = $target_dir . basename($profile_picture["name"]);
        if (!move_uploaded_file($profile_picture["tmp_name"], $profile_picture_path)) {
            echo "Sorry, there was an error uploading your file.";
            exit;
        }
    } else {
        // Use the existing profile picture if no new file is uploaded
        $profile_picture_path = $existing_profile_picture;
    }

    // Update the existing user record
    $sql_update = "UPDATE internal_users SET first_name = ?, middle_initial = ?, last_name = ?, email = ?, profile_picture = ? WHERE user_id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("ssssss", $first_name, $middle_initial, $last_name, $email, $profile_picture_path, $user_id);

    if ($stmt_update->execute()) {
        echo "Profile updated successfully. <a href='internal.php'>Back to Profile</a>";
    } else {
        echo "Error updating profile: " . $stmt_update->error;
    }
    $stmt_update->close();
    $conn->close();
}
?>
