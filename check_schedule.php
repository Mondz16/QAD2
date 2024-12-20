<?php
include 'connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the schedules array from POST
    $schedules = isset($_POST['schedules']) ? $_POST['schedules'] : null;
    
    if (!$schedules || !is_array($schedules)) {
        echo json_encode(['error' => 'Invalid data format']);
        exit;
    }

    $response = ['conflicts' => []];
    
    foreach ($schedules as $index => $schedule) {
        $date = mysqli_real_escape_string($conn, $schedule['date']);
        $college_name = mysqli_real_escape_string($conn, $schedule['college']);
        
        // Step 1: Get the college_code using the college_name
        $college_query = "SELECT code FROM college WHERE college_name = ?";
        $stmt_college = $conn->prepare($college_query);
        $stmt_college->bind_param("s", $college_name);
        $stmt_college->execute();
        $stmt_college->bind_result($college_code);
        $stmt_college->fetch();
        $stmt_college->close();

        if (!$college_code) {
            $response['conflicts'][] = [
                'index' => $index,
                'status' => 'error',
                'message' => 'Invalid college selected'
            ];
            continue;
        }

        // Step 3: Check for conflicting schedules
        $sql_check_status = "SELECT id, college_code, schedule_status, program_id 
                            FROM schedule 
                            WHERE schedule_date = ? 
                            AND schedule_status IN ('approved', 'pending')";

        $stmt_check_date = $conn->prepare($sql_check_status);
        $stmt_check_date->bind_param("s", $date);
        $stmt_check_date->execute();
        $result = $stmt_check_date->get_result();
        
        $hasConflict = false;
        
        while ($row = $result->fetch_assoc()) {
            if ($row['college_code'] !== $college_code) {
                // Different college with same date/time
                $hasConflict = true;
                $response['conflicts'][] = [
                    'index' => $index,
                    'status' => 'exists',
                    'schedule_status' => $row['schedule_status'],
                    'message' => "Schedule conflict with another college"
                ];
                break;
            }
        }
        
        if (!$hasConflict) {
            $response['conflicts'][] = [
                'index' => $index,
                'status' => 'available',
                'message' => null
            ];
        }
        
        $stmt_check_date->close();
    }

    $conn->close();
    echo json_encode($response);
}
?>