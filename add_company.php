<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Company</title>
</head>
<body>
    <h2>Add Company</h2>
    <form action="add_company_process.php" method="post">
        <label for="company_name">Company Name:</label>
        <input type="text" id="company_name" name="company_name" required><br><br>
        <input type="submit" value="Add Company">
    </form>
    <br>
    <button onclick="location.href='college.php'">Back to Colleges and Companies</button>
</body>
</html>
