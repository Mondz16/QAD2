<?php
include 'connection.php';

$college_code = $_GET['code'];

$sql = "SELECT * FROM college WHERE code = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $college_code);
$stmt->execute();
$result = $stmt->get_result();
$college = $result->fetch_assoc();

$sql = "SELECT * FROM program WHERE college_code = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $college_code);
$stmt->execute();
$programs_result = $stmt->get_result();

$programs = [];
while ($row = $programs_result->fetch_assoc()) {
    $programs[] = [
        'id' => $row['id'],
        'program_name' => $row['program_name'],
        'program_level' => $row['program_level'],
        'date_received' => $row['date_received']
    ];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit College</title>
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
            max-width: 800px;
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

        input[type="text"],
        input[type="email"],
        input[type="date"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
        }

        .form-buttons {
            display: flex;
            align-items: center;
            margin-top: 20px;
            font-size: 14px;
        }

        .update-college-button {
            margin-right: 10px;
            background-color: #2cb84f;
            color: white;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
        }

        .remove-program-button {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
            margin-top: 10px;
        }

        .add-program-button {
            background-color: #888;
            color: white;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
        }

        .update-college-button:hover {
            background-color: #218838;
        }

        .add-program-button:hover {
            background-color: #dc3545;
        }

        .remove-program-button {
            background-color: #dc3545;
        }

        .remove-program-button:hover {
            background-color: #c82333;
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
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin-left: -310px;
            transition: background-color 0.3s ease;
        }

        .headerRight .btn:hover {
            background-color: #b82c3b;
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <div class="hair" style="height: 15px; background: linear-gradient(275.52deg, #973939 0.28%, #DC7171 100%);"></div>

        <div class="container">
            <div class="header">
                <div class="headerLeft">
                    <div class=USePData>
                        <img class="USeP" src="images/USePLogo.png" height="36">
                        <div style="height: 0px; width: 16px;"></div>
                        <div style="height: 32px; width: 1px; background: #E5E5E5"></div>
                        <div style="height: 0px; width: 16px;"></div>
                        <div class="headerLeftText">
                            <div class="onedata" style="height: 100%; width: 100%; display: flex; flex-flow: unset; place-content: unset; align-items: unset; overflow: unset;">
                                <h><span class="one" style="color: rgb(229, 156, 36); font-weight: 600; font-size: 18px;">One</span>
                                    <span class="datausep" style="color: rgb(151, 57, 57); font-weight: 600; font-size: 18px;">Data.</span>
                                    <span class="one" style="color: rgb(229, 156, 36); font-weight: 600; font-size: 18px;">One</span>
                                    <span class="datausep" style="color: rgb(151, 57, 57); font-weight: 600; font-size: 18px;">USeP.</span>
                                </h>
                            </div>
                            <h>Accreditor Portal</h>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <div class="container2">
            <div class="pageHeader">
                <div class="headerRight">
                    <a class="btn" href="college.php">Back</a>
                </div>
                <h2>Edit College</h2>
            </div>
            <form action="edit_college_process.php" method="post">
                <input type="hidden" name="college_code" value="<?php echo $college_code; ?>">
                <input type="hidden" name="removed_program_ids" id="removed_program_ids" value="">
                <div class="form-group">
                    <label for="college_name">College Name:</label>
                    <input type="text" id="college_name" name="college_name" value="<?php echo htmlspecialchars($college['college_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="college_email">College Email:</label>
                    <input type="email" id="college_email" name="college_email" value="<?php echo htmlspecialchars($college['college_email']); ?>" required>
                </div>
                <?php foreach ($programs as $index => $program) : ?>
                    <div class="form-group programs">
                        <input type="hidden" name="program_ids[]" value="<?php echo htmlspecialchars($program['id']); ?>">
                        <label for="program_<?php echo $index + 1; ?>">Program:</label>
                        <input type="text" id="program_<?php echo $index + 1; ?>" name="programs[]" value="<?php echo htmlspecialchars($program['program_name']); ?>" required>
                        <label for="level_<?php echo $index + 1; ?>">Level:</label>
                        <input type="text" id="level_<?php echo $index + 1; ?>" name="levels[]" value="<?php echo htmlspecialchars($program['program_level']); ?>" required>
                        <label for="date_received_<?php echo $index + 1; ?>">Date Received:</label>
                        <input type="date" id="date_received_<?php echo $index + 1; ?>" name="dates_received[]" value="<?php echo htmlspecialchars($program['date_received']); ?>" required>
                        <div class="btn-group">
                            <button type="button" class="remove-program-button" onclick="removeProgram(this, <?php echo htmlspecialchars($program['id']); ?>)">Remove Program</button>
                        </div>
                    </div>
                <?php endforeach; ?>
                <div class="form-buttons">
                    <input type="submit" class="update-college-button" value="Update College">
                    <button type="button" class="add-program-button" onclick="addProgram()">Add Program</button>
                </div>
            </form>
        </div>

        <script>
            let removedProgramIds = [];

            function addProgram() {
                const formButtons = document.querySelector('.form-buttons');
                const newProgramDiv = document.createElement('div');
                newProgramDiv.classList.add('form-group', 'programs');
                newProgramDiv.innerHTML = `
                    <label for="new_program">Program:</label>
                    <input type="text" id="new_program" name="new_programs[]" required>
                    <label for="new_level">Level:</label>
                    <input type="text" id="new_level" name="new_levels[]" required>
                    <label for="new_date_received">Date Received:</label>
                    <input type="date" id="new_date_received" name="new_dates_received[]" required>
                    <div class="btn-group">
                        <button type="button" class="remove-program-button" onclick="removeProgram(this)">Remove Program</button>
                    </div>
                `;
                formButtons.parentNode.insertBefore(newProgramDiv, formButtons);
            }

            function removeProgram(btn, programId = null) {
                if (programId) {
                    removedProgramIds.push(programId);
                    document.getElementById('removed_program_ids').value = removedProgramIds.join(',');
                }
                const programDiv = btn.parentNode.parentNode;
                programDiv.parentNode.removeChild(programDiv);
            }
        </script>
    </div>
</body>

</html>