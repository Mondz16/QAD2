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
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>College and Programs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <link rel="stylesheet" href="css/sidebar.css">
    <link href="css/pagestyle.css" rel="stylesheet">
    <link href="college_style.css" rel="stylesheet">
    <style>
        .hidden {
            display: none;
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <aside id="sidebar">
            <div class="row" style=" background: linear-gradient(275.52deg, #CD7E7E 0%, #FF7A7A 96%); height: 9px;"></div>
            <div class="d-flex">
                <button class="toggle-btn" type="button">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-grid" viewBox="0 0 16 16">
                        <path d="M1 2.5A1.5 1.5 0 0 1 2.5 1h3A1.5 1.5 0 0 1 7 2.5v3A1.5 1.5 0 0 1 5.5 7h-3A1.5 1.5 0 0 1 1 5.5zM2.5 2a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5zm6.5.5A1.5 1.5 0 0 1 10.5 1h3A1.5 1.5 0 0 1 15 2.5v3A1.5 1.5 0 0 1 13.5 7h-3A1.5 1.5 0 0 1 9 5.5zm1.5-.5a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5zM1 10.5A1.5 1.5 0 0 1 2.5 9h3A1.5 1.5 0 0 1 7 10.5v3A1.5 1.5 0 0 1 5.5 15h-3A1.5 1.5 0 0 1 1 13.5zm1.5-.5a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5zm6.5.5A1.5 1.5 0 0 1 10.5 9h3a1.5 1.5 0 0 1 1.5 1.5v3a1.5 1.5 0 0 1-1.5 1.5h-3A1.5 1.5 0 0 1 9 13.5zm1.5-.5a.5.5 0 0 0-.5.5v3a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5v-3a.5.5 0 0 0-.5-.5z"/>
                    </svg>
                </button>
                <div class="sidebar-logo">
                    <a href="#">SQAD</a>
                </div>
            </div>
            <ul class="sidebar-nav">
                <li class="sidebar-item">
                    <a href="admin_sidebar.php" class="sidebar-link">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person" viewBox="0 0 16 16">
                            <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0m4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4m-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10s-3.516.68-4.168 1.332c-.678.678-.83 1.418-.832 1.664z"/>
                        </svg>
                        <span style="margin-left: 8px;">Admin Profile</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="schedule.php" class="sidebar-link">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-calendar-range" viewBox="0 0 16 16">
                            <path d="M9 7a1 1 0 0 1 1-1h5v2h-5a1 1 0 0 1-1-1M1 9h4a1 1 0 0 1 0 2H1z"/>
                            <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5M1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4z"/>
                        </svg>
                        <span style="margin-left: 8px;">Schedule</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="college.php" class="sidebar-link">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-mortarboard" viewBox="0 0 16 16">
                            <path d="M8.211 2.047a.5.5 0 0 0-.422 0l-7.5 3.5a.5.5 0 0 0 .025.916l7.5 3a.5.5 0 0 0 .372 0L14 7.14V13a1 1 0 0 0-1 1v2h3v-2a1 1 0 0 0-1-1V6.739l.686-.275a.5.5 0 0 0 .025-.916zM8 8.46 1.758 5.965 8 3.052l6.242 2.913z"/>
                            <path d="M4.166 9.032a.5.5 0 0 0-.656.327l-.5 1.7a.5.5 0 0 0 .294.605l4.5 1.8a.5.5 0 0 0 .372 0l4.5-1.8a.5.5 0 0 0 .294-.605l-.5-1.7a.5.5 0 0 0-.656-.327L8 10.466zm-.068 1.873.22-.748 3.496 1.311a.5.5 0 0 0 .352 0l3.496-1.311.22.748L8 12.46z"/>
                        </svg>
                        <span style="margin-left: 8px;">College</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="orientation.php" class="sidebar-link">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chat-square-text" viewBox="0 0 16 16">
                            <path d="M14 1a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1h-2.5a2 2 0 0 0-1.6.8L8 14.333 6.1 11.8a2 2 0 0 0-1.6-.8H2a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1zM2 0a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2.5a1 1 0 0 1 .8.4l1.9 2.533a1 1 0 0 0 1.6 0l1.9-2.533a1 1 0 0 1 .8-.4H14a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2z"/>
                            <path d="M3 3.5a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5M3 6a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9A.5.5 0 0 1 3 6m0 2.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5"/>
                        </svg>
                        <span style="margin-left: 8px;">Orientation</span>
                    </a>
                </li>
                <li class="sidebar-item mt-3">
                    <a href="admin_assessment.php" class="sidebar-link">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-list-check" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M5 11.5a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5M3.854 2.146a.5.5 0 0 1 0 .708l-1.5 1.5a.5.5 0 0 1-.708 0l-.5-.5a.5.5 0 1 1 .708-.708L2 3.293l1.146-1.147a.5.5 0 0 1 .708 0m0 4a.5.5 0 0 1 0 .708l-1.5 1.5a.5.5 0 0 1-.708 0l-.5-.5a.5.5 0 1 1 .708-.708L2 7.293l1.146-1.147a.5.5 0 0 1 .708 0m0 4a.5.5 0 0 1 0 .708l-1.5 1.5a.5.5 0 0 1-.708 0l-.5-.5a.5.5 0 1 1 .708-.708l.146.147 1.146-1.147a.5.5 0 0 1 .708 0"/>
                        </svg>
                        <span style="margin-left: 8px;">Assessment</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="udas_assessment.php" class="sidebar-link">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-clipboard2-check" viewBox="0 0 16 16">
                            <path d="M9.5 0a.5.5 0 0 1 .5.5.5.5 0 0 0 .5.5.5.5 0 0 1 .5.5V2a.5.5 0 0 1-.5.5h-5A.5.5 0 0 1 5 2v-.5a.5.5 0 0 1 .5-.5.5.5 0 0 0 .5-.5.5.5 0 0 1 .5-.5z"/>
                            <path d="M3 2.5a.5.5 0 0 1 .5-.5H4a.5.5 0 0 0 0-1h-.5A1.5 1.5 0 0 0 2 2.5v12A1.5 1.5 0 0 0 3.5 16h9a1.5 1.5 0 0 0 1.5-1.5v-12A1.5 1.5 0 0 0 12.5 1H12a.5.5 0 0 0 0 1h.5a.5.5 0 0 1 .5.5v12a.5.5 0 0 1-.5.5h-9a.5.5 0 0 1-.5-.5z"/>
                            <path d="M10.854 7.854a.5.5 0 0 0-.708-.708L7.5 9.793 6.354 8.646a.5.5 0 1 0-.708.708l1.5 1.5a.5.5 0 0 0 .708 0z"/>
                        </svg>
                        <span style="margin-left: 8px;">UDAS Assessment</span>
                    </a>
                </li>
                <li class="sidebar-item mt-3">
                    <a href="registration.php" class="sidebar-link">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-people" viewBox="0 0 16 16">
                            <path d="M15 14s1 0 1-1-1-4-5-4-5 3-5 4 1 1 1 1zm-7.978-1L7 12.996c.001-.264.167-1.03.76-1.72C8.312 10.629 9.282 10 11 10c1.716 0 2.687.63 3.24 1.276.593.69.758 1.457.76 1.72l-.008.002-.014.002zM11 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4m3-2a3 3 0 1 1-6 0 3 3 0 0 1 6 0M6.936 9.28a6 6 0 0 0-1.23-.247A7 7 0 0 0 5 9c-4 0-5 3-5 4q0 1 1 1h4.216A2.24 2.24 0 0 1 5 13c0-1.01.377-2.042 1.09-2.904.243-.294.526-.569.846-.816M4.92 10A5.5 5.5 0 0 0 4 13H1c0-.26.164-1.03.76-1.724.545-.636 1.492-1.256 3.16-1.275ZM1.5 5.5a3 3 0 1 1 6 0 3 3 0 0 1-6 0m3-2a2 2 0 1 0 0 4 2 2 0 0 0 0-4"/>
                        </svg>
                        <span style="margin-left: 8px;">Register Verification</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="reports.php" class="sidebar-link">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-bar-chart-line" viewBox="0 0 16 16">
                            <path d="M11 2a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v12h.5a.5.5 0 0 1 0 1H.5a.5.5 0 0 1 0-1H1v-3a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v3h1V7a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v7h1zm1 12h2V2h-2zm-3 0V7H7v7zm-5 0v-3H2v3z"/>
                        </svg>
                        <span style="margin-left: 8px;">Reports</span>
                    </a>
                </li>
            </ul>
            <div class="sidebar-footer p-1">
                <a href="login.php" class="sidebar-link">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-box-arrow-left" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M6 12.5a.5.5 0 0 0 .5.5h8a.5.5 0 0 0 .5-.5v-9a.5.5 0 0 0-.5-.5h-8a.5.5 0 0 0-.5.5v2a.5.5 0 0 1-1 0v-2A1.5 1.5 0 0 1 6.5 2h8A1.5 1.5 0 0 1 16 3.5v9a1.5 1.5 0 0 1-1.5 1.5h-8A1.5 1.5 0 0 1 5 12.5v-2a.5.5 0 0 1 1 0z"/>
                        <path fill-rule="evenodd" d="M.146 8.354a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L1.707 7.5H10.5a.5.5 0 0 1 0 1H1.707l2.147 2.146a.5.5 0 0 1-.708.708z"/>
                    </svg>
                    <span style="margin-left: 8px;">Logout</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="main">
            <div class="row top-bar"></div>
            <div class="row header mb-3">
                <div class="col-6 col-md-2 mx-auto d-flex align-items-center justify-content-end">
                    <img src="images/USePLogo.png" alt="USeP Logo">
                </div>
                <div class="col-6 col-md-4 d-flex align-items-start">
                    <div class="vertical-line"></div>
                    <div class="divider"></div>
                    <div class="text">
                        <span class="one">One</span>
                        <span class="datausep">Data.</span>
                        <span class="one">One</span>
                        <span class="datausep">USeP.</span><br>
                        <span>Quality Assurance Division</span>
                    </div>
                </div>
                <div class="col-md-4 d-none d-md-flex align-items-center justify-content-end">
                </div>
                <div class="col-md-2 d-none d-md-flex align-items-center justify-content-start">
                </div>
            </div>
            <div class="container text-center mt-4">
                <h1>University of Southeastern Philippines</h1>
                <div class="row m-1 mt-3">
                    <div class="col-12 border rounded-2 d-flex justify-content-between" style="background: white;">
                        <div class="d-flex">
                            <button id="collegesBtn" class="pobtn border btn m-2" onclick="showTable('collegeTable', 'collegesBtn')">Colleges</button>
                            <button id="sucBtn" class="nebtn border btn m-2" onclick="showTable('sucTable', 'sucBtn')">SUC/Company</button>
                        </div>
                        <div class="d-flex">
                            <button class="nebtn border btn m-2" onclick="openImportModal()">Import
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-download ms-2" viewBox="0 0 16 16">
                                    <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5"/>
                                    <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708z"/>
                                </svg>
                            </button>
                            <button class="refresh border btn m-2" onclick="location.href='add_college.php'">Add College
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-plus ms-2" viewBox="0 0 16 16">
                                    <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="table-responsive col-12">
                        <table id="collegeTable" class="table border rounded-2">
                            <thead>
                                <tr>
                                    <th>COLLEGE CODE</th>
                                    <th>COLLEGE NAME</th>
                                    <th>COLLEGE CAMPUS</th>
                                    <th>COLLEGE EMAIL</th>
                                    <th>PROGRAMS</th>
                                    <th>ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($collegePrograms as $code => $college) : ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($college['code']); ?></td>
                                        <td><?php echo htmlspecialchars($college['college_name']); ?></td>
                                        <td><?php echo htmlspecialchars($college['college_campus']); ?></td>
                                        <td><?php echo htmlspecialchars($college['college_email']); ?></td>
                                        <td><?php echo count($college['programs']); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-view" onclick="showPrograms('<?php echo $code; ?>')">VIEW</button>
                                            <button class="btn btn-sm btn-edit" onclick="location.href='edit_college.php?code=<?php echo $code; ?>'">EDIT</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <table id="sucTable" class="table border rounded-2 hidden">
                            <thead>
                                <tr>
                                    <th>SUC/COMPANY CODE</th>
                                    <th>SUC/COMPANY NAME</th>
                                    <th>SUC/COMPANY ADDRESS</th>
                                    <th>SUC/COMPANY EMAIL</th>
                                    <th>PROGRAMS</th>
                                    <th>ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($companyDetails as $code => $company) : ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($company['code']); ?></td>
                                        <td><?php echo htmlspecialchars($company['company_name']); ?></td>
                                        <td><?php echo htmlspecialchars($company['company_email']); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-view">VIEW</button>
                                            <button class="btn btn-sm btn-edit" onclick="location.href='edit_company.php?code=<?php echo $code; ?>'">EDIT</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal for showing programs -->
        <div id="programModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe"
        crossorigin="anonymous"></script>
    <script>
        const hamBurger = document.querySelector(".toggle-btn");

        hamBurger.addEventListener("click", function () {
            document.querySelector("#sidebar").classList.toggle("expand");
        });
        function showTable(tableId, buttonId) {
            const tables = document.querySelectorAll('.table');
            tables.forEach(table => {
                if (table.id === tableId) {
                    table.classList.remove('hidden');
                } else {
                    table.classList.add('hidden');
                }
            });

            const buttons = document.querySelectorAll('.pobtn, .nebtn');
            buttons.forEach(button => {
                button.classList.remove('pobtn');
                button.classList.add('nebtn');
            });

            const activeButton = document.getElementById(buttonId);
            activeButton.classList.remove('nebtn');
            activeButton.classList.add('pobtn');
        }

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


