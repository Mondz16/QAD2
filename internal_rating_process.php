<?php
require 'vendor/autoload.php';
include 'connection.php';
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__, 'sensitive_information.env');
$dotenv->load();

session_start();

if (!isset($_SESSION['user_id']) || substr($_SESSION['user_id'], 3, 2) !== '11') {
    header("Location: login.php");
    exit();
}

$success = false;
$message = '';

// Handle the POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $schedule_id = $_POST['schedule_id'];
    $ratings = $_POST['area_rating'];  // Ratings for each area

    // Retrieve team_id for the specific schedule_id
    $sql_team = "SELECT id FROM team WHERE internal_users_id = ? AND schedule_id = ? AND status = 'accepted'";
    $stmt_team = $conn->prepare($sql_team);
    $stmt_team->bind_param("si", $user_id, $schedule_id);
    $stmt_team->execute();
    $stmt_team->bind_result($team_id);
    $stmt_team->fetch();
    $stmt_team->close();

    if (!$team_id) {
        $message = "No matching team found for the user.";
    } else {
        // Update team_areas with the ratings
        foreach ($ratings as $area_id => $rating) {
            $rating = floatval($rating); // Ensure that the rating is being passed as a float (decimal)

            $sql_update_areas = "UPDATE team_areas SET rating = ? WHERE team_id = ? AND area_id = ?";
            $stmt_update_areas = $conn->prepare($sql_update_areas);
            $stmt_update_areas->bind_param("dii", $rating, $team_id, $area_id);
            $stmt_update_areas->execute();
            $stmt_update_areas->close();
        }

        // Set session variable to indicate successful submission
        $_SESSION['rating_submitted'] = true;
        $success = true;
        $message = "Rating submitted successfully.";
    }
    $conn->close();
} else {
    $message = "Invalid request method.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rating Submission</title>
    <link rel="stylesheet" href="index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" />
    <style>
        body {
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }
    </style>
</head>
<body>
    <?php if ($success): ?>
        <div id="successPopup" class="popup">
            <div class="popup-content">
                <div style="height: 50px; width: 0px;"></div>
                <img class="Success" src="images/Success.png" height="100">
                <div style="height: 20px; width: 0px;"></div>
                <div class="popup-text"><?php echo $message; ?></div>
                <div style="height: 50px; width: 0px;"></div>
                <a href="internal_assessment.php" class="okay" id="closePopup">Okay</a>
                <div style="height: 100px; width: 0px;"></div>
                <div class="hairpop-up"></div>
            </div>
        </div>
        <script>
            document.getElementById('successPopup').style.display = 'block';

            document.getElementById('closePopup').addEventListener('click', function() {
                document.getElementById('successPopup').style.display = 'none';
            });

            window.addEventListener('click', function(event) {
                if (event.target == document.getElementById('successPopup')) {
                    document.getElementById('successPopup').style.display = 'none';
                }
            });
        </script>
    <?php else: ?>
        <p><?php echo $message; ?></p>
    <?php endif; ?>
</body>
</html>
