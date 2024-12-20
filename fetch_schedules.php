<?php
include 'connection.php';
session_start();

$user_id = $_SESSION['user_id'];

$college = $_POST['college'] ?? '';
$year = $_POST['year'] ?? '';
$status = $_POST['status'] ?? '';

$start = $_POST['start'] ?? 0;
$length = $_POST['length'] ?? 10;
$searchValue = $_POST['search']['value'] ?? '';

// Base query
$query = "
    SELECT 
        DATE_FORMAT(s.schedule_date, '%M %d, %Y') as schedule_date,
        TIME_FORMAT(s.schedule_time, '%h:%i %p') as schedule_time,
        c.college_name,
        p.program_name,
        s.schedule_status
    FROM schedule s
    JOIN team t ON s.id = t.schedule_id
    JOIN college c ON s.college_code = c.code
    JOIN program p ON s.program_id = p.id
    WHERE t.internal_users_id = ?
";

$params = [$user_id];
$types = "s";

// Add filters
if (!empty($college)) {
    $query .= " AND s.college_code = ?";
    $params[] = $college;
    $types .= "s";
}

if (!empty($year)) {
    $query .= " AND YEAR(s.schedule_date) = ?";
    $params[] = $year;
    $types .= "i";
}

if (!empty($status)) {
    if ($status == "pending") {
        $query .= " AND s.schedule_status IN ('pending', 'approved')";
    } elseif ($status == "finished") {
        $query .= " AND s.schedule_status IN ('finished', 'failed', 'passed')";
    } else {
        $query .= " AND s.schedule_status = ?";
        $params[] = $status;
        $types .= "s";
    }
}

// Total records (before filtering)
$totalQuery = "SELECT COUNT(*) as total FROM ($query) as subquery";
$stmtTotal = $conn->prepare($totalQuery);
$stmtTotal->bind_param($types, ...$params);
$stmtTotal->execute();
$totalResult = $stmtTotal->get_result();
$totalRecords = $totalResult->fetch_assoc()['total'];
$stmtTotal->close();

// Search filtering
if (!empty($searchValue)) {
    $query .= " AND (
        c.college_name LIKE ? OR
        p.program_name LIKE ? OR
        s.schedule_status LIKE ?
    )";
    $params[] = "%$searchValue%";
    $params[] = "%$searchValue%";
    $params[] = "%$searchValue%";
    $types .= "sss";
}

// Total filtered records
$filteredQuery = "SELECT COUNT(*) as total FROM ($query) as subquery";
$stmtFiltered = $conn->prepare($filteredQuery);
$stmtFiltered->bind_param($types, ...$params);
$stmtFiltered->execute();
$filteredResult = $stmtFiltered->get_result();
$totalFiltered = $filteredResult->fetch_assoc()['total'];
$stmtFiltered->close();

// Add pagination
$query .= " LIMIT ?, ?";
$params[] = (int)$start;
$params[] = (int)$length;
$types .= "ii";

// Get paginated results
$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

$stmt->close();

// Return JSON for DataTables
echo json_encode([
    "draw" => $_POST['draw'] ?? 1, // Echo back the draw count from DataTables
    "recordsTotal" => $totalRecords, // Total records before filtering
    "recordsFiltered" => $totalFiltered, // Total records after filtering
    "data" => $data // Actual data to display
]);
?>
