<?php
include 'connection.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'];
    $new_user_id = $_POST['new_user_id'];
    $previous_user_id = $_POST['previous_user_id'];

    if ($action == 'accept') {
        // Update the new user's status to 'active'
        $sql_accept = "UPDATE internal_users SET status = 'active' WHERE user_id = ?";
        $stmt_accept = $conn->prepare($sql_accept);
        $stmt_accept->bind_param("s", $new_user_id);
        $stmt_accept->execute();
        $stmt_accept->close();

        // Update the previous user's status to 'inactive'
        $sql_inactivate = "UPDATE internal_users SET status = 'inactive' WHERE user_id = ?";
        $stmt_inactivate = $conn->prepare($sql_inactivate);
        $stmt_inactivate->bind_param("s", $previous_user_id);
        $stmt_inactivate->execute();
        $stmt_inactivate->close();

        echo "Transfer request accepted. <a href='college_transfer.php'>Back to Transfer Requests</a>";
    } elseif ($action == 'reject') {
        // Delete the new user request
        $sql_reject = "DELETE FROM internal_users WHERE user_id = ?";
        $stmt_reject = $conn->prepare($sql_reject);
        $stmt_reject->bind_param("s", $new_user_id);
        $stmt_reject->execute();
        $stmt_reject->close();

        echo "Transfer request rejected. <a href='college_transfer.php'>Back to Transfer Requests</a>";
    }
}
?>
