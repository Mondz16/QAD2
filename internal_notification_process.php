<?php
include 'connection.php';
session_start();

if (!isset($_SESSION['user_id']) || substr($_SESSION['user_id'], 3, 2) !== '11') {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['notification_id']) && isset($_POST['action'])) {
        $notification_id = $_POST['notification_id'];
        $action = $_POST['action'];
        
        if ($action == 'accept') {
            // Update team status to accepted
            $sql_update_status = "UPDATE team t 
                                  INNER JOIN internal_users i ON t.fname = i.first_name AND t.mi = i.middle_initial AND t.lname = i.last_name
                                  SET t.status = 'accepted' 
                                  WHERE i.user_id = ?";
            $stmt_update_status = $conn->prepare($sql_update_status);
            $stmt_update_status->bind_param("s", $_SESSION['user_id']);
            $stmt_update_status->execute();
            $stmt_update_status->close();
            
            // Retrieve schedule details for the logged-in user
            $sql_schedule = "SELECT s.college, s.program, s.level_applied, s.schedule_date, s.schedule_time 
                             FROM schedule s
                             INNER JOIN team t ON s.id = t.schedule_id
                             INNER JOIN internal_users i ON t.fname = i.first_name AND t.mi = i.middle_initial AND t.lname = i.last_name
                             WHERE i.user_id = ?";
            $stmt_schedule = $conn->prepare($sql_schedule);
            $stmt_schedule->bind_param("s", $_SESSION['user_id']);
            $stmt_schedule->execute();
            $stmt_schedule->bind_result($college, $program, $level_applied, $schedule_date, $schedule_time);
            $stmt_schedule->fetch();
            $stmt_schedule->close();
            
            // Delete the accepted notification
            $sql_delete_notification = "DELETE FROM notifications WHERE id = ?";
            $stmt_delete_notification = $conn->prepare($sql_delete_notification);
            $stmt_delete_notification->bind_param("i", $notification_id);
            $stmt_delete_notification->execute();
            $stmt_delete_notification->close();
            
            // Redirect to assessment page with schedule details
            header("Location: internal_assessment.php?college=$college&program=$program&level=$level_applied&date=$schedule_date&time=$schedule_time");
            exit();
        } elseif ($action == 'decline') {
            // Handle decline action if needed
            // Redirect back to notifications page or handle as per your application logic
        }
    }
}

// Redirect back to notifications page if action was not handled properly
header("Location: internal_notification.php");
exit();
?>
