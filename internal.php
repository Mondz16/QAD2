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
    <title>Internal Accreditor</title>
    <link rel="stylesheet" href="loginstyle.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" />
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
            margin: 0;
            padding: 0;
        }
        .wrapper {
            text-align: center;
            padding: 20px;
            background-color: #fff;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .wrapper header {
            font-size: 24px;
            margin-bottom: 10px;
        }
        .site-header {
            background-color: #333;
            color: #fff;
            padding: 10px 0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .site-header nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
            text-align: center;
        }
        .site-header nav ul li {
            display: inline;
            margin: 0 10px;
        }
        .site-header nav ul li a {
            color: #fff;
            text-decoration: none;
            padding: 10px 20px;
            background-color: #444;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .site-header nav ul li a:hover {
            background-color: #555;
        }
        .profile {
            padding: 20px;
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .profile h2 {
            font-size: 22px;
            margin-bottom: 10px;
        }
        .profile p {
            margin: 5px 0;
        }
        .profile img {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            object-fit: cover;
        }
        .edit-button, .trasnfer-button {
            background-color: #007bff;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }
        .edit-button:hover, .transfer-button:hover {
            background-color: #0056b3;
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
            background-color: rgba(0, 0, 0, 0.4);
        }
        .modal-content {
            background-color: #fff;
            margin: 10% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 600px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }
        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
        .edit-form input,
        .edit-form select {
            display: block;
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .edit-form button {
            background-color: #28a745;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }
        .edit-form button:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <header>Internal Accreditor</header>
    </div>
    <header class="site-header">
        <nav>
            <ul class="nav-list">
                <li class="btn"><a href="internal.php">Home</a></li>
                <li class="btn"><a href="internal_notification.php">Notifications</a></li>
                <li class="btn"><a href="internal_assessment.php">Assessment</a></li>
                <li class="btn"><a href="logout.php">Log Out</a></li>
            </ul>
        </nav>
    </header>
    <div class="container">
        <div class="profile">
            <h2>Profile</h2>
            <p><strong>Name:</strong> <?php echo $first_name . ' ' . $middle_initial . '. ' . $last_name; ?></p>
            <p><strong>Type:</strong> <?php echo $accreditor_type; ?></p>
            <p><strong>College:</strong> <?php echo $college_name; ?></p>
            <p><strong>Email:</strong> <?php echo $email; ?></p>
            <p><strong>Profile Picture:</strong></p>
            <img src="<?php echo $profile_picture; ?>" alt="Profile Picture">
            <button class="edit-button" onclick="document.getElementById('editModal').style.display='block'">Edit Profile</button>
            <button class="transfer-button" onclick="document.getElementById('transferModal').style.display='block'">Request College Transfer</button>
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