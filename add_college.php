<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add College and Programs</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600&display=swap">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="css/form_style.css">
</head>

<body>
    <div class="wrapper">
        <div class="row top-bar"></div>
        <div class="row header mb-3">
            <div class="col-6 col-md-2 mx-auto d-flex align-items-center justify-content-end">
                <img src="images/USePLogo.png" alt="USeP Logo">
            </div>
            <div class="col-6 col-md-4 d-flex align-items-start">
                <div class="vertical-line"></div>
                <div class="divider"></div>
                <div class="text">
                    <span class="one">One</span>
                    <span class="datausep">Data.</span>
                    <span class="one">One</span>
                    <span class="datausep">USeP.</span><br>
                    <span>Quality Assurance Division</span>
                </div>
            </div>
            <div class="col-md-4 d-none d-md-flex align-items-center justify-content-end">
            </div>
            <div class="col-md-2 d-none d-md-flex align-items-center justify-content-start">
            </div>
        </div>
        <div class="container d-flex align-items-center mt-4">
            <a class="btn-back" href="college.php">&lt; BACK</a>
            <h2 class="mt-4 mb-4">ADD COLLEGE</h2>
        </div>
    </div>

    <div class="container2">
        <div class="form-container">
            <form action="add_college_process.php" method="post">
                <div class="form-group">
                    <label for="college_name">COLLEGE NAME:</label>
                    <input type="text" id="college_name" name="college_name" required>
                </div>
                <div class="form-group">
                    <label for="college_campus">COLLEGE CAMPUS:</label>
                    <select id="college_campus" name="college_campus" required>
                        <option value="Obrero">Obrero</option>
                        <option value="Mintal">Mintal</option>
                        <option value="Mabini">Tagum-Mabini</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="college_email">COLLEGE EMAIL:</label>
                    <input type="email" id="college_email" name="college_email" required>
                </div>
                <label for="program_${programCount}">PROGRAMS:</label>
                <div id="programs">
                    <!-- Program entries will be appended here -->
                </div>
                <button type="button" class="add-button" onclick="showAddProgramModal()">ADD PROGRAM</button>
                <button type="button" id="remove-program-button" class="remove-program-button" onclick="showRemoveProgramModal()">REMOVE PROGRAM</button>
                <button type="submit">SUBMIT</button>
            </form>
        </div>
    </div>

    <!-- Add Program Modal -->
    <div class="modal fade" id="programModal" tabindex="-1" aria-labelledby="programModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="programModalLabel">ADD PROGRAM</h5>
                </div>
                <div class="modal-body">
                    <form id="programForm">
                        <div class="form-group">
                            <label for="modal_program">PROGRAM NAME:</label>
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
                            <label for="modal_date_received">DATE RECEIVED:</label>
                            <input type="date" id="modal_date_received" name="modal_date_received" required>
                        </div>
                        <div class="bottom-button-holder">
                            <button type="button" class="cancel-modal-button" data-dismiss="modal"">CANCEL</button>
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
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        let programCount = 0;

        function showAddProgramModal() {
            $('#programModal').modal('show');
        }

        function showRemoveProgramModal() {
            $('#removeProgramModal').modal('show');
            updateRemoveProgramsList();
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

            programCount++;

            const programsDiv = document.getElementById('programs');

            const newProgramDiv = document.createElement('div');
            newProgramDiv.classList.add('programs');
            newProgramDiv.dataset.index = programCount; // Use data-index to identify program

            newProgramDiv.innerHTML = `
                <div class="program-holder">
                    <input type="text" id="program_${programCount}" name="programs[]" value="${program}" readonly>
                    <input type="text" id="level_${programCount}" name="levels[]" value="LEVEL: ${level}" readonly>
                    <input type="date" id="date_received_${programCount}" name="dates_received[]" value="${dateReceived}" readonly>
                </div>
                    `;

            programsDiv.appendChild(newProgramDiv);

            // Clear the modal form fields
            document.getElementById('programForm').reset();

            // Hide the modal
            $('#programModal').modal('hide');

            // Show or hide the Remove Program button
            toggleRemoveProgramButton();
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
            const checkboxes = document.querySelectorAll('#removeProgramsList input[type="checkbox"]:checked');
            const indices = Array.from(checkboxes).map(checkbox => checkbox.value);

            // Remove programs in reverse order
            indices.reverse().forEach(index => {
                const programDiv = document.querySelector(`#programs .programs[data-index='${index}']`);
                if (programDiv) {
                    programDiv.remove();
                }
            });

            // Hide the modal
            $('#removeProgramModal').modal('hide');

            // Show or hide the Remove Program button
            toggleRemoveProgramButton();
        }

        function toggleRemoveProgramButton() {
            const removeProgramButton = document.getElementById('remove-program-button');
            const programs = document.querySelectorAll('#programs .programs');
            if (programs.length > 0) {
                removeProgramButton.style.display = 'inline-block';
            } else {
                removeProgramButton.style.display = 'none';
            }
        }

        // Initial check to hide or show the Remove Program button on page load
        document.addEventListener('DOMContentLoaded', toggleRemoveProgramButton);
    </script>


</body>

</html>