<?php
include 'connection.php';

if (isset($_POST['college_id'])) {
    $college_id = trim($_POST['college_id']); // Basic sanitization

    // Validate input
    if (empty($college_id) || !preg_match('/^\w+$/', $college_id)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid college ID.']);
        exit;
    }

    // Query to fetch available programs
    $sql = "
        SELECT p.id, p.program_name 
        FROM program p
        LEFT JOIN schedule s ON p.id = s.program_id AND s.schedule_status IN ('pending', 'approved', 'finished')
        WHERE p.college_code = ? AND (s.program_id IS NULL OR s.schedule_status NOT IN ('pending', 'approved', 'finished'))
        ORDER BY p.program_name
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to prepare query.']);
        exit;
    }

    $stmt->bind_param("s", $college_id);

    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to execute query.']);
        exit;
    }

    $result = $stmt->get_result();
    $programs = [];
    while ($row = $result->fetch_assoc()) {
        $programs[] = [
            'id' => $row['id'],
            'name' => $row['program_name']
        ];
    }

    $stmt->close();
    $conn->close();

    // Ensure response is valid JSON
    $jsonResponse = json_encode($programs);
    if ($jsonResponse === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to encode JSON.']);
        exit;
    }

    // Return JSON response
    header('Content-Type: application/json');
    echo $jsonResponse;
} else {
    http_response_code(400);
    echo json_encode(['error' => 'College ID not provided.']);
}
?>
