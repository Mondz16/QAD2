<?php
include 'connection.php';
session_start();

if (!isset($_SESSION['user_id']) || substr($_SESSION['user_id'], 3, 2) !== '11') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user details
$sql_user = "SELECT first_name, middle_initial, last_name, email, college_code, profile_picture FROM internal_users WHERE user_id = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("s", $user_id);
$stmt_user->execute();
$stmt_user->bind_result($first_name, $middle_initial, $last_name, $email, $college_code, $profile_picture);
$stmt_user->fetch();
$stmt_user->close();

// Fetch college name
$sql_college = "SELECT college_name FROM college WHERE code = ?";
$stmt_college = $conn->prepare($sql_college);
$stmt_college->bind_param("s", $college_code);
$stmt_college->execute();
$stmt_college->bind_result($user_college_name);
$stmt_college->fetch();
$stmt_college->close();

$accreditor_type = (substr($user_id, 3, 2) == '11') ? 'Internal Accreditor' : 'External Accreditor';

// Fetch user details for displaying in the form
$sql_user_details = "SELECT first_name, middle_initial, last_name FROM internal_users WHERE user_id = ?";
$stmt_user_details = $conn->prepare($sql_user_details);
$stmt_user_details->bind_param("s", $user_id);
$stmt_user_details->execute();
$stmt_user_details->bind_result($first_name, $middle_initial, $last_name);
$stmt_user_details->fetch();
$full_name = $first_name . ' ' . $middle_initial . '. ' . $last_name;
$stmt_user_details->close();

// Fetch schedule details for the logged-in user with status 'accepted'
$sql_schedules = "
    SELECT s.id AS schedule_id, c.college_name, p.program_name, s.level_applied, s.schedule_date, s.schedule_time, t.id AS team_id, t.role, t.area
    FROM team t
    JOIN schedule s ON t.schedule_id = s.id
    JOIN program p ON s.program_id = p.id
    JOIN college c ON s.college_code = c.code
    WHERE t.internal_users_id = ? 
    AND t.status = 'accepted'
    AND s.schedule_status NOT IN ('cancelled', 'finished')
";
$stmt_schedules = $conn->prepare($sql_schedules);
$stmt_schedules->bind_param("s", $user_id);
$stmt_schedules->execute();
$stmt_schedules->store_result();
$stmt_schedules->bind_result($schedule_id, $college_name, $program_name, $level_applied, $schedule_date, $schedule_time, $team_id, $role, $area);
$schedules = [];
while ($stmt_schedules->fetch()) {
    $schedules[] = [
        'schedule_id' => $schedule_id,
        'college_name' => $college_name,
        'program_name' => $program_name,
        'level_applied' => $level_applied,
        'schedule_date' => $schedule_date,
        'schedule_time' => $schedule_time,
        'team_id' => $team_id,
        'role' => $role,
        'area' => $area
    ];
}
$stmt_schedules->close();

// Fetch existing assessments and summaries for the user
$existing_assessments = [];
$existing_summaries = [];

$sql_assessments = "SELECT team_id FROM assessment WHERE team_id IN (SELECT id FROM team WHERE internal_users_id = ?)";
$stmt_assessments = $conn->prepare($sql_assessments);
$stmt_assessments->bind_param("s", $user_id);
$stmt_assessments->execute();
$stmt_assessments->bind_result($team_id);
while ($stmt_assessments->fetch()) {
    $existing_assessments[] = $team_id;
}
$stmt_assessments->close();

$sql_summaries = "SELECT team_id FROM summary WHERE team_id IN (SELECT id FROM team WHERE internal_users_id = ?)";
$stmt_summaries = $conn->prepare($sql_summaries);
$stmt_summaries->bind_param("s", $user_id);
$stmt_summaries->execute();
$stmt_summaries->bind_result($team_id);
while ($stmt_summaries->fetch()) {
    $existing_summaries[] = $team_id;
}
$stmt_summaries->close();

// Fetch team members and their assessment status
$team_members = [];
$sql_team_members = "
    SELECT t.schedule_id, t.internal_users_id, iu.first_name, iu.middle_initial, iu.last_name, t.id AS team_id, 
    (SELECT a.assessment_file FROM assessment a WHERE a.team_id = t.id LIMIT 1) AS assessment_file, t.role
    FROM team t
    JOIN internal_users iu ON t.internal_users_id = iu.user_id
    WHERE t.schedule_id IN (SELECT schedule_id FROM team WHERE internal_users_id = ?)
";
$stmt_team_members = $conn->prepare($sql_team_members);
$stmt_team_members->bind_param("s", $user_id);
$stmt_team_members->execute();
$stmt_team_members->bind_result($team_schedule_id, $team_member_id, $team_member_first_name, $team_member_middle_initial, $team_member_last_name, $team_member_team_id, $team_member_assessment_file, $team_member_role);
while ($stmt_team_members->fetch()) {
    $team_members[$team_schedule_id][] = [
        'user_id' => $team_member_id,
        'name' => $team_member_first_name . ' ' . $team_member_middle_initial . '. ' . $team_member_last_name,
        'team_id' => $team_member_team_id,
        'assessment_file' => $team_member_assessment_file,
        'role' => $team_member_role
    ];
}
$stmt_team_members->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Internal Accreditor - Assessment</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" />
    
</head>
<body>
    <div class="wrapper">
        <div class="hair" style="height: 15px; background: linear-gradient(275.52deg, #973939 0.28%, #DC7171 100%);"></div>
        <div class="container">
            <div class="header">
                <div class="headerLeft">
                    <div class="USePData">
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
                    <div class="SDMD">
                        <div class="headerRightText">
                            <h style="color: rgb(87, 87, 87); font-weight: 600; font-size: 16px;">Quality Assurance Division</h>
                        </div>
                        <div style="height: 0px; width: 16px;"></div>
                        <div style="height: 32px; width: 1px; background: #E5E5E5"></div>
                        <div style="height: 0px; width: 16px;"></div>
                        <img class="USeP" src="images/SDMDLogo.png" height="36">
                    </div>
                </div>
            </div>
        </div>
        <div style="height: 1px; width: 100%; background: #E5E5E5"></div>
    </div>
    <header class="site-header">
        <nav>
            <ul class="nav-list">
                <li class="btn"><a href="internal.php">Home</a></li>
                <li class="btn"><a href="internal_notification.php">Notifications</a></li>
                <li class="btn"><a href="internal_orientation.php">Orientation</a></li>
                <li class="btn"><a href="internal_assessment.php">Assessment</a></li>
                <li class="btn"><a href="logout.php">Log Out</a></li>
            </ul>
        </nav>
    </header>
    <div style="height: 30px; width: 0px;"></div>
    <div class="container">
        <div class="profile">
            <img src="<?php echo $profile_picture; ?>" alt="Profile Picture" class="profile-picture">
            <div class="profile-details">
                <p class="profile-name"><?php echo $first_name . ' ' . $middle_initial . '. ' . $last_name; ?></p>
                <p class="profile-type"><?php echo $user_college_name; ?> (<?php echo $accreditor_type; ?>)</p>
            </div>
        </div>
    </div>
    <div class="assessments">
        <h2>Assessment</h2>
        <?php foreach ($schedules as $schedule): ?>
            <div class="assessment">
                <p><strong>College:</strong> <?php echo htmlspecialchars($schedule['college_name']); ?></p>
                <p><strong>Program:</strong> <?php echo htmlspecialchars($schedule['program_name']); ?></p>
                <p><strong>Level Applied:</strong> <?php echo htmlspecialchars($schedule['level_applied']); ?></p>
                <p><strong>Schedule Date:</strong> <?php 
                    $schedule_date = new DateTime($schedule['schedule_date']);
                    echo $schedule_date->format('F j, Y'); 
                ?></p>
                <p><strong>Schedule Time:</strong> <?php 
                    $schedule_time = new DateTime($schedule['schedule_time']);
                    echo $schedule_time->format('g:i A'); 
                ?></p>
                <?php if ($schedule['role'] === 'team leader'): ?>
                    <?php
                        $team_member_count = 0;
                        $submitted_count = 0;
                        foreach ($team_members[$schedule['schedule_id']] as $member) {
                            if ($member['role'] !== 'team leader') {
                                $team_member_count++;
                                if ($member['assessment_file']) {
                                    $submitted_count++;
                                }
                            }
                        }
                    ?>
                    <p><?php echo $submitted_count; ?>/<?php echo $team_member_count; ?> team members have submitted assessments.</p>
                    <?php if (in_array($schedule['team_id'], $existing_summaries)): ?>
                        <p>You have already submitted a summary for this schedule.</p>
                    <?php elseif ($submitted_count < $team_member_count): ?>
                        <p>All team members must submit their assessments before you can submit a summary.</p>
                    <?php else: ?>
                        <button onclick="SummaryopenPopup(<?php echo htmlspecialchars(json_encode($schedule)); ?>)">Summary</button>
                        <h3>Submitted Assessments:</h3>
                        <ul>
                        <?php foreach ($team_members[$schedule['schedule_id']] as $member): ?>
                            <?php if ($member['assessment_file'] && $member['role'] !== 'team leader'): ?>
                                <li><?php echo htmlspecialchars($member['name']); ?>: <a href="<?php echo htmlspecialchars($member['assessment_file']); ?>">Download Assessment</a></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                <?php else: ?>
                    <?php if (in_array($schedule['team_id'], $existing_assessments)): ?>
                        <p>You have already submitted an assessment for this schedule.</p>
                    <?php else: ?>
                        <button onclick="openPopup(<?php echo htmlspecialchars(json_encode($schedule)); ?>)">Assess</button>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <div class="back-btn">
            <a href="internal.php" class="btn">Back to Home</a>
        </div>
    </div>

    <!-- Popup Form for Team Member -->
    <div class="assessmentmodal" id="popup">
        <div class="assessmentmodal-content">
            <span class="close" onclick="closePopup()">&times;</span>
            <h2>Assessment Form</h2>
            <form action="internal_assessment_process.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="schedule_id" id="modal_schedule_id">
                <label for="college">College:</label>
                <input type="text" id="college" name="college" readonly><br><br>
                <label for="program">Program:</label>
                <input type="text" id="program" name="program" readonly><br><br>
                <label for="level">Level Applied:</label>
                <input type="text" id="level" name="level" readonly><br><br>
                <label for="date">Schedule Date:</label>
                <input type="text" id="date" name="date" readonly><br><br>
                <label for="result">Result:</label>
                <select id="result" name="result" required>
                    <option value="Ready">Ready</option>
                    <option value="Needs Improvement">Needs Improvement</option>
                    <option value="Revisit">Revisit</option>
                </select><br><br>
                <label for="area_evaluated">Area Evaluated:</label>
                <input type="text" id="area_evaluated" name="area_evaluated" required><br><br>
                <label for="findings">Findings:</label>
                <textarea id="findings" name="findings" rows="4" required></textarea><br><br>
                <label for="recommendations">Recommendations:</label>
                <textarea id="recommendations" name="recommendations" rows="4" required></textarea><br><br>
                <label for="evaluator">Evaluator:</label>
                <input type="text" id="evaluator" name="evaluator" value="<?php echo $full_name; ?>" readonly><br><br>
                <label for="evaluator_signature">Evaluator Signature (PNG format):</label>
                <input type="file" id="evaluator_signature" name="evaluator_signature" accept="image/png" required><br><br>
                <div class="modal-footer">
                    <button type="button" onclick="closePopup()">Close</button>
                    <button type="submit">Submit Assessment</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Popup Form for Team Leader -->
    <div class="Summarymodal" id="Summarypopup">
        <div class="Summarymodal-content">
            <span class="Summaryclose" onclick="SummaryclosePopup()">&times;</span>
            <h2>Summary Form</h2>
            <form action="internal_summary_assessment_process.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="schedule_id" id="Summarymodal_schedule_id">
                <label for="college">College:</label>
                <input type="text" id="Summarycollege" name="college" readonly><br><br>
                <label for="program">Program:</label>
                <input type="text" id="Summaryprogram" name="program" readonly><br><br>
                <label for="level">Level Applied:</label>
                <input type="text" id="Summarylevel" name="level" readonly><br><br>
                <label for="date">Schedule Date:</label>
                <input type="text" id="Summarydate" name="date" readonly><br><br>
                <label for="areas">Areas Evaluated:</label>
                <textarea id="areas" name="areas" rows="4" readonly></textarea><br><br>
                <label for="results">Results:</label>
                <textarea id="results" name="results" rows="4" required></textarea><br><br>
                <label for="evaluator">Evaluator:</label>
                <input type="text" id="Summaryevaluator" name="evaluator" value="<?php echo $full_name; ?>" readonly><br><br>
                <label for="Summaryevaluator_signature">Team Leader Signature (PNG format):</label>
                <input type="file" id="Summaryevaluator_signature" name="evaluator_signature" accept="image/png" required><br><br>
                <div class="modal-footer">
                    <button type="button" onclick="SummaryclosePopup()">Close</button>
                    <button type="submit">Submit Summary</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openPopup(schedule) {
            document.getElementById('modal_schedule_id').value = schedule.schedule_id;
            document.getElementById('college').value = schedule.college_name;
            document.getElementById('program').value = schedule.program_name;
            document.getElementById('level').value = schedule.level_applied;
            document.getElementById('date').value = schedule.schedule_date;
            document.getElementById('area_evaluated').value = schedule.area;

            document.getElementById('popup').style.display = 'block';
        }

        function closePopup() {
            document.getElementById('popup').style.display = 'none';
        }

        function SummaryopenPopup(schedule) {
            document.getElementById('Summarymodal_schedule_id').value = schedule.schedule_id;
            document.getElementById('Summarycollege').value = schedule.college_name;
            document.getElementById('Summaryprogram').value = schedule.program_name;
            document.getElementById('Summarylevel').value = schedule.level_applied;
            document.getElementById('Summarydate').value = schedule.schedule_date;

            // Fetch team members' areas
            var areasXhr = new XMLHttpRequest();
            areasXhr.open('GET', 'get_team_areas.php?schedule_id=' + schedule.schedule_id, true);
            areasXhr.onreadystatechange = function() {
                if (areasXhr.readyState == 4 && areasXhr.status == 200) {
                    var areas = JSON.parse(areasXhr.responseText);
                    document.getElementById('areas').value = areas.join('\n');
                    document.getElementById('areas').readOnly = true;
                    document.getElementById('Summarypopup').style.display = 'block';
                } else {
                    console.error('Failed to fetch team areas.');
                }
            };
            areasXhr.send();
        }

        function SummaryclosePopup() {
            document.getElementById('Summarypopup').style.display = 'none';
        }
    </script>
</body>
</html>