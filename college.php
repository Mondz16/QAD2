<?php
include 'connection.php';

$sql_colleges = "SELECT id, college_code, college_name, college_email FROM college ORDER BY id ASC";
$result_colleges = $conn->query($sql_colleges);

$collegePrograms = [];
while ($row_college = $result_colleges->fetch_assoc()) {
    $collegePrograms[$row_college['id']] = [
        'college_code' => $row_college['college_code'],
        'college_name' => $row_college['college_name'],
        'college_email' => $row_college['college_email'],
        'programs' => []
    ];
}

$sql_programs = "SELECT college_id, program, level, date_received FROM program";
$result_programs = $conn->query($sql_programs);

while ($row_program = $result_programs->fetch_assoc()) {
    $collegePrograms[$row_program['college_id']]['programs'][] = [
        'program' => $row_program['program'],
        'level' => $row_program['level'],
        'date_received' => $row_program['date_received']
    ];
}

$sql_companies = "SELECT id, company_code, company_name FROM company ORDER BY company_name";
$result_companies = $conn->query($sql_companies);

$companyDetails = [];
while ($row_company = $result_companies->fetch_assoc()) {
    $companyDetails[$row_company['id']] = [
        'company_code' => $row_company['company_code'],
        'company_name' => $row_company['company_name']
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
<<<<<<< Updated upstream
=======
<body>
    <h2>University of Southeastern Philippines</h2>
    
    <table>
        <caption>Colleges</caption>
        <tr>
            <th>College Code</th>
            <th>College Name</th>
            <th>College Email</th>
            <th>Programs</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($collegePrograms as $id => $college): ?>
        <tr>
            <td><?php echo htmlspecialchars($college['college_code']); ?></td>
            <td><?php echo htmlspecialchars($college['college_name']); ?></td>
            <td><?php echo htmlspecialchars($college['college_email']); ?></td>
            <td>
                <?php 
                $programCount = count($college['programs']);
                echo $programCount . " programs"; 
                ?>
                <button onclick="showPrograms(<?php echo $id; ?>)">Show All Programs</button>
            </td>
            <td>
                <button onclick="location.href='edit_college.php?id=<?php echo $id; ?>'">Edit</button>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <br><button onclick="location.href='add_college.php'">Add College</button><br><br>
    <br>
    
    <table>
        <caption>Companies</caption>
        <tr>
            <th>Company Code</th>
            <th>Company Name</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($companyDetails as $id => $company): ?>
        <tr>
            <td><?php echo htmlspecialchars($company['company_code']); ?></td>
            <td><?php echo htmlspecialchars($company['company_name']); ?></td>
            <td>
                <button onclick="location.href='edit_company.php?id=<?php echo $id; ?>'">Edit</button>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
>>>>>>> Stashed changes

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
                                    <span class="datausep" style="color: rgb(151, 57, 57); font-weight: 600; font-size: 18px;">USeP.</span></h>
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
                <th class="table_header" colspan="3">
                    Colleges
                </th>
                <th class="button-container">
                    <div>
                        <button onclick="location.href='add_college.php'">Add College</button>
                    </div>
                </th>
            </tr>
            <tr>
                <th>College Code</th>
                <th>College Name</th>
                <th>Programs</th>
                <th>Actions</th>
            </tr>
            <?php foreach ($collegePrograms as $id => $college) : ?>
                <tr>
                    <td><?php echo htmlspecialchars($college['college_code']); ?></td>
                    <td><?php echo htmlspecialchars($college['college_name']); ?></td>
                    <td>
                        <?php
                        $programCount = count($college['programs']);
                        echo $programCount . " programs";
                        ?>
                    </td>
                    <td>
                        <button class="view_button" onclick="showPrograms(<?php echo $id; ?>)">View</button>
                        <button class="edit_button" onclick="location.href='edit_college.php?id=<?php echo $id; ?>'">Edit</button>
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
            <?php foreach ($companyDetails as $id => $company) : ?>
                <tr>
                    <td><?php echo htmlspecialchars($company['company_code']); ?></td>
                    <td><?php echo htmlspecialchars($company['company_name']); ?></td>
                    <td>
                        <button onclick="location.href='edit_company.php?id=<?php echo $id; ?>'">Edit</button>
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
                        <th>Level</th>
                        <th>Date Received</th>
                    </tr>
                    <!-- Program details will be populated here using JavaScript -->
                </table>
            </div>
        </div>
    </div>

    <script>
        var modal = document.getElementById("programModal");
        var span = document.getElementsByClassName("close")[0];

        span.onclick = function() {
            modal.style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }

        function showPrograms(collegeId) {
            var collegePrograms = <?php echo json_encode($collegePrograms); ?>;
            var programs = collegePrograms[collegeId].programs;

            var modalTable = document.getElementById("modalTable");
            modalTable.innerHTML = `
                <tr>
                    <th>Program</th>
                    <th>Level</th>
                    <th>Date Received</th>
                </tr>
            `;

            programs.forEach(function(program) {
                var row = modalTable.insertRow();
                var cell1 = row.insertCell(0);
                var cell2 = row.insertCell(1);
                var cell3 = row.insertCell(2);

                cell1.innerHTML = program.program;
                cell2.innerHTML = program.level;
                cell3.innerHTML = program.date_received;
            });

            modal.style.display = "block";
        }
    </script>
</body>

</html>