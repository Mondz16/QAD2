<?php
include 'connection.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user details
$sql_user = "SELECT first_name, middle_initial, last_name, email, college_code, profile_picture FROM internal_users WHERE user_id = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("s", $user_id);
$stmt_user->execute();
$stmt_user->bind_result($first_name, $middle_initial, $last_name, $email, $college_code, $profile_picture);
$stmt_user->fetch();
$stmt_user->close();

// Fetch college name
$sql_college = "SELECT college_name FROM college WHERE code = ?";
$stmt_college = $conn->prepare($sql_college);
$stmt_college->bind_param("s", $college_code);
$stmt_college->execute();
$stmt_college->bind_result($college_name);
$stmt_college->fetch();
$stmt_college->close();

$accreditor_type = (substr($user_id, 3, 2) == '11') ? 'Internal Accreditor' : 'External Accreditor';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Internal</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" />
</head>

<body>
    <div class="wrapper">
        <div class="hair" style="height: 15px; background: #9B0303;"></div>
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
                                    <span class="datausep" style="color: rgb(151, 57, 57); font-weight: 600; font-size: 18px;">USeP.</span></h>
                            </div>
                            <h>Accreditor Portal</h>
                        </div>
                    </div>
                </div>

                <div class="headerRight">
                    <div class="SDMD">
                        <div class="headerRightText">
                            <h style="color: rgb(87, 87, 87); font-weight: 600; font-size: 16px;">Quality Assurance Division</h>
                        </div>
                        <div style="height: 0px; width: 16px;"></div>
                        <div style="height: 32px; width: 1px; background: #E5E5E5"></div>
                        <div style="height: 0px; width: 16px;"></div>
                        <img class="USeP" src="images/SDMDLogo.png" height="36">
                    </div>
                </div>
            </div>
        </div>
        <div style="height: 1px; width: 100%; background: #E5E5E5"></div>
        <header class="site-header">
            <nav>
                <ul class="nav-list">
                    <li class="btn"><a href="internal.php">Home</a></li>
                    <li class="btn"><a href="internal_notification.php">Notifications</a></li>
                    <li class="btn"><a href="internal_orientation.php">Orientation</a></li>
                    <li class="btn"><a href="internal_assessment.php">Assessment</a></li>
                    <li class="btn"><a href="logout.php">Log Out</a></li>
                </ul>
            </nav>
        </header>
        <div style="height: 30px; width: 0px;"></div>
        <div class="container">
            <div class="profile">
                <img src="<?php echo $profile_picture; ?>" alt="Profile Picture" class="profile-picture">
                <div class="profile-details">
                    <p class="profile-name"><?php echo $first_name . ' ' . $middle_initial . '. ' . $last_name; ?></p>
                    <p class="profile-type"><?php echo $college_name; ?> (<?php echo $accreditor_type; ?>)</p>
                    <div class="button-group">
                        <button class="edit-button" onclick="document.getElementById('editModal').style.display='block'">Edit Profile</button>
                        <button class="transfer-button" onclick="document.getElementById('transferModal').style.display='block'">Request College Transfer</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Profile Modal -->
        <div id="editModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="document.getElementById('editModal').style.display='none'">&times;</span>
                <form class="edit-form" action="update_profile.php" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                    <input type="hidden" name="existing_profile_picture" value="<?php echo $profile_picture; ?>">
                    <input type="text" name="first_name" value="<?php echo $first_name; ?>" placeholder="First Name">
                    <input type="text" name="middle_initial" value="<?php echo $middle_initial; ?>" placeholder="Middle Initial">
                    <input type="text" name="last_name" value="<?php echo $last_name; ?>" placeholder="Last Name">
                    <input type="email" name="email" value="<?php echo $email; ?>" placeholder="Email">
                    <input type="file" name="profile_picture" accept="image/*">
                    <button type="submit">Save</button>
                </form>
            </div>
        </div>

        <!-- Request College Transfer Modal -->
        <div id="transferModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="document.getElementById('transferModal').style.display='none'">&times;</span>
                <form class="edit-form" action="update_college.php" method="post">
                    <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                    <input type="hidden" name="first_name" value="<?php echo $first_name; ?>">
                    <input type="hidden" name="middle_initial" value="<?php echo $middle_initial; ?>">
                    <input type="hidden" name="last_name" value="<?php echo $last_name; ?>">
                    <input type="hidden" name="email" value="<?php echo $email; ?>">
                    <select name="college_code">
                        <?php
                        $sql_colleges = "SELECT code, college_name FROM college ORDER BY college_name";
                        $result_colleges = $conn->query($sql_colleges);
                        while ($row_college = $result_colleges->fetch_assoc()) {
                            echo "<option value='{$row_college['code']}'>{$row_college['college_name']}</option>";
                        }
                        ?>
                    </select>
                    <button type="submit">Request Transfer</button>
                </form>
            </div>
        </div>
    </div>
    <script>
        // Close the modal when clicking outside of the modal content
        window.onclick = function(event) {
            if (event.target == document.getElementById('editModal')) {
                document.getElementById('editModal').style.display = "none";
            }
            if (event.target == document.getElementById('transferModal')) {
                document.getElementById('transferModal').style.display = "none";
            }
        }
    </script>
</body>
</html>
