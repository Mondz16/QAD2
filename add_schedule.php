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
            <a class="btn-back" href="schedule.php">&lt; BACK</a>
            <h2 class="mt-4 mb-4">ADD SCHEDULE</h2>
        </div>
    </div>
    <div class="container2">
        <div class="form-container">
            <form action="add_schedule_process.php" method="POST" id="schedule-form">
                <div class="form-group">
                    <label for="college">COLLEGE:</label>
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
                    <label for="program">PROGRAM:</label>
                    <select id="program" name="program" onchange="fetchProgramLevel()" required class="select2">
                        <option value="">Select Program</option>
                        <!-- Options will be dynamically populated based on college selection -->
                    </select>
                </div>
                <div>
                    <div class="level-header">
                        <label for="level">CURRENT LEVEL:</label>
                        <label class="level-applied" for="level">LEVEL APPLIED:</label>
                        <label for="level_validity">Years of Validity:</label>
                    </div>
                    <div class="level-input-holder">
                        <input class="level-input" type="text" id="program-level" name="level" readonly>
                        <div class="level-holder">
                            <span id="level-acquired"></span>
                        </div>
                        <input class="level-input highlight" type="text" id="level" name="level" readonly>
                        <input class="level-input" type="text" id="year_validity" name="level_validity" required>
                    </div>
                </div>
                <div class="dateTime-holder">
                    <div class="form-group">
                        <label for="date">Date:</label>
                        <input type="date" id="date" name="date" required>
                    </div>
                    <div class="form-group">
                        <label for="time">Time:</label>
                        <input type="time" id="time" name="time" required>
                    </div>
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
                        </div>
                    </div>
                    <button type="button" class="add-team-member-button" onclick="addTeamMemberInput()">ADD MEMBER</button>
                </div>
                <div class="bottom-button-holder">
                    <button type="button" class="discard-button" onclick="window.location.href='schedule.php'">DISCARD</button>
                    <button type="submit" class="submit-button">SUBMIT</button>
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
            <button type="button" class="remove-team-member-button" onclick="removeTeamMemberInput(this)"><svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="currentColor" class="bi bi-x-circle" viewBox="0 0 16 16">
  <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
  <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708"/>
</svg></button>
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
                    data: {
                        college_id: collegeId
                    },
                    success: function(response) {
                        $('#program').html(response);
                        $('#program-level').html(''); // Clear the program level display
                        $('#level').html(''); // Clear the program level display
                        $('#level-acquired').html(''); // Clear the program level display
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

            }
        }

        function fetchScheduledPrograms(collegeId) {
            $.ajax({
                url: 'get_scheduled_programs.php',
                type: 'POST',
                data: {
                    college_id: collegeId
                },
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
                    data: {
                        program_id: programId
                    },
                    success: function(response) {
                        var data = JSON.parse(response);
                        var currentLevel = data.program_level.trim();
                        var dateReceived = data.date_received.trim();
                        var levelApplied = 'N/A';

                        if (currentLevel === 'Not Accreditable') {
                            levelApplied = 'Candidate';
                        } else if (currentLevel === 'Candidate') {
                            levelApplied = 'PSV';
                        } else if (currentLevel === 'PSV') {
                            levelApplied = 1;
                        } else if (currentLevel < 4) {
                            levelApplied = parseInt(currentLevel) + 1;
                        } else {
                            levelApplied = currentLevel;
                        }

                        // Update the readonly level input field
                        $('#program-level').val(currentLevel);
                        $('#level').val(levelApplied);
                        $('#year_validity').val(3);
                        if (dateReceived !== 'N/A')
                            $('#level-acquired').html('AQUIRED IN ' + dateReceived); // Update the date received display
                    }
                });
            } else {
                // Clear both program level display and level dropdown
                $('#program-level').val(''); // Clear the program level display
                $('#level').val(''); // Clear the program level display
                $('#level-acquired').html(''); // Clear the date received display
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
                            populateDropdown('#team-leader', data.teamLeaders);
                            populateAllTeamMemberDropdowns(data.teamMembers);
                            updateDropdowns();
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