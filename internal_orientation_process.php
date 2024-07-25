<?php
include 'connection.php';
session_start();

if (!isset($_SESSION['user_id']) || substr($_SESSION['user_id'], 3, 2) !== '11') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $schedule_id = $_POST['schedule_id'];
    $orientation_date = $_POST['orientation_date'];
    $orientation_time = $_POST['orientation_time'];
    $mode = $_POST['mode'];

    // Check if orientation already exists for the given schedule
    $sql_check = "SELECT id FROM orientation WHERE schedule_id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("i", $schedule_id);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        // Orientation already requested for this schedule
        $stmt_check->close();
        header("Location: internal_orientation.php?error=orientation_already_requested");
        exit();
    }
    $stmt_check->close();

    // Check for existing orientations on the same date with status 'pending' or 'approved'
    $sql_date_check = "
        SELECT id FROM orientation 
        WHERE orientation_date = ? 
        AND orientation_status IN ('pending', 'approved')
    ";
    $stmt_date_check = $conn->prepare($sql_date_check);
    $stmt_date_check->bind_param("s", $orientation_date);
    $stmt_date_check->execute();
    $stmt_date_check->store_result();

    if ($stmt_date_check->num_rows > 0) {
        // An orientation on the same date already exists
        $stmt_date_check->close();
        header("Location: internal_orientation.php?error=orientation_date_conflict");
        exit();
    }
    $stmt_date_check->close();

    // Insert into orientation table with default status 'pending'
    $orientation_type = ($mode === 'online') ? 'online' : 'face_to_face';
    $orientation_status = 'pending';
    $sql_orientation = "
        INSERT INTO orientation (schedule_id, orientation_date, orientation_time, orientation_type, orientation_status)
        VALUES (?, ?, ?, ?, ?)
    ";
    $stmt_orientation = $conn->prepare($sql_orientation);
    $stmt_orientation->bind_param("issss", $schedule_id, $orientation_date, $orientation_time, $orientation_type, $orientation_status);
    $stmt_orientation->execute();
    $orientation_id = $stmt_orientation->insert_id;
    $stmt_orientation->close();

    if ($mode === 'online') {
        $orientation_link = $_POST['orientation_link'];
        $link_passcode = $_POST['link_passcode'];

        // Insert into online table
        $sql_online = "
            INSERT INTO online (orientation_id, orientation_link, link_passcode)
            VALUES (?, ?, ?)
        ";
        $stmt_online = $conn->prepare($sql_online);
        $stmt_online->bind_param("iss", $orientation_id, $orientation_link, $link_passcode);
        $stmt_online->execute();
        $stmt_online->close();
    } else {
        $orientation_building = $_POST['orientation_building'];
        $room_number = $_POST['room_number'];

        // Insert into face_to_face table
        $sql_f2f = "
            INSERT INTO face_to_face (orientation_id, college_building, room_number)
            VALUES (?, ?, ?)
        ";
        $stmt_f2f = $conn->prepare($sql_f2f);
        $stmt_f2f->bind_param("iss", $orientation_id, $orientation_building, $room_number);
        $stmt_f2f->execute();
        $stmt_f2f->close();
    }

    header("Location: internal_orientation.php?success=orientation_requested");
    exit();
} else {
    header("Location: internal_orientation.php");
    exit();
}
?>
