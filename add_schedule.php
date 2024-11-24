<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Schedule</title>
    <!-- Include Select2 CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="css/form_style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <style>
        .popup {
            display: none;
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
            margin: 10% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
            text-align: center;
            border-radius: 10px;
            position: relative;
        }

        .popup-image {
            max-width: 100%;
            height: auto;
        }

        .popup-text {
            margin: 20px 25px;
            font-size: 17px;
            font-weight: 500;
        }

        .hairpop-up {
            height: 15px;
            background: #9B0303;
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            width: 100%;
            border-bottom-left-radius: 10px;
            border-bottom-right-radius: 10px;
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

        .loading-spinner .spinner-border {
            width: 40px;
            height: 40px;
            border-width: 5px;
            border-color: #B73033 !important;
            /* Enforce the custom color */
            border-right-color: transparent !important;
        }

        #loadingSpinner.spinner-hidden {
            display: none;
        }

        .loading-spinner {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        input[type="date"],
        input[type="time"] {
            cursor: pointer;
        }

        /* Ensure the icon itself is also covered */
        input[type="date"]::-webkit-calendar-picker-indicator,
        input[type="time"]::-webkit-calendar-picker-indicator {
            cursor: pointer;
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <div class="hair" style="height: 15px; background: #9B0303;"></div>
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
            <a class="btn-back" href="schedule.php">&lt; BACK</a>
            <h2 class="mt-4 mb-4">ADD SCHEDULE</h2>
        </div>
    </div>
    <div class="container2">
        <div class="form-container">
            <form id="schedule-form" method="POST" action="add_schedule_process.php">
                <div class="form-group">
                    <label for="college">COLLEGE:</label>
                    <select id="college" name="college" onchange="fetchPrograms(); fetchTeamLeadersAndMembers();" required class="select2" style="cursor: pointer;">
                        <option value="" disabled selected hidden>Select College</option>
                        <?php
                        include 'connection.php';

                        $sql = "SELECT code, college_name FROM college ORDER BY college_name";
                        $result = $conn->query($sql);

                        while ($row = $result->fetch_assoc()) {
                            echo "<option value='{$row['code']}'>{$row['college_name']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div id="programs-container" class="selected-programs-list">

                </div>
                <button type="button" id="add-program-button" class="add-program-input-button" onclick="addProgramInput()" disable>Add Program</button>

                <div class="form-group">
                    <label for="team-leader">TEAM LEADER:</label>
                    <select id="team-leader" name="team_leader" required onchange="updateDropdowns()" style="cursor: pointer;">
                        <option value="">Select Team Leader</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="team-members" id="member-count">TEAM MEMBER/S:</label>
                    <div id="team-members-container">
                        <div class="team-member-input">
                            <select name="team_members[]" class="team-member-select" required onchange="updateDropdowns()" style="cursor: pointer;">
                                <option value="">Select Team Member</option>
                            </select>
                        </div>
                    </div>
                    <button type="button" id="add-member-button" class="add-team-member-button" onclick="addTeamMemberInput()">ADD MEMBER</button>
                </div>
                <div class="bottom-button-holder">
                    <button type="button" class="discard-button" onclick="window.location.href='schedule.php'">DISCARD</button>
                    <button type="submit" class="submit-button" disabled>SUBMIT</button>
                </div>
            </form>
        </div>
    </div>

    <div id="programModal" class="program-modal">
        <div class="program-modal-content">
            <h2>Add Program Schedule</h2>
            <div id="program-form"></div>
            <button type="button" class="save-program-btn">Save Schedule</button>
            <button type="button" class="cancel-program-btn">Cancel</button>
        </div>
    </div>

    <div id="errorPopup" class="popup" style="display: none;">
        <div class="popup-content">
            <div style="height: 50px; width: 0px;"></div>
            <img class="Error" src="images/Error.png" height="100">
            <div style="height: 20px; width: 0px;"></div>
            <div class="popup-text">A schedule for the selected date, and time already exists.</div>
            <div style="height: 50px; width: 0px;"></div>
            <a href="#" class="okay" id="closeErrorPopup">Okay</a>
            <div style="height: 100px; width: 0px;"></div>
            <div class="hairpop-up"></div>
        </div>
    </div>

    <div id="loadingSpinner" class="loading-spinner spinner-hidden">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
        let programCount = 0;

        document.getElementById('college').addEventListener('change', function() {
            fetchPrograms();
            clearProgramsOnCollegeChange();
            updateSubmitButtonState();
        });

        document.addEventListener('DOMContentLoaded', function() {
            // Get the add program button
            const addProgramButton = document.getElementById('add-program-button');

            // Disable the button initially
            if (addProgramButton) {
                addProgramButton.disabled = true;
                // Optional: Add a visual indication that the button is disabled
                addProgramButton.style.cursor = 'not-allowed';
                addProgramButton.style.opacity = '0.6';
            }
        });

        function updateSubmitButtonState() {
            const submitButton = document.querySelector('.submit-button');
            if (submitButton) {
                submitButton.disabled = programsData.length === 0;
                // Optional: Add visual feedback for disabled state
                if (programCount === 0) {
                    submitButton.classList.add('submit-button-disabled');
                } else {
                    submitButton.classList.remove('submit-button-disabled');
                }
            }
        }

        function clearProgramsOnCollegeChange() {
            const programsContainer = document.getElementById('programs-container');
            const programBlocks = document.querySelectorAll('.program-block');

            // Destroy all Select2 instances
            programBlocks.forEach((block) => {
                const blockId = block.id.split('-')[2];
                try {
                    $(`#program-${blockId}`).select2('destroy');
                } catch (e) {
                    console.log('Select2 instance not found or already destroyed');
                }
            });

            // Clear all programs
            programsContainer.innerHTML = '';

            // Reset program count
            programCount = 0;
        }

        function fetchPrograms() {
            const collegeId = document.getElementById('college').value;
            const addProgramButton = document.getElementById('add-program-button');

            if (collegeId) {
                $.ajax({
                    url: 'get_programs.php',
                    type: 'POST',
                    data: {
                        college_id: collegeId
                    },
                    success: function(response) {
                        console.log('Raw response:', response);
                        try {
                            // Enable the add program button
                            if (addProgramButton) {
                                addProgramButton.disabled = false;
                                addProgramButton.style.cursor = 'pointer';
                                addProgramButton.style.opacity = '1';
                            }

                            populateProgramDropdown('#program-1', response);
                        } catch (error) {
                            console.error('Error processing response:', error);
                            // Disable the button if there's an error
                            if (addProgramButton) {
                                addProgramButton.disabled = true;
                                addProgramButton.style.cursor = 'not-allowed';
                                addProgramButton.style.opacity = '0.6';
                            }
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', error);
                        alert('Failed to fetch programs. Please try again.');
                        // Disable the button on error
                        if (addProgramButton) {
                            addProgramButton.disabled = true;
                            addProgramButton.style.cursor = 'not-allowed';
                            addProgramButton.style.opacity = '0.6';
                        }
                    }
                });
            } else {
                console.log("clear program dropdown");
                // Disable the button when no college is selected
                if (addProgramButton) {
                    addProgramButton.disabled = true;
                    addProgramButton.style.cursor = 'not-allowed';
                    addProgramButton.style.opacity = '0.6';
                }
                clearDropdown('#program-1');
            }
        }

        function clearAllProgram() {
            programCount = 0;
            const programsContainer = document.getElementById('programs-container');
            programsContainer.insertAdjacentHTML('beforeend', ``);
        }

        let programsData = [];

        function showProgramModal() {
            const modal = document.getElementById('programModal');
            const programForm = document.getElementById('program-form');

            // Clear previous content
            programForm.innerHTML = '';

            // Add the program form template
            const template = `
        <div class="program-block" id="program-block-temp">
            <div class="form-group">
                <label for="program-temp">PROGRAM:</label>
                <select id="program-temp" name="program" onchange="fetchProgramLevelDynamic('temp')" required class="select2" style="cursor: pointer;">
                    <option value="" disabled selected hidden>Select Program</option>
                </select>
            </div>
            <div>
                <div class="level-header">
                    <label for="level-temp">CURRENT LEVEL:</label>
                    <label class="level-applied" for="level-applied-temp">LEVEL APPLIED:</label>
                    <label for="level-validity-temp">YEARS OF VALIDITY:</label>
                </div>
                <div class="level-input-holder">
                    <input class="level-input" type="text" id="program-level-temp" name="level" readonly>
                    <div class="level-holder">
                        <span id="level-acquired-temp"></span>
                    </div>
                    <input class="level-input highlight" type="text" id="level-output-temp" name="level-output" readonly>
                    <input class="level-input-validity" type="text" id="year-validity-temp" name="level_validity" value="3" required>
                </div>
            </div>
            <div class="dateTime-holder">
                <div class="form-group">
                    <label for="date-temp">DATE:</label>
                    <input type="date" id="date-temp" name="date" required style="cursor: pointer;">
                </div>
                <div class="form-group">
                    <label for="time-temp">TIME:</label>
                    <input type="time" id="time-temp" name="time" required style="cursor: pointer;">
                </div>
            </div>
            <div class="form-group">
                <label for="zoom-temp">MEETING LINK:</label>
                <textarea id="zoom-temp" name="zoom" cols="40" rows="1" placeholder="OPTIONAL"></textarea>
            </div>
        </div>
    `;

            programForm.insertAdjacentHTML('beforeend', template);
            modal.style.display = "block";

            // Initialize select2 for the new dropdown
            $("#program-temp").select({
                dropdownParent: $('#programModal')
            });

            // Populate program dropdown
            updateSelectedPrograms();
            updateSubmitButtonState();
            clearScheduleErrors();
            updateNewProgramDropdown("#program-temp");
        }

        function saveProgramData() {
            // Gather input elements
            const program = document.getElementById('program-temp');
            const levelValidity = document.getElementById('year-validity-temp');
            const date = document.getElementById('date-temp');
            const time = document.getElementById('time-temp');

            // Initialize validation flag and error messages
            let isValid = true;
            const errors = [];

            // Check each required field
            if (!program.value) {
                isValid = false;
                errors.push("Program is required.");
            }

            if (!levelValidity.value) {
                isValid = false;
                errors.push("Level validity is required.");
            }

            if (!date.value) {
                isValid = false;
                errors.push("Date is required.");
            }

            if (!time.value) {
                isValid = false;
                errors.push("Time is required.");
            }

            // If any validation fails, show errors and stop saving
            if (!isValid) {
                alert(`Fill in all the input required! \n\n${errors.join("\n")}`);
                return;
            }

            // Proceed with saving if all fields are valid
            checkScheduleDate((isScheduleValid) => {
                if (isScheduleValid) {
                    programCount++;
                    const programData = {
                        id: programCount,
                        program: program.value,
                        programName: program.options[program.selectedIndex].text,
                        level: document.getElementById('program-level-temp').value,
                        levelApplied: document.getElementById('level-output-temp').value,
                        validity: levelValidity.value,
                        date: date.value,
                        time: time.value,
                        zoom: document.getElementById('zoom-temp').value
                    };

                    programsData.push(programData);
                    updateProgramsList();
                    updateSubmitButtonState();
                    closeModal();
                }
                // If not valid, the modal stays open with the error displayed
            });
        }


        function updateProgramsList() {
            const container = document.querySelector('.selected-programs-list');
            container.innerHTML = '';

            programsData.forEach(program => {
                const programElement = `
            <div class="program-wrapper" id="program-wrapper-${program.id}">
                <div class="selected-program-item">
                    <div class="program-info">
                        <strong>${program.programName}</strong>
                        <div>Date: ${program.date} Time: ${program.time}</div>
                    </div>
                    <div class="program-actions">
                        <button type="button" onclick="editProgram(${program.id})" class="edit-btn">Edit</button>
                        <button type="button" onclick="removeProgram(${program.id})" class="remove-btn">Remove</button>
                    </div>
                </div>
                <div class="hidden-inputs-container">
                    <input type="hidden" class="hidden-program-input" name="program[]" value="${program.program}">
                    <input type="hidden" class="hidden-program-input" name="level[]" value="${program.level}">
                    <input type="hidden" class="hidden-program-input" name="level-output[]" value="${program.levelApplied}">
                    <input type="hidden" class="hidden-program-input" name="level_validity[]" value="${program.validity}">
                    <input type="hidden" class="hidden-program-input" name="date[]" value="${program.date}">
                    <input type="hidden" class="hidden-program-input" name="time[]" value="${program.time}">
                    <input type="hidden" class="hidden-program-input" name="zoom[]" value="${program.zoom}">
                </div>
            </div>
        `;
                container.insertAdjacentHTML('beforeend', programElement);
            });
        }

        function updateHiddenInputs() {
            const form = document.getElementById('schedule-form');

            // Remove existing hidden inputs
            const existingInputs = form.querySelectorAll('.hidden-program-input');
            existingInputs.forEach(input => input.remove());

            // Add new hidden inputs
            programsData.forEach(program => {
                const hiddenInputs = `
            <input type="hidden" class="hidden-program-input" name="program[]" value="${program.program}">
            <input type="hidden" class="hidden-program-input" name="level[]" value="${program.level}">
            <input type="hidden" class="hidden-program-input" name="level-output[]" value="${program.levelApplied}">
            <input type="hidden" class="hidden-program-input" name="level_validity[]" value="${program.validity}">
            <input type="hidden" class="hidden-program-input" name="date[]" value="${program.date}">
            <input type="hidden" class="hidden-program-input" name="time[]" value="${program.time}">
            <input type="hidden" class="hidden-program-input" name="zoom[]" value="${program.zoom}">
        `;
                form.insertAdjacentHTML('beforeend', hiddenInputs);
            });
        }

        function editProgram(id) {
            const program = programsData.find(p => p.id === id);
            if (!program) return;

            showProgramModal();

            // Populate modal with program data
            setTimeout(() => {
                document.getElementById('program-temp').value = program.program;
                document.getElementById('program-level-temp').value = program.level;
                document.getElementById('level-output-temp').value = program.levelApplied;
                document.getElementById('year-validity-temp').value = program.validity;
                document.getElementById('date-temp').value = program.date;
                document.getElementById('time-temp').value = program.time;
                document.getElementById('zoom-temp').value = program.zoom;

                // Remove the old program data
                programsData = programsData.filter(p => p.id !== id);
            }, 100);
        }


        function removeProgram(id) {
            programsData = programsData.filter(program => program.id !== id);
            updateProgramsList();
            updateSubmitButtonState();
        }

        function closeModal() {
            document.getElementById('programModal').style.display = "none";
        }
        document.querySelector('.cancel-program-btn').onclick = closeModal;
        document.querySelector('.save-program-btn').onclick = saveProgramData;
        document.getElementById('add-program-button').onclick = showProgramModal;

        window.onclick = function(event) {
            const modal = document.getElementById('programModal');
            if (event.target === modal) {
                closeModal();
            }
        }

        function reindexPrograms() {
            const programBlocks = document.querySelectorAll('.program-block');
            let newIndex = 1;

            programBlocks.forEach((block) => {
                // Update the block ID
                const oldId = block.id.split('-')[2];
                block.id = `program-block-${newIndex}`;

                // Update all internal IDs and names
                const elements = block.querySelectorAll('[id*="-' + oldId + '"]');
                elements.forEach((element) => {
                    element.id = element.id.replace(oldId, newIndex);
                    if (element.name) {
                        element.name = element.name.replace(oldId, newIndex);
                    }
                });

                // Update onclick handler for remove button
                const removeButton = block.querySelector('.remove-program-btn');
                if (removeButton) {
                    removeButton.setAttribute('onclick', `removeProgramInput(${newIndex})`);
                }

                // Update onchange handler for program select
                const programSelect = block.querySelector(`select[id^="program-"]`);
                if (programSelect) {
                    programSelect.setAttribute('onchange', `fetchProgramLevelDynamic(${newIndex})`);
                }

                newIndex++;
            });

            // Update the global programCount
            programCount = programBlocks.length;
        }

        function updateNewProgramDropdown(selector) {
            const collegeId = document.getElementById('college').value;
            if (collegeId) {
                $.ajax({
                    url: 'get_programs.php',
                    type: 'POST',
                    data: {
                        college_id: collegeId
                    },
                    success: function(response) {
                        updateProgramDropdown(selector, response);
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', error);
                    }
                });
            } else {
                for (let i = 1; i <= programCount; i++) {
                    clearDropdown(`#program-${i}`);
                }
            }
        }

        function updateAllPrograms() {
            const collegeId = document.getElementById('college').value;
            if (collegeId) {
                $.ajax({
                    url: 'get_programs.php',
                    type: 'POST',
                    data: {
                        college_id: collegeId
                    },
                    success: function(response) {
                        updateProgramDropdown(`#program-temp`, response);
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', error);
                    }
                });
            } else {
                for (let i = 1; i <= programCount; i++) {
                    clearDropdown(`#program-${i}`);
                }
            }
        }

        let selectedPrograms = new Set();

        // Update program selections and disable options accordingly
        function updateProgramDropdown(selector, programs) {
            const dropdown = $(selector);

            // Store the current value before clearing
            const currentValue = dropdown.val();
            console.log(`Current value for ${selector}:`, currentValue);

            // Clear and add default option
            dropdown.empty();
            dropdown.append($('<option>').text('Select Program').attr('value', ''));

            // Add and disable options as needed
            programs.forEach(option => {
                // Check if the current option ID exists in programsData
                const isSelectedElsewhere = programsData.some(p => p.program === option.id.toString());

                // Log for debugging purposes
                console.log(`Option ID: ${option.id}, Is Selected Elsewhere: ${isSelectedElsewhere}`);

                // Create the option element and disable it if already selected
                const optionElement = $('<option>')
                    .text(option.name)
                    .attr('value', option.id)
                    .prop('disabled', isSelectedElsewhere);

                dropdown.append(optionElement);
            });

        }

        function updateSelectedPrograms() {
            selectedPrograms.clear();
            for (let i = 1; i <= programCount; i++) {
                const value = $(`#program-${i}`).val();
                if (value) {
                    selectedPrograms.add(value.toString());
                }
            }

            // Update all dropdowns to reflect new selections
            updateAllPrograms();
        }

        // Helper Function: Populate Dropdown
        function populateProgramDropdown(selector, options) {
            console.log(selector);
            const dropdown = $(selector);
            dropdown.empty();
            dropdown.append($('<option>').text('Select Program').attr('value', ''));
            options.forEach(option => {
                dropdown.append($('<option>').text(option.name).attr('value', option.id));
            });
        }

        // Helper Function: Clear Dropdown
        function clearDropdown(selector) {
            const dropdown = $(selector);
            dropdown.empty();
            dropdown.append($('<option>').text('Select Program').attr('value', ''));
        }

        // Modified function to handle program selection change
        function handleProgramChange(count) {
            // Update selected programs
            updateSelectedPrograms();

            // Fetch program level
            fetchProgramLevelDynamic(count);
        }

        // Fetch Program Level for a Specific Program
        function fetchProgramLevelDynamic(count) {
            const programId = document.getElementById(`program-${count}`).value;
            if (programId) {
                $.ajax({
                    url: 'get_program_level.php',
                    type: 'POST',
                    data: {
                        program_id: programId
                    },
                    success: function(response) {
                        const data = JSON.parse(response);
                        const currentLevel = data.program_level.trim();
                        const dateReceived = data.date_received.trim();

                        let levelApplied = 'NA';
                        if (currentLevel === 'Candidate') {
                            levelApplied = 'PSV';
                        } else if (currentLevel < 4) {
                            levelApplied = parseInt(currentLevel) + 1;
                        } else {
                            levelApplied = currentLevel;
                        }

                        document.getElementById(`program-level-${count}`).value = currentLevel;
                        document.getElementById(`level-output-${count}`).value = levelApplied;

                        if (dateReceived !== 'N/A' && currentLevel !== 'NA') {
                            document.getElementById(`level-acquired-${count}`).innerText = `ACQUIRED IN ${dateReceived}`;
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', error);
                    }
                });
            }
        }


        let membersCount = 0;

        function addTeamMemberInput() {
            const container = document.getElementById('team-members-container');
            const newInputDiv = document.createElement('div');
            newInputDiv.className = 'team-member-input';

            newInputDiv.innerHTML = `
        <select name="team_members[]" class="team-member-select" required onchange="updateDropdowns()">
            <option value="">Select Team Member</option>
        </select>
        <button type="button" class="remove-team-member-button" onclick="removeTeamMemberInput(this)">
            <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="currentColor" class="bi bi-x-circle" viewBox="0 0 16 16">
                <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
                <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708"/>
            </svg>
        </button>
        `;
            container.appendChild(newInputDiv);
            fetchTeamMembersForNewDropdown(newInputDiv.querySelector('.team-member-select'));
            checkAvailableLeadersAndMembers();
        }

        function removeTeamMemberInput(button) {
            button.parentElement.remove();
            updateDropdowns();
            checkAvailableLeadersAndMembers();
        }

        // function fetchPrograms() {
        //     var collegeId = document.getElementById('college').value;
        //     console.log(collegeId);
        //     if (collegeId) {
        //         $.ajax({
        //             url: 'get_programs.php',
        //             type: 'POST',
        //             data: {
        //                 college_id: collegeId
        //             },
        //             success: function(response) {
        //                 console.log(response);
        //                 $('#program').html(response);
        //                 $('#program-level').html(''); // Clear the program level display
        //                 $('#level').val(''); // Clear the program level display
        //                 $('#level-acquired').val(''); // Clear the program level display
        //                 $('#program-level-output').val('');
        //                 $('#level-output').val('');
        //                 $('#level-acquired').html(''); // Update the date received display
        //             },
        //             error: function(xhr, status, error) {
        //                 console.error('Error:', error);
        //             }
        //         });
        //     } else {
        //         $('#program').html('<option value="">Select Program</option>');
        //         $('#program-level').html(''); // Clear the program level display
        //         $('#level').html(''); // Clear the program level display
        //         $('#level-acquired').html(''); // Clear the program level display
        //         $('#program-level-output').val('');
        //         $('#level-output').val('');
        //         $('#level-acquired').html(''); // Update the date received display
        //     }
        // }

        function fetchProgramLevel() {
            var programId = document.getElementById('program').value;
            if (programId) {
                $.ajax({
                    url: 'get_program_level.php',
                    type: 'POST',
                    data: {
                        program_id: programId
                    },
                    success: function(response) {
                        var data = JSON.parse(response);
                        var currentLevel = data.program_level.trim();
                        var dateReceived = data.date_received.trim();
                        var levelApplied = 'NA';
                        var currentLevelTextOutput = currentLevel;
                        var levelAppliedTextOutput = levelApplied;

                        if (currentLevel === 'Not Accreditable' || currentLevel === 'No Graduates Yet') {
                            currentLevelTextOutput = 'NA';
                            levelApplied = 'Candidate';
                            levelAppliedTextOutput = 'CAN';
                        } else if (currentLevel === 'Candidate') {
                            currentLevelTextOutput = 'CAN';
                            levelApplied = 'PSV';
                            levelAppliedTextOutput = levelApplied;
                        } else if (currentLevel === 'PSV') {
                            levelApplied = 1;
                            levelAppliedTextOutput = levelApplied;
                        } else if (currentLevel < 4) {
                            levelApplied = parseInt(currentLevel) + 1;
                            levelAppliedTextOutput = levelApplied;
                        } else {
                            levelApplied = currentLevel;
                            levelAppliedTextOutput = levelApplied;
                        }

                        // Update the readonly level input field
                        $('#program-level').val(currentLevel);
                        $('#level').val(levelApplied);

                        $('#program-level-output').val(currentLevelTextOutput);
                        $('#level-output').val(levelAppliedTextOutput);

                        $('#year_validity').val(3);
                        if (dateReceived !== 'N/A' && currentLevelTextOutput !== 'NA') {
                            $('#level-acquired').html('AQUIRED IN ' + dateReceived); // Update the date received display
                        }
                    }
                });
            } else {
                // Clear both program level display and level dropdown
                $('#program-level').val(''); // Clear the program level display
                $('#level').val(''); // Clear the program level display
                $('#level-acquired').html(''); // Clear the date received display
                $('#program-level-output').val('');
                $('#level-output').val('');
                $('#level-acquired').html(''); // Update the date received display
            }
        }

        function fetchTeamLeadersAndMembers() {
            var collegeId = document.getElementById('college').value;

            if (collegeId) {
                $.ajax({
                    url: 'get_team.php',
                    type: 'POST',
                    data: {
                        college_id: collegeId
                    },
                    success: function(response) {
                        console.log('Response from get_team.php:', response); // Debugging log
                        try {
                            const data = JSON.parse(response);
                            console.log('Parsed data:', data); // Debugging log
                            membersCount = data.teamMembers.length;
                            populateDropdown('#team-leader', data.teamLeaders);
                            populateAllTeamMemberDropdowns(data.teamMembers);
                            updateDropdowns();
                            checkAvailableLeadersAndMembers(); // Check after populating
                        } catch (e) {
                            console.error('Error parsing JSON response:', e);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', error);
                    }
                });
            } else {
                $('#team-leader').html('<option value="">Select Team Leader</option>');
                $('.team-member-input select').html('<option value="">Select Team Member</option>');
                checkAvailableLeadersAndMembers(); // Check after populating
            }
        }

        function fetchTeamMembersForNewDropdown(dropdown) {
            var collegeId = document.getElementById('college').value;
            if (collegeId) {
                $.ajax({
                    url: 'get_team.php',
                    type: 'POST',
                    data: {
                        college_id: collegeId
                    },
                    success: function(response) {
                        const data = JSON.parse(response);
                        populateDropdown(dropdown, data.teamMembers);
                        updateDropdowns();
                        checkAvailableLeadersAndMembers(); // Check after populating
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', error);
                    }
                });
            }
        }

        function populateDropdown(selector, options) {
            const dropdown = $(selector);
            dropdown.empty();
            dropdown.append($('<option>').text('Select').attr('value', ''));
            options.forEach(function(option) {
                const displayText = `${option.name} (${option.count} Schedules)`; // Combine name and count
                dropdown.append($('<option>').text(displayText).attr('value', option.id));
            });
        }

        function populateAllTeamMemberDropdowns(options) {
            $('.team-member-input select').each(function() {
                populateDropdown(this, options);
            });
        }

        function updateDropdowns() {
            const selectedTeamLeader = document.getElementById('team-leader').value;
            const selectedTeamMembers = [];

            // Collect all selected team members
            document.querySelectorAll('.team-member-select').forEach(function(select) {
                selectedTeamMembers.push(select.value);
            });

            // Enable all options first (reset any previous disables)
            document.querySelectorAll('#team-leader option, .team-member-select option').forEach(function(option) {
                option.disabled = false;
            });

            // Disable the selected team leader in all member dropdowns
            if (selectedTeamLeader) {
                document.querySelectorAll('.team-member-select option[value="' + selectedTeamLeader + '"]').forEach(function(option) {
                    option.disabled = true;
                });
            }

            // Disable the selected team members in the leader dropdown and other team member dropdowns
            selectedTeamMembers.forEach(function(member) {
                if (member) {
                    document.querySelectorAll('#team-leader option[value="' + member + '"], .team-member-select option[value="' + member + '"]').forEach(function(option) {
                        if (!option.selected) {
                            option.disabled = true;
                        }
                    });
                }
            });

            // After updating dropdowns, check if the "ADD MEMBER" button should be enabled or disabled
            checkAvailableLeadersAndMembers();

            updateMemberCount();
        }

        function updateMemberCount() {
            // Get all select elements
            const selects = document.querySelectorAll('.team-member-select');
            let availableMembersCount = 0;

            // Count non-disabled options for each select element
            selects.forEach(function(select) {
                availableMembersCount += 1;
            });

            // Display the count in the #member-count span
            var content = availableMembersCount > 1 ? "TEAM MEMBER/S: " + availableMembersCount + " Members" : "TEAM MEMBER/S: " + availableMembersCount + " Member";
            document.getElementById('member-count').textContent = content;
        }

        function checkScheduleDate(callback) {
            // For modal validation (when adding/editing a program)
            const modalDate = document.getElementById('date-temp');
            const selectedCollege = document.getElementById('college');

            if (modalDate && modalDate.value && selectedCollege.value) {
                // Check the date in the modal
                const scheduleToCheck = [{
                    date: modalDate.value,
                    college: selectedCollege.options[selectedCollege.selectedIndex].text,
                    index: 0
                }];

                // Also include existing programs' dates (excluding the one being edited if any)
                programsData.forEach((program, index) => {
                    scheduleToCheck.push({
                        date: program.date,
                        college: selectedCollege.options[selectedCollege.selectedIndex].text,
                        index: index + 1
                    });
                });

                // Send all schedules to be checked at once
                $.ajax({
                    url: 'check_schedule.php',
                    type: 'POST',
                    data: {
                        schedules: scheduleToCheck
                    },
                    success: function(response) {
                        try {
                            const data = JSON.parse(response);

                            if (data.error) {
                                showErrorMessage("Error: " + data.error);
                                if (callback) callback(false);
                                return;
                            }

                            const conflicts = data.conflicts.filter(conflict =>
                                conflict.status === 'exists' || conflict.status === 'error'
                            );

                            if (conflicts.length > 0) {
                                // Format and display all conflict messages
                                const errorMessages = conflicts.map(conflict =>
                                    `\nProgram ${conflict.index === 0 ? '(Current)' : conflict.index}: ${conflict.message}`
                                ).join('\n');

                                showErrorMessage(errorMessages);
                                if (callback) callback(false);
                            } else {
                                hideErrorMessage();
                                if (callback) callback(true);
                            }
                        } catch (e) {
                            console.error('Error parsing response:', e);
                            showErrorMessage('Error processing server response');
                            if (callback) callback(false);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error checking schedule:', error);
                        showErrorMessage('Error checking schedule availability');
                        if (callback) callback(false);
                    }
                });
            } else {
                // When validating the entire form (for submission)
                if (programsData.length === 0) {
                    if (callback) callback(true);
                    return;
                }

                const schedulesToCheck = programsData.map((program, index) => ({
                    date: program.date,
                    college: selectedCollege.options[selectedCollege.selectedIndex].text,
                    index: index + 1
                }));

                $.ajax({
                    url: 'check_schedule.php',
                    type: 'POST',
                    data: {
                        schedules: schedulesToCheck
                    },
                    success: function(response) {
                        try {
                            const data = JSON.parse(response);

                            if (data.error) {
                                showErrorMessage("Error: " + data.error);
                                if (callback) callback(false);
                                return;
                            }

                            const conflicts = data.conflicts.filter(conflict =>
                                conflict.status === 'exists' || conflict.status === 'error'
                            );

                            if (conflicts.length > 0) {
                                // Format and display all conflict messages
                                const errorMessages = conflicts.map(conflict =>
                                    `\nProgram ${conflict.index}: ${conflict.message}`
                                ).join('\n');

                                showErrorMessage(errorMessages);
                                if (callback) callback(false);
                            } else {
                                hideErrorMessage();
                                if (callback) callback(true);
                            }
                        } catch (e) {
                            console.error('Error parsing response:', e);
                            showErrorMessage('Error processing server response');
                            if (callback) callback(false);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error checking schedule:', error);
                        showErrorMessage('Error checking schedule availability');
                        if (callback) callback(false);
                    }
                });
            }
        }

        function showErrorMessage(message) {
            const errorPopup = document.getElementById('errorPopup');
            const errorContent = errorPopup.querySelector('.popup-text');
            errorContent.textContent = message;
            errorPopup.style.display = 'block';
        }

        function hideErrorMessage() {
            const errorPopup = document.getElementById('errorPopup');
            if (errorPopup) {
                errorPopup.style.display = 'none';
            }
        }

        // Event listener for date changes on any program block
        document.getElementById('programModal').addEventListener('change', function(event) {
            if (event.target.matches('input[type="date"]') ||
                event.target.matches('select[name="program[]"]')) {
                checkScheduleDate();
            }
        });

        // Event listener for form submission
        document.getElementById('schedule-form').addEventListener('submit', function(event) {
            event.preventDefault();

            const loadingSpinner = document.getElementById('loadingSpinner');
            loadingSpinner.classList.remove('spinner-hidden');
            document.getElementById('schedule-form').submit();

        });

        // Optional: Add this helper function to clear error messages when adding/removing program blocks
        function clearScheduleErrors() {
            const errorPopup = document.getElementById('errorPopup');
            if (errorPopup) {
                errorPopup.style.display = 'none';
                const errorContent = errorPopup.querySelector('.error-content');
                if (errorContent) {
                    errorContent.textContent = '';
                }
            }
        }

        // Event listener for closing the error popup
        document.getElementById('closeErrorPopup').addEventListener('click', function() {
            document.getElementById('errorPopup').style.display = 'none';
        });

        // Close the error popup if the user clicks outside of it
        window.addEventListener('click', function(event) {
            if (event.target == document.getElementById('errorPopup')) {
                document.getElementById('errorPopup').style.display = 'none';
            }
        });

        function openDatePicker(id) {
            document.getElementById(id).showPicker();
        }

        // document.getElementById('year_validity').addEventListener('input', function(e) {
        //     let middleinitialInput = e.target.value;

        //     // Remove any non-numeric characters
        //     middleinitialInput = middleinitialInput.replace(/[^0-9]/g, '');

        //     // Limit to 1 character
        //     if (middleinitialInput.length > 1) {
        //         middleinitialInput = middleinitialInput.slice(0, 1);
        //     }

        //     // Set the cleaned value back to the input
        //     e.target.value = middleinitialInput;
        // });

        function checkAvailableLeadersAndMembers() {
            const teamMemberSelects = document.querySelectorAll('.team-member-select');

            const addButton = document.querySelector('.add-team-member-button');

            // Disable the "ADD MEMBER" button if no leaders or members are available
            if (membersCount <= (teamMemberSelects.length + 1)) {
                addButton.disabled = true;
            } else {
                addButton.disabled = false;
            }
        }

        // Event listeners to trigger the updateDropdowns function
        document.getElementById('team-leader').addEventListener('change', updateDropdowns);
        document.querySelectorAll('.team-member-select').forEach(function(select) {
            select.addEventListener('change', updateDropdowns);
        });

        // Call updateDropdowns to initialize the state
        updateDropdowns();
    </script>
</body>

</html>