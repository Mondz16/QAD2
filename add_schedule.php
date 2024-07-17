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
        body {
            padding: 20px;
        }
        .form-container {
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
        select, input[type="date"], input[type="time"] {
            width: calc(100% - 10px);
            padding: 8px;
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
        .add-team-member-button {
            background-color: #007bff;
            color: #fff;
            border: none;
            padding: 8px 12px;
            cursor: pointer;
            border-radius: 4px;
        }
        .add-team-member-button:hover {
            background-color: #0056b3;
        }
        button[type="submit"], button {
            background-color: #007bff;
            color: #fff;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
            margin-right: 10px;
            text-align: center;
        }
        button[type="submit"]:hover, button:hover {
            background-color: #0056b3;
        }
        button[type="button"] {
            background-color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Add Schedule</h2>
        <form action="add_schedule_process.php" method="POST" id="schedule-form">
            <div class="form-group">
                <label for="college">College:</label>
                <select id="college" name="college" onchange="fetchPrograms(); fetchUsers();" required class="select2">
                    <option value="">Select College</option>
                    <?php
                    include 'connection.php';

                    $sql = "SELECT id, college_name FROM college ORDER BY college_name";
                    $result = $conn->query($sql);

                    while ($row = $result->fetch_assoc()) {
                        echo "<option value='{$row['id']}'>{$row['college_name']}</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label for="program">Program:</label>
                <select id="program" name="program" onchange="fetchProgramLevel()" required class="select2">
                    <option value="">Select Program</option>
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
                <select id="team-leader" name="team_leader" required class="select2 team-leader-select">
                    <option value="">Select Team Leader</option>
                </select>
            </div>
            <div class="form-group" id="team-members-container">
                <label for="team-members">Team Members:</label>
                <div class="team-member-input">
                    <select name="team_members[]" required class="select2 team-member-select">
                        <option value="">Select Team Member</option>
                    </select>
                    <button type="button" class="add-team-member-button" onclick="addTeamMemberInput()">Add</button>
                </div>
            </div>
            <div class="form-group">
                <button type="submit">Submit</button>
            </div>
        </form>
        <button onclick="location.href='schedule.php'">Back</button>
    </div>

    <!-- Include jQuery -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <!-- Include Select2 JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/js/select2.min.js"></script>
    <script>
    $(document).ready(function() {
        // Initialize Select2 on team leader and team members dropdowns
        $('.select2').select2();

        // Event listener for team leader selection
        $('#team-leader').on('change', function() {
            updateDropdowns();
        });

        // Event listener for team member selection
        $(document).on('change', '.team-member-select', function() {
            updateDropdowns();
        });
    });

    function addTeamMemberInput() {
        const container = document.getElementById('team-members-container');
        const newInputDiv = document.createElement('div');
        newInputDiv.className = 'team-member-input';

        newInputDiv.innerHTML = `
            <select name="team_members[]" required class="select2 team-member-select">
                <option value="">Select Team Member</option>
            </select>
            <button type="button" class="add-team-member-button" onclick="removeTeamMemberInput(this)">Remove</button>
        `;
        container.appendChild(newInputDiv);

        // Initialize Select2 on the newly added dropdown
        $('.select2').select2();

        // Update dropdowns to reflect current selections
        updateDropdowns();
    }

    function removeTeamMemberInput(button) {
        button.parentElement.remove();
        updateDropdowns();
    }

    function updateDropdowns() {
        const selectedTeamLeader = $('#team-leader').val();
        const selectedTeamMembers = [];

        $('.team-member-select').each(function() {
            selectedTeamMembers.push($(this).val());
        });

        // Reset all options
        $('.team-leader-select option, .team-member-select option').prop('disabled', false);

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

        // Refresh Select2 dropdowns to reflect changes
        $('.select2').select2();
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
                }
            });
        } else {
            $('#program').html('<option value="">Select Program</option>');
            $('#program-level').html(''); // Clear the program level display
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

    function fetchUsers() {
        var collegeId = $('#college').val();
        if (collegeId) {
            $.ajax({
                url: 'get_users.php',
                type: 'POST',
                data: {college_id: collegeId},
                success: function(response) {
                    var users = JSON.parse(response);

                    // Update team leader dropdown
                    var teamLeaderDropdown = $('#team-leader');
                    teamLeaderDropdown.html('<option value="">Select Team Leader</option>');
                    users.forEach(function(user) {
                        teamLeaderDropdown.append('<option value="' + user.user_id + '">' + user.first_name + ' ' + user.middle_initial + ' ' + user.last_name + '</option>');
                    });

                    // Update team members dropdowns
                    $('.team-member-select').each(function() {
                        var teamMemberDropdown = $(this);
                        teamMemberDropdown.html('<option value="">Select Team Member</option>');
                        users.forEach(function(user) {
                            teamMemberDropdown.append('<option value="' + user.user_id + '">' + user.first_name + ' ' + user.middle_initial + ' ' + user.last_name + '</option>');
                        });
                    });

                    // Refresh Select2 dropdowns
                    $('.select2').select2();

                    // Update the dropdowns to disable selected options
                    updateDropdowns();
                }
            });
        } else {
            // Clear dropdowns if no college is selected
            $('#team-leader').html('<option value="">Select Team Leader</option>');
            $('.team-member-select').html('<option value="">Select Team Member</option>');
        }
    }
    </script>
</body>
</html>
