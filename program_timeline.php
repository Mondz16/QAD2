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
                $programEvents = [];
                while ($row = $result->fetch_assoc()) {
                    $programEvents[] = [
                        'label' => $row['program_level'],
                        'date' => $row['date_received']
                    ];
                }
                $events[$program_name] = $programEvents; // Store events per program
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
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
            <div class="orientation5" id="chartContainer">
                <!-- Dynamic chart canvases will be appended here -->
            </div>
        </div>
        <button class="export-button" id="exportPdfButton">EXPORT <img src="images/export.png"></button>
    </div>

    <script>
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
    const selectItems = document.querySelector('.select-items');
    const selectedDiv = document.querySelector('.select-selected');
    const items = selectItems.getElementsByClassName('select-item');
    const maxSelection = 5; // Maximum number of selectable programs

    Array.from(items).forEach(function(item) {
        item.addEventListener('click', function(e) {
            e.stopPropagation();
            item.classList.toggle('same-as-selected');

            // Get selected values
            let selectedValues = Array.from(selectItems.getElementsByClassName('same-as-selected')).map(function(selectedItem) {
                return selectedItem.dataset.value;
            });

            // Limit selection to maxSelection
            if (selectedValues.length > maxSelection) {
                alert(`You can only select up to ${maxSelection} programs.`);
                item.classList.remove('same-as-selected');
                return;
            }

            // Update display
            if (selectedValues.length > 0) {
                const displayedText = selectedValues.length > 1 ? `${selectedValues[0]} and ${selectedValues.length - 1} more` : selectedValues[0];
                selectedDiv.textContent = displayedText;
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
        document.getElementById('chartContainer').innerHTML = "";
        return;
    }

    var xhr = new XMLHttpRequest();
    xhr.open("POST", "", true);  // Send POST request to the same page
    xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function() {
        if (xhr.readyState == 4 && xhr.status == 200) {
            try {
                const allEvents = JSON.parse(xhr.responseText);
                renderTimelineCharts(allEvents, selectedPrograms);
            } catch (e) {
                console.error("Failed to parse JSON response", e);
                document.getElementById('programHistory').innerHTML = xhr.responseText;
            }
        }
    };
    xhr.send("program_names=" + encodeURIComponent(JSON.stringify(selectedPrograms)));
}

function renderTimelineCharts(eventsGroupedByProgram, selectedPrograms) {
    const chartContainer = document.getElementById('chartContainer');
    
    // Clear previous charts
    chartContainer.innerHTML = '';

    Object.keys(eventsGroupedByProgram).forEach((programName, programIndex) => {
        const events = eventsGroupedByProgram[programName];

        // Create a container div for each program
        const programContainer = document.createElement('div');
        programContainer.classList.add('orientation4');
        
        // Create a label for each program
        const programLabel = document.createElement('h3');
        programLabel.textContent = `${programName}`;
        programContainer.appendChild(programLabel);

        // Create a new canvas element for each program
        const canvas = document.createElement('canvas');
        canvas.id = `timelineChart${programIndex}`;
        canvas.style.height = '200px';
        canvas.style.width = '100%';
        programContainer.appendChild(canvas);

        // Append the program container to the chart container
        chartContainer.appendChild(programContainer);

        // Create a chart for each canvas
        if (events.length === 0) {
            return;
        }

        // Convert date strings to Date objects
        events.forEach(event => {
            event.x = new Date(event.date);
            event.y = 0;
            event.label = `${event.label}; ${new Date(event.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}`;
        });

        // Determine the timeline range
        const dates = events.map(event => event.x);
        const minDate = new Date(Math.min.apply(null, dates));
        const maxDate = new Date(Math.max.apply(null, dates));
        minDate.setFullYear(minDate.getFullYear() - 1);
        maxDate.setFullYear(maxDate.getFullYear() + 6);

        // Prepare the dataset for Chart.js
        const dataset = {
            datasets: [{
                label: 'Program Timeline',
                data: events,
                backgroundColor: 'blue',
                pointRadius: 0, // Hide the scatter points
                pointHoverRadius: 0,
                showLine: false
            }]
        };

        // Get the context of the new canvas element
        const ctx = canvas.getContext('2d');

        // Define colors for each level
        const levelColors = {
            'Not Accreditable': '#FF7B7A',  // Red
            'Candidate': '#76FA97',        // Green
            'PSV': '#CCCCCC',              // Grey
            '1': '#FDC879',                // Yellow
            '2': '#FDC879',                // Yellow
            '3': '#FDC879',                // Yellow
            '4': '#FDC879'                 // Yellow
        };

        // Define abbreviations for each level
        const levelAbbreviations = {
            'Not Accreditable': 'NA',
            'Candidate': 'CAN',
            'PSV': 'PSV',
            '1': 'LVL 1',
            '2': 'LVL 2',
            '3': 'LVL 3',
            '4': 'LVL 4'
        };

        // Function to draw rounded rectangles with border
        function drawRoundedRectWithBorder(ctx, x, y, width, height, radius, borderColor) {
            ctx.beginPath();
            ctx.moveTo(x + radius, y);
            ctx.lineTo(x + width - radius, y);
            ctx.quadraticCurveTo(x + width, y, x + width, y + radius);
            ctx.lineTo(x + width, y + height - radius);
            ctx.quadraticCurveTo(x + width, y + height, x + width - radius, y + height);
            ctx.lineTo(x + radius, y + height);
            ctx.quadraticCurveTo(x, y + height, x, y + height - radius);
            ctx.lineTo(x, y + radius);
            ctx.quadraticCurveTo(x, y, x + radius, y);
            ctx.closePath();

            // Fill the rectangle
            ctx.fill();

            // Draw the border
            ctx.strokeStyle = borderColor;
            ctx.lineWidth = 1;
            ctx.stroke();
        }

        // Define a plugin to draw vertical lines with labels and borders
        const verticalLinePlugin = {
            id: 'verticalLinePlugin',
            beforeDraw: chart => {
                const { ctx, chartArea: { top, bottom }, scales: { x, y } } = chart;
                ctx.save();

                events.forEach(event => {
                    const level = event.label.split(';')[0];  // Extract the level from the label
                    ctx.fillStyle = levelColors[level] || 'black';  // Use black as default color if not found

                    const xPosition = x.getPixelForValue(event.x);
                    const lineWidth = 60;
                    const lineHeight = bottom - y.getPixelForValue(0);
                    const lineTop = y.getPixelForValue(0);

                    // Define margins
                    const marginBetweenLines = 10; // Space between the first and second vertical lines
                    const marginBelowSecondLine = 5; // Space below the second vertical line

                    // Calculate the offset for the first rectangle
                    const offset = lineHeight / 2 + marginBetweenLines; // Increase offset to move the first rectangle up

                    // Draw the first rectangle for the level, adjusted upward, with border
                    ctx.fillStyle = levelColors[level] || 'black';
                    drawRoundedRectWithBorder(ctx, xPosition - lineWidth / 2, lineTop - offset, lineWidth, 70, 5, '#AFAFAF');

                    // Draw the level abbreviation inside the first rectangle
                    ctx.fillStyle = 'black';  // Text color
                    ctx.font = 'bold 16px Arial';  // Bold font style
                    ctx.textAlign = 'center'; // Center the text
                    ctx.textBaseline = 'middle';
                    ctx.fillText(levelAbbreviations[level] || '', xPosition, lineTop - offset + 35); // Centered at 35 pixels

                    const secondRectOffset = -5; // Increase or decrease this value to move the rectangle

                    // Draw the second rectangle for the date with margin below, with border
                    const dateHeight = lineHeight * 0.6; // Increase the height of the second vertical line
                    ctx.fillStyle = '#FFFFFF';  // White background
                    drawRoundedRectWithBorder(ctx, xPosition - lineWidth / 2, lineTop + lineHeight / 2 - marginBelowSecondLine + secondRectOffset, lineWidth, dateHeight - marginBelowSecondLine, 5, '#AFAFAF');

                    // Draw the date inside the second rectangle, centered vertically
                    ctx.fillStyle = 'black';  // Text color
                    ctx.font = 'bold 14px Arial';  // Bold font style for date
                    ctx.textBaseline = 'middle'; // Center the text vertically
                    ctx.fillText(
                        new Date(event.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }), 
                        xPosition, 
                        lineTop + lineHeight / 2 - marginBelowSecondLine + secondRectOffset + (dateHeight - marginBelowSecondLine) / 2
                    );
                });

                ctx.restore();
            }
        };

        // Render the chart on the new canvas
        new Chart(ctx, {
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
                        enabled: false // Disable default tooltips
                    },
                    datalabels: {
                        display: false // Hide default data labels
                    }
                }
            },
            plugins: [verticalLinePlugin] // Add the vertical line plugin
        });
    });
}








        function closeAllSelect() {
            var selectItems = document.querySelector('.select-items');
            selectItems.style.display = 'none';
        }

        document.addEventListener('click', closeAllSelect);

        document.getElementById('exportPdfButton').addEventListener('click', function() {
    const charts = document.querySelectorAll('canvas');
    const programNames = document.querySelectorAll('#chartContainer h3');
    
    const images = [];
    let count = 0;
    
    charts.forEach((chart, index) => {
        html2canvas(chart).then(canvas => {
            images.push({
                name: programNames[index].innerText,
                data: canvas.toDataURL('image/png')
            });
            count++;
            if (count === charts.length) {
                console.log('All charts captured, sending to server');
                sendImagesToServer(images);
            }
        }).catch(err => console.error('Error capturing canvas:', err));
    });
});

function sendImagesToServer(images) {
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'program_timeline_pdf.php', true);
    xhr.setRequestHeader('Content-Type', 'application/json');
    xhr.onreadystatechange = function() {
        if (xhr.readyState == 4) {
            if (xhr.status == 200) {
                console.log('PDF generated:', xhr.responseText);
                downloadFile(xhr.responseText);
            } else {
                console.error('Failed to generate PDF:', xhr.statusText);
            }
        }
    };
    xhr.send(JSON.stringify(images));
}

function downloadFile(fileName) {
    const link = document.createElement('a');
    link.href = 'program_timeline_download.php?file=' + encodeURIComponent(fileName);
    link.download = 'program_history.pdf';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

    </script>
</body>
</html>
