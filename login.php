<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Page</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/pagestyle.css" rel="stylesheet">
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
            <div class="col-6 d-none d-md-flex align-items-center justify-content-end">
                <div class="text-right"></div>
                <span>Quality Assurance Division</span>
                <div class="divider"></div>
                <div class="vertical-line"></div>
                <div class="divider"></div>
                <img src="images/sdmdlogo.png" alt="USeP Logo">
            </div>
        </div>

        <div class="row justify-content-start">
            <div class="col-md-2"></div>
            <div class="col-md-6 col-lg-4 mt-5">
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
                    <div class="container mt-3">
                        <div class="row mt-3 justify-content-start">
                            <button type="submit" value="Login" class="pobtn btn btn-block me-md-2 col-md-4 col-12 mb-3 mb-md-0">LOG IN</button>
                            <a href="register.php" class="nebtn btn btn-block col-md-4 col-12">SIGN UP</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <footer class="row text-left mt-5">
            <div class="col-2"></div>
            <div class="col">
                <p>Copyright © 2024. All Rights Reserved.</p>
                <a href="#">Terms of Use</a> | <a href="#">Privacy Policy</a>
            </div>
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