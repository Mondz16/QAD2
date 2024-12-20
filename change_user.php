<?php
include 'connection.php';

$team_id = $_GET['team_id'];
$college_code = $_GET['college_code'];

// Get the current user_id and schedule_id of the team member being changed
$current_user_sql = "SELECT internal_users_id, schedule_id FROM team WHERE id = ?";
$current_user_stmt = $conn->prepare($current_user_sql);
$current_user_stmt->bind_param("i", $team_id);
$current_user_stmt->execute();
$current_user_result = $current_user_stmt->get_result();
$current_user_row = $current_user_result->fetch_assoc();
$current_user_id = $current_user_row['internal_users_id'];
$current_schedule_id = $current_user_row['schedule_id'];
$current_user_stmt->close();

// Extract bb-cccc part from the current user_id
$current_user_suffix = substr($current_user_id, 3);

// Fetch the list of available users not from the same college, not the current user, not already assigned to the same schedule, and handling the exclusion criteria
$sql = "SELECT u.user_id, u.first_name, u.middle_initial, u.last_name, 
               (SELECT COUNT(*) FROM team t 
                WHERE t.internal_users_id = u.user_id 
                AND t.status IN ('pending', 'accepted')) AS schedule_count
        FROM internal_users u
        WHERE u.college_code != ? 
        AND u.user_id != ? 
        AND u.user_id NOT IN (SELECT internal_users_id FROM team WHERE schedule_id = ?)
        AND u.status = 'active'
        AND NOT EXISTS (
            SELECT 1
            FROM internal_users u2
            WHERE SUBSTRING(u2.user_id, 3) = SUBSTRING(u.user_id, 3)
            AND (
                (u2.status = 'pending' AND u.status = 'active')
                OR (u2.status = 'active' AND u.status = 'pending')
            )
        )
        AND EXISTS (
            SELECT 1
            FROM internal_users u2
            WHERE SUBSTRING(u2.user_id, 3) = SUBSTRING(u.user_id, 3)
            AND (
                (u2.status = 'active')
                OR (u2.status = 'inactive')
            )
        )";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ssi", $college_code, $current_user_id, $current_schedule_id);
$stmt->execute();
$result = $stmt->get_result();

echo '<form action="change_user_process.php" method="post" style="
    width: 400px;">';
echo '<input type="hidden" name="team_id" value="' . htmlspecialchars($team_id) . '">';
echo '<label for="new_user" style="
    width: 300px;
    margin-bottom: 10px;
">Select New User:</label>';
echo '<select name="new_user" required="" style="
    width: 100%;
    padding: 20px 10px;
    border-radius: 10px;
    margin-bottom: 20px;
">';
while ($row = $result->fetch_assoc()) {
    $schedule_count = $row['schedule_count'];
    echo '<option value="' . htmlspecialchars($row['user_id']) . '">' . htmlspecialchars($row['first_name']) . ' ' . htmlspecialchars($row['middle_initial']) . '. ' . htmlspecialchars($row['last_name']) . ' (' . $schedule_count . ' schedule/s)</option>';
}
echo '</select>';
echo '<button type="submit" style="
    width: 150px;
    height: 50px;
    border-radius: 10px;
    background-color: #35C659;
    color: white;
">CHANGE</button>';
echo '</form>';

$stmt->close();
$conn->close();
?>
