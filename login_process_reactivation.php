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
        display_message("Invalid type.", "error");
        exit;
    }

    if ($stmt_reactivate->execute()) {
        display_message("Your application has been reactivated and is now pending for approval.", "success");
    } else {
        display_message("Error: " . $stmt_reactivate->error, "error");
    }

    $stmt_reactivate->close();
    $conn->close();
} else {
    display_message("Invalid request.", "error");
}
?>
