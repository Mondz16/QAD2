<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Schedule</title>
    <!-- Include Select2 CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/css/select2.min.css" rel="stylesheet" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Quicksand", sans-serif;
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
            font-size: 14px;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin-left: -240px;
            transition: background-color 0.3s ease;
        }

        .headerRight .btn:hover {
            background-color: #b82c3b;
        }

        .container2 {
            display: flex;
            justify-content: center;
        }

        .form-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            padding: 20px 30px;
            max-width: 700px;
            width: 100%;
            margin-top: 40px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            margin-top: 10px;
            display: block;
            font-weight: 500;
            color: #333;
        }

        select,
        input[type="date"],
        input[type="time"],
        input[type="text"],
        button {
            width: calc(100% - 10px);
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
        }

        .team-member-input {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        .team-member-input select {
            flex: 1;
            margin-right: 10px;
        }

        button[type="submit"],
        .action-btn {
            background-color: #2cb84f;
            color: #fff;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        button[type="submit"]:hover,
        .action-btn:hover {
            background-color: #259b42;
        }

        button[type="button"] {
            background-color: #6c757d;
        }

        button[type="button"]:hover {
            background-color: #5a6268;
        }

        .add-team-member-button {
            background-color: #28a745;
            color: #fff;
            border: none;
            padding: 8px 10px;
            cursor: pointer;
            border-radius: 4px;
            width: 100px;
            margin-right: 10px;
        }

        .add-team-member-button:hover {
            background-color: #218838;
        }

        .cancel {
            background-color: #dc3545;
            color: #fff;
        }

        .cancel:hover {
            background-color: #c82333;
        }

        .btn-back {
            background-color: #ffc107;
            color: #fff;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            margin-bottom: 20px;
        }

        .btn-back:hover {
            background-color: #e0a800;
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
        <div style="height: 1px; width: 100%; background: #E5E5E5"></div>
    </div>
    <div class="container2">
        <div class="form-container">
            <div class="pageHeader">
                <div class="headerRight">
                    <a class="btn" href="schedule.php">Back</a>
                </div>
                <h2>Add Schedule</h2>
            </div>
            <form action="add_schedule_process.php" method="POST" id="schedule-form">
                <div class="form-group">
                    <label for="college">College:</label>
                    <select id="college" name="college" onchange="fetchPrograms(); fetchTeamLeadersAndMembers();" required class="select2">
                        <option value="">Select College</option>
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
                    <label for="program">Program:</label>
                    <select id="program" name="program" onchange="fetchProgramLevel()" required class="select2">
                        <option value="">Select Program</option>
                        <!-- Options will be dynamically populated based on college selection -->
                    </select>
                    <span id="program-level"></span>
                </div>
                <div class="form-group">
                    <label for="level">Level Applied:</label>
                    <select id="level" name="level" required>
                        <option value="">Select Level</option>
                        <!-- Options will be dynamically populated based on program selection -->
                    </select>
                </div>
                <div class="form-group">
                    <label for="date">Date:</label>
                    <input type="date" id="date" name="date" required>
                </div>
                <div class="form-group">
                    <label for="time">Time:</label>
                    <input type="time" id="time" name="time" required>
                </div>
                <div class="form-group">
                    <label for="team-leader">Team Leader:</label>
                    <select id="team-leader" name="team_leader" required onchange="updateDropdowns()">
                        <option value="">Select Team Leader</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="team-members">Team Members:</label>
                    <div id="team-members-container">
                        <div class="team-member-input">
                            <select name="team_members[]" class="team-member-select" required onchange="updateDropdowns()">
                                <option value="">Select Team Member</option>
                            </select>
                            <button type="button" class="add-team-member-button" onclick="addTeamMemberInput()">Add</button>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <button type="submit">Submit</button>
                </div>
            </form>
        </div>

    </div>

    <!-- Include jQuery -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
    function addTeamMemberInput() {
        const container = document.getElementById('team-members-container');
        const newInputDiv = document.createElement('div');
        newInputDiv.className = 'team-member-input';

        newInputDiv.innerHTML = `
            <select name="team_members[]" class="team-member-select" required onchange="updateDropdowns()">
                <option value="">Select Team Member</option>
            </select>
            <button type="button" class="add-team-member-button" onclick="removeTeamMemberInput(this)">Remove</button>
        `;
        container.appendChild(newInputDiv);

        // Fetch team members for the new dropdown
        fetchTeamMembersForNewDropdown(newInputDiv.querySelector('.team-member-select'));
    }

    function removeTeamMemberInput(button) {
        button.parentElement.remove();
        updateDropdowns();
    }

    function fetchPrograms() {
        var collegeId = document.getElementById('college').value;
        if (collegeId) {
            $.ajax({
                url: 'get_programs.php',
                type: 'POST',
                data: {college_id: collegeId},
                success: function(response) {
                    $('#program').html(response);
                    $('#program-level').html(''); // Clear the program level display
                    fetchScheduledPrograms(collegeId); // Fetch scheduled programs after populating
                }
            });
        } else {
            $('#program').html('<option value="">Select Program</option>');
            $('#program-level').html(''); // Clear the program level display
        }
    }

    function fetchScheduledPrograms(collegeId) {
        $.ajax({
            url: 'get_scheduled_programs.php',
            type: 'POST',
            data: {college_id: collegeId},
            success: function(response) {
                const scheduledPrograms = JSON.parse(response);
                disableScheduledPrograms(scheduledPrograms);
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
            }
        });
    }

    function disableScheduledPrograms(scheduledPrograms) {
        $('#program option').each(function() {
            const programId = $(this).val();
            if (programId !== '') {
                const found = scheduledPrograms.find(program => program.id === programId);
                if (found) {
                    $(this).remove(); // Remove the option from the dropdown
                }
            }
        });

        // If all options are disabled, add a default placeholder
        if ($('#program option').length === 0) {
            $('#program').html('<option value="">No Programs Available</option>');
        }
    }

    function fetchProgramLevel() {
        var programId = document.getElementById('program').value;
        if (programId) {
            $.ajax({
                url: 'get_program_level.php',
                type: 'POST',
                data: {program_id: programId},
                success: function(response) {
                    var currentLevel = parseInt(response.trim());

                    // Display current level beside the program
                    $('#program-level').html('Current Level: ' + currentLevel);

                    // Populate the level dropdown with options
                    $('#level').html('<option value="">Select Level</option>');
                    for (var i = 1; i <= 4; i++) {
                        if (i > currentLevel) {
                            $('#level').append('<option value="' + i + '">' + i + '</option>');
                        }
                    }
                }
            });
        } else {
            // Clear both program level display and level dropdown
            $('#program-level').html('');
            $('#level').html('<option value="">Select Level</option>');
        }
    }

    function fetchTeamLeadersAndMembers() {
        var collegeId = document.getElementById('college').value;

        if (collegeId) {
            $.ajax({
                url: 'get_team.php',
                type: 'POST',
                data: { college_id: collegeId },
                success: function(response) {
                    const data = JSON.parse(response);
                    populateDropdown('#team-leader', data.teamLeaders);
                    populateAllTeamMemberDropdowns(data.teamMembers);
                    updateDropdowns();
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                }
            });
        } else {
            $('#team-leader').html('<option value="">Select Team Leader</option>');
            $('.team-member-input select').html('<option value="">Select Team Member</option>');
        }
    }

    function fetchTeamMembersForNewDropdown(dropdown) {
        var collegeId = document.getElementById('college').value;
        if (collegeId) {
            $.ajax({
                url: 'get_team.php',
                type: 'POST',
                data: { college_id: collegeId },
                success: function(response) {
                    const data = JSON.parse(response);
                    populateDropdown(dropdown, data.teamMembers);
                    updateDropdowns();
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
            dropdown.append($('<option>').text(option.name).attr('value', option.id));
        });
    }

    function populateAllTeamMemberDropdowns(options) {
        $('.team-member-input select').each(function() {
            populateDropdown(this, options);
        });
    }

    function updateDropdowns() {
        const selectedTeamLeader = $('#team-leader').val();
        const selectedTeamMembers = [];

        $('.team-member-select').each(function() {
            selectedTeamMembers.push($(this).val());
        });

        // Reset all options
        $('#team-leader option, .team-member-select option').prop('disabled', false);

        // Disable selected team leader in team members dropdowns
        if (selectedTeamLeader) {
            $('.team-member-select option[value="' + selectedTeamLeader + '"]').prop('disabled', true);
        }

        // Disable selected team members in team leader and other team member dropdowns
        selectedTeamMembers.forEach(function(member) {
            if (member) {
                $('#team-leader option[value="' + member + '"]').prop('disabled', true);
                $('.team-member-select option[value="' + member + '"]').not(':selected').prop('disabled', true);
            }
        });
    }

    // Fetch initial team leaders and members on page load
    $(document).ready(function() {
        fetchTeamLeadersAndMembers();
    });
    </script>
</body>

</html>