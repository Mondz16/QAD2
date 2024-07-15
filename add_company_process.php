<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $company_name = $_POST['company_name'];

    include 'connection.php';

    $sql = "SELECT MAX(company_code) AS max_code FROM company";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $max_code = $row['max_code'];

    if ($max_code === null || $max_code < 20) {
        $new_company_code = 21;
    } else {
        $new_company_code = $max_code + 1;
    }

    if ($new_company_code > 35) {
        echo "Error: Maximum number of companies reached. <a href='college.php'>Back to Colleges and Companies</a>";
    } else {
        $stmt = $conn->prepare("INSERT INTO company (company_code, company_name) VALUES (?, ?)");
        $stmt->bind_param("is", $new_company_code, $company_name);

        if ($stmt->execute()) {
            echo "Company added successfully. <a href='college.php'>Back to Colleges and Companies</a>";
        } else {
            echo "Error: " . $stmt->error;
        }

        $stmt->close();
    }

    $conn->close();
}
?>
