<?php
include 'connection.php';

// Fetch colleges
$colleges = [];
$sql = "SELECT code, college_name FROM college";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $colleges[] = $row;
    }
}

// Handle AJAX requests
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['college_code'])) {
        // Fetch distinct programs for a specific college
        $college_code = $_POST['college_code'];
        
        $sql = "SELECT DISTINCT program_name FROM program WHERE college_code = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $college_code);
        $stmt->execute();
        $result = $stmt->get_result();

        $options = "";

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $options .= "<div class='select-item' data-value='" . htmlspecialchars($row['program_name']) . "'>" . htmlspecialchars($row['program_name']) . "</div>";
            }
        } else {
            $options .= "<div class='select-item'>No programs available</div>";
        }

        echo $options;
        $stmt->close();
        exit;  // Exit to prevent further HTML output
    }

    if (isset($_POST['program_names'])) {
        // Fetch program level history for specific programs
        $program_names = json_decode($_POST['program_names'], true);
        $events = []; // Array to store events for the timeline

        foreach ($program_names as $program_name) {
            $sql = "SELECT plh.program_level, plh.date_received 
                    FROM program_level_history plh
                    JOIN program p ON plh.program_id = p.id
                    WHERE p.program_name = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $program_name);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $events[] = [
                        'label' => $row['program_level'],
                        'date' => $row['date_received']
                    ];
                }
            }
            $stmt->close();
        }

        // Return events data as JSON
        echo json_encode($events);
        exit;  // Exit to prevent further HTML output
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Internal</title>
    <link rel="stylesheet" href="index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-moment"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
    <style>
        .program-history {
            margin-bottom: 20px;
        }
        .custom-select {
            width: 300px;
            position: relative;
            display: inline-block;
        }
        .select-items {
            position: absolute;
            background-color: #f9f9f9;
            top: 100%;
            left: 0;
            right: 0;
            z-index: 99;
            border: 1px solid #ccc;
            max-height: 150px;
            overflow-y: auto;
            display: none;
        }
        .select-item {
            padding: 10px;
            cursor: pointer;
        }
        .select-item:hover {
            background-color: #e9e9e9;
        }
        .same-as-selected {
            background-color: #d1e0e0;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="hair"></div>
        <div class="container">
            <div class="header">
                <div class="headerLeft">
                    <div class="USePData">
                        <img class="USeP" src="images/USePLogo.png" height="36">
                        <div style="height: 0px; width: 16px;"></div>
                        <div style="height: 32px; width: 1px; background: #E5E5E5"></div>
                        <div style="height: 0px; width: 16px;"></div>
                        <div class="headerLeftText">
                            <div class="onedata">
                                <h><span class="one">One</span>
                                    <span class="datausep">Data.</span>
                                    <span class="one">One</span>
                                    <span class="datausep">USeP.</span></h>
                            </div>
                            <h>Accreditor Portal</h>
                        </div>
                    </div>
                </div>

                <div class="headerRight">
                    <div class="QAD">
                        <div class="headerRightText">
                            <h>Quality Assurance Division</h>
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
        <div style="height: 10px; width: 0px;"></div>
        <div class="container">
            <div class="college-program">
                <div class="college-program-history">
                    <select id="collegeSelect" onchange="loadPrograms(this.value)">
                    <option value="">Select a college</option>
                    <?php
                    foreach ($colleges as $college) {
                        echo "<option value='" . $college['code'] . "'>" . htmlspecialchars($college['college_name']) . "</option>";
                    }
                    ?>
                </select>
                </div>
                <div class="college-program-history">
                    <div class="select-selected">Select programs</div>
                    <div class="select-items">
                        <!-- Options will be populated based on selected college -->
                    </div>
                    <div class="custom-select">
                    </div>
                </div>
            </div>
            <div style="height: 32px;"></div>
            <div class="orientation2" id="programHistory">
                <!-- Program level history will be displayed here -->
            </div>
            <div style="height: 32px;"></div>
            <div class="orientation2">
                <canvas id="timelineChart" style="height: 200px; width: 100%;"></canvas>
            </div>
        </div>
    </div>

    <script>
        let chartInstance = null; // Store chart instance

        function loadPrograms(collegeCode) {
            if (collegeCode === "") {
                document.querySelector('.select-items').innerHTML = "<div>Select programs</div>";
                document.getElementById('programHistory').innerHTML = "";
                return;
            }
            
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "", true);  // Send POST request to the same page
            xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function() {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    document.querySelector('.select-items').innerHTML = xhr.responseText;
                    setupCustomSelect();
                }
            };
            xhr.send("college_code=" + encodeURIComponent(collegeCode));
        }

        function setupCustomSelect() {
            var selectItems = document.querySelector('.select-items');
            var selectedDiv = document.querySelector('.select-selected');
            var items = selectItems.getElementsByClassName('select-item');

            Array.from(items).forEach(function(item) {
                item.addEventListener('click', function(e) {
                    e.stopPropagation();
                    item.classList.toggle('same-as-selected');

                    // Get selected values
                    var selectedValues = Array.from(selectItems.getElementsByClassName('same-as-selected')).map(function(selectedItem) {
                        return selectedItem.dataset.value;
                    });

                    // Update display
                    if (selectedValues.length > 0) {
                        selectedDiv.textContent = selectedValues[0] + (selectedValues.length > 1 ? ', ...' : '');
                    } else {
                        selectedDiv.textContent = 'Select programs';
                    }

                    loadProgramHistories(selectedValues);
                });
            });

            // Toggle dropdown
            selectedDiv.addEventListener('click', function(e) {
                e.stopPropagation();
                closeAllSelect();
                selectItems.style.display = selectItems.style.display === 'block' ? 'none' : 'block';
            });
        }

        function loadProgramHistories(selectedPrograms) {
            if (selectedPrograms.length === 0) {
                document.getElementById('programHistory').innerHTML = "";
                document.getElementById('timelineChart').style.display = 'none';
                return;
            }

            var xhr = new XMLHttpRequest();
            xhr.open("POST", "", true);  // Send POST request to the same page
            xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function() {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    try {
                        const events = JSON.parse(xhr.responseText);
                        renderTimelineChart(events);
                    } catch (e) {
                        console.error("Failed to parse JSON response", e);
                        document.getElementById('programHistory').innerHTML = xhr.responseText;
                    }
                }
            };
            xhr.send("program_names=" + encodeURIComponent(JSON.stringify(selectedPrograms)));
        }

        function renderTimelineChart(events) {
    if (events.length === 0) {
        document.getElementById('timelineChart').style.display = 'none';
        return;
    }

    // Destroy existing chart instance if exists
    if (chartInstance) {
        chartInstance.destroy();
    }

    // Convert date strings to Date objects and adjust y position alternately
    events.forEach((event, index) => {
        event.x = new Date(event.date);
        event.y = index % 2 === 0 ? 0 : -0.4;  // Alternate between -0.5 and 0.5 for zigzag
        event.label = `${event.label}; ${new Date(event.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}`;
    });

    // Determine the timeline range
    const dates = events.map(event => event.x);
    const minDate = new Date(Math.min.apply(null, dates));
    const maxDate = new Date(Math.max.apply(null, dates));
    minDate.setFullYear(minDate.getFullYear() - 1);
    maxDate.setFullYear(maxDate.getFullYear() + 1);

    // Prepare the dataset for Chart.js
    const dataset = {
        datasets: [{
            label: 'Program Timeline',
            data: events,
            backgroundColor: 'blue',
            pointRadius: 5,
            pointHoverRadius: 7,
            showLine: false
        }]
    };

    // Get the context of the canvas element
    const ctx = document.getElementById('timelineChart').getContext('2d');

    // Define a plugin to draw vertical lines
    const verticalLinePlugin = {
        id: 'verticalLinePlugin',
        beforeDraw: chart => {
            const { ctx, chartArea: { top, bottom }, scales: { x, y } } = chart;
            ctx.save();
            ctx.strokeStyle = 'black';  // Set line color to black
            ctx.lineWidth = 2;  // Set line thickness

            events.forEach(event => {
                const xPosition = x.getPixelForValue(event.x);
                const yPosition = y.getPixelForValue(event.y);

                ctx.beginPath();
                ctx.moveTo(xPosition, yPosition);
                ctx.lineTo(xPosition, bottom);
                ctx.stroke();
            });
            ctx.restore();
        }
    };

    // Render the chart
    chartInstance = new Chart(ctx, {
        type: 'scatter',
        data: dataset,
        options: {
            scales: {
                x: {
                    type: 'time',
                    time: {
                        unit: 'year', // Set unit to year
                        displayFormats: {
                            year: 'YYYY' // Format the year
                        }
                    },
                    min: minDate,
                    max: maxDate,
                    title: {
                        display: true,
                        text: 'Year'
                    }
                },
                y: {
                    display: false,
                    min: -1,
                    max: 1
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.raw.label;
                        }
                    }
                },
                datalabels: {
                    align: 'top',
                    anchor: 'end',
                    formatter: function(value) {
                        return value.label;
                    }
                }
            }
        },
        plugins: [ChartDataLabels, verticalLinePlugin]
    });

    // Ensure the chart is displayed
    document.getElementById('timelineChart').style.display = 'block';
}




        function closeAllSelect() {
            var selectItems = document.querySelector('.select-items');
            selectItems.style.display = 'none';
        }

        document.addEventListener('click', closeAllSelect);
    </script>
</body>
</html>
