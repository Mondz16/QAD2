<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add College and Programs</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600&display=swap">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
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

        .container2 {
            max-width: 750px;
            padding-left: 24px;
            padding-right: 24px;
            width: 100%;
            display: block;
            box-sizing: border-box;
            margin-left: auto;
            margin-right: auto;
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            margin: 0 0 10px;
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

        .form-container {
            background-color: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
        }

        h2 {
            font-size: 24px;
            color: #973939;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            margin-bottom: 8px;
            margin-top: 10px;
            display: block;
            font-weight: 500;
        }

        label {
            display: block;
            margin-bottom: 5px;
            color: #333;
        }

        .headerRight .btn {
            background-color: #dc3545;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin-left: -175px;
            font-size: 14px;
            transition: background-color 0.3s ease;
        }

        .headerRight .btn:hover {
            background-color: #b82c3b;
        }

        .pageHeader {
            display: flex;
            max-width: 900px;
            align-items: center;
            justify-content: center;
            margin-bottom: 30px;
            margin-top: 20px;
        }

        input[type="text"],
        input[type="email"],
        input[type="date"],
        select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
        }

        .button {
            display: inline-block;
            padding: 10px 20px;
            font-size: 14px;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            border-radius: 4px;
            margin: 5px 0;
        }

        .button-primary {
            background-color: #2cb84f;
            color: #fff;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 4px;
            margin-top: 10px;
            color: white;
        }

        .button-primary:hover {
            background-color: #259b42;
        }

        .button-secondary {
            background-color: #6c757d;
            color: white;
        }

        .add-program-button,
        .remove-program-button {
            background-color: #888;
            color: #fff;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 4px;
            margin-top: 10px;
            font-size: 14px;
        }

        .add-program-button:hover,
        .remove-program-button:hover {
            background-color: #dc3545;
        }

        .programs {
            border: 1px solid #ccc;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 10px;
            position: relative;
        }

        .programs label {
            margin-bottom: 8px;
            margin-top: 10px;
            display: block;
            font-weight: 500;
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
        <div style="height: 1px; width: 100%; background: #E5E5E5"></div>
    </div>

    <div class="container2">
        <div class="form-container">
            <div class="pageHeader">
                <div class="headerRight">
                    <a class="btn" href="college.php">Back</a>
                </div>
                <h2>Add College and Programs</h2>
            </div>
            <form action="add_college_process.php" method="post">
                <div class="form-group">
                    <label for="college_name">College Name:</label>
                    <input type="text" id="college_name" name="college_name" required>
                </div>
                <div class="form-group">
                    <label for="college_campus">College Campus:</label>
                    <select id="college_campus" name="college_campus" required>
                        <option value="Main">Main</option>
                        <option value="Mintal">Mintal</option>
                        <option value="Obrero">Obrero</option>
                        <option value="Mabini">Mabini</option>
                        <option value="Tagum">Tagum</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="college_email">College Email:</label>
                    <input type="email" id="college_email" name="college_email" required>
                </div>
                <div id="programs">
                    <!-- Program entries will be appended here -->
                </div>
                <button type="submit" class="button button-primary">Submit</button>
                <button type="button" class="add-program-button" onclick="showAddProgramModal()">Add Program</button>
                <button type="button" id="remove-program-button" class="remove-program-button" onclick="showRemoveProgramModal()">Remove Program</button>
            </form>
        </div>
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
                        <button type="button" class="button button-primary" onclick="addProgram()">Add Program</button>
                        <button type="button" class="add-program-button" data-dismiss="modal"">Cancel</button>
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
                        <button type="button" class="button button-primary" onclick="removeSelectedPrograms()">Remove Selected Programs</button>
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
        programCount++;
        const program = document.getElementById('modal_program').value;
        const level = document.getElementById('modal_level').value;
        const dateReceived = document.getElementById('modal_date_received').value;

        const programsDiv = document.getElementById('programs');

        const newProgramDiv = document.createElement('div');
        newProgramDiv.classList.add('programs');
        newProgramDiv.dataset.index = programCount; // Use data-index to identify program

        newProgramDiv.innerHTML = `
            <label for="program_${programCount}">Program:</label>
            <input type="text" id="program_${programCount}" name="programs[]" value="${program}" readonly>
            <label for="level_${programCount}">Level:</label>
            <input type="text" id="level_${programCount}" name="levels[]" value="${level}" readonly>
            <label for="date_received_${programCount}">Date Received:</label>
            <input type="date" id="date_received_${programCount}" name="dates_received[]" value="${dateReceived}" readonly>
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
                <input type="checkbox" id="remove_program_${programDiv.dataset.index}" name="remove_programs[]" value="${programDiv.dataset.index}">
                <label for="remove_program_${programDiv.dataset.index}">${program} - ${level} - ${dateReceived}</label>
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