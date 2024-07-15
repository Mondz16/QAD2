<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add College and Programs</title>
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
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
        }
        input[type="text"],
        input[type="date"] {
            width: calc(100% - 10px);
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
        }
        .add-program-button {
            background-color: #007bff;
            color: #fff;
            border: none;
            padding: 8px 12px;
            cursor: pointer;
            border-radius: 4px;
        }
        .add-program-button:hover {
            background-color: #0056b3;
        }
        .programs {
            border: 1px solid #ccc;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        .programs label {
            margin-bottom: 5px;
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Add College and Programs</h2>
        <form action="add_college_process.php" method="post">
            <div class="form-group">
                <label for="college_name">College Name:</label>
                <input type="text" id="college_name" name="college_name" required>
            </div>
            <div id="programs">
                <div class="programs">
                    <label for="program_1">Program:</label>
                    <input type="text" id="program_1" name="programs[]" required>
                    <label for="level_1">Level:</label>
                    <input type="text" id="level_1" name="levels[]" required>
                    <label for="date_received_1">Date Received:</label>
                    <input type="date" id="date_received_1" name="dates_received[]" required>
                </div>
            </div>
            <button type="button" class="add-program-button" onclick="addProgram()">Add Program</button>
            <button type="submit">Add College</button>
        </form>
        <a href="college.php">Back</a>
    </div>
    <script>
        let programCount = 1;

        function addProgram() {
            programCount++;
            const programsDiv = document.getElementById('programs');

            const newProgramDiv = document.createElement('div');
            newProgramDiv.classList.add('programs');
            newProgramDiv.innerHTML = `
                <label for="program_${programCount}">Program:</label>
                <input type="text" id="program_${programCount}" name="programs[]" required>
                <label for="level_${programCount}">Level:</label>
                <input type="text" id="level_${programCount}" name="levels[]" required>
                <label for="date_received_${programCount}">Date Received:</label>
                <input type="date" id="date_received_${programCount}" name="dates_received[]" required>
            `;

            programsDiv.appendChild(newProgramDiv);
        }
    </script>
</body>
</html>
