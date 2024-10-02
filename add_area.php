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
    <title>Add Area</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="css/form_style.css">
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script> <!-- Font Awesome icons -->
</head>

<body>
    <div class="wrapper">
        <div class="hair" style="height: 15px; background: linear-gradient(275.52deg, #973939 0.28%, #DC7171 100%);"></div>
        <div class="container">
            <div class="header">
                <!-- Your existing header code -->
            </div>
        </div>
        <div style="height: 1px; width: 100%; background: #E5E5E5"></div>
        <div class="container d-flex align-items-center mt-4">
            <a class="btn-back" href="area.php">&lt; BACK</a>
            <h2 class="mt-4 mb-4">ADD AREA</h2>
        </div>
    </div>

    <div class="container2">
        <div class="form-container">
            <form action="add_area_process.php" method="post" id="areaForm">
                <div class="form-group">
                    <label for="area_name">AREA NAME:</label>
                    <input type="text" id="area_name" name="area_name" required>
                </div>
                
                <div id="parameterFields">
                    <div class="form-group parameter-group">
                        <label for="parameter_name">PARAMETER NAME:</label>
                        <div style="display: flex; align-tems: center;">
                            <input type="text" id="parameter_name" name="parameter_name[]" required>
                            <i class="fa-solid fa-circle-plus" style="color: green; font-size: 25px; cursor: pointer; padding-left: 8px; padding-top: 10px;" onclick="addParameterField()"></i>
                        </div>
                    </div>
                    <div class="form-group parameter-group">
                        <label for="parameter_description">PARAMETER DESCRIPTION:</label>
                        <input type="text" id="parameter_description" name="parameter_description[]" required>
                    </div>
                </div>

                <button type="submit" class="btn-submit">SUBMIT</button>
            </form>
        </div>
    </div>

    <script>
        function addParameterField() {
            var parameterFields = document.getElementById("parameterFields");

            // Create a new parameter name input group
            var newParameterGroup = document.createElement("div");
            newParameterGroup.classList.add("form-group", "parameter-group");

            var newParameterLabel = document.createElement("label");
            newParameterLabel.innerHTML = "PARAMETER NAME:";
            newParameterGroup.appendChild(newParameterLabel);

            var newParameterInputDiv = document.createElement("div");
            newParameterInputDiv.setAttribute("style", "display: flex; align-items: center;");

            var newParameterInput = document.createElement("input");
            newParameterInput.setAttribute("type", "text");
            newParameterInput.setAttribute("name", "parameter_name[]");
            newParameterInput.setAttribute("required", true);
            newParameterInputDiv.appendChild(newParameterInput);

            // Add the minus icon for removing the parameter field
            var removeIcon = document.createElement("i");
            removeIcon.classList.add("fa-solid", "fa-circle-minus");
            removeIcon.setAttribute("style", "color: red; font-size: 25px; cursor: pointer; padding-left: 8px; padding-top: 10px;");
            removeIcon.setAttribute("onclick", "removeParameterField(this)");
            newParameterInputDiv.appendChild(removeIcon);

            // Append the input group to the parameter name group
            newParameterGroup.appendChild(newParameterInputDiv);
            parameterFields.appendChild(newParameterGroup);

            // Create a new parameter description input group
            var newDescriptionGroup = document.createElement("div");
            newDescriptionGroup.classList.add("form-group", "parameter-group");

            var newDescriptionLabel = document.createElement("label");
            newDescriptionLabel.innerHTML = "PARAMETER DESCRIPTION:";
            newDescriptionGroup.appendChild(newDescriptionLabel);

            var newDescriptionInput = document.createElement("input");
            newDescriptionInput.setAttribute("type", "text");
            newDescriptionInput.setAttribute("name", "parameter_description[]");
            newDescriptionInput.setAttribute("required", true);
            newDescriptionGroup.appendChild(newDescriptionInput);

            // Append the parameter description input to the form
            parameterFields.appendChild(newDescriptionGroup);
        }

        function removeParameterField(element) {
            // Remove both the parameter name and description inputs
            var parameterGroup = element.parentNode; // Get the parent div of the parameter name
            var descriptionGroup = parameterGroup.nextElementSibling; // Get the next sibling (description group)
            
            parameterGroup.remove();
            descriptionGroup.remove();
        }
    </script>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>
