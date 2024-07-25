<?php
session_start();
require 'connection.php'; // Include your database connection file

// Fetch team leaders
$teamLeadersQuery = "SELECT id FROM team WHERE role = 'team leader'";
$teamLeadersResult = $conn->query($teamLeadersQuery);
$teamLeaders = $teamLeadersResult->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assessment</title>
    <link rel="stylesheet" href="admin_style.css"> <!-- Include admin style here -->
    <style>
        /* Additional CSS for numbered boxes */
        .assessment-box {
            border: 1px solid #ccc;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .assessment-box h2 {
            font-size: 18px;
            margin-bottom: 10px;
        }
        
        .assessment-details {
            margin-left: 20px;
        }

        /* Scrollable container for assessments */
        .scrollable-container {
            max-height: 500px; /* Adjust the maximum height as needed */
            overflow-y: auto;
            overflow-x: hidden;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="hair" style="height: 15px; background: linear-gradient(275.52deg, #973939 0.28%, #DC7171 100%);"></div>

        <div class="container">
            <div class="header">
                 <div class="headerLeft">
                    <div class=USePData>
                        <img class="USeP" src="images/USePLogo.png" height="36">
                        <div style="height: 0px; width: 16px;"></div>
                        <div style="height: 32px; width: 1px; background: #E5E5E5"></div>
                        <div style="height: 0px; width: 16px;"></div>
                        <div class="headerLeftText">
                            <div class="onedata" style="height: 100%; width: 100%; display: flex; flex-flow: unset; place-content: unset; align-items: unset; overflow: unset;">
                                <h><span class="one" style="color: rgb(229, 156, 36); font-weight: 600; font-size: 18px;">One</span>
                                    <span class="datausep" style="color: rgb(151, 57, 57); font-weight: 600; font-size: 18px;">Data.</span>
                                    <span class="one" style="color: rgb(229, 156, 36); font-weight: 600; font-size: 18px;">One</span>
                                    <span class="datausep" style="color: rgb(151, 57, 57); font-weight: 600; font-size: 18px;">USeP.</span></h>
                            </div>
                                <h>Accreditor Portal</h>
                        </div>
                    </div>
                </div>
                <div class="headerRight">
                    <a class="btn" href="logout.php">Log Out</a>
                </div>
            </div>
        </div>
        <div style="height: 1px; width: 100%; background: #E5E5E5"></div>
        <div style="height: 32px; width: 0px;"></div>
        <div class="body">
            <div class="bodyLeft">
                <a class="btn" href="admin.php">Back</a>
            </div>
        </div>
        <div class="admin-content scrollable-container">
            <h1>Assessments</h1>
            
            <?php
            if (count($teamLeaders) > 0) {
                $counter = 1; // Counter for numbering assessments
                foreach ($teamLeaders as $leader) {
                    $teamLeaderId = $leader['id'];
                    
                    // Fetch summaries for the team leader
                    $summariesQuery = "SELECT summary_file, team_id FROM summary WHERE team_id = '$teamLeaderId'";
                    $summariesResult = $conn->query($summariesQuery);
                    $summaries = $summariesResult->fetch_all(MYSQLI_ASSOC);
                    
                    if (count($summaries) > 0) {
                        foreach ($summaries as $summary) {
                            $teamId = $summary['team_id'];
                            $summaryFile = $summary['summary_file'];
                            
                            // Fetch schedule details for the team
                            $scheduleQuery = "
                                SELECT s.id, s.level_applied, s.schedule_date, s.schedule_time, 
                                       c.college_name, p.program_name
                                FROM schedule s
                                JOIN college c ON s.college_code = c.code
                                JOIN program p ON s.program_id = p.id
                                WHERE s.id = (
                                    SELECT schedule_id FROM team WHERE id = '$teamId'
                                )
                            ";
                            $scheduleResult = $conn->query($scheduleQuery);
                            $schedule = $scheduleResult->fetch_assoc();
                            
                            if ($schedule) {
                                // Display assessment box with numbered content
                                echo "<div class='assessment-box'>";
                                echo "<h2>Assessment #" . $counter . "</h2>";
                                echo "<div class='assessment-details'>";
                                echo "<p><strong>College:</strong> " . $schedule['college_name'] . " <br> <strong>Program:</strong> " . $schedule['program_name'] . " <br> <strong>Level Applied:</strong> " . $schedule['level_applied'] . "</p>";
                                echo "<p><strong>Date:</strong> " . $schedule['schedule_date'] . " | <strong>Time:</strong> " . $schedule['schedule_time'] . "</p>";
                                echo "<h3>Summary File:</h3>";
                                echo "<a href='$summaryFile' download>Download Summary</a>";
                                echo "</div>";
                                echo "</div>";

                                
                                $counter++; // Increment counter for next assessment
                            }
                        }
                    }
                }
            } else {
                echo "<p>No team leaders found.</p>";
            }
            ?>
            
        </div>
    </div>
</body>
</html>
