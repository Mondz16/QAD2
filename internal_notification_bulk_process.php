<?php
include 'connection.php';
session_start();

// Check user session
if (!isset($_SESSION['user_id']) || substr($_SESSION['user_id'], 3, 2) !== '11') {
    header("Location: login.php");
    exit();
}

// Initialize variables for status and message
$status = '';
$message = '';
$processed_count = 0;
$error_count = 0;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if any schedules were selected
    if (!isset($_POST['selected_schedules']) || empty($_POST['selected_schedules'])) {
        $status = "error";
        $message = "No schedules were selected.";
    } else {
        $bulk_action = $_POST['bulk_action'];
        $selected_schedules = $_POST['selected_schedules'];

        // Determine the status based on the action
        if ($bulk_action === 'accept') {
            $status = 'accepted';
        } elseif ($bulk_action === 'decline') {
            $status = 'declined';
        } else {
            header("Location: internal_notification.php");
            exit();
        }

        // Prepare the update statement
        $sql_update = "UPDATE team SET status = ? WHERE schedule_id = ? AND status = 'pending'";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("si", $status, $schedule_id);

        // Process each selected schedule
        foreach ($selected_schedules as $schedule_id) {
            // Convert to integer for safety
            $schedule_id = intval($schedule_id);
            
            if ($stmt_update->execute()) {
                if ($stmt_update->affected_rows > 0) {
                    $processed_count++;
                }
            } else {
                $error_count++;
            }
        }

        $stmt_update->close();

        // Set appropriate message based on results
        if ($processed_count > 0) {
            $message = "$processed_count schedule(s) successfully $status.";
            if ($error_count > 0) {
                $message .= " However, $error_count schedule(s) could not be processed.";
            }
        } else {
            $status = "error";
            $message = "No schedules were processed. They may have already been processed or you may not have permission.";
        }
    }
} else {
    header("Location: internal_notification.php");
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Operation Result</title>
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
            text-align: center;
        }
        .success {
            color: green;
        }
        .error {
            color: red;
        }
        .btn-hover {
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
        .popup-content {
            background-color: white;
            padding: 40px;
            border-radius: 20px;
            text-align: center;
            max-width: 500px;
            width: 90%;
        }
    </style>
</head>
<body>
    <div class="popup-content">
        <div style='height: 50px; width: 0px;'></div>
        <img src="images/<?php echo ($error_count === 0 && $processed_count > 0) ? 'Success.png' : 'Error.png'; ?>" style="height:100px;">
        <div style="height: 25px; width: 0px;"></div>
        <div class="message <?php echo ($error_count === 0 && $processed_count > 0) ? 'success' : 'error'; ?>">
            <?php echo $message; ?>
        </div>
        <div style="height: 50px; width: 0px;"></div>
        <a href="internal_notification.php" class="btn-hover">OKAY</a>
        <div style='height: 100px; width: 0px;'></div>
        <div class='hairpop-up'></div>
    </div>
</body>
</html>