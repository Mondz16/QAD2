<?php
include 'connection.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if the form was submitted and which field is being updated
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['field'])) {
    $field = $_POST['field'];

    try {
        $conn->begin_transaction(); // Start a transaction

        switch ($field) {
            case 'prefix':
                if (isset($_POST['newPrefix']) && !empty($_POST['newPrefix'])) {
                    $newPrefix = trim($_POST['newPrefix']);

                    // Update prefix
                    $stmt = $conn->prepare("UPDATE internal_users SET prefix = ? WHERE user_id = ?");
                    $stmt->bind_param("ss", $newPrefix, $user_id);
                    $stmt->execute();
                    $stmt->close();
                    echo "Prefix updated successfully.";
                } else {
                    echo "Please select a prefix.";
                }
                break;

            case 'fullname':
                if (!empty($_POST['newFirstName']) && !empty($_POST['newMiddleInitial']) && !empty($_POST['newLastName'])) {
                    $newFirstName = trim($_POST['newFirstName']);
                    $newMiddleInitial = strtoupper(trim($_POST['newMiddleInitial']));
                    $newLastName = trim($_POST['newLastName']);

                    // Update full name
                    $stmt = $conn->prepare("UPDATE internal_users SET first_name = ?, middle_initial = ?, last_name = ? WHERE user_id = ?");
                    $stmt->bind_param("ssss", $newFirstName, $newMiddleInitial, $newLastName, $user_id);
                    $stmt->execute();
                    $stmt->close();
                    echo "Full name updated successfully.";
                } else {
                    echo "All fields for full name must be filled out.";
                }
                break;

            case 'email':
                if (!empty($_POST['newEmail']) && filter_var($_POST['newEmail'], FILTER_VALIDATE_EMAIL)) {
                    $newEmail = trim($_POST['newEmail']);

                    // Update email
                    $stmt = $conn->prepare("UPDATE internal_users SET email = ? WHERE user_id = ?");
                    $stmt->bind_param("ss", $newEmail, $user_id);
                    $stmt->execute();
                    $stmt->close();
                    echo "Email updated successfully.";
                } else {
                    echo "Please enter a valid email address.";
                }
                break;

            case 'gender':
                $newGender = $_POST['newGender'];
                $genderOthers = isset($_POST['gender_others']) ? trim($_POST['gender_others']) : '';

                // Update gender
                if ($newGender === 'Others' && !empty($genderOthers)) {
                    $newGender = $genderOthers;
                }
                if (!empty($newGender)) {
                    $stmt = $conn->prepare("UPDATE internal_users SET gender = ? WHERE user_id = ?");
                    $stmt->bind_param("ss", $newGender, $user_id);
                    $stmt->execute();
                    $stmt->close();
                    echo "Gender updated successfully.";
                } else {
                    echo "Please select or specify a gender.";
                }
                break;

            case 'profilePicture':
                if (isset($_FILES['profilePicture']) && $_FILES['profilePicture']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = 'Profile Pictures/';
                    $fileTmpPath = $_FILES['profilePicture']['tmp_name'];
                    $fileName = basename($_FILES['profilePicture']['name']);
                    $filePath = $uploadDir . $user_id . '_' . $fileName;

                    // Ensure the uploads directory exists
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }

                    // Move the uploaded file to the desired directory
                    if (move_uploaded_file($fileTmpPath, $filePath)) {
                        // Update profile picture path in the database
                        $stmt = $conn->prepare("UPDATE internal_users SET profile_picture = ? WHERE user_id = ?");
                        $stmt->bind_param("ss", $filePath, $user_id);
                        $stmt->execute();
                        $stmt->close();
                        echo "Profile picture updated successfully.";
                    } else {
                        echo "Error moving the uploaded file.";
                    }
                } else {
                    echo "Error uploading profile picture.";
                }
                break;

            default:
                echo "Invalid update request.";
                break;
        }

        $conn->commit(); // Commit the transaction
    } catch (Exception $e) {
        $conn->rollback(); // Rollback the transaction on error
        echo "Error updating profile: " . $e->getMessage();
    }
} else {
    echo "Invalid request.";
}

// Redirect back to profile page
header("Location: internal.php");
exit();

?>
