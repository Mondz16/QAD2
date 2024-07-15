<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $company_id = $_POST['company_id'];
    $company_name = $_POST['company_name'];

    include 'connection.php';

    $stmt = $conn->prepare("UPDATE company SET company_name = ? WHERE id = ?");
    $stmt->bind_param("si", $company_name, $company_id);

    if ($stmt->execute()) {
        echo "Company updated successfully. <a href='college.php'>Back to Colleges and Companies</a>";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>