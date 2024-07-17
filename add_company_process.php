<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Operation Result</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600&display=swap">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Quicksand", sans-serif;
        }

        body {
            background-color: #f9f9f9;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }

        .container {
            max-width: 750px;
            padding: 24px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        h2 {
            font-size: 24px;
            color: #973939;
            margin-bottom: 20px;
        }

        .message {
            margin-bottom: 20px;
            font-size: 18px;
        }

        .success {
            color: green;
        }

        .error {
            color: red;
        }

        .button-primary {
            background-color: #2cb84f;
            color: #fff;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 4px;
            margin-top: 10px;
            color: white;
            font-size: 16px;
        }

        .button-primary:hover {
            background-color: #259b42;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>Operation Result</h2>
        <div class="message">
            <?php
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
                    echo "<p class='error'>Error: Maximum number of companies reached. <a href='college.php'>Back to Colleges and Companies</a></p>";
                } else {
                    $stmt = $conn->prepare("INSERT INTO company (company_code, company_name) VALUES (?, ?)");
                    $stmt->bind_param("is", $new_company_code, $company_name);

                    if ($stmt->execute()) {
                        echo "<p class='success'>Company added successfully.</p>";
                    } else {
                        echo "<p class='error'>Error: " . $stmt->error . "</p>";
                    }

                    $stmt->close();
                }

                $conn->close();
            }
            ?>
        </div>
        <button class="button-primary" onclick="window.location.href='college.php'">OK</button>
    </div>
</body>

</html>
