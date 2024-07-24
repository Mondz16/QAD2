<?php
include 'connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['schedule_id'])) {
    $schedule_id = mysqli_real_escape_string($conn, $_POST['schedule_id']);
    $new_date = mysqli_real_escape_string($conn, $_POST['new_date']);
    $new_time = mysqli_real_escape_string($conn, $_POST['new_time']);

    // Update schedule with new date and time
    $sql_update_schedule = "UPDATE schedule SET schedule_date = ?, schedule_time = ? WHERE id = ?";
    $stmt_update_schedule = $conn->prepare($sql_update_schedule);
    $stmt_update_schedule->bind_param("ssi", $new_date, $new_time, $schedule_id);

    if ($stmt_update_schedule->execute()) {
        $college = isset($_POST['college']) ? mysqli_real_escape_string($conn, $_POST['college']) : '';

        // If schedule update is successful, update team status
        $new_status = 'pending'; // or whatever logic you have for setting the new status

        $sql_update_team_status = "UPDATE team SET status = ? WHERE schedule_id = ?";
        $stmt_update_team_status = $conn->prepare($sql_update_team_status);
        $stmt_update_team_status->bind_param("si", $new_status, $schedule_id);

        if ($stmt_update_team_status->execute()) {
            
        } else {
            echo "Error updating team status: " . $conn->error;
        }

        $stmt_update_team_status->close();
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
                    text-decoration: none;
                    display: inline-block;
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
                    Schedule updated successfully
                </div>
                <a class="button-primary" href="schedule_college.php?college=<?php echo urlencode($college); ?>#">OK</a>
            </div>
        </body>
        </html>
        <?php
    } else {
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
                    text-decoration: none;
                    display: inline-block;
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
                    Error updating schedule: <?php echo $conn->error; ?>
                </div>
                <a class="button-primary" href="schedule.php">OK</a>
            </div>
        </body>
        </html>
        <?php
    } 

    $stmt_update_schedule->close();
    $conn->close();

} else {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Invalid Request</title>
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
                text-decoration: none;
                display: inline-block;
            }

            .button-primary:hover {
                background-color: #259b42;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h2>Invalid Request</h2>
            <div class="message">
                This page should only be accessed through a valid form submission.
            </div>
            <a class="button-primary" href="schedule.php">OK</a>
        </div>
    </body>
    </html>
    <?php
}
?>
