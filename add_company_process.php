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
    <link rel="stylesheet" href="index.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Quicksand", sans-serif;
        }
        body {
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }
        h2 {
            font-size: 24px;
            color: #292D32;
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
        .btn-hover{
            border: 1px solid #AFAFAF;
            text-decoration: none;
            color: black;
            border-radius: 10px;
            padding: 20px 50px;
            font-size: 1rem;
            font-weight: bold;
            text-transform: uppercase;
        }
        .btn-hover:hover {
            background-color: #AFAFAF;
        }
    </style>
</head>
<body>
            <?php
            if ($_SERVER["REQUEST_METHOD"] == "POST") {
                $company_name = $_POST['company_name'];

                include 'connection.php';

                // Check if the company name already exists
                $check_stmt = $conn->prepare("SELECT * FROM company WHERE company_name = ?");
                $check_stmt->bind_param("s", $company_name);
                $check_stmt->execute();
                $result = $check_stmt->get_result();

                if ($result->num_rows > 0) {
                    echo "
                    <div class='popup-content'>
                        <div style='height: 50px; width: 0px;'></div>
                        <img class='Error' src='images/Error.png' height='100'>
                        <div style='height: 25px; width: 0px;'></div>
                        <p class='error'>Error: Company name already exists. <a href='add_company.php'>Try again</a></p>
                        <div style='height: 50px; width: 0px;'></div>
                        <a href='college.php'class='btn-hover'>OKAY</a>
                        <div style='height: 100px; width: 0px;'></div>
                        <div class='hairpop-up'></div>
                    </div>";
                
                    } else {
                    // Proceed with adding the company if no duplicate is found
                    $sql = "SELECT MAX(code) AS max_code FROM company";
                    $result = $conn->query($sql);
                    $row = $result->fetch_assoc();
                    $max_code = $row['max_code'];

                    if ($max_code === null || $max_code < 20) {
                        $new_company_code = 21;
                    } else {
                        $new_company_code = $max_code + 1;
                    }

                    if ($new_company_code > 35) {
                    echo "
                    <div class='popup-content'>
                        <div style='height: 50px; width: 0px;'></div>
                        <img class='Error' src='images/Error.png' height='100'>
                        <div style='height: 25px; width: 0px;'></div>                        
                        <p class='error'>Error: Maximum number of companies reached. <a href='college.php'>Back to Colleges and Companies</a></p>
                        <div style='height: 50px; width: 0px;'></div>
                        <a href='college.php'class='btn-hover'>OKAY</a>
                        <div style='height: 100px; width: 0px;'></div>
                        <div class='hairpop-up'></div>
                    </div>";
                    } else {
                        $stmt = $conn->prepare("INSERT INTO company (code, company_name) VALUES (?, ?)");
                        $stmt->bind_param("is", $new_code, $company_name);

                        if ($stmt->execute()) {
                            echo "
                        <div class='popup-content'>
                            <div style='height: 50px; width: 0px;'></div>
                            <img class='Success' src='images/Success.png' height='100'>
                            <div style='height: 25px; width: 0px;'></div>                        
                            <p class='success'>Company added successfully.</p>
                            <div style='height: 50px; width: 0px;'></div>
                            <a href='college.php'class='btn-hover'>OKAY</a>
                            <div style='height: 100px; width: 0px;'></div>
                            <div class='hairpop-up'></div>
                        </div>";                        
                    } else {
                            echo "
                        <div class='popup-content'>
                            <div style='height: 50px; width: 0px;'></div>
                            <img class='Error' src='images/Error.png' height='100'>
                            <div style='height: 25px; width: 0px;'></div>                        
                            <p class='error'>Error: " . $stmt->error . "</p>
                            <div style='height: 50px; width: 0px;'></div>
                            <a href='college.php'class='btn-hover'>OKAY</a>
                            <div style='height: 100px; width: 0px;'></div>
                            <div class='hairpop-up'></div>
                        </div>";                         }

                        $stmt->close();
                    }
                }

                $check_stmt->close();
                $conn->close();
            }
            ?>
</body>
</html>
