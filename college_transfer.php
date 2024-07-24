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
                            <form action="college_transfer_process.php" method="post" style="display:inline;">
                                <input type="hidden" name="action" value="accept">
                                <input type="hidden" name="new_user_id" value="<?php echo $new_user['user_id']; ?>">
                                <input type="hidden" name="previous_user_id" value="<?php echo $previous_user['user_id']; ?>">
                                <button type="submit" class="accept-button">Accept</button>
                            </form>
                            <form action="college_transfer_process.php" method="post" style="display:inline;">
                                <input type="hidden" name="action" value="reject">
                                <input type="hidden" name="new_user_id" value="<?php echo $new_user['user_id']; ?>">
                                <button type="submit" class="reject-button">Reject</button>
                            </form>
                        </td>
                    </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
