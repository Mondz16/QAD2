<?php
session_start();

function display_message($message, $type, $redirect = 'login.php') {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Operation Result</title>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600&display=swap">
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

            .container {
                max-width: 750px;
                padding: 24px;
                background-color: #fff;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                text-align: center;
            }

            h2 {
                font-size: 24px;
                color: #973939;
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

            .button-primary {
                background-color: #2cb84f;
                color: #fff;
                border: none;
                padding: 10px 20px;
                cursor: pointer;
                border-radius: 4px;
                margin-top: 10px;
                color: white;
                font-size: 16px;
            }

            .button-primary:hover {
                background-color: #259b42;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h2>Operation Result</h2>
            <div class="message">
                <p class='<?php echo $type; ?>'><?php echo htmlspecialchars($message); ?></p>
            </div>
            <button class="button-primary" onclick="window.location.href='<?php echo $redirect; ?>'">OK</button>
        </div>
    </body>
    </html>
    <?php
}

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

    // Check admin table
    $stmt = $conn->prepare("SELECT * FROM admin WHERE user_id = ?");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $result_admin = $stmt->get_result();

    if ($result_admin->num_rows == 1) {
        $admin = $result_admin->fetch_assoc();
        if (password_verify($password, $admin['password'])) {
            $_SESSION['user_id'] = $admin['user_id'];
            header("Location: admin.php");
            exit;
        }
    }

    // Function to check user in a specific table
    function check_user($conn, $table, $user_id, $password) {
        $stmt = $conn->prepare("SELECT * FROM $table WHERE user_id = ?");
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

    // Check internal_users table
    $internal_user = check_user($conn, 'internal_users', $user_id, $password);
    if ($internal_user) {
        if ($internal_user['otp'] != 'verified') {
            header("Location: verify_otp.php?email=" . urlencode($internal_user['email']) . "&type=internal");
            exit;
        }
        if ($internal_user['status'] == 'active') {
            $_SESSION['user_id'] = $internal_user['user_id'];
            header("Location: internal.php");
            exit;
        } elseif ($internal_user['status'] == 'inactive') {
            echo "<script>
                    if (confirm('This account is inactive. Would you like to apply again?')) {
                        window.location.href = 'login_process_reactivation.php?type=internal&user_id=$user_id';
                    } else {
                        window.location.href = 'login.php';
                    }
                  </script>";
            exit;
        } else {
            display_message("Internal user status is pending. Please wait for the admin to approve.", "error");
            exit;
        }
    }

    // Check external_users table
    $external_user = check_user($conn, 'external_users', $user_id, $password);
    if ($external_user) {
        if ($external_user['otp'] != 'verified') {
            header("Location: verify_otp.php?email=" . urlencode($external_user['email']) . "&type=external");
            exit;
        }
        if ($external_user['status'] == 'active') {
            $_SESSION['user_id'] = $external_user['user_id'];
            header("Location: external.php");
            exit;
        } elseif ($external_user['status'] == 'inactive') {
            echo "<script>
                    if (confirm('This account is inactive. Would you like to apply again?')) {
                        window.location.href = 'login_process_reactivation.php?type=external&user_id=$user_id';
                    } else {
                        window.location.href = 'login.php';
                    }
                  </script>";
            exit;
        } else {
            display_message("External user status is pending. Please wait for the admin to approve.", "error");
            exit;
        }
    }

    // If no match found in any table
    display_message("User not found or password incorrect", "error");

    $conn->close();
}
?>
