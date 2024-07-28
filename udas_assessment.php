<?php
session_start();
require 'connection.php'; // Include your database connection file

// Fetch approved schedules
$approvedSchedulesQuery = "
    SELECT s.id, s.level_applied, s.schedule_date, s.schedule_time, 
           c.college_name, p.program_name, ua.udas_assessment_file
    FROM schedule s
    JOIN college c ON s.college_code = c.code
    JOIN program p ON s.program_id = p.id
    LEFT JOIN udas_assessment ua ON s.id = ua.schedule_id
    WHERE s.schedule_status = 'approved'
";
$approvedSchedulesResult = $conn->query($approvedSchedulesQuery);
$approvedSchedules = $approvedSchedulesResult->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UDAS Assessment</title>
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

        /* Modal styles */
        .modal {
            display: none; 
            position: fixed; 
            z-index: 1; 
            padding-top: 60px; 
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
            margin: 5% auto; 
            padding: 20px;
            border: 1px solid #888;
            width: 80%; 
            max-width: 600px;
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
            <h1>Approved Schedules</h1>
            
            <?php
            if (count($approvedSchedules) > 0) {
                $counter = 1; // Counter for numbering assessments
                foreach ($approvedSchedules as $schedule) {
                    $scheduleDate = date("F j, Y", strtotime($schedule['schedule_date']));
                    $scheduleTime = date("g:i A", strtotime($schedule['schedule_time']));
                    echo "<div class='assessment-box'>";
                    echo "<h2>Schedule #" . $counter . "</h2>";
                    echo "<div class='assessment-details'>";
                    echo "<p><strong>College:</strong> " . $schedule['college_name'] . " <br> <strong>Program:</strong> " . $schedule['program_name'] . " <br> <strong>Level Applied:</strong> " . $schedule['level_applied'] . "</p>";
                    echo "<p><strong>Date:</strong> " . $scheduleDate . " | <strong>Time:</strong> " . $scheduleTime . "</p>";
                    
                    if (!empty($schedule['udas_assessment_file'])) {
                        echo "<p><strong>Note:</strong> You have already submitted UDAS Assessment for this schedule.</p>";
                        echo "<p><strong>Assessment File:</strong> <a href='" . $schedule['udas_assessment_file'] . "' download>Download</a></p>";
                    } else {
                        echo "<button class='btn open-modal' data-schedule='" . json_encode($schedule) . "'>UDAS Assessment</button>";
                    }

                    echo "</div>";
                    echo "</div>";
                    
                    $counter++; // Increment counter for next assessment
                }
            } else {
                echo "<p>No approved schedules found.</p>";
            }
            ?>
        </div>
    </div>

    <!-- The Modal -->
    <div id="udasModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>UDAS Assessment</h2>
            <form action="udas_assessment_process.php" method="post" enctype="multipart/form-data">
                <input type="hidden" id="schedule_id" name="schedule_id">
                <div>
                    <label for="college">College:</label>
                    <input type="text" id="college" name="college" readonly>
                </div>
                <div>
                    <label for="program">Program:</label>
                    <input type="text" id="program" name="program" readonly>
                </div>
                <div>
                    <label for="level_applied">Level Applied:</label>
                    <input type="text" id="level_applied" name="level_applied" readonly>
                </div>
                <div>
                    <label for="area">Area:</label>
                    <input type="text" id="area" name="area" required>
                </div>
                <div>
                    <label for="date">Date:</label>
                    <input type="text" id="date" name="date" readonly>
                </div>
                <div>
                    <label for="time">Time:</label>
                    <input type="text" id="time" name="time" readonly>
                </div>
                <div>
                    <label for="comments">Comments:</label>
                    <textarea id="comments" name="comments"></textarea>
                </div>
                <div>
                    <label for="remarks">Remarks:</label>
                    <textarea id="remarks" name="remarks"></textarea>
                </div>
                <div>
                    <label for="current_datetime">Current Date and Time:</label>
                    <input type="text" id="current_datetime" name="current_datetime" readonly>
                </div>
                <div>
                    <label for="qad_officer">QAD Officer:</label>
                    <input type="text" id="qad_officer" name="qad_officer" required>
                </div>
                <div>
                    <label for="qad_officer_signature">QAD Officer Signature (PNG format):</label>
                    <input type="file" id="qad_officer_signature" name="qad_officer_signature" accept="image/png" required>
                </div>
                <div>
                    <label for="qad_director">QAD Director:</label>
                    <input type="text" id="qad_director" name="qad_director" required>
                </div>
                <button type="submit" class="btn">Submit</button>
            </form>
        </div>
    </div>

    <script>
        // Get the modal
        var modal = document.getElementById("udasModal");

        // Get the button that opens the modal
        var btns = document.getElementsByClassName("open-modal");

        // Get the <span> element that closes the modal
        var span = document.getElementsByClassName("close")[0];

        // When the user clicks the button, open the modal
        for (let btn of btns) {
            btn.onclick = function() {
                var schedule = JSON.parse(this.getAttribute('data-schedule'));
                document.getElementById('schedule_id').value = schedule.id;
                document.getElementById('college').value = schedule.college_name;
                document.getElementById('program').value = schedule.program_name;
                document.getElementById('level_applied').value = schedule.level_applied;
                document.getElementById('date').value = new Date(schedule.schedule_date).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
                
                // Fix the time parsing issue
                var timeParts = schedule.schedule_time.split(':');
                var hours = parseInt(timeParts[0]);
                var minutes = parseInt(timeParts[1]);
                var ampm = hours >= 12 ? 'PM' : 'AM';
                hours = hours % 12;
                hours = hours ? hours : 12; // the hour '0' should be '12'
                var formattedTime = hours + ':' + (minutes < 10 ? '0'+minutes : minutes) + ' ' + ampm;
                
                document.getElementById('time').value = formattedTime;

                // Set current date and time
                var now = new Date();
                var formattedNow = now.toLocaleString('en-US', { month: 'long', day: 'numeric', year: 'numeric', hour: 'numeric', minute: 'numeric', hour12: true });
                document.getElementById('current_datetime').value = formattedNow;

                modal.style.display = "block";
            }
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
