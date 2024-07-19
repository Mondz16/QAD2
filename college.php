<?php
include 'connection.php';

$sql_colleges = "SELECT id, college_code, college_name, college_email FROM college ORDER BY id ASC";
$result_colleges = $conn->query($sql_colleges);

$collegePrograms = [];
while ($row_college = $result_colleges->fetch_assoc()) {
    $collegePrograms[$row_college['id']] = [
        'college_code' => $row_college['college_code'],
        'college_name' => $row_college['college_name'],
        'college_email' => $row_college['college_email'], // Add this line
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
    <title>College</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="css/pagestyle.css" rel="stylesheet">
</head>
<body>
<div class="container-fluid">
<div class="row top-bar"></div>
        <div class="row header mb-3">
            <div class="col-6 col-md-2 mx-auto d-flex align-items-center justify-content-end">
                <img src="images/USePLogo.png" alt="USeP Logo">
            </div>
            <div class="col-6 col-md-4 d-flex align-items-center">
                <div class="vertical-line"></div>
                <div class="divider"></div>
                <div class="text">
                    <span class="one">One</span>
                    <span class="datausep">Data.</span>
                    <span class="one">One</span>
                    <span class="datausep">USeP.</span><br>
                    <span>Accreditor Portal</span>
                </div>
            </div>
            <div class="col-md-4 d-none d-md-flex align-items-center justify-content-end">
            </div>
            <div class="col-md-2 d-none d-md-flex align-items-center justify-content-start">
            </div>
        </div>
        <div class="row justify-content-start">
            <div class="col-2"></div>
            <div class="col text-center mt-3">
                <div class="row">
                <button class="col-2 mx-1 pobtn" onclick="location.href='admin.php'"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-left" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8"/>
                </svg></button>
                <h2 class="col">University of Southeastern Philippines</h2>
                </div>
                <div class="row mb-3">
            <div class="col text-start mt-5">
                <h2>Colleges</h2>
            </div>
            <div class="col-auto mt-5">
                <button class="btn refresh" onclick="location.href='add_college.php'"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-plus" viewBox="0 0 16 16">
                <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4"/>
                </svg>Add College</button>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>College Code</th>
                        <th>College Name</th>
                        <th>College Email</th>
                        <th>Programs</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($collegePrograms as $id => $college) : ?>
                <tr>
                    <td><?php echo htmlspecialchars($college['college_code']); ?></td>
                    <td><?php echo htmlspecialchars($college['college_name']); ?></td>
                    <td><?php echo htmlspecialchars($college['college_email']); ?></td>
                    <td>
                        <?php
                        $programCount = count($college['programs']);
                        echo $programCount . " programs";
                        ?>
                    </td>
                    <td class="text-center">
                        <button class="btn pobtn btn-sm mb-3 mb-md-0" onclick="showPrograms(<?php echo $id; ?>)">View</button>
                        <button class="btn pobtn btn-sm" onclick="location.href='edit_college.php?id=<?php echo $id; ?>'">Edit</button>
                    </td>
                </tr>
            <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="row mb-3 mt-5">
            <div class="col text-start">
                <h2>Company</h2>
            </div>
            <div class="col-auto">
                <button class="btn refresh" onclick="location.href='add_company.php'"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-plus" viewBox="0 0 16 16">
                <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4"/>
                </svg>Add Company</button>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Company Code</th>
                        <th>Company Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($companyDetails as $id => $company) : ?>
                        <tr>
                            <td><?php echo htmlspecialchars($company['company_code']); ?></td>
                            <td><?php echo htmlspecialchars($company['company_name']); ?></td>
                            <td class="text-center">
                                <button class="btn pobtn btn-sm" onclick="location.href='edit_company.php?id=<?php echo $id; ?>'">Edit</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
            </div>
            <div class="col-2"></div>
        </div>
</div>
<div class="modal fade" id="programModal" tabindex="-1" aria-labelledby="programModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="programModalLabel">Programs</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <table class="table">
          <thead>
            <tr>
              <th>Program</th>
              <th>Level</th>
              <th>Date Received</th>
            </tr>
          </thead>
          <tbody id="programDetails">
          </tbody>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
<footer class="row text-left mt-5">
        <div class="col-2"></div>
        <div class="col">
            <p>Copyright © 2024. All Rights Reserved.</p>
            <a href="#">Terms of Use</a> | <a href="#">Privacy Policy</a>
        </div>
        </footer>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>    
    <script>
const collegePrograms = <?php echo json_encode($collegePrograms); ?>;

function showPrograms(collegeId) {
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

    const programModal = new bootstrap.Modal(document.getElementById('programModal'));
    programModal.show();
}
</script>
</body>
</html>
