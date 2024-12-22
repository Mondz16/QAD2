<?php
session_start();
require 'vendor/autoload.php';  // Include PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Function to send OTP email
function sendOTPEmail($email, $otp) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'usepqad@gmail.com'; // Your email
        $mail->Password = 'ofcx jwfa ghkv hsgz'; // Your email password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Bypass SSL certificate verification
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        $mail->setFrom('usepqad@gmail.com', 'USeP - Quality Assurance Division');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Admin Login OTP';
        $mail->Body    = 'Your OTP for login is: <b>' . $otp . '</b>';

        $mail->send();
    } catch (Exception $e) {
        echo 'Message could not be sent. Mailer Error: ', $mail->ErrorInfo;
    }
}

function display_popup($message, $type, $redirect = 'login.php', $has_apply_cancel = false, $apply_redirect = '') {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login</title>
        <link rel="stylesheet" href="index.css">
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600&display=swap">
    </head>
    <body>
        <div id="errorPopup" class="popup" style="display: block;">
            <div class="popup-content">
                <div style="height: 50px; width: 0px;"></div>
                <img class="Error" src="images/Error.png" height="100">
                <div style="height: 20px; width: 0px;"></div>
                <div class="popup-text"><?php echo $message; ?></div>
                <div style="height: 50px; width: 0px;"></div>
                <?php if ($has_apply_cancel): ?>
                    <button class="cancel" onclick="window.location.href='login.php'">Cancel</button>
                    <a href="<?php echo $apply_redirect; ?>" class="apply">Apply</a>
                <?php else: ?>
                    <a href="javascript:void(0);" class="okay" id="closePopup">Okay</a>
                <?php endif; ?>
                <div style="height: 100px; width: 0px;"></div>
                <div class="hairpop-up"></div>
            </div>
        </div>
        <script>
            document.getElementById('closePopup').addEventListener('click', function() {
                window.location.href = '<?php echo $redirect; ?>';
            });

            document.getElementById('closeErrorBtn').addEventListener('click', function() {
                window.location.href = '<?php echo $redirect; ?>';
            });

            window.addEventListener('click', function(event) {
                if (event.target == document.getElementById('errorPopup')) {
                    window.location.href = '<?php echo $redirect; ?>';
                }
            });
        </script>
    </body>
    </html>
    <?php
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_POST['user_id'];
    $password = $_POST['password'];

    include 'connection.php';

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Check admin table
    $stmt = $conn->prepare("SELECT * FROM admin WHERE user_id = ?");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $result_admin = $stmt->get_result();

    if ($result_admin->num_rows == 1) {
        $admin = $result_admin->fetch_assoc();

        if (password_verify($password, $admin['password'])) {
            // Check if email is a placeholder value
            if ($admin['email'] === '') {
                // Redirect directly if email is a placeholder
                $_SESSION['user_id'] = $admin['user_id'];  // Set session variable to 'user_id'
                header("Location: dashboard.php");
                exit;
            }

            $otp = rand(100000, 999999); // Generate OTP
            $hashed_otp = password_hash($otp, PASSWORD_DEFAULT); // Hash OTP

            // Update OTP in admin table
            $stmt = $conn->prepare("UPDATE admin SET otp = ?, otp_created_at = NOW() WHERE user_id = ?");
            $stmt->bind_param("ss", $hashed_otp, $user_id);
            if ($stmt->execute()) {
                // Send OTP to admin email
                sendOTPEmail($admin['email'], $otp);

                $_SESSION['user_id_verify'] = $admin['user_id'];  // Set session variable to 'user_id'
                header("Location: admin_verify_otp.php"); // Redirect to OTP verification page
                exit;
            } else {
                echo "Failed to update OTP in database.";
            }
        }
    } else {
        $message = "No admin found with this user ID.";
    }

    // Function to check user in a specific table
    function check_user($conn, $table, $user_id, $password) {
        $stmt = $conn->prepare("SELECT * FROM $table WHERE user_id = ?");
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            if (password_verify($password, $row['password'])) {
                return $row;
            }
        }
        
        return false;
    }

    // Check internal_users table
    $internal_user = check_user($conn, 'internal_users', $user_id, $password);
    if ($internal_user) {
        // Extract the second and third parts of the user_id
        $bb_cccc = substr($user_id, 3); // Extracts '11-0001'

        // Prepare statement to find users with the same second and third parts, but different user_id
        $stmt = $conn->prepare("SELECT status FROM internal_users WHERE SUBSTRING(user_id, 4) = ?");
        $stmt->bind_param("s", $bb_cccc);
        $stmt->execute();
        $result = $stmt->get_result();

        // Initialize flags for status
        $status_active = false;
        $status_pending = false;

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Check for 'active' and 'pending' statuses
                if ($row['status'] == 'active') {
                    $status_active = true;
                }
                if ($row['status'] == 'pending') {
                    $status_pending = true;
                }
            }

            // If both statuses are found, display the message
            if ($status_active && $status_pending) {
                $message = "This account with User ID: $user_id is currently applying for college transfer. Please wait for the admin to approve.";
                display_popup($message, "error");
                exit;
            }
        }

        if ($internal_user['otp'] != 'verified') {
            header("Location: verify_otp.php?email=" . urlencode($internal_user['email']) . "&type=internal");
            exit;
        }
        if ($internal_user['status'] == 'active') {
            $_SESSION['user_id'] = $internal_user['user_id'];
            header("Location: internal.php");
            exit;
        } elseif ($internal_user['status'] == 'inactive') {
            $message = "This account with User ID: $user_id is inactive.<br>Would you like to apply again?";
            display_popup($message, "error", "login.php", true, "login_process_reactivation.php?type=internal&user_id=$user_id");
            exit;
        } else {
            $message = "This account with User ID: $user_id is pending.<br>Please wait for the admin to approve.";
            display_popup($message, "error");
            exit;
        }
    }

    // Check external_users table
    $external_user = check_user($conn, 'external_users', $user_id, $password);
    if ($external_user) {
        if ($external_user['otp'] != 'verified') {
            header("Location: verify_otp.php?email=" . urlencode($external_user['email']) . "&type=external");
            exit;
        }
        if ($external_user['status'] == 'active') {
            $_SESSION['user_id'] = $external_user['user_id'];
            header("Location: external.php");
            exit;
        } elseif ($external_user['status'] == 'inactive') {
            $message = "This account with User ID: $user_id is inactive.<br>Would you like to apply again?";
            display_popup($message, "error", "login.php", true, "login_process_reactivation.php?type=external&user_id=$user_id");
            exit;
        } else {
            $message = "This account with User ID: $user_id is pending.<br>Please wait for the admin to approve.";
            display_popup($message, "error");
            exit;
        }
    }

    // If no match found in any table
    display_popup("User not found or password incorrect", "error");

    $conn->close();
}
?>
