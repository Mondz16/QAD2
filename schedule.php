<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule</title>
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
                <h2>Schedules</h2>
            </div>
            <div class="col-auto mt-5">
                <button class="btn refresh" onclick="location.href='add_college.php'"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-plus" viewBox="0 0 16 16">
                <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4"/>
                </svg>Add Schedule</button>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>College</th>
                        <th>Total Schedules</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                    include 'connection.php';

                    $sql = "SELECT c.college_name, COUNT(s.id) AS total_schedules 
                            FROM college c 
                            LEFT JOIN schedule s ON c.id = s.college_id 
                            GROUP BY c.college_name 
                            ORDER BY c.college_name";

                    $result = $conn->query($sql);

                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . $row["college_name"] . "</td>";
                            echo "<td>" . $row["total_schedules"] . "</td>";
                            echo "<td><button class='btn pobtn btn-sm mb-3 mb-md-0 p-2' onclick="."location.href='schedule_college.php?college=" . urlencode($row["college_name"]) . "'>View</button></td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='2'>No colleges found</td></tr>";
                    }

                    $conn->close();
                    ?>
                </tbody>
            </table>
        </div>
            </div>
            <div class="col-2"></div>
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
