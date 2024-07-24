<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $prefix = $_POST['prefix'];
    $suffix = $_POST['suffix'];
    $first_name = $_POST['first_name'];
    $middle_initial = $_POST['middle_initial'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $college_id = $_POST['college'];
    $gender = $_POST['gender'];

    if ($gender == 'Other') {
        $gender = $_POST['custom_gender'];
    }

    if ($password !== $confirm_password) {
        echo "Passwords do not match!";
        exit;
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    include 'connection.php';

    // Function to generate unique user_id
    function generate_unique_number($conn, $table) {
        $sql_count_users = "SELECT COUNT(*) AS count FROM $table";
        $result_count_users = $conn->query($sql_count_users);
        $count_users = $result_count_users->fetch_assoc()['count'];

        $unique_number = str_pad($count_users + 1, 4, "0", STR_PAD_LEFT);
        return $unique_number;
    }

    // Fetch college details based on college_id
    $stmt_college = $conn->prepare("SELECT code, college_name FROM college WHERE code = ?");
    $stmt_college->bind_param("i", $college_code);
    $stmt_college->execute();
    $result_college = $stmt_college->get_result();

    if ($result_college->num_rows > 0) {
        $row_college = $result_college->fetch_assoc();
        $college = $row_college['college_name'];
    } else {
        echo "Invalid college selected.";
        exit;
    }

    $table = "internal_users"; // Table to insert into
    $unique_number = generate_unique_number($conn, $table);
    $user_id = $college_code . "-11-" . $unique_number;

    $stmt = $conn->prepare("INSERT INTO internal_users (user_id, prefix, suffix, first_name, middle_initial, last_name, email, password, college_id, gender) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    if (!$stmt) {
        die("Error preparing statement: " . $conn->error);
    }

    $stmt->bind_param("ssssssssss", $user_id, $prefix, $suffix, $first_name, $middle_initial, $last_name, $email, $hashed_password, $college_id, $gender);

    if ($stmt->execute()) {
        echo "Registration successful and pending for internal approval. Your User ID: " . $user_id . " <a href='login.php'>OK</a>";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>
