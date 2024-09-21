<?php
include 'connection.php';
session_start();

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Retrieve the posted data
$schedule_id = $_POST['schedule_id'];
$assigned_areas = $_POST['area']; // Expected to be an associative array of team_member_id => area_ids

$success = true; // Track whether all operations succeed
$message = "";

// Prepare to insert into team_areas
foreach ($assigned_areas as $team_member_id => $areas) {
    // Check if the team member exists in the team table
    $stmt = $conn->prepare("SELECT id FROM team WHERE id = ?"); // Assuming id is used to identify the team member
    $stmt->bind_param("i", $team_member_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        // Clear previous assignments for this team member in the junction table if necessary
        $deleteStmt = $conn->prepare("DELETE FROM team_areas WHERE team_id = ?");
        $deleteStmt->bind_param("i", $team_member_id);
        $deleteStmt->execute();

        // Insert new area assignments
        foreach ($areas as $area_id) {
            $insertStmt = $conn->prepare("INSERT INTO team_areas (team_id, area_id) VALUES (?, ?)");
            $insertStmt->bind_param("ii", $team_member_id, $area_id);

            if ($insertStmt->execute()) {
                $message = "Areas Assigned Successfully";
            } else {
                $message1 = "Asssigning of areas failed." . $insertStmt->error . "<br>";
                $success = false;
            }
        }
    } else {
        $message2 = "Team members are not found in the database table.";
        $success = false;
    }

    $stmt->close(); // Close the select statement
}

if ($success) {
    // Success message in HTML format
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
            <img class=\"Error\" src=\"images/Success.png\" height=\"100\">
            <div style=\"height: 25px; width: 0px;\"></div>
            <div class=\"message\">
                $message
            </div>
            <div style=\"height: 50px; width: 0px;\"></div>
            <a href=\"internal_assessment.php\" class=\"btn-hover\">OKAY</a>
            <div style=\"height: 100px; width: 0px;\"></div>
            <div class=\"hairpop-up\"></div>
        </div>
    </body>
    </html>";
} else {
    // Error message in HTML format
    echo "
    <!DOCTYPE html>
    <html lang=\"en\">
    <head>
        <meta charset=\"UTF-8\">
        <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
        <title>Error</title>
        <link rel=\"stylesheet\" href=\"https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600&display=swap\">
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
                $message1
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
?>
