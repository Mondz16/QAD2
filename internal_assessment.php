<?php
include 'connection.php';
session_start();

date_default_timezone_set('Asia/Manila');
$current_date = date('F j, Y'); // Format: "Month Day, Year"

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Check user type and redirect accordingly
if ($user_id === 'admin' && basename($_SERVER['PHP_SELF']) !== 'admin_sidebar.php') {
    header("Location: admin_sidebar.php");
    exit();
} else {
    $user_type_code = substr($user_id, 3, 2);

    if ($user_type_code === '11') {
        // Internal user
        if (basename($_SERVER['PHP_SELF']) !== 'internal_assessment.php') {
            header("Location: internal.php");
            exit();
        }
    } elseif ($user_type_code === '22') {
        // External user
        if (basename($_SERVER['PHP_SELF']) !== 'external.php') {
            header("Location: external.php");
            exit();
        }
    } else {
        // Handle unexpected user type, redirect to login or error page
        header("Location: login.php");
        exit();
    }
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

// Fetch approved assessments
$approved_assessments = [];
$sql_approved_assessments = "SELECT assessment_id FROM approved_assessment";
$result_approved_assessments = $conn->query($sql_approved_assessments);
while ($row = $result_approved_assessments->fetch_assoc()) {
    $approved_assessments[] = $row['assessment_id'];
}

// Fetch team members and their assessment status
$team_members = [];
$sql_team_members = "
    SELECT t.schedule_id, t.internal_users_id, iu.first_name, iu.middle_initial, iu.last_name, t.id AS team_id, 
    (SELECT a.id FROM assessment a WHERE a.team_id = t.id LIMIT 1) AS assessment_id, 
    (SELECT a.assessment_file FROM assessment a WHERE a.team_id = t.id LIMIT 1) AS assessment_file, t.role
    FROM team t
    JOIN internal_users iu ON t.internal_users_id = iu.user_id
    WHERE t.schedule_id IN (SELECT schedule_id FROM team WHERE internal_users_id = ?)
";
$stmt_team_members = $conn->prepare($sql_team_members);
$stmt_team_members->bind_param("s", $user_id);
$stmt_team_members->execute();
$stmt_team_members->bind_result($team_schedule_id, $team_member_id, $team_member_first_name, $team_member_middle_initial, $team_member_last_name, $team_member_team_id, $team_member_assessment_id, $team_member_assessment_file, $team_member_role);
while ($stmt_team_members->fetch()) {
    $team_members[$team_schedule_id][] = [
        'user_id' => $team_member_id,
        'name' => $team_member_first_name . ' ' . $team_member_middle_initial . '. ' . $team_member_last_name,
        'team_id' => $team_member_team_id,
        'assessment_id' => $team_member_assessment_id,
        'assessment_file' => $team_member_assessment_file,
        'role' => $team_member_role
    ];
}
$stmt_team_members->close();

// Check NDA status for each schedule
$nda_signed_status = [];
foreach ($schedules as $schedule) {
    $sql_nda = "SELECT id FROM NDA WHERE team_id = ?";
    $stmt_nda = $conn->prepare($sql_nda);
    $stmt_nda->bind_param("i", $schedule['team_id']);
    $stmt_nda->execute();
    $stmt_nda->store_result();
    $nda_signed_status[$schedule['schedule_id']] = $stmt_nda->num_rows > 0;
    $stmt_nda->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Internal Accreditor - Assessment</title>
    <link rel="stylesheet" href="index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
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
                    <div class="QAD">
                        <div class="headerRightText">
                            <h style="color: rgb(87, 87, 87); font-weight: 600; font-size: 16px;">Quality Assurance Division</h>
                        </div>
                        <div style="height: 0px; width: 16px;"></div>
                        <div style="height: 32px; width: 1px; background: #E5E5E5"></div>
                        <div style="height: 0px; width: 16px;"></div>
                        <img class="USeP" src="images/QADLogo.png" height="36">
                    </div>
                </div>
            </div>
        </div>

        <div style="height: 1px; width: 100%; background: #E5E5E5"></div>
        <div style="height: 10px; width: 0px;"></div>
        <div class="container">
            <div class="header1">
                <div class="nav-list">
                    <a href="internal.php" class="profile1">Profile <i class="fa-regular fa-user"></i></a>
                    <a href="internal_notification.php" class="orientation1">NOTIFICATION<i class="fa-regular fa-bell"></i></i></a>
                    <a href="internal_assessment.php" class="active assessment1">Assessment<i class="fa-solid fa-medal"></i></a>
                    <a href="internal_orientation.php" class="orientation1">Orientation<i class="fa-regular fa-calendar"></i></a>
                    <a href="logout.php" class="logout"><i class="fa-solid fa-arrow-right-from-bracket"></i></a>
                </div>
            </div>
        </div>
    <div class="container">
        <div style="height: 32px;"></div>
        <div class="orientation2">
            <?php if (!empty($schedules)): ?>
                <?php foreach ($schedules as $schedule): ?>
                    <div class="notification-list1">
                        <div class="orientation3">
                             <div class="container">
                                <div class="body4">
                                    <div class="bodyLeft2" style="margin-right: ;">
                            <p>College <br>
                                    <div style="height: 10px;"></div>
                                    <div class="orientationname">
                                        <div class="nameContainer">
                                            <?php echo htmlspecialchars($schedule['college_name']); ?>
                                        </div>
                                </div></p>
                                <div style="height: 20px;"></div>
                            <p>PROGRAM <br>
                                <div style="height: 10px;"></div>
                                <div class="orientationname">
                                    <div class="nameContainer">
                                <?php echo htmlspecialchars($schedule['program_name']); ?>
                            </div>
                            </div></p>
                            <div class="orientationname">
                                    <div class="titleContainer">
                                        <p>LEVEL APPLIED</p>
                                    </div>
                                    <div class="titleContainer">
                                        <p>DATE</p>
                                    </div>
                                <div class="titleContainer">
                                            <p>TIME</p>
                                </div>
                            </div>
                            <div class="orientationname">
                            <div class="nameContainer orientationContainer1">
                                <?php echo htmlspecialchars($schedule['level_applied']); ?>
                            </div>
                            <div class="nameContainer orientationContainer"><?php 
                                $schedule_date = new DateTime($schedule['schedule_date']);
                                echo $schedule_date->format('F j, Y'); 
                            ?>
                        </div>
                            <div class="nameContainer orientationContainer"><?php 
                                $schedule_time = new DateTime($schedule['schedule_time']);
                                echo $schedule_time->format('g:i A'); 
                            ?>
                        </div>
                        </div>
                        </div>
                        <div class="bodyRight2">
                            <?php if ($schedule['role'] === 'Team Leader'): ?>
                                <?php if (!$nda_signed_status[$schedule['schedule_id']]): ?>
                                    <p>NON-DISCLOSURE AGREEMENT</p>
                                    <div style="height: 10px;"></div>
                                    <button class="assessment-button" onclick="openNdaPopup('<?php echo $full_name; ?>', <?php echo $schedule['team_id']; ?>)">SIGN</button>
                                <?php else: ?>
                                    <?php
                                        $team_member_count = 0;
                                        $submitted_count = 0;
                                        $approved_count = 0;
                                        foreach ($team_members[$schedule['schedule_id']] as $member) {
                                            if ($member['role'] !== 'Team Leader') {
                                                $team_member_count++;
                                                if ($member['assessment_file']) {
                                                    $submitted_count++;
                                                }
                                                if (in_array($member['assessment_id'], $approved_assessments)) {
                                                    $approved_count++;
                                                }
                                            }
                                        }
                                    ?>
                                    <p>MEMBER SUBMISSION STATUS</p>
                                    <div style="height: 10px;"></div>
                                    <div class="assessmentname2">
                                        <div class="nameContainer">
                                            <p><?php echo $submitted_count; ?>/<?php echo $team_member_count; ?> SUBMITTED ASSESSMENTS</p>
                                        </div>
                                    </div>
                                    <div style="height: 20px;"></div>
                                    <p>TEAM MEMBERS ASSESSMENT</p>
                                    <div style="height: 10px;"></div>
                                    <ul style="list-style: none; font-size: 18px;">
                                    <?php foreach ($team_members[$schedule['schedule_id']] as $member): ?>
                                        <?php if ($member['assessment_file'] && $member['role'] !== 'team leader'): ?>
                                            <li>
                                                <div class="assessmentname1">
                                                <div class="titleContainer1">
                                                <?php echo htmlspecialchars($member['name']); ?>
                                            </div>
                                            <div class="titleContainer2">
                                            <a href="<?php echo htmlspecialchars($member['assessment_file']); ?>"><i class="bi bi-file-earmark-arrow-down"></i></a>
                                        </div>
                                        <div class="titleContainer3">
                                                <?php if (in_array($member['assessment_id'], $approved_assessments)): ?>
                                                    <i class="fas fa-check approve1"></i>
                                                <?php else: ?>
                                                    <button class="approve" onclick="approveAssessmentPopup(<?php echo htmlspecialchars(json_encode($member)); ?>)">APPROVE</button>
                                                <?php endif; ?>
                                                </div>
                                            </li>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                    </ul>
                                    <?php if (in_array($schedule['team_id'], $existing_summaries)): ?>
                                        <div style="height: 20px;"></div>
                                        <p>SUBMIT SUMMARY</p>
                                        <div style="height: 10px;"></div>
                                        <p class="assessment-button-done">ALREADY SUBMITTED</p>
                                    <?php elseif ($submitted_count < $team_member_count): ?>
                                    <?php elseif ($approved_count < $team_member_count): ?>
                                        <div style="height: 20px;"></div>
                                        <p>SUBMIT SUMMARY</p>
                                        <div style="height: 10px;"></div>
                                        <p class="pending-assessments">APPROVE ASSESSMENTS FIRST</p>
                                    <?php else: ?>
                                        <div style="height: 20px;"></div>
                                        <p>SUBMIT SUMMARY</p>
                                        <div style="height: 10px;"></div>
                                        <button class="assessment-button" onclick="SummaryopenPopup(<?php echo htmlspecialchars(json_encode($schedule)); ?>)">START SUMMARY</button>
                                    <?php endif; ?>
                                <?php endif; ?>
                            <?php else: ?>
                                <?php if (!$nda_signed_status[$schedule['schedule_id']]): ?>
                                    <p>NON-DISCLOSURE AGREEMENT</p>
                                    <div style="height: 10px;"></div>
                                    <button class="assessment-button" onclick="openNdaPopup('<?php echo $full_name; ?>', <?php echo $schedule['team_id']; ?>)">SIGN</button>
                                <?php elseif ($schedule['area'] == ''): ?>
                                    <p>ASSESSMENT</p>
                                    <div style="height: 10px;"></div>
                                    <p class="pending-assessments">YOUR TEAM LEADER SHOULD ASSIGN AREA FIRST</p>
                                <?php elseif (in_array($schedule['team_id'], $existing_assessments)): ?>
                                    <p>ASSESSMENT</p>
                                    <div style="height: 10px;"></div>
                                        <p class="assessment-button-done">ALREADY SUBMITTED</p>
                                <?php else: ?>
                                    <p>ASSESSMENT</p>
                                    <div style="height: 10px;"></div>
                                        <button class="assessment-button" onclick="openPopup(<?php echo htmlspecialchars(json_encode($schedule)); ?>)">START ASSESSMENT</button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        </div>
                        </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center; font-size: 20px"><strong>NO SCHEDULED INTERNAL ACCREDITATION HAS BEEN ACCEPTED</strong></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- NDA Signing Popup -->
    <div class="ndamodal" id="ndaPopup" style="display: none;">
        <div class="ndamodal-content">
            <span style="float: right; font-size: 40px; cursor: pointer;" class="close" onclick="closeNdaPopup()">&times;</span>
            <h2>NON-DISCLOSURE AGREEMENT</h2>
            <form action="internal_nda_process.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="team_id" id="nda_team_id">
                <input type="hidden" name="internal_accreditor" id="nda_internal_accreditor">
                <input type="hidden" name="date_added" id="date_added" value="<?php echo date('Y-m-d'); ?>">
                <div class="orientationname1">
                    <div class="titleContainer">
                        <label for="internal_accreditor"><strong>INTERNAL ACCREDITOR</strong></label>
                    </div>
                    <div class="titleContainer" style="padding-left: 100px;">
                        <label for="internal_accreditor_signature"><strong>E-SIGNATURE</strong></label>
                    </div>
                </div>
                <div class="orientationname1 upload">
                    <div class="nameContainer orientationContainer" style="padding-right: 110px">
                        <input class="area_evaluated" type="text" id="internal_accreditor" name="internal_accreditor" value="<?php echo $full_name; ?>" readonly>
                    </div>
                    <div class="nameContainer orientationContainer uploadContainer">
                        <span class="upload-text">UPLOAD</span>
                        <img id="upload-icon-nda" src="images/download-icon1.png" alt="Upload Icon" class="upload-icon">
                        <input class="uploadInput" type="file" id="internal_accreditor_signature" name="internal_accreditor_signature" accept="image/png" required>
                    </div>
                </div>
                <div class="button-container">
                    <button class="cancel-button" type="button" onclick="closeNdaPopup()">CANCEL</button>
                    <button class="submit-button" type="submit">SUBMIT</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Popup Form for Team Member -->
    <div class="assessmentmodal" id="popup">
        <div class="assessmentmodal-content">
            <span style="float: right; font-size: 40px; cursor: pointer;" class="Summaryclose" onclick="closePopup()">&times;</span>
            <h2>ASSESSMENT FORM</h2>
            <form action="internal_assessment_process.php" method="POST" enctype="multipart/form-data">
                <div class="assessment-group">
                    <input type="hidden" name="schedule_id" id="modal_schedule_id">
                    <label for="college">COLLEGE</label>
                    <input class="assessment-group-college" type="text" id="college" name="college" readonly>
                    <label for="program">PROGRAM</label>
                    <input class="assessment-group-program" type="text" id="program" name="program" readonly>
                </div>
                <div class="orientationname1">
                    <div class="titleContainer">
                        <label for="level"><strong>LEVEL APPLIED</strong></label>
                    </div>
                    <div class="titleContainer">
                        <label for="date"><strong>DATE</strong></label>
                    </div>
                    <div class="titleContainer">
                        <label for="time"><strong>TIME</strong></label>
                    </div>
                </div>
                <div class="orientationname1">
                    <div class="nameContainer orientationContainer1">
                        <input class="level" type="text" id="level" name="level" readonly>
                    </div>
                    <div class="nameContainer orientationContainer">
                        <input class="level" type="text" id="date" name="date" readonly>
                    </div>
                    <div class="nameContainer orientationContainer">
                        <input class="time" type="text" id="time" name="time" readonly>
                    </div>
                </div>
                <div class="orientationname1">
                    <div class="titleContainer">
                        <label for="result"><strong>RESULT</strong></label>
                    </div>
                    <div class="titleContainer">
                        <label for="area_evaluated"><strong>AREA EVALUATED</strong></label>
                    </div>
                </div>
                <div class="orientationname1">
                    <div class="nameContainer orientationContainer">
                        <select style="cursor: pointer;" class="result" id="result" name="result" required>
                            <option value="">SELECT RESULT</option>
                            <option value="Ready">Ready</option>
                            <option value="Needs Improvement">Needs Improvement</option>
                            <option value="Revisit">Revisit</option>
                        </select>
                    </div>
                    <div class="nameContainer orientationContainer">
                        <input class="area_evaluated" type="text" id="area_evaluated" name="area_evaluated" readonly>
                    </div>
                </div>
                <div style="height: 20px;"></div>
                <div class="assessment-group">
                    <label for="findings"><strong>FINDINGS</strong></label>
                    <textarea style="border: 1px solid #AFAFAF; border-radius: 10px; width: 100%; padding: 20px;" id="findings" name="findings" rows="10" placeholder="Add a comment" required></textarea>
                    <div style="height: 20px;"></div>
                    <label for="recommendations"><strong>RECOMMENDATIONS</strong></label>
                    <textarea style="border: 1px solid #AFAFAF; border-radius: 10px; width: 100%; padding: 20px;" id="recommendations" name="recommendations" rows="10" placeholder="Add a comment" required></textarea>
                </div>
                <div class="orientationname1">
                    <div class="titleContainer">
                        <label for="evaluator"><strong>EVALUATOR</strong></label>
                    </div>
                    <div class="titleContainer">
                        <label for="evaluator_signature"><strong>EVALUATOR E-SIGN</strong></label>
                    </div>
                </div>
                <div class="orientationname1 upload">
                    <div class="nameContainer orientationContainer">
                        <input class="area_evaluated" type="text" id="evaluator" name="evaluator" value="<?php echo $full_name; ?>" readonly>
                    </div>
                    <div class="nameContainer orientationContainer uploadContainer">
                        <span class="upload-text">UPLOAD</span>
                        <img id="upload-icon-evaluator" src="images/download-icon1.png" alt="Upload Icon" class="upload-icon">
                        <input class="uploadInput" type="file" id="evaluator_signature" name="evaluator_signature" accept="image/png" required>
                    </div>
                </div>
                <div class="button-container">
                    <button class="cancel-button1" type="button" onclick="closePopup()">CLOSE</button>
                    <button class="submit-button1" type="submit">SUBMIT</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Popup Form for Team Leader -->
    <div class="Summarymodal" id="Summarypopup">
        <div class="Summarymodal-content">
            <span style="float: right; font-size: 40px; cursor: pointer;" class="Summaryclose" onclick="SummaryclosePopup()">&times;</span>
            <h2>SUMMARY FORM</h2>
            <form action="internal_summary_assessment_process.php" method="POST" enctype="multipart/form-data">
                <div class="assessment-group">
                    <input type="hidden" name="schedule_id" id="Summarymodal_schedule_id">
                    <label for="college">COLLEGE</label>
                    <input type="text" id="Summarycollege" name="college" readonly>
                    <div style="height: 20px;"></div>
                    <label for="program">PROGRAM</label>
                    <input type="text" id="Summaryprogram" name="program" readonly>
                </div>
                <div class="orientationname1">
                    <div class="titleContainer">
                        <label for="level"><strong>LEVEL APPLIED</strong></label>
                    </div>
                    <div class="titleContainer">
                        <label for="date"><strong>DATE</strong></label>
                    </div>
                    <div class="titleContainer">
                        <label for="time"><strong>TIME</strong></label>
                    </div>
                </div>
                <div class="orientationname1">
                    <div class="nameContainer orientationContainer1">
                        <input class="level" type="text" id="Summarylevel" name="level" readonly>
                    </div>
                    <div class="nameContainer orientationContainer">
                        <input class="level" type="text" id="Summarydate" name="date" readonly>
                    </div>
                    <div class="nameContainer orientationContainer">
                        <input class="time" type="text" id="Summarytime" name="time" readonly>
                    </div>
                </div>
                <div id="result-section" style="display: none;">
                    <div class="orientationname1">
                        <div class="titleContainer">
                            <label for="result"><strong>RESULT (TEAM LEADER)</strong></label>
                        </div>
                    </div>
                    <div class="orientationname1">
                        <div class="nameContainer orientationContainer" id="result-container">
                            <!-- Result dropdown will be dynamically added here -->
                        </div>
                    </div>
                </div>

                <div style="height: 20px;"></div>
                <div class="assessment-group">
                    <label for="results"><strong>RESULTS (TEAM MEMBERS)</strong></label>
                    <textarea style="border: 1px solid #AFAFAF; border-radius: 10px; width: 100%; padding: 20px; font-size: 16px;" id="results" name="results" rows="10" readonly></textarea>
                    <div style="height: 20px;"></div>
                    <label for="areas"><strong>AREAS EVALUATED</strong></label>
                    <textarea style="border: 1px solid #AFAFAF; border-radius: 10px; width: 100%; padding: 20px; font-size: 16px;" id="areas" name="areas" rows="10" readonly></textarea>
                </div>
                <div class="orientationname1">
                    <div class="titleContainer">
                        <label for="evaluator"><strong>EVALUATOR</strong></label>
                    </div>
                    <div class="titleContainer">
                        <label for="Summaryevaluator_signature"><strong>TEAM LEADER E-SIGN</strong></label>
                    </div>
                </div>
                <div class="orientationname1 upload">
                    <div class="nameContainer orientationContainer">
                        <input class="area_evaluated" type="text" id="Summaryevaluator" name="evaluator" value="<?php echo $full_name; ?>" readonly>
                    </div>
                    <div class="nameContainer orientationContainer uploadContainer">
                        <span class="upload-text">UPLOAD</span>
                        <img id="upload-icon-team-evaluator" src="images/download-icon1.png" alt="Upload Icon" class="upload-icon">
                        <input class="uploadInput" type="file" id="Summaryevaluator_signature" name="evaluator_signature" accept="image/png" required>
                    </div>
                </div>
                <div class="button-container">
                    <button class="cancel-button1" type="button" onclick="SummaryclosePopup()">Close</button>
                    <button class="submit-button1" type="submit">Submit Summary</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Popup Form for Approving Assessment -->
    <div class="approvalmodal" id="approveAssessmentPopup">
        <div class="approvalmodal-content">
            <span style="float: right; font-size: 40px; cursor: pointer;" class="close" onclick="closeApproveAssessmentPopup()">&times;</span>
            <h2>APPROVE ASSESSMENT</h2>
            <form action="internal_approve_assessment_process.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="team_id" id="approve_team_id">
                <input type="hidden" name="assessment_file" id="approve_assessment_file">
                <div class="orientationname1">
                    <div class="titleContainer">
                        <label for="team_leader"><strong>TEAM LEADER</strong></label>
                    </div>
                    <div class="titleContainer" style="padding-left: 100px;">
                        <label for="team_leader_signature"><strong>TEAM LEADER E-SIGN</strong></label>
                    </div>
                </div>
                <div class="orientationname1 upload">
                    <div class="nameContainer orientationContainer" style="padding-right: 110px">
                        <input class="area_evaluated" type="text" id="team_leader" name="team_leader" value="<?php echo $full_name; ?>" readonly>
                    </div>
                    <div class="nameContainer orientationContainer uploadContainer">
                        <span class="upload-text">UPLOAD</span>
                        <img id="upload-icon-leader" src="images/download-icon1.png" alt="Upload Icon" class="upload-icon">
                        <input class="uploadInput" type="file" id="team_leader_signature" name="team_leader_signature" accept="image/png" required>
                    </div>
                </div>
                <div class="button-container">
                    <button class="approve-cancel-button" type="button" onclick="closeApproveAssessmentPopup()">CANCEL</button>
                    <button class="approve-assessment-button" type="submit">SUBMIT</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function handleFileChange(inputElement, iconElement) {
            inputElement.addEventListener('change', function () {
                if (this.files && this.files.length > 0) {
                    // Change icon to check mark if a file is selected
                    iconElement.src = 'images/success.png'; // Ensure this path is correct and the image exists
                } else {
                    // Change icon back to download if no file is selected
                    iconElement.src = 'images/download-icon1.png';
                }
            });
        }

        handleFileChange(document.getElementById('internal_accreditor_signature'), document.getElementById('upload-icon-nda'));
        handleFileChange(document.getElementById('evaluator_signature'), document.getElementById('upload-icon-evaluator'));

        handleFileChange(document.getElementById('Summaryevaluator_signature'), document.getElementById('upload-icon-team-evaluator'));
        handleFileChange(document.getElementById('team_leader_signature'), document.getElementById('upload-icon-leader'));

        function toggleNotifications() {
            var dropdown = document.getElementById('notificationDropdown');
            dropdown.classList.toggle('show');
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
            });
        }

        function formatTime(timeString) {
            const [hours, minutes] = timeString.split(':');
            const date = new Date();
            date.setHours(hours, minutes);
            return date.toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: 'numeric',
                hour12: true,
            });
        }

        function openPopup(schedule) {
            document.getElementById('modal_schedule_id').value = schedule.schedule_id;
            document.getElementById('college').value = schedule.college_name;
            document.getElementById('program').value = schedule.program_name;
            document.getElementById('level').value = schedule.level_applied;

            // Format the date and time
            document.getElementById('date').value = formatDate(schedule.schedule_date);
            document.getElementById('time').value = formatTime(schedule.schedule_time);
            document.getElementById('area_evaluated').value = schedule.area;

            document.getElementById('popup').style.display = 'block';
        }

        function SummaryopenPopup(schedule) {
    document.getElementById('Summarymodal_schedule_id').value = schedule.schedule_id;
    document.getElementById('Summarycollege').value = schedule.college_name;
    document.getElementById('Summaryprogram').value = schedule.program_name;
    document.getElementById('Summarylevel').value = schedule.level_applied;

    // Format the date and time
    document.getElementById('Summarydate').value = formatDate(schedule.schedule_date);
    document.getElementById('Summarytime').value = formatTime(schedule.schedule_time);

    // Fetch team members' areas
    var areasXhr = new XMLHttpRequest();
    areasXhr.open('GET', 'get_team_areas.php?schedule_id=' + schedule.schedule_id, true);
    areasXhr.onreadystatechange = function() {
        if (areasXhr.readyState == 4 && areasXhr.status == 200) {
            var areas = JSON.parse(areasXhr.responseText);
            document.getElementById('areas').value = areas.join('\n');
            document.getElementById('areas').readOnly = true;
        } else {
            console.error('Failed to fetch team areas.');
        }
    };
    areasXhr.send();

    // Fetch team members' results
    var resultsXhr = new XMLHttpRequest();
    resultsXhr.open('GET', 'get_team_results.php?schedule_id=' + schedule.schedule_id, true);
    resultsXhr.onreadystatechange = function() {
        if (resultsXhr.readyState == 4 && resultsXhr.status == 200) {
            var results = JSON.parse(resultsXhr.responseText);
            document.getElementById('results').value = results.join('\n');
        } else {
            console.error('Failed to fetch team results.');
        }
    };
    resultsXhr.send();

    var resultSection = document.getElementById('result-section');
    var resultContainer = document.getElementById('result-container');

    // Remove any existing result dropdown
    if (resultContainer.firstChild) {
        resultContainer.removeChild(resultContainer.firstChild);
    }

    // Check if the team leader's area is blank
    if (schedule.area && schedule.area.trim() !== '') {
        // If the area is not blank, show the result section and add the dropdown
        resultSection.style.display = 'block';

        // Create and add the result dropdown
        var resultDropdown = document.createElement('select');
        resultDropdown.setAttribute('style', 'cursor: pointer;');
        resultDropdown.setAttribute('class', 'result');
        resultDropdown.setAttribute('id', 'result');
        resultDropdown.setAttribute('name', 'result');
        resultDropdown.setAttribute('required', 'true');

        var options = [
            { value: '', text: 'SELECT RESULT' },
            { value: 'Ready', text: 'Ready' },
            { value: 'Needs Improvement', text: 'Needs Improvement' },
            { value: 'Revisit', text: 'Revisit' }
        ];

        options.forEach(function(optionData) {
            var option = document.createElement('option');
            option.value = optionData.value;
            option.textContent = optionData.text;
            resultDropdown.appendChild(option);
        });

        resultContainer.appendChild(resultDropdown);
    } else {
        // If the area is blank, hide the result section and do not add the dropdown
        resultSection.style.display = 'none';
    }

    document.getElementById('Summarypopup').style.display = 'block';
}




        function SummaryclosePopup() {
            document.getElementById('Summarypopup').style.display = 'none';
        }

        function closePopup() {
            document.getElementById('popup').style.display = 'none';
        }

        function openNdaPopup(fullName, teamId) {
            document.getElementById('nda_internal_accreditor').value = fullName;
            document.getElementById('nda_team_id').value = teamId;
            document.getElementById('ndaPopup').style.display = 'block';
        }

        function closeNdaPopup() {
            document.getElementById('ndaPopup').style.display = 'none';
        }

        function approveAssessmentPopup(member) {
            document.getElementById('approve_team_id').value = member.team_id;
            document.getElementById('approve_assessment_file').value = member.assessment_file;

            document.getElementById('approveAssessmentPopup').style.display = 'block';
        }

        function closeApproveAssessmentPopup() {
            document.getElementById('approveAssessmentPopup').style.display = 'none';
        }

        // Close modals when clicking outside of them
        window.onclick = function(event) {
            var modals = [
                document.getElementById('popup'),
                document.getElementById('Summarypopup'),
                document.getElementById('approveAssessmentPopup'),
                document.getElementById('ndaPopup')
            ];

            modals.forEach(function(modal) {
                if (event.target == modal) {
                    modal.style.display = "none";
                }
            });
        }
    </script>
</body>
</html>
