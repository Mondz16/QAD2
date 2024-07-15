<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Company</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600&display=swap">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Quicksand", sans-serif;
        }
        body {
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        h2 {
            font-size: 24px;
            margin-bottom: 20px;
        }
        form {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
        }
        input[type="text"] {
            width: calc(100% - 10px);
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
        }
        input[type="submit"],
        button {
            background-color: #007bff;
            color: #fff;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
            margin-right: 10px;
            text-align: center;
        }
        input[type="submit"]:hover,
        button:hover {
            background-color: #0056b3;
        }
        button {
            background-color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Add Company</h2>
        <form action="add_company_process.php" method="post">
            <label for="company_name">Company Name:</label>
            <input type="text" id="company_name" name="company_name" required><br><br>
            <input type="submit" value="Add Company">
        </form>
        <button onclick="location.href='college.php'">Back to Colleges and Companies</button>
    </div>
</body>
</html>
