<?php
include 'connection.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$area_code = $_GET['code'];

// Fetch area details
$sql = "SELECT * FROM area WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $area_code);
$stmt->execute();
$result = $stmt->get_result();
$area = $result->fetch_assoc();

// Fetch parameters associated with the area
$sql = "SELECT * FROM parameters WHERE area_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $area_code);
$stmt->execute();
$params_result = $stmt->get_result();

$parameters = [];
while ($row = $params_result->fetch_assoc()) {
    $parameters[] = [
        'id' => $row['id'],
        'parameter_name' => $row['parameter_name'],
        'parameter_description' => $row['parameter_description']
    ];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Area</title>
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
            <a class="btn-back" href="area.php">&lt; BACK</a>
            <h2 class="mt-4 mb-4"><?php echo htmlspecialchars($area['area_name']); ?></h2>
        </div>

        <div class="container2">
            <div class="form-container">
                <form action="edit_area_process.php" method="post">
                    <input type="hidden" name="area_id" value="<?php echo htmlspecialchars($area['id']); ?>">
                    <input type="hidden" id="removed_parameter_ids" name="removed_parameter_ids" value="">
                    <div class="form-group">
                        <label for="area_name">Area Name:</label>
                        <input type="text" id="area_name" name="area_name" value="<?php echo htmlspecialchars($area['area_name']); ?>" required>
                    </div>
                    <h3>Parameters:</h3>
                    <div id="parameters">
                        <?php foreach ($parameters as $index => $param): ?>
                            <div class="form-group">
                                <input type="hidden" name="parameter_ids[]" value="<?php echo htmlspecialchars($param['id']); ?>">
                                <label for="param_name_<?php echo $index; ?>">Parameter <?php echo chr(65 + $index); ?>:</label>
                                <input type="text" id="param_name_<?php echo $index; ?>" name="parameter_names[]" value="<?php echo htmlspecialchars($param['parameter_name']); ?>" required>

                                <label for="param_description_<?php echo $index; ?>">Description:</label>
                                <input type="text" id="param_description_<?php echo $index; ?>" name="parameter_descriptions[]" value="<?php echo htmlspecialchars($param['parameter_description']); ?>" required>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="form-buttons">
                        <button type="button" class="add-button" onclick="showAddParameterModal()">Add Parameter</button>
                        <button type="button" class="remove-program-button" onclick="showRemoveParameterModal()">Remove Parameter</button>
                        <button type="submit" class="btn-update">UPDATE</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Add Parameter Modal -->
        <div class="modal fade" id="parameterModal" tabindex="-1" aria-labelledby="parameterModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="parameterModalLabel">Add Parameter</h5>
                    </div>
                    <div class="modal-body">
                        <form action="add_parameter_process.php" method="post" id="parameterForm">
                            <input type="hidden" name="area_id" value="<?php echo htmlspecialchars($area['id']); ?>"> <!-- Pass the area ID -->
                            <div class="form-group">
                                <label for="modal_param_name">Parameter Name:</label>
                                <input type="text" id="modal_param_name" name="modal_param_name" required>
                            </div>
                            <div class="form-group">
                                <label for="modal_param_description">Description:</label>
                                <input type="text" id="modal_param_description" name="modal_param_description" required>
                            </div>
                            <div class="bottom-button-holder">
                                <button type="button" class="cancel-modal-button" data-bs-dismiss="modal">CANCEL</button>
                                <button type="submit" class="submit-modal-button">ADD PARAMETER</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Remove Parameter Modal -->
        <div class="modal fade" id="removeParameterModal" tabindex="-1" aria-labelledby="removeParameterModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="removeParameterModalLabel">REMOVE PARAMETER</h5>
                    </div>
                    <div class="modal-body">
                        <form id="removeParameterForm">
                            <div id="removeParametersList">
                                <!-- Parameter entries will be listed here -->
                            </div>
                            <div class="bottom-button-holder">
                                <button type="button" class="cancel-modal-button" data-dismiss="modal">CANCEL</button>
                                <button type="button" class="remove-program-button" onclick="removeSelectedParameters()">CONFIRM</button>
                            </div>
                            <input type="hidden" id="removed_parameter_ids" name="removed_parameter_ids" value="">
                        </form>
                    </div>
                </div>
            </div>
        </div>


        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> <!-- jQuery first -->
        <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/js/bootstrap.bundle.min.js"></script> <!-- Bootstrap JS bundle -->

        <script>
            function showAddParameterModal() {
                $('#parameterModal').modal('show');
            }

            function addParameter() {
                const paramName = document.getElementById('modal_param_name').value;
                const paramDescription = document.getElementById('modal_param_description').value;

                const parametersDiv = document.getElementById('parameters');
                const newIndex = parametersDiv.children.length;

                const newParameterDiv = document.createElement('div');
                newParameterDiv.classList.add('form-group');
                newParameterDiv.innerHTML = `
                    <input type="hidden" name="new_parameter_ids[]" value="">
                    <label>Parameter ${String.fromCharCode(65 + newIndex)}:</label>
                    <input type="text" name="new_parameter_names[]" value="${paramName}" required>
                    <label>Description:</label>
                    <input type="text" name="new_parameter_descriptions[]" value="${paramDescription}" required>
                `;
                parametersDiv.appendChild(newParameterDiv);
                document.getElementById('parameterForm').reset();
                $('#parameterModal').modal('hide');
            }

            function showRemoveParameterModal() {
                const parametersDiv = document.getElementById('parameters');
                const removeParametersList = document.getElementById('removeParametersList');
                removeParametersList.innerHTML = ''; // Clear the list

                const parameterDivs = parametersDiv.querySelectorAll('.form-group');
                parameterDivs.forEach((paramDiv, index) => {
                    const paramName = paramDiv.querySelector('input[name="parameter_names[]"]').value;
                    const paramId = paramDiv.querySelector('input[name="parameter_ids[]"]').value || paramDiv.querySelector('input[name="new_parameter_ids[]"]').value;

                    const checkboxHtml = `
                        <div class="form-check parameter-entry d-flex justify-content-between align-items-center">
                            <label class="form-check-label mb-0" for="removeParamCheck_${index}">${paramName}</label>
                            <input class="form-check-input" type="checkbox" value="${index}" id="removeParamCheck_${index}" data-param-id="${paramId}">
                            <input type="hidden" value="${paramId}" class="hiddenParamId">
                        </div>
                    `;
                    removeParametersList.insertAdjacentHTML('beforeend', checkboxHtml);
                });

                $('#removeParameterModal').modal('show');
            }

            function removeSelectedParameters() {
                const selectedParameters = document.querySelectorAll('#removeParametersList input[type="checkbox"]:checked');
                const removedParameterIds = [];

                selectedParameters.forEach(checkbox => {
                    const paramId = checkbox.getAttribute('data-param-id');
                    if (paramId) {
                        removedParameterIds.push(paramId); // Collect parameter IDs for deletion
                        
                        // Find the associated parameter div and remove it
                        const parameterDiv = document.querySelector(`.form-group input[value="${paramId}"]`).closest('.form-group');
                        if (parameterDiv) {
                            parameterDiv.remove(); // Remove from the form
                        }
                    }
                });

                // Proceed with AJAX deletion if there are IDs to delete
                if (removedParameterIds.length > 0) {
                    $.ajax({
                        url: 'remove_parameter_process.php',
                        type: 'POST',
                        data: { ids: removedParameterIds },
                        success: function(response) {
                            console.log(response); // Handle success response if needed
                        },
                        error: function(xhr, status, error) {
                            console.error(error); // Handle error if needed
                        }
                    });
                }

                const removedParameterIdsInput = document.getElementById('removed_parameter_ids');
                removedParameterIdsInput.value = removedParameterIds.join(',');

                $('#removeParameterModal').modal('hide');
            }
        </script>
    </div>
</body>
</html>
