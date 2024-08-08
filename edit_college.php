<?php
include 'connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$college_code = $_GET['code'];

// Fetch college details
$sql = "SELECT * FROM college WHERE code = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $college_code); // Corrected to "s"
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
$stmt->bind_param("s", $college_code); // Corrected to "s"
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="css/form_style.css">
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

                <div class="headerRight">
                    <div class="QAD">
                        <div class="headerRightText">
                            <h style="color: rgb(87, 87, 87); font-weight: 600; font-size: 16px;">Quality Assurance Division</h>
                        </div>
                        <div style="height: 0px; width: 16px;"></div>
                        <div style="height: 32px; width: 1px; background: #E5E5E5"></div>
                        <div style="height: 0px; width: 16px;"></div>
                        <img class="USeP" src="images/QADLogo.png" height="36">
                    </div>
                </div>
            </div>
        </div>
        <div style="height: 1px; width: 100%; background: #E5E5E5"></div>
        <div class="container d-flex align-items-center mt-4">
            <a class="btn-back" href="college.php">&lt; BACK</a>
            <h2 class="mt-4 mb-4">EDIT COLLEGE</h2>
        </div>

        <div class="container2">
            <div class="form-container">
                <form action="edit_college_process.php" method="post">
                    <input type="hidden" name="college_code" value="<?php echo htmlspecialchars($college_code); ?>">
                    <input type="hidden" name="removed_program_ids" id="removed_program_ids" value="">
                    <div class="form-group">
                        <label for="college_name">College Name:</label>
                        <input type="text" id="college_name" name="college_name" value="<?php echo htmlspecialchars($college['college_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="college_email">College Email:</label>
                        <input type="email" id="college_email" name="college_email" value="<?php echo htmlspecialchars($college['college_email']); ?>" required>
                    </div>
                    <label for="program_${programCount}">PROGRAMS:</label>
                    <div id="programs">
                        <?php foreach ($programs as $index => $program) : ?>
                            <div class="program-holder programs" data-index="<?php echo $index + 1; ?>">
                                <input type="hidden" name="program_ids[]" value="<?php echo htmlspecialchars($program['id']); ?>">
                                <input type="text" id="program_<?php echo $index + 1; ?>" name="programs[]" value="<?php echo htmlspecialchars($program['program_name']); ?>" required readonly>
                                <input type="text" id="level_<?php echo $index + 1; ?>" name="levels[]" value="<?php echo htmlspecialchars($program['program_level']); ?>" readonly required readonly>
                                <input type="date" id="date_received_<?php echo $index + 1; ?>" name="dates_received[]" value="<?php echo htmlspecialchars($program['date_received']); ?>" readonly required>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="form-buttons">
                        <button type="button" class="add-button" onclick="showAddProgramModal()">Add Program</button>
                        <button type="button" class="remove-program-button" id="remove-program-button" onclick="showRemoveProgramModal()">Remove Program</button>
                        <button type="submit">UPDATE</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Add Program Modal -->
        <div class="modal fade" id="programModal" tabindex="-1" aria-labelledby="programModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="programModalLabel">Add Program</h5>
                    </div>
                    <div class="modal-body">
                        <form id="programForm">
                            <div class="form-group">
                                <label for="modal_program">Program:</label>
                                <input type="text" id="modal_program" name="modal_program" required>
                            </div>
                            <div class="form-group">
                                <label for="modal_level">LEVEL:</label>
                                <select id="modal_level" name="modal_level" required>
                                    <option value="Not Accreditable">Not Accreditable</option>
                                    <option value="Candidate">Candidate</option>
                                    <option value="PSV">PSV</option>
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
                            <div class="bottom-button-holder">
                                <button type="button" class="cancel-modal-button" data-dismiss="modal">CANCEL</button>
                                <button type=" button" class="submit-modal-button" onclick="addProgram()">ADD PROGRAM</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Remove Program Modal -->
        <div class=" modal fade" id="removeProgramModal" tabindex="-1" aria-labelledby="removeProgramModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="removeProgramModalLabel">REMOVE PROGRAM</h5>
                    </div>
                    <div class="modal-body">
                        <form id="removeProgramForm">
                            <div id="removeProgramsList">
                                <!-- Program entries will be listed here -->
                            </div>
                            <div class="bottom-button-holder">
                                <button type="button" class="cancel-modal-button" data-dismiss="modal">CANCEL</button>
                                <button type="button" class="remove-program-button" onclick="removeSelectedPrograms()">CONFIRM</button>
                            </div>
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

                // Check if any of the values are empty or null
                if (!program || !level || !dateReceived) {
                    alert('Please fill in all fields before adding a program.');
                    return; // Exit the function if any field is empty
                }

                const programsDiv = document.getElementById('programs');
                const newIndex = programsDiv.children.length + 1;

                const newProgramDiv = document.createElement('div');
                newProgramDiv.classList.add('form-group', 'programs');
                newProgramDiv.dataset.index = newIndex;
                newProgramDiv.innerHTML = `
                    <div class="program-holder">
                        <input type="hidden" name="program_ids[]" value="">
                        <input type="text" id="new_program" name="new_programs[]" value="${program}" readonly>
                        <input type="text" id="new_level" name="new_levels[]" value="${level}" readonly>
                        <input type="date" id="new_date_received" name="new_dates_received[]" value="${dateReceived}" readonly>
                    </div>
                `;

                programsDiv.appendChild(newProgramDiv);
                console.log(`${program} | ${level} | ${dateReceived}`);

                // Clear the modal form fields
                document.getElementById('programForm').reset();

                $('#programModal').modal('hide');
            }

            function updateRemoveProgramsList() {
                const removeProgramsList = document.getElementById('removeProgramsList');
                removeProgramsList.innerHTML = '';

                const programs = document.querySelectorAll('#programs .programs');
                programs.forEach((programDiv) => {
                    const program = programDiv.querySelector(`[id^='program_']`).value;
                    const level = programDiv.querySelector(`[id^='level_']`).value;
                    const dateReceived = programDiv.querySelector(`[id^='date_received_']`).value;

                    const programEntryDiv = document.createElement('div');
                    programEntryDiv.classList.add('program-entry');
                    programEntryDiv.innerHTML = `
                    <label for="remove_program_${programDiv.dataset.index}">${program} - ${level}</label>
                    <input type="checkbox" id="remove_program_${programDiv.dataset.index}" name="remove_programs[]" value="${programDiv.dataset.index}">
                `;

                    removeProgramsList.appendChild(programEntryDiv);
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
