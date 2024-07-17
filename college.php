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
    <style>
        table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid black;
            text-align: left;
            padding: 8px;
        }
        th {
            background-color: #f2f2f2;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }
        .modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
        }
    </style>
</head>
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

    <div id="programModal" class="modal">
        <div class="modal-content">
            <span onclick="closeModal()" style="float: right; cursor: pointer;">&times;</span>
            <h2>Programs</h2>
            <table>
                <tr>
                    <th>Program</th>
                    <th>Level</th>
                    <th>Date Received</th>
                </tr>
                <tbody id="programDetails">
                </tbody>
            </table>
        </div>
    </div>
    <br><button onclick="location.href='add_company.php'">Add Company</button><br><br>
    <button onclick="location.href='admin.php'">Back</button>
    <script>
        const collegePrograms = <?php echo json_encode($collegePrograms); ?>;

        function showPrograms(collegeId) {
            const modal = document.getElementById('programModal');
            const programDetails = document.getElementById('programDetails');
            programDetails.innerHTML = '';

            collegePrograms[collegeId].programs.forEach(program => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${program.program}</td>
                    <td>${program.level}</td>
                    <td>${program.date_received}</td>
                `;
                programDetails.appendChild(row);
            });

            modal.style.display = 'block';
        }

        function closeModal() {
            const modal = document.getElementById('programModal');
            modal.style.display = 'none';
        }
    </script>
</body>
</html>
