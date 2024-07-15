<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Page</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/pagestyle.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row top-bar"></div>
        <div class="row header">
            <div class="col-12 col-md-6 d-flex align-items-center">
                <img src="images/USePLogo.png" alt="USeP Logo">
                <div class="divider"></div>
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
            <div class="col-6 d-none d-md-flex align-items-center justify-content-end">
                <div class="text-right"></div>
                <span>Quality Assurance Division</span>
                <div class="divider"></div>
                <div class="vertical-line"></div>
                <div class="divider"></div>
                <img src="images/sdmdlogo.png" alt="USeP Logo">
            </div>
        </div>

        <div class="row justify-content-left content">
            <div style="height: 0px; width: 45px;"></div>
            <div class="col-md-6 col-lg-4">
                <h1>Welcome Back!</h1>
                <p>Please login to get started.</p>
                <form action="login_process.php" method="post">
                    <input type="text" class="form-control" name="user_id" placeholder="User ID" required>
                    <input type="password" class="form-control passwordText" name="password" placeholder="Password" required>
                    <div class="form-check d-flex justify-content-between align-items-center">
                        <div>
                            <input type="checkbox" id="showPasswordCheckbox">
                            <label class="form-check-label" for="showPasswordCheckbox">Show Password</label>
                        </div>
                        <a href="#">Forgot password?</a>
                    </div>
                    <div class="row mt-3">
                        <div class="col-6">
                            <a href="register.php" class="btn btn-block" style="color: black; border: 1px solid; border-radius: 10px; outline: unset; text-decoration: none; cursor: pointer; padding: 16px; font-size: 16px;">SIGN UP</a>
                        </div>
                        <div class="col-6">
                            <button type="submit" value="Login" class="btn btn-block" style="color: white; background: linear-gradient(275.52deg, #FF7A7A 0.28%, #E6A33E 100%); border: unset; outline: unset; text-decoration: none; cursor: pointer; border-radius: 10px; padding: 16px; font-size: 16px;">LOG IN</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <footer class="text-center mt-5">
            <p>Copyright © 2024. All Rights Reserved.</p>
            <a href="#">Terms of Use</a> | <a href="#">Privacy Policy</a>
        </footer>
    </div>
    <script>
        document.getElementById('showPasswordCheckbox').addEventListener('change', function() {
            var passwordInput = document.querySelector('.passwordText');
            if (this.checked) {
                passwordInput.type = 'text';
            } else {
                passwordInput.type = 'password';
            }
        });
    </script>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
</body>
</html>
