<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add College and Programs</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600&display=swap">
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
        input[type="date"] {
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

        .add-program-button {
            background-color: #888;
            color: #fff;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 4px;
            margin-top: 10px;
            font-size: 14px;
        }

        .add-program-button:hover {
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
        
        .remove-program-button {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
            margin-top: 10px;
        }
        .remove-program-button {
            background-color: #dc3545;
        }

        .remove-program-button:hover {
            background-color: #c82333;
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
                        <option value="Obrero">Obrero</option>
<<<<<<< Updated upstream
                        <option value="Mintal">Mintal</option>
=======
                        <option value="Mabini">Mintal</option>
>>>>>>> Stashed changes
                        <option value="Tagum">Tagum</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="college_email">College Email:</label>
                    <input type="email" id="college_email" name="college_email" required>
                </div>
                <div id="programs">
                    <div class="programs">
                        <label for="program_1">Program:</label>
                        <input type="text" id="program_1" name="programs[]" required>
                        <label for="level_1">Level:</label>
                        <input type="text" id="level_1" name="levels[]" required>
                        <label for="date_received_1">Date Received:</label>
                        <input type="date" id="date_received_1" name="dates_received[]" required>
                        <button type="button" class="remove-program-button" onclick="removeProgram(this)">Remove Program</button>
                    </div>
                </div>
                <button type="submit" class="button button-primary">Submit</button>
                <button type="button" class="add-program-button" onclick="addProgram()">Add Program</button>
            </form>
        </div>
    </div>

    <script>
        let programCount = 1;

        function addProgram() {
            programCount++;
            const programsDiv = document.getElementById('programs');

            const newProgramDiv = document.createElement('div');
            newProgramDiv.classList.add('programs');
            newProgramDiv.innerHTML = `
                <label for="program_${programCount}">Program:</label>
                <input type="text" id="program_${programCount}" name="programs[]" required>
                <label for="level_${programCount}">Level:</label>
                <input type="text" id="level_${programCount}" name="levels[]" required>
                <label for="date_received_${programCount}">Date Received:</label>
                <input type="date" id="date_received_${programCount}" name="dates_received[]" required>
                <button type="button" class="remove-program-button" onclick="removeProgram(this)">Remove Program</button>
            `;

            programsDiv.appendChild(newProgramDiv);
        }

        function removeProgram(button) {
            const programDiv = button.parentElement;
            programDiv.remove();
        }
    </script>
</body>

</html>
