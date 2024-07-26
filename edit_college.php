<?php
include 'connection.php';

$college_code = $_GET['code'];

// Fetch college details
$sql = "SELECT * FROM college WHERE code = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $college_code);
$stmt->execute();
$result = $stmt->get_result();
$college = $result->fetch_assoc();

// Fetch programs along with their levels and date received from program_level_history
$sql = "SELECT 
            p.id, 
            p.program_name, 
            plh.program_level, 
            plh.date_received 
        FROM 
            program p
        LEFT JOIN 
            program_level_history plh 
        ON 
            p.program_level_id = plh.id 
        WHERE 
            p.college_code = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $college_code);
$stmt->execute();
$programs_result = $stmt->get_result();

$programs = [];
while ($row = $programs_result->fetch_assoc()) {
    $programs[] = [
        'id' => $row['id'],
        'program_name' => $row['program_name'],
        'program_level' => $row['program_level'] ?? 'N/A',
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
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">

    
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
        }

        .add-program-button {
            margin-right: 10px;
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
                <div id="programs">
                    <?php foreach ($programs as $index => $program) : ?>
                        <div class="form-group programs" data-index="<?php echo $index + 1; ?>">
                            <input type="hidden" name="program_ids[]" value="<?php echo htmlspecialchars($program['id']); ?>">
                            <label for="program_<?php echo $index + 1; ?>">Program:</label>
                            <input type="text" id="program_<?php echo $index + 1; ?>" name="programs[]" value="<?php echo htmlspecialchars($program['program_name']); ?>" required>
                            <label for="level_<?php echo $index + 1; ?>">Level:</label>
                            <input type="text" id="level_<?php echo $index + 1; ?>" name="levels[]" value="<?php echo htmlspecialchars($program['program_level']); ?>" readonly required>
                            <label for="date_received_<?php echo $index + 1; ?>">Date Received:</label>
                            <input type="date" id="date_received_<?php echo $index + 1; ?>" name="dates_received[]" value="<?php echo htmlspecialchars($program['date_received']); ?>" readonly required>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="form-buttons">
                    <input type="submit" class="update-college-button" value="Update College">
                    <button type="button" class="add-program-button" onclick="showAddProgramModal()">Add Program</button>
                    <button type="button" class="remove-program-button" id="remove-program-button" onclick="showRemoveProgramModal()">Remove Program</button>
                </div>
            </form>
        </div>

        <!-- Add Program Modal -->
        <div class="modal fade" id="programModal" tabindex="-1" aria-labelledby="programModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="programModalLabel">Add Program</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="programForm">
                            <div class="form-group">
                                <label for="modal_program">Program:</label>
                                <input type="text" id="modal_program" name="modal_program" required>
                            </div>
                            <div class="form-group">
                                <label for="modal_level">Level:</label>
                                <select id="modal_level" name="modal_level" required>
                                    <option value="N/A">Optional</option>
                                    <option value="PSV">No Graduates Yet</option>
                                    <option value="PSV">Not Accreditable</option>
                                    <option value="PSV">TBV</option>
                                    <option value="PSV">PSV</option>
                                    <option value="PSV">Candidate</option>
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                    <option value="4">4</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="modal_date_received">Date Received:</label>
                                <input type="date" id="modal_date_received" name="modal_date_received" required>
                            </div>
                            <button type="button" class="btn btn-primary" onclick="addProgram()">Add Program</button>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Remove Program Modal -->
        <div class="modal fade" id="removeProgramModal" tabindex="-1" aria-labelledby="removeProgramModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="removeProgramModalLabel">Remove Program</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="removeProgramForm">
                            <div id="removeProgramsList">
                                <!-- Program entries will be listed here -->
                            </div>
                            <button type="button" class="btn btn-danger" onclick="removeSelectedPrograms()">Remove Selected Programs</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
        <script>
            function showAddProgramModal() {
                $('#programModal').modal('show');
            }

            function showRemoveProgramModal() {
                updateRemoveProgramsList();
                $('#removeProgramModal').modal('show');
            }

            function addProgram() {
                const program = document.getElementById('modal_program').value;
                const level = document.getElementById('modal_level').value;
                const dateReceived = document.getElementById('modal_date_received').value;

                const programsDiv = document.getElementById('programs');
                const newIndex = programsDiv.children.length + 1;

                const newProgramDiv = document.createElement('div');
                newProgramDiv.classList.add('form-group', 'programs');
                newProgramDiv.dataset.index = newIndex;
                newProgramDiv.innerHTML = `
                    <input type="hidden" name="program_ids[]" value="">
                    <label for="new_program">Program:</label>
                    <input type="text" id="new_program" name="new_programs[]" value="${program}" required>
                    <label for="new_level">Level:</label>
                    <input type="text" id="new_level" name="new_levels[]" value="${level}" required>
                    <label for="new_date_received">Date Received:</label>
                    <input type="date" id="new_date_received" name="new_dates_received[]" value="${dateReceived}" required>
                `;

                programsDiv.appendChild(newProgramDiv);
                $('#programModal').modal('hide');
            }

            function updateRemoveProgramsList() {
                const programs = document.querySelectorAll('.programs');
                const removeProgramsList = document.getElementById('removeProgramsList');
                removeProgramsList.innerHTML = '';

                programs.forEach((program, index) => {
                    const programLabel = program.querySelector(`[for="program_${index + 1}"]`);
                    const programName = program.querySelector(`[name="programs[]"]`).value;

                    const checkbox = document.createElement('input');
                    checkbox.type = 'checkbox';
                    checkbox.id = `remove_program_${index + 1}`;
                    checkbox.name = 'remove_programs';
                    checkbox.value = index + 1;

                    const label = document.createElement('label');
                    label.htmlFor = `remove_program_${index + 1}`;
                    label.textContent = programName;

                    const div = document.createElement('div');
                    div.appendChild(checkbox);
                    div.appendChild(label);

                    removeProgramsList.appendChild(div);
                });
            }

            function removeSelectedPrograms() {
                const selectedPrograms = document.querySelectorAll('#removeProgramsList input[type="checkbox"]:checked');
                const removedProgramIds = [];

                selectedPrograms.forEach(checkbox => {
                    const index = checkbox.value;
                    const programDiv = document.querySelector(`.programs[data-index="${index}"]`);
                    const programId = programDiv.querySelector('input[name="program_ids[]"]').value;

                    programDiv.remove();
                    removedProgramIds.push(programId);
                });

                const removedProgramIdsInput = document.getElementById('removed_program_ids');
                removedProgramIdsInput.value = removedProgramIds.join(',');

                $('#removeProgramModal').modal('hide');
            }
        </script>
    </div>
</body>

</html>
