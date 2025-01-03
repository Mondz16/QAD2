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

    $sql_check = "SELECT id FROM orientation WHERE schedule_id = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("i", $schedule_id);
    $stmt_check->execute();
    $stmt_check->store_result();

    $status = '';
    $message = '';
    
    if ($stmt_check->num_rows > 0) {
        $stmt_check->close();
        header("Location: internal_orientation.php?error=orientation_already_requested");
        exit();
    }
    $stmt_check->close();

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
        $stmt_date_check->close();
        
        $status = "error";
        $message = "An orientation for the selected date already exists.";
        
        echo "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Orientation Error</title>
            <link rel='stylesheet' href='index.css'>
            <style>
            .popup {
                display: block;
                position: fixed;
                z-index: 200;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                overflow: auto;
                background-color: rgba(0, 0, 0, 0.5);
            }

            .popup-content {
                background-color: #fff;
                padding: 20px;
                border: 1px solid #888;
                width: 80%;
                max-width: 500px;
                text-align: center;
                border-radius: 10px;
                position: relative;
                argin: 10% auto;
            }
            
            .hairpop-up {
                height: 15px;
                background: #9B0303;
                position: absolute;
                bottom: 0;
                left: 0;
                right: 0;
                width: 100%;
                border-bottom-left-radius: 10px;
                border-bottom-right-radius: 10px;
            }

            .okay {
                color: black;
                text-decoration: none;
                white-space: unset;
                font-size: 1rem;
                font-weight: bold;
                text-transform: uppercase;
                border: 1px solid;
                border-radius: 10px;
                cursor: pointer;
                padding: 16px 55px;
                min-width: 120px;
            }

            .okay:hover {
                background-color: #EAEAEA;
            }
            </style>
        </head>
        <body>
            <div id='errorPopup' class='popup'>
                <div class='popup-content'>
                    <div style='height: 50px; width: 0px;'></div>
                    <img src='images/" . ucfirst($status) . ".png' height='100' alt='" . ucfirst($status) . "'>
                    <div style='height: 25px; width: 0px;'></div>
                    <div class='message " . $status . "'>
                        " . $message . "
                    </div>
                    <div style='height: 50px; width: 0px;'></div>
                    <a href='internal_orientation.php' class='okay'>OKAY</a>
                    <div style='height: 100px; width: 0px;'></div>
                    <div class='hairpop-up'></div>
                </div>
            </dive
        </body>
        </html>
        ";
    
        exit();
    }    

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
