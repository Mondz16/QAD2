<?php
include 'connection.php';
session_start();

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Fetch the schedule ID and areas from the POST request
    $schedule_id = intval($_POST['schedule_id']);
    $areas = $_POST['area'];  // This will be an array with team member IDs as keys and assigned areas as values

    // Start a transaction
    $conn->begin_transaction();

    try {
        // Loop through each team member and update their assigned area
        foreach ($areas as $team_member_id => $area) {
            $area = trim($area);  // Ensure any whitespace is trimmed

            // Prepare the SQL statement to update the area
            $sql_update = "UPDATE team SET area = ? WHERE id = ? AND schedule_id = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("sii", $area, $team_member_id, $schedule_id);
            $stmt_update->execute();
            $stmt_update->close();
        }

        // Commit the transaction
        $conn->commit();

        // Redirect back to the internal assessment page or a success page
        header("Location: internal_assessment.php");
        exit();
    } catch (Exception $e) {
        // Rollback the transaction in case of an error
        $conn->rollback();

        // Display an error message
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
            background-color: rgba(0, 0, 0, 0.5);
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
            Error: {$e->getMessage()};
        </div>
        <div style=\"height: 50px; width: 0px;\"></div>
            <a href=\"internal_assessment.php\" class=\"btn-hover\">OKAY</a>
            <div style=\"height: 100px; width: 0px;\"></div>
            <div class=\"hairpop-up\"></div>
    </div>
</body>
</html>";
    }

    $conn->close();
} else {
    // Redirect to the internal assessment page if the request method is not POST
    header("Location: internal_assessment.php");
    exit();
}
?>
