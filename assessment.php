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
    <link rel="stylesheet" href="admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgb(0,0,0);
            background-color: rgba(0,0,0,0.4);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        .check-symbol {
            color: green;
            font-size: 24px;
            margin-left: 10px;
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
                    $summariesQuery = "SELECT id, summary_file, team_id FROM summary WHERE team_id = '$teamLeaderId'";
                    $summariesResult = $conn->query($summariesQuery);
                    $summaries = $summariesResult->fetch_all(MYSQLI_ASSOC);
                    
                    if (count($summaries) > 0) {
                        foreach ($summaries as $summary) {
                            $teamId = $summary['team_id'];
                            $summaryFile = $summary['summary_file'];
                            $summaryId = $summary['id'];
                            
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
                                // Check if the summary has been approved
                                $approvedQuery = "SELECT id FROM approved_summary WHERE summary_id = '$summaryId'";
                                $approvedResult = $conn->query($approvedQuery);
                                $isApproved = $approvedResult->num_rows > 0;
                                
                                // Display assessment box with numbered content
                                echo "<div class='assessment-box'>";
                                echo "<h2>Assessment #" . $counter . "</h2>";
                                echo "<div class='assessment-details'>";
                                echo "<p><strong>College:</strong> " . $schedule['college_name'] . " <br> <strong>Program:</strong> " . $schedule['program_name'] . " <br> <strong>Level Applied:</strong> " . $schedule['level_applied'] . "</p>";
                                echo "<p><strong>Date:</strong> " . $schedule['schedule_date'] . " | <strong>Time:</strong> " . $schedule['schedule_time'] . "</p>";
                                echo "<h3>Summary File:</h3>";
                                echo "<a href='$summaryFile' download>Download Summary</a>";
                                if ($isApproved) {
                                    echo "<i class='fas fa-check check-symbol'></i>";
                                } else {
                                    echo "<button class='btn approve-btn' data-summary-file='$summaryFile'>Approve Summary</button>";
                                }
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

    <!-- Modal -->
    <div id="approvalModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Approve Summary</h2>
            <form id="approveForm" method="POST" action="approve_summary.php" enctype="multipart/form-data">
                <label for="qadOfficerName">QAD Officer Name:</label>
                <input type="text" id="qadOfficerName" name="qadOfficerName" required>
                <label for="qadOfficerSignature">QAD Officer Signature (PNG only):</label>
                <input type="file" id="qadOfficerSignature" name="qadOfficerSignature" accept="image/png" required>
                <input type="hidden" id="summaryFile" name="summaryFile">
                <button type="submit">Submit</button>
            </form>
        </div>
    </div>

    <script>
        // Get modal element
        var modal = document.getElementById("approvalModal");

        // Get the <span> element that closes the modal
        var span = document.getElementsByClassName("close")[0];

        // Get all approve buttons
        var approveBtns = document.getElementsByClassName("approve-btn");

        // Loop through approve buttons to add click event
        for (var i = 0; i < approveBtns.length; i++) {
            approveBtns[i].addEventListener("click", function() {
                var summaryFile = this.getAttribute("data-summary-file");

                document.getElementById("summaryFile").value = summaryFile;

                modal.style.display = "block";
            });
        }

        // When the user clicks on <span> (x), close the modal
        span.onclick = function() {
            modal.style.display = "none";
        }

        // When the user clicks anywhere outside of the modal, close it
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>
