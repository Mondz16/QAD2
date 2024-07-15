<?php
include 'connection.php';
session_start();

if (!isset($_SESSION['user_id']) || substr($_SESSION['user_id'], 3, 2) !== '11') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch notifications for the logged-in user
$sql_notifications = "SELECT id, message, created_at FROM notifications WHERE user_id = ? AND is_read = FALSE ORDER BY created_at DESC";
$stmt_notifications = $conn->prepare($sql_notifications);
$stmt_notifications->bind_param("s", $user_id);
$stmt_notifications->execute();
$stmt_notifications->bind_result($notification_id, $message, $created_at);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Internal Accreditor</title>
    <link rel="stylesheet" href="loginstyle.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" />
</head>
<body>
    <div class="wrapper">
        <header>Internal Accreditor</header>
        <p>INTERNAL ACCREDITOR</p>
    </div>
    <header class="site-header">
        <nav>
            <ul class="nav-list">
                <li class="btn"><a href="logout.php">Log Out</a></li>
            </ul>
        </nav>
    </header>

    <div class="notifications">
        <h2>Notifications</h2>
        <?php while ($stmt_notifications->fetch()): ?>
            <div class="notification">
                <p><?php echo $message; ?></p>
                <small><?php echo $created_at; ?></small>
                <form action="process_notification.php" method="POST">
                    <input type="hidden" name="notification_id" value="<?php echo $notification_id; ?>">
                    <button type="submit" name="action" value="accept">Accept</button>
                    <button type="submit" name="action" value="decline">Decline</button>
                </form>
            </div>
        <?php endwhile; ?>
        <?php $stmt_notifications->close(); ?>
    </div>
</body>
</html>
