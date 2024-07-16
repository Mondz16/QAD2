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
        }
        .container2 {
            max-width: 700px;
            margin: 40px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .container {
            max-width: 1280px;
            padding-left: 24px;
            padding-right: 24px;
            width: 100%;
            display: block;
            box-sizing: border-box;
            margin-left: auto;
            margin-right: auto;
        }

        .header {
            height: 58px;
            width: 100%;
            display: flex;
            flex-flow: unset;
            justify-content: space-between;
            align-items: center;
            align-content: unset;
            overflow: unset;
        }

        .headerLeft {
            order: unset;
            flex: unset;
            align-self: unset;
        }

        .USePData {
            height: 100%;
            width: 100%;
            display: flex;
            flex-flow: unset;
            place-content: unset;
            align-items: center;
            overflow: unset;
        }

        .headerLeftText {
            height: 100%;
            width: 100%;
            display: flex;
            flex-direction: column;
            flex-wrap: unset;
            place-content: unset;
            align-items: unset;
            overflow: unset;
            font-size: 18px;
        }


        .pageHeader {
            display: flex;
            max-width: 900px;
            align-items: center;
            justify-content: center;
            margin-bottom: 30px;
            margin-top: 20px;
        }
        
        .headerRight .btn {
            background-color: #dc3545;
            color: white;
            font-size: 14px;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin-left: -250px;
            transition: background-color 0.3s ease;
        }

        .headerRight .btn:hover {
            background-color: #b82c3b;
        }

        h2 {
            font-size: 24px;
            color: #973939;
            margin-bottom: 20px;
            text-align: center;
        }
        form {
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-size: 14px;
            color: #333;
        }
        input[type="text"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
        }
        input[type="submit"] {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
        }
        input[type="submit"]:hover {
            background-color: #218838;
        }
        .back-button {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 600;
            margin-top: 10px;
        }
        .back-button:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="hair" style="height: 15px; background: linear-gradient(275.52deg, #973939 0.28%, #DC7171 100%);"></div>

        <div class="container">
            <div class="header">
                <div class="headerLeft">
                    <div class="USePData">
                        <img class="USeP" src="images/USePLogo.png" height="36">
                        <div style="height: 0px; width: 16px;"></div>
                        <div style="height: 32px; width: 1px; background: #E5E5E5"></div>
                        <div style="height: 0px; width: 16px;"></div>
                        <div class="headerLeftText">
                            <div class="onedata">
                                <span class="one">One</span>
                                <span class="datausep">Data.</span>
                                <span class="one">One</span>
                                <span class="datausep">USeP.</span>
                            </div>
                            <h>Accreditor Portal</h>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div style="height: 1px; width: 100%; background: #E5E5E5"></div>
    </div>

    <div class="container2">
        <div class="pageHeader">
            <div class="headerRight">
                <a class="btn" href="college.php">Back</a>
            </div>
            <h2>Edit Company</h2>
        </div>
        <form action="edit_company_process.php" method="post">
            <input type="hidden" name="company_id" value="<?php echo $company_id; ?>">
            <div class="form-group">
                <label for="company_name">Company Name:</label>
                <input type="text" id="company_name" name="company_name" value="<?php echo htmlspecialchars($company['company_name']); ?>" required>
            </div>
            <input type="submit" value="Update Company">
        </form>
    </div>
</body>
</html>
