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
            margin: 20px 50px;
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
            border-color: #B73033 !important; /* Enforce the custom color */
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

        input[type="date"], input[type="time"] {
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
                <div class="form-group">
                    <label for="program">PROGRAM:</label>
                    <select id="program" name="program" onchange="fetchProgramLevel()" required class="select2" style="cursor: pointer;">
                        <option value="" disabled selected hidden>Select Program</option>
                        <!-- Options will be dynamically populated based on college selection -->
                    </select>
                </div>
                <div>
                    <div class="level-header">
                        <label for="level">CURRENT LEVEL:</label>
                        <label class="level-applied" for="level">LEVEL APPLIED:</label>
                        <label for="level_validity">YEARS OF VALIDITY:</label>
                    </div>
                    <div class="level-input-holder">
                        <input class="level-input" type="hidden" id="program-level" name="level" readonly>
                        <input class="level-input" type="text" id="program-level-output" name="level-output" readonly>
                        <div class="level-holder">
                            <span id="level-acquired"></span>
                        </div>
                        <input class="level-input highlight" type="hidden" id="level" name="level" readonly>
                        <input class="level-input highlight" type="text" id="level-output" name="level-output" readonly>
                        <input class="level-input-validity" type="text" id="year_validity" name="level_validity" style="width:" required>
                    </div>
                </div>
                <div class="dateTime-holder">
                    <div class="form-group">
                        <label for="date">DATE:</label>
                        <input type="date" id="date" name="date" required style="cursor: pointer;" onchange="checkScheduleDate()" onclick="openDatePicker('date')">
                    </div>
                    <div class="form-group">
                        <label for="time">TIME:</label>
                        <input type="time" id="time" name="time" required style="cursor: pointer;" onchange="checkScheduleDate()" onclick="openDatePicker('time')">
                    </div>
                    <div class="form-group">
                        <label for="zoom">ZOOM:</label>
                        <input type="text" id="zoom" name="zoom" placeholder="OPTIONAL">
                    </div>
                </div>
                <div class="form-group">
                    <label for="team-leader">TEAM LEADER:</label>
                    <select id="team-leader" name="team_leader" required onchange="updateDropdowns()" style="cursor: pointer;">
                        <option value="">Select Team Leader</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="team-members">TEAM MEMBER/S:</label>
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
                    <button type="submit" class="submit-button">SUBMIT</button>
                </div>
            </form>
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

    function fetchPrograms() {
        var collegeId = document.getElementById('college').value;
        console.log(collegeId);
        if (collegeId) {
            $.ajax({
                url: 'get_programs.php',
                type: 'POST',
                data: {
                    college_id: collegeId
                },
                success: function(response) {
                    console.log(response);
                    $('#program').html(response);
                    $('#program-level').html(''); // Clear the program level display
                    $('#level').val(''); // Clear the program level display
                    $('#level-acquired').val(''); // Clear the program level display
                    $('#program-level-output').val('');
                    $('#level-output').val('');
                    $('#level-acquired').html(''); // Update the date received display
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                }
            });
        } else {
            $('#program').html('<option value="">Select Program</option>');
            $('#program-level').html(''); // Clear the program level display
            $('#level').html(''); // Clear the program level display
            $('#level-acquired').html(''); // Clear the program level display
            $('#program-level-output').val('');
            $('#level-output').val('');
            $('#level-acquired').html(''); // Update the date received display
        }
    }

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
        document.querySelectorAll('.team-member-select').forEach(function (select) {
            selectedTeamMembers.push(select.value);
        });

        // Enable all options first (reset any previous disables)
        document.querySelectorAll('#team-leader option, .team-member-select option').forEach(function (option) {
            option.disabled = false;
        });

        // Disable the selected team leader in all member dropdowns
        if (selectedTeamLeader) {
            document.querySelectorAll('.team-member-select option[value="' + selectedTeamLeader + '"]').forEach(function (option) {
                option.disabled = true;
            });
        }

        // Disable the selected team members in the leader dropdown and other team member dropdowns
        selectedTeamMembers.forEach(function (member) {
            if (member) {
                document.querySelectorAll('#team-leader option[value="' + member + '"], .team-member-select option[value="' + member + '"]').forEach(function (option) {
                    if (!option.selected) {
                        option.disabled = true;
                    }
                });
            }
        });

        // After updating dropdowns, check if the "ADD MEMBER" button should be enabled or disabled
        checkAvailableLeadersAndMembers();
    }

    // Function to check if the selected date and time have a conflict
    function checkScheduleDate(callback) {
        var date = document.getElementById('date').value;
        var time = document.getElementById('time').value; // Get time value
        var exclude_schedule_id = document.getElementById('exclude_schedule_id') ? document.getElementById('exclude_schedule_id').value : null;
        
        if (date && time) { // Ensure both date and time are selected
            $.ajax({
                url: 'check_schedule.php',
                type: 'POST',
                data: { date: date, time: time, exclude_schedule_id: exclude_schedule_id }, // Include time in the request
                success: function(response) {
                    var data = JSON.parse(response);
                    if (data.status === 'exists') {
                        // Check if the conflicting schedule status is 'approved' or 'pending'
                        if (data.schedule_status === 'approved' || data.schedule_status === 'pending') {
                            document.getElementById('errorPopup').style.display = 'block';
                            if (callback) callback(false); // Date and time conflict with status, callback with false
                        } else {
                            if (callback) callback(true); // Date conflict but status is not approved/pending, callback with true
                        }
                    } else {
                        if (callback) callback(true); // No conflict, callback with true
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                    if (callback) callback(false); // Error occurred, callback with false
                }
            });
        } else {
            if (callback) callback(true); // No date or time selected, continue with submission
        }
    }

    // Event listener for date change
    document.getElementById('date').addEventListener('change', function() {
        checkScheduleDate(); // Just check the date, no callback needed here
    });

    // Event listener for form submission
    document.getElementById('schedule-form').addEventListener('submit', function(event) {
        event.preventDefault(); // Prevent the form from submitting immediately

        // Check if the selected date is available
        checkScheduleDate(function(isDateAvailable) {
            if (isDateAvailable) {
                // If date is available, show the loading spinner and submit the form
                document.getElementById('loadingSpinner').classList.remove('spinner-hidden');
                document.getElementById('schedule-form').submit();
            } else {
                // If date is not available, ensure the loading spinner is hidden
                document.getElementById('loadingSpinner').classList.add('spinner-hidden');
            }
        });
    });
    
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

    document.getElementById('year_validity').addEventListener('input', function(e) {
        let middleinitialInput = e.target.value;

        // Remove any non-numeric characters
        middleinitialInput = middleinitialInput.replace(/[^0-9]/g, '');

        // Limit to 1 character
        if (middleinitialInput.length > 1) {
            middleinitialInput = middleinitialInput.slice(0, 1);
        }

        // Set the cleaned value back to the input
        e.target.value = middleinitialInput;
    });

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
    document.querySelectorAll('.team-member-select').forEach(function (select) {
        select.addEventListener('change', updateDropdowns);
    });

    // Call updateDropdowns to initialize the state
    updateDropdowns();
</script>
</body>
</html>
