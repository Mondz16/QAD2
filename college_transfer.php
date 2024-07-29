<?php
include 'connection.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch all users and group by the same bb-cccc part of user_id
$sql = "SELECT user_id, college_code, first_name, middle_initial, last_name, email, status 
        FROM internal_users";
$result = $conn->query($sql);

$users = [];
while ($row = $result->fetch_assoc()) {
    $bb_cccc = substr($row['user_id'], 3); // Extract bb-cccc part
    $users[$bb_cccc][] = $row;
}

// Filter out groups with less than 2 users (no transfer request)
$transfer_requests = array_filter($users, function($group) {
    return count($group) > 1;
});

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>College Transfer Requests</title>
    <link rel="stylesheet" href="college_style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 1000px;
            margin: 50px auto;
            padding: 20px;
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
        }
        .action-buttons button {
            padding: 5px 10px;
            margin-right: 5px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .accept-button {
            background-color: #28a745;
            color: #fff;
        }
        .reject-button {
            background-color: #dc3545;
            color: #fff;
        }
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
    </style>
</head>
<body>
    <div class="container">
        <h2>College Transfer Requests</h2>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Previous College</th>
                    <th>New College</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transfer_requests as $bb_cccc => $group): ?>
                    <?php 
                    $previous_user = null;
                    $new_user = null;
                    foreach ($group as $user) {
                        if ($user['status'] == 'active') {
                            $previous_user = $user;
                        } elseif ($user['status'] == 'pending') {
                            $new_user = $user;
                        }
                    }
                    if ($previous_user && $new_user):
                        $previous_college_code = $previous_user['college_code'];
                        $new_college_code = $new_user['college_code'];

                        // Fetch college names
                        $stmt_prev_college = $conn->prepare("SELECT college_name FROM college WHERE code = ?");
                        $stmt_prev_college->bind_param("s", $previous_college_code);
                        $stmt_prev_college->execute();
                        $stmt_prev_college->bind_result($previous_college_name);
                        $stmt_prev_college->fetch();
                        $stmt_prev_college->close();

                        $stmt_new_college = $conn->prepare("SELECT college_name FROM college WHERE code = ?");
                        $stmt_new_college->bind_param("s", $new_college_code);
                        $stmt_new_college->execute();
                        $stmt_new_college->bind_result($new_college_name);
                        $stmt_new_college->fetch();
                        $stmt_new_college->close();
                    ?>
                    <tr>
                        <td><?php echo $previous_user['first_name'] . ' ' . $previous_user['middle_initial'] . '. ' . $previous_user['last_name']; ?></td>
                        <td><?php echo $previous_user['email']; ?></td>
                        <td><?php echo $previous_college_name; ?></td>
                        <td><?php echo $new_college_name; ?></td>
                        <td class="action-buttons">
                            <button class="accept-button" onclick="openAcceptModal('<?php echo $new_user['user_id']; ?>', '<?php echo $previous_user['user_id']; ?>', '<?php echo $new_user['email']; ?>', '<?php echo $new_user['first_name'] . ' ' . $new_user['middle_initial'] . '. ' . $new_user['last_name']; ?>')">Accept</button>
                            <button class="reject-button" onclick="openRejectModal('<?php echo $new_user['user_id']; ?>', '<?php echo $previous_user['user_id']; ?>')">Reject</button>
                        </td>
                    </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- The Modal for Acceptance -->
    <div id="acceptModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeAcceptModal()">&times;</span>
            <h2>Accept Transfer Request</h2>
            <p>Are you sure you want to accept this college transfer request?</p>
            <form id="acceptForm" action="college_transfer_process.php" method="post">
                <input type="hidden" name="action" value="accept">
                <input type="hidden" name="new_user_id" id="accept_new_user_id">
                <input type="hidden" name="previous_user_id" id="accept_previous_user_id">
                <input type="hidden" name="new_user_email" id="accept_new_user_email">
                <input type="hidden" name="new_user_name" id="accept_new_user_name">
                <button type="submit" class="accept-button">Yes</button>
                <button type="button" class="reject-button" onclick="closeAcceptModal()">No</button>
            </form>
        </div>
    </div>

    <!-- The Modal for Rejection -->
    <div id="rejectModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeRejectModal()">&times;</span>
            <h2>Reject Transfer Request</h2>
            <form id="rejectForm" action="college_transfer_process.php" method="post">
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="new_user_id" id="reject_new_user_id">
                <input type="hidden" name="previous_user_id" id="reject_previous_user_id">
                <div class="form-group">
                    <label for="reject_reason">Reason for rejection:</label>
                    <textarea id="reject_reason" name="reject_reason" rows="4" required></textarea>
                </div>
                <button type="submit" class="reject-button">Submit</button>
            </form>
        </div>
    </div>

    <script>
        function openAcceptModal(newUserId, previousUserId, newUserEmail, newUserName) {
            document.getElementById('accept_new_user_id').value = newUserId;
            document.getElementById('accept_previous_user_id').value = previousUserId;
            document.getElementById('accept_new_user_email').value = newUserEmail;
            document.getElementById('accept_new_user_name').value = newUserName;
            document.getElementById('acceptModal').style.display = 'block';
        }

        function closeAcceptModal() {
            document.getElementById('acceptModal').style.display = 'none';
        }

        function openRejectModal(newUserId, previousUserId) {
            document.getElementById('reject_new_user_id').value = newUserId;
            document.getElementById('reject_previous_user_id').value = previousUserId;
            document.getElementById('rejectModal').style.display = 'block';
        }

        function closeRejectModal() {
            document.getElementById('rejectModal').style.display = 'none';
        }
    </script>
</body>
</html>
