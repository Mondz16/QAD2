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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $team_id = intval($_POST['team_id']);
    $schedule_id = intval($_POST['schedule_id']);
    $action = $_POST['action'];

    if ($action === 'accept') {
        $status = 'accepted';
    } elseif ($action === 'decline') {
        $status = 'declined';
    } else {
        header("Location: internal_notification.php");
        exit();
    }

    $sql_update = "UPDATE team SET status = ? WHERE id = ? AND schedule_id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("sii", $status, $team_id, $schedule_id);

    if ($stmt_update->execute()) {
        $status = "success";
        $message = "Notification status updated successfully.";
    } else {
        $status = "error";
        $message = "Error updating notification: " . $conn->error;
    }

    $stmt_update->close();
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
        <img src="images/<?php echo ucfirst($status); ?>.png" height="100" alt="<?php echo ucfirst($status); ?>">
        <div style="height: 25px; width: 0px;"></div>
        <div class="message <?php echo $status; ?>">
            <?php echo $message; ?>
        </div>
        <div style="height: 50px; width: 0px;"></div>
        <a href="internal_notification.php" class="btn-hover">OKAY</a>
        <div style='height: 100px; width: 0px;'></div>
        <div class='hairpop-up'></div>
    </div>
</body>
</html>
