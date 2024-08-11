<?php
include 'connection.php';
session_start();

if (!isset($_SESSION['user_id']) || substr($_SESSION['user_id'], 3, 2) !== '11') {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $schedule_id = intval($_POST['schedule_id']);
    $areas = $_POST['areas'];

    $conn->begin_transaction();

    try {
        foreach ($areas as $team_member_id => $area) {
            $sql_update = "UPDATE team SET area = ? WHERE id = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("si", $area, $team_member_id);
            $stmt_update->execute();
            $stmt_update->close();
        }

        // Update the team leader status only
        $sql_update_leader = "UPDATE team SET status = 'accepted' WHERE internal_users_id = ? AND schedule_id = ?";
        $stmt_update_leader = $conn->prepare($sql_update_leader);
        $stmt_update_leader->bind_param("si", $_SESSION['user_id'], $schedule_id);
        $stmt_update_leader->execute();
        $stmt_update_leader->close();

        $conn->commit();
        header("Location: internal_notification.php");
    } catch (Exception $e) {
        $conn->rollback();
        echo "
        <!DOCTYPE html>
<html lang=\"en\">
<head>
    <meta charset=\"UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <title>Operation Result</title>
    <link rel=\"stylesheet\" href=\"https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600&display=swap\">
    <link rel=\"stylesheet\" href=\"index.css\">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: \"Quicksand\", sans-serif;
        }
        body {
            background-color: #f9f9f9;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }
        .container {
            max-width: 600px;
            padding: 24px;
            background-color: #fff;
            border-radius: 20px;
            border: 2px solid #AFAFAF;
            text-align: center;
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
    <div class=\"popup-content\">
        <div style=\"height: 50px; width: 0px;\"></div>
        <img class=\"Error\" src=\"images/Error.png\" height=\"100\">
        <div style=\"height: 25px; width: 0px;\"></div>
        <div class=\"message\">
            Error: $e->getMessage();
        </div>
        <div style=\"height: 50px; width: 0px;\"></div>
            <a href=\"internal_notification.php\" class=\"btn-hover\">OKAY</a>
            <div style=\"height: 100px; width: 0px;\"></div>
            <div class=\"hairpop-up\"></div>
    </div>
</body>
</html>";
    }

    $conn->close();
} else {
    header("Location: internal_notification.php");
}
?>
