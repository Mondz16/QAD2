<?php
include 'connection.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $image = "";
    if (isset($_POST['action']) && $_POST['action'] == 'reactivate') {
        $user_id = $_POST['user_id'];

        // Reactivate the inactive user by setting their status to 'pending'
        $sql_reactivate = "UPDATE internal_users SET status = 'pending' WHERE user_id = ?";
        $stmt_reactivate = $conn->prepare($sql_reactivate);
        $stmt_reactivate->bind_param("s", $user_id);
        if ($stmt_reactivate->execute()) {
            $message = "Reactivation request submitted successfully.";
            $image = "success.png";
        } else {
            $message = "Error submitting reactivation request: " . $stmt_reactivate->error;
            $image = "error.png";
        }
        $stmt_reactivate->close();
    } else {
        $user_id = $_POST['user_id'];
        $first_name = $_POST['first_name'];
        $middle_initial = $_POST['middle_initial'];
        $last_name = $_POST['last_name'];
        $email = $_POST['email'];
        $new_college_code = $_POST['newCollege'];

        // Fetch the current user's details
        $sql_user = "SELECT college_code, password, profile_picture, prefix, gender, e_sign_agreement, otp FROM internal_users WHERE user_id = ?";
        $stmt_user = $conn->prepare($sql_user);
        $stmt_user->bind_param("s", $user_id);
        $stmt_user->execute();
        $stmt_user->bind_result($current_college_code, $password, $profile_picture, $prefix, $gender, $e_sign_agreement, $otp);
        $stmt_user->fetch();
        $stmt_user->close();

        if ($new_college_code != $current_college_code) {
            // Generate a new user_id
            $bb_cccc = substr($user_id, 3); // Keep bb-cccc part of the user_id

            // Create the new user_id
            $new_user_id = $new_college_code . "-" . $bb_cccc;

            // Check if transferring back to a previous college
            $previous_user_id = $current_college_code . "-" . $bb_cccc;
            $sql_check = "SELECT status FROM internal_users WHERE user_id = ? AND status = 'inactive'";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->bind_param("s", $previous_user_id);
            $stmt_check->execute();
            $stmt_check->store_result();

            if ($stmt_check->num_rows > 0) {
                // If an inactive user exists, prompt the user
                echo "<script>
                        if (confirm('A user with this college is already in the database but inactive. Would you like to apply again?')) {
                            document.addEventListener('DOMContentLoaded', function() {
                                var form = document.createElement('form');
                                form.method = 'post';
                                form.action = 'update_college.php';

                                var actionField = document.createElement('input');
                                actionField.type = 'hidden';
                                actionField.name = 'action';
                                actionField.value = 'reactivate';
                                form.appendChild(actionField);

                                var userIdField = document.createElement('input');
                                userIdField.type = 'hidden';
                                userIdField.name = 'user_id';
                                userIdField.value = '$previous_user_id';
                                form.appendChild(userIdField);

                                document.body.appendChild(form);
                                form.submit();
                            });
                        } else {
                            window.location.href = 'internal.php';
                        }
                      </script>";
                exit;
            }
            $stmt_check->close();

            // Ensure the new user_id does not already exist
            $sql_check_new = "SELECT user_id FROM internal_users WHERE user_id = ?";
            $stmt_check_new = $conn->prepare($sql_check_new);
            $stmt_check_new->bind_param("s", $new_user_id);
            $stmt_check_new->execute();
            $stmt_check_new->store_result();
            
            if ($stmt_check_new->num_rows > 0) {
                // If the new user ID exists, prompt for reactivation if it is inactive
                $stmt_check_new->bind_result($existing_user_id);
                $stmt_check_new->fetch();
                if ($existing_user_id == $new_user_id) {
                    echo "<script>
                            if (confirm('A user with this college is already in the database but inactive. Would you like to apply again?')) {
                                document.addEventListener('DOMContentLoaded', function() {
                                    var form = document.createElement('form');
                                    form.method = 'post';
                                    form.action = 'update_college.php';

                                    var actionField = document.createElement('input');
                                    actionField.type = 'hidden';
                                    actionField.name = 'action';
                                    actionField.value = 'reactivate';
                                    form.appendChild(actionField);

                                    var userIdField = document.createElement('input');
                                    userIdField.type = 'hidden';
                                    userIdField.name = 'user_id';
                                    userIdField.value = '$new_user_id';
                                    form.appendChild(userIdField);

                                    document.body.appendChild(form);
                                    form.submit();
                                });
                            } else {
                                window.location.href = 'internal.php';
                            }
                          </script>";
                    exit;
                }
                $stmt_check_new->close();
            }

            // Insert the new user record with status 'pending'
            $sql_insert = "INSERT INTO internal_users (user_id, college_code, first_name, middle_initial, last_name, email, password, profile_picture, prefix, gender, e_sign_agreement, otp, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
            $stmt_insert = $conn->prepare($sql_insert);
            $stmt_insert->bind_param("ssssssssssss", $new_user_id, $new_college_code, $first_name, $middle_initial, $last_name, $email, $password, $profile_picture, $prefix, $gender, $e_sign_agreement, $otp);

            if ($stmt_insert->execute()) {
                $message = "College transfer request submitted successfully.";
                $image = "success.png";
            } else {
                $message = "Error submitting transfer request: " . $stmt_insert->error;
                $image = "error.png";
            }
            $stmt_insert->close();
        } else {
            $message = "You have selected the same college. No changes made.";
            $image = "error.png";
        }
    }

    $conn->close();
} else {
    $message = "This page should only be accessed through a valid form submission.";
    $image = "error.png";
}

// Display the operation result
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
            background-color: rgba(0, 0, 0, 0.5);
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
        <img class="status-image" src="images/<?php echo $image; ?>" alt="Status Image">
        <div style="height: 25px; width: 0px;"></div>
        <div class="message">
            <?php echo $message; ?>
        </div>
        <div style="height: 50px; width: 0px;"></div>
        <a class="btn-hover" href="internal.php">Back to Profile</a>
        <div style='height: 100px; width: 0px;'></div>
        <div class='hairpop-up'></div>
    </div>
</body>
</html>
