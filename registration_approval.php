<?php
session_start();

$servername = "localhost";
$db_username = "root";
$db_password = "";
$dbname = "qadDB";

$conn = new mysqli($servername, $db_username, $db_password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $action = $_POST['action'];

    $user_type = "";
    $sql = "SELECT * FROM internal_users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $id); // Change "i" to "s" since user_id is a string
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        $sql = "SELECT * FROM external_users WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user_type = "external";
    } else {
        $user_type = "internal";
    }

    $message = "";
    $message_class = "";

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();

        if ($action == "approve") {
            if ($user_type == "internal") {
                $sql_update_internal = "UPDATE internal_users SET status = 'active', date_added = CURRENT_TIMESTAMP WHERE user_id = ?";
                $stmt_update_internal = $conn->prepare($sql_update_internal);
                $stmt_update_internal->bind_param("s", $id);
                $stmt_update_internal->execute();
                $stmt_update_internal->close();

                $message = "User approved with ID: " . $id;
                $message_class = "success";
            } elseif ($user_type == "external") {
                $sql_update_external = "UPDATE external_users SET status = 'active', date_added = CURRENT_TIMESTAMP WHERE user_id = ?";
                $stmt_update_external = $conn->prepare($sql_update_external);
                $stmt_update_external->bind_param("s", $id);
                $stmt_update_external->execute();
                $stmt_update_external->close();

                $message = "User approved with ID: " . $id;
                $message_class = "success";
            }
        } else if ($action == "reject") {
            if ($user_type == "internal") {
                $sql_update_internal = "UPDATE internal_users SET status = 'inactive', date_added = CURRENT_TIMESTAMP WHERE user_id = ?";
                $stmt_update_internal = $conn->prepare($sql_update_internal);
                $stmt_update_internal->bind_param("s", $id);
                $stmt_update_internal->execute();
                $stmt_update_internal->close();

                $message = "User rejected with ID: " . $id;
                $message_class = "success";
            } else if ($user_type == "external") {
                $sql_update_external = "UPDATE external_users SET status = 'inactive', date_added = CURRENT_TIMESTAMP WHERE user_id = ?";
                $stmt_update_external = $conn->prepare($sql_update_external);
                $stmt_update_external->bind_param("s", $id);
                $stmt_update_external->execute();
                $stmt_update_external->close();

                $message = "User rejected with ID: " . $id;
                $message_class = "error";
            }
        }
    } else {
        $message = "Invalid registration ID.";
        $message_class = "error";
    }
}

$conn->close();
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
        <div class="message <?php echo $message_class; ?>">
            <?php echo $message; ?>
        </div>
        <button class="button-primary" onclick="window.location.href='registration.php'">OK</button>
    </div>
</body>
</html>
