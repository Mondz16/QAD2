<?php
include 'connection.php';

$college_id = $_GET['id'];

$sql = "SELECT * FROM college WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $college_id);
$stmt->execute();
$result = $stmt->get_result();
$college = $result->fetch_assoc();

$sql = "SELECT * FROM program WHERE college_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $college_id);
$stmt->execute();
$programs_result = $stmt->get_result();

$programs = [];
while ($row = $programs_result->fetch_assoc()) {
    $programs[] = [
        'id' => $row['id'],
        'program' => $row['program'],
        'level' => $row['level'],
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
    <style>
        form {
            width: 50%;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 10px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
        }
        .form-group input {
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
        }
        .programs {
            margin-bottom: 15px;
        }
        .programs input {
            display: inline-block;
            width: calc(100% - 40px);
        }
        .programs .btn-group {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .programs .add-btn,
        .programs .remove-btn {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
        }
        .programs .remove-btn {
            background-color: #f44336;
        }
    </style>
</head>
<body>
    <h2>Edit College</h2>
    <form action="edit_college_process.php" method="post">
        <input type="hidden" name="college_id" value="<?php echo $college_id; ?>">
        <div class="form-group">
            <label for="college_name">College Name:</label>
            <input type="text" id="college_name" name="college_name" value="<?php echo htmlspecialchars($college['college_name']); ?>" required>
        </div>
        <?php foreach ($programs as $index => $program): ?>
            <div class="form-group programs">
                <input type="hidden" name="program_ids[]" value="<?php echo htmlspecialchars($program['id']); ?>">
                <label for="program_<?php echo $index + 1; ?>">Program:</label>
                <input type="text" id="program_<?php echo $index + 1; ?>" name="programs[]" value="<?php echo htmlspecialchars($program['program']); ?>" required>
                <label for="level_<?php echo $index + 1; ?>">Level:</label>
                <input type="text" id="level_<?php echo $index + 1; ?>" name="levels[]" value="<?php echo htmlspecialchars($program['level']); ?>" required>
                <label for="date_received_<?php echo $index + 1; ?>">Date Received:</label>
                <input type="date" id="date_received_<?php echo $index + 1; ?>" name="dates_received[]" value="<?php echo htmlspecialchars($program['date_received']); ?>" required>
                <div class="btn-group">
                    <?php if ($index === count($programs) - 1): ?>
                        <button type="button" class="add-btn" onclick="addProgram(this)">Add Program</button>
                    <?php endif; ?>
                    <button type="button" class="remove-btn" onclick="removeProgram(this)">Remove Program</button>
                </div>
            </div>
        <?php endforeach; ?>
        <button type="submit">Update College</button>
        <a href="college.php">Back</a>
    </form>

    <script>
        function addProgram(btn) {
            const programDiv = btn.parentNode.parentNode;
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
                    <button type="button" class="add-btn" onclick="addProgram(this)">Add Program</button>
                    <button type="button" class="remove-btn" onclick="removeProgram(this)">Remove Program</button>
                </div>
            `;
            programDiv.parentNode.insertBefore(newProgramDiv, programDiv.nextSibling);
            const previousProgram = programDiv.previousSibling;
            if (previousProgram) {
                previousProgram.querySelector('.add-btn').style.display = 'none';
            }
            btn.style.display = 'none';
        }

        function removeProgram(btn) {
            const programDiv = btn.parentNode.parentNode;
            programDiv.parentNode.removeChild(programDiv);
            const lastProgram = document.querySelector('.programs:last-of-type');
            if (lastProgram) {
                lastProgram.querySelector('.add-btn').style.display = 'inline-block';
            }
        }
    </script>
</body>
</html>
