<?php
include 'connection.php';

if (isset($_POST['college_id'])) {
    $college_id = $_POST['college_id'];

    $sql = "
        SELECT p.id, p.program_name 
        FROM program p
        LEFT JOIN schedule s ON p.id = s.program_id AND s.schedule_status IN ('pending', 'approved')
        WHERE p.college_code = ? AND s.program_id IS NULL
        ORDER BY p.program_name
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $college_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $options = '<option value="">Select Program</option>';
    while ($row = $result->fetch_assoc()) {
        $options .= '<option value="' . htmlspecialchars($row['id']) . '">' . htmlspecialchars($row['program_name']) . '</option>';
    }
    echo $options;

    $stmt->close();
}
?>
