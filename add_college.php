<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add College and Programs</title>
</head>
<body>
    <h2>Add College and Programs</h2>
    <form action="add_college_process.php" method="post">
        <div class="form-group">
            <label for="college_name">College Name:</label>
            <input type="text" id="college_name" name="college_name" required>
        </div>
        <div class="form-group">
            <label for="college_email">College Email:</label>
            <input type="email" id="college_email" name="college_email" required>
        </div>
        <div id="programs">
            <div class="form-group programs" id="program_1_group">
                <label for="program_1">Program:</label>
                <input type="text" id="program_1" name="programs[]" required>
                <label for="level_1">Level:</label>
                <input type="text" id="level_1" name="levels[]" required>
                <label for="date_received_1">Date Received:</label>
                <input type="date" id="date_received_1" name="dates_received[]" required>
                <button type="button" onclick="addProgram()">+</button>
            </div>
        </div>
        <button type="submit">Add College</button>
    </form>
    <a href="college.php">Back</a>
    <script>
        let programCount = 1;

        function addProgram() {
            programCount++;
            const programsDiv = document.getElementById('programs');

            const newProgramDiv = document.createElement('div');
            newProgramDiv.classList.add('form-group', 'programs');
            newProgramDiv.innerHTML = `
                <label for="program_${programCount}">Program:</label>
                <input type="text" id="program_${programCount}" name="programs[]" required>
                <label for="level_${programCount}">Level:</label>
                <input type="text" id="level_${programCount}" name="levels[]" required>
                <label for="date_received_${programCount}">Date Received:</label>
                <input type="date" id="date_received_${programCount}" name="dates_received[]" required>
                <button type="button" onclick="addProgram()">+</button>
            `;

            programsDiv.appendChild(newProgramDiv);
        }
    </script>
</body>
</html>
