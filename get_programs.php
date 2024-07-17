<?php
include 'connection.php';

if (isset($_POST['college_id'])) {
    $college_id = $_POST['college_id'];

    $sql = "SELECT id, program FROM program WHERE college_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $college_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $options = '<option value="">Select Program</option>';
    while ($row = $result->fetch_assoc()) {
        $options .= '<option value="' . htmlspecialchars($row['id']) . '">' . htmlspecialchars($row['program']) . '</option>';
    }
    echo $options;

    $stmt->close();
}
?>
