<?php
include 'connection.php';

$sql_colleges = "SELECT code, college_name, college_campus, college_email FROM college ORDER BY code ASC";
$result_colleges = $conn->query($sql_colleges);

$collegePrograms = [];
while ($row_college = $result_colleges->fetch_assoc()) {
    $collegePrograms[$row_college['code']] = [
        'code' => $row_college['code'],
        'college_name' => $row_college['college_name'],
        'college_campus' => $row_college['college_campus'],
        'college_email' => $row_college['college_email'],
        'programs' => []
    ];
}

$sql_programs = "SELECT 
                    p.college_code, 
                    p.program_name, 
                    plh.program_level, 
                    plh.date_received 
                 FROM 
                    program p
                 LEFT JOIN 
                    program_level_history plh 
                 ON 
                    p.program_level_id = plh.id";

$result_programs = $conn->query($sql_programs);

while ($row_program = $result_programs->fetch_assoc()) {
    $program_level = $row_program['program_level'] ?? 'N/A';
    $collegePrograms[$row_program['college_code']]['programs'][] = [
        'program_name' => $row_program['program_name'],
        'program_level' => $program_level,
        'date_received' => $row_program['date_received']
    ];
}

$sql_companies = "SELECT code, company_name, company_email FROM company ORDER BY company_name";
$result_companies = $conn->query($sql_companies);

$companyDetails = [];
while ($row_company = $result_companies->fetch_assoc()) {
    $companyDetails[$row_company['code']] = [
        'code' => $row_company['code'],
        'company_name' => $row_company['company_name'],
        'company_email' => $row_company['company_email']
    ];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>College and Programs</title>
    <link rel="stylesheet" href="college_style.css">
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

    <div class="pageHeader">
        <div class="headerRight">
            <a class="btn" href="admin.php">Back</a>
        </div>
        <h2>University of Southeastern Philippines</h2>
    </div>

    <div class="container2">
        <table>
            <tr>
                <th class="table_header" colspan="5">
                    Colleges
                </th>
                <th class="button-container">
                    <div>
                        <button onclick="openImportModal()">Import</button>
                        <button onclick="location.href='add_college.php'">Add College</button>
                    </div>
                </th>
            </tr>
            <tr>
                <th>College Code</th>
                <th>College Name</th>
                <th>College Campus</th>
                <th>College Email</th>
                <th>Programs</th>
                <th>Actions</th>
            </tr>
            <?php foreach ($collegePrograms as $code => $college) : ?>
                <tr>
                    <td><?php echo htmlspecialchars($college['code']); ?></td>
                    <td><?php echo htmlspecialchars($college['college_name']); ?></td>
                    <td><?php echo htmlspecialchars($college['college_campus']); ?></td>
                    <td><?php echo htmlspecialchars($college['college_email']); ?></td>
                    <td>
                        <?php
                        $programCount = count($college['programs']);
                        echo $programCount . " programs";
                        ?>
                    </td>
                    <td>
                        <button class="view_button" onclick="showPrograms('<?php echo $code; ?>')">View</button>
                        <button class="edit_button" onclick="location.href='edit_college.php?code=<?php echo $code; ?>'">Edit</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>

        <br>

        <table>
            <tr>
                <th class="table_header" colspan="2">
                    Company
                </th>
                <th class="button-container">
                    <div>
                        <button onclick="location.href='add_company.php'">Add Company</button>
                    </div>
                </th>
            </tr>
            <tr>
                <th>Company Code</th>
                <th>Company Name</th>
                <th>Actions</th>
            </tr>
            <?php foreach ($companyDetails as $code => $company) : ?>
                <tr>
                    <td><?php echo htmlspecialchars($company['code']); ?></td>
                    <td><?php echo htmlspecialchars($company['company_name']); ?></td>
                    <td>
                        <button onclick="location.href='edit_company.php?code=<?php echo $code; ?>'">Edit</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>

        <!-- Modal for showing programs -->
        <div id="programModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Programs</h2>
                <table id="modalTable">
                    <tr>
                        <th>Program</th>
                        <th>Level <button onclick="sortPrograms('program_level')">Sort</button></th>
                        <th>Date Received <button onclick="sortPrograms('date_received')">Sort</button></th>
                    </tr>
                    <!-- Program details will be populated here using JavaScript -->
                </table>
            </div>
        </div>

        <!-- Modal for importing colleges -->
        <div id="importModal" class="modal">
            <div class="import-modal-content">
                <span class="close" onclick="closeImportModal()">&times;</span>
                <h2>Import Colleges</h2>
                <form action="add_college_import.php" method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="excel_file">Upload Excel File:</label>
                        <input type="file" id="excel_file" name="excel_file" accept=".xlsx, .xls" required>
                    </div>
                    <button type="submit" class="button button-primary">Import</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        var programModal = document.getElementById("programModal");
        var importModal = document.getElementById("importModal");
        var spanProgram = document.getElementsByClassName("close")[0];
        var spanImport = document.getElementsByClassName("close")[1];
        var programsData = [];

        spanProgram.onclick = function() {
            programModal.style.display = "none";
        }

        spanImport.onclick = function() {
            importModal.style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target == programModal) {
                programModal.style.display = "none";
            }
            if (event.target == importModal) {
                importModal.style.display = "none";
            }
        }

        function showPrograms(collegeId) {
            var collegePrograms = <?php echo json_encode($collegePrograms); ?>;
            programsData = collegePrograms[collegeId].programs;
            displayPrograms(programsData);
            programModal.style.display = "block";
        }

        function displayPrograms(programs) {
            var modalTable = document.getElementById("modalTable");
            modalTable.innerHTML = `
        <tr>
            <th>Program</th>
            <th>Level <button onclick="sortPrograms('program_level')">Sort</button></th>
            <th>Date Received <button onclick="sortPrograms('date_received')">Sort</button></th>
        </tr>
    `;

            programs.forEach(function(program) {
                var row = modalTable.insertRow();
                var cell1 = row.insertCell(0);
                var cell2 = row.insertCell(1);
                var cell3 = row.insertCell(2);

                cell1.innerHTML = program.program_name;
                cell2.innerHTML = program.program_level || 'N/A';
                cell3.innerHTML = program.date_received;
            });
        }

        function sortPrograms(criteria) {
            programsData.sort(function(a, b) {
                if (criteria === 'date_received') {
                    return new Date(a[criteria]) - new Date(b[criteria]);
                } else {
                    if (a[criteria] < b[criteria]) return -1;
                    if (a[criteria] > b[criteria]) return 1;
                    return 0;
                }
            });
            displayPrograms(programsData);
        }

        function openImportModal() {
            importModal.style.display = "block";
        }

        function closeImportModal() {
            importModal.style.display = "none";
        }
    </script>
</body>

</html>
