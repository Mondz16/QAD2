<?php
include 'connection.php';

if (isset($_POST['program_id'])) {
    $program_id = $_POST['program_id'];

    $sql = "SELECT program_level FROM program WHERE id = $program_id";
    $result = $conn->query($sql);

    if ($row = $result->fetch_assoc()) {
        echo $row['program_level'];
    } else {
        echo '0'; // Return a default level if no result found
    }
}
?>
