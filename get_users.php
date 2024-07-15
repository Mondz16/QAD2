<?php
include 'connection.php';

$college_id = $_POST['college_id'];

// Fetch the selected college name from the college table
$college_query = "SELECT college_name FROM college WHERE id = ?";
$stmt = $conn->prepare($college_query);
$stmt->bind_param('i', $college_id);
$stmt->execute();
$stmt->bind_result($college_name);
$stmt->fetch();
$stmt->close();

// Fetch all internal users
$users_query = "SELECT user_id, first_name, middle_initial, last_name, college FROM internal_users";
$result = $conn->query($users_query);

$users = array();

while ($row = $result->fetch_assoc()) {
    // Exclude users from the selected college
    if ($row['college'] != $college_name) {
        $users[] = $row;
    }
}

// Fetch users already in team for the selected college
$team_query = "SELECT fname, mi, lname FROM team WHERE schedule_id IN 
              (SELECT id FROM schedule WHERE college = ?)";
$stmt = $conn->prepare($team_query);
$stmt->bind_param('s', $college_name);
$stmt->execute();
$stmt->bind_result($fname, $mi, $lname);

$team_members = array();

while ($stmt->fetch()) {
    $team_members[] = array(
        'fname' => $fname,
        'mi' => $mi,
        'lname' => $lname
    );
}

$stmt->close();

// Filter out users who are already in the team
$filtered_users = array();
foreach ($users as $user) {
    $is_in_team = false;
    foreach ($team_members as $member) {
        if ($user['first_name'] == $member['fname'] &&
            $user['middle_initial'] == $member['mi'] &&
            $user['last_name'] == $member['lname']) {
            $is_in_team = true;
            break;
        }
    }
    if (!$is_in_team) {
        $filtered_users[] = $user;
    }
}

echo json_encode($filtered_users);
?>
