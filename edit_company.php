<?php
include 'connection.php';

$company_id = $_GET['id'];

$sql = "SELECT * FROM company WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $company_id);
$stmt->execute();
$result = $stmt->get_result();
$company = $result->fetch_assoc();

if (!$company) {
    echo "Company not found. <a href='college.php'>Back to Colleges and Companies</a>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Company</title>
</head>
<body>
    <h2>Edit Company</h2>
    <form action="edit_company_process.php" method="post">
        <input type="hidden" name="company_id" value="<?php echo $company_id; ?>">
        
        <label for="company_name">Company Name:</label>
        <input type="text" id="company_name" name="company_name" value="<?php echo htmlspecialchars($company['company_name']); ?>" required><br><br>
        
        <input type="submit" value="Update Company">
    </form>
    <br>
    <button onclick="location.href='college.php'">Back to Colleges and Companies</button>
</body>
</html>