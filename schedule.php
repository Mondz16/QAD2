<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule List</title>
    <link rel="stylesheet" href="schedule_style.css">
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
            <th class="table_header" colspan="2">
                Schedule List
            </th>
            <th class="button-container">
                <div>
                    <button onclick="location.href='add_schedule.php'">Add Schedule</button>
                </div>
            </th>
        </tr>
        <tr>
            <th>College</th>
            <th>Total Schedules</th>
            <th>Action</th>
        </tr>
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
                echo "<td><button onclick="."location.href='schedule_college.php?college=" . urlencode($row["college_name"]) . "'>View</button></td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='2'>No colleges found</td></tr>";
        }

        $conn->close();
        ?>
    </table>
    </div>
</body>

</html>
