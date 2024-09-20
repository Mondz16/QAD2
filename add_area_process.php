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

        .popup {
            display: block;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .popup-content {
            background-color: #fff;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
            text-align: center;
            border-radius: 10px;
            position: relative;
            margin: 10% auto;
        }

        .okay {
            color: black;
            text-decoration: none;
            white-space: unset;
            font-size: 1rem;
            font-weight: bold;
            text-transform: uppercase;
            border: 1px solid;
            border-radius: 10px;
            cursor: pointer;
            padding: 16px 55px;
            min-width: 120px;
        }

        .okay:hover {
            background-color: #EAEAEA;
        }

        .hairpop-up {
            height: 15px;
            background: linear-gradient(275.52deg, #973939 0.28%, #DC7171 100%);
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            width: 100%;
            border-bottom-left-radius: 10px;
            border-bottom-right-radius: 10px;
        }

        .error {
            color: red;
        }

        .success {
            color: green;
        }
    </style>
</head>
<body>
    <div id="successPopup" class="popup">
        <div class="popup-content">
            <div style="height: 50px; width: 0px;"></div>
            <img class="Success" src="images/Success.png" height="100">
            <div style="height: 20px; width: 0px;"></div>
            <div class="message">
                <?php
                $servername = "localhost";
                $username = "root";
                $password = "";
                $dbname = "qadDB";

                $conn = new mysqli($servername, $username, $password, $dbname);

                if ($conn->connect_error) {
                    die("<p class='error'>Connection failed: " . $conn->connect_error . "</p>");
                }

                $area_code = isset($_POST['area_code']) ? intval($_POST['area_code']) : 0;
                $area_name = isset($_POST['area_name']) ? trim($_POST['area_name']) : '';
                $area_parameters = isset($_POST['area_parameters']) ? intval($_POST['area_parameters']) : 0;

                if ($area_code > 0 && !empty($area_name) && $area_parameters >= 0) {
                    // Prepare and bind
                    $stmt = $conn->prepare("INSERT INTO area (area_code, area_name, area_parameters) VALUES (?, ?, ?)");
                    $stmt->bind_param("isi", $area_code, $area_name, $area_parameters);

                    if ($stmt->execute()) {
                        echo "<p class='success'>New area added successfully!</p>";
                    } else {
                        echo "<p class='error'>Error: " . $stmt->error . "</p>";
                    }

                    $stmt->close();
                } else {
                    echo "<p class='error'>Please fill in all fields correctly.</p>";
                }

                $conn->close();
                ?>
            </div>
            <div style="height: 50px; width: 0px;"></div>
            <a href="area.php" class="okay" id="closePopup">Okay</a>
            <div style="height: 100px; width: 0px;"></div>
            <div class="hairpop-up"></div>
        </div>
    </div>
</body>
</html>
