<?php
session_start();
include 'C:\xampp\htdocs\survey_system\config.php'; // Include database connection
$error_message = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);

    // Validate email
    if (empty($email)) {
        $error_message = 'Please enter your email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Invalid email format. Please enter a valid email.';
    } else {
        // Check if the email exists in the database
        try {
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                header("Location: reset-password.php");
                $_SESSION['email'] = $email;
            } else {
                $error_message = 'No account found with this email address.';
            }
        } catch (Exception $e) {
            // Catch any database or other errors and display a user-friendly message
            $error_message = 'An error occurred. Please try again later.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Survey System | Forgot Password</title>
  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
  <!-- icheck bootstrap -->
  <link rel="stylesheet" href="plugins/icheck-bootstrap/icheck-bootstrap.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="dist/css/adminlte.min2.css">
    <style>
        .login-page {
            height: 100vh;
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('assets/bg1.jpg') no-repeat center center;
            background-size: cover;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center; 
        }
        .login-box {
            width: 100%;
            max-width: 400px;
        }
        .login-card {
            border-radius: 10px;
            box-shadow: 0px 0px 15px rgba(0, 0, 0, 0.1);
        }
        .login-card-body {
            padding: 20px;
        }
        .input-group-append .input-group-text {
            background-color: #fff;
        }
        .input-group input {
            border-radius: 5px;
        }
        .btn {
            border-radius: 5px;
            width: 100%;
        }
        .mb-1, .mb-0 {
            text-align: center;
        }
        .mb-1 a, .mb-0 a {
            color: #007bff;
        }
        .mb-1 a:hover, .mb-0 a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body class="hold-transition login-page">
    
    <div class="login-box">
        <div class="card login-card">
            <div class="card-body login-card-body">
                <h4 class="login-box-msg text-center">Forgot Password</h4>

                <!-- Display Error Message -->
                <?php if ($error_message): ?>
                    <div class="alert alert-danger text-center">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
       
                <!-- Forgot Password Form -->
                <form action="forgot-password.php" method="POST">
                    <div class="input-group mb-3">
                        <input type="email" class="form-control" placeholder="Enter your email" name="email" required>
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-envelope"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="row">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary btn-block">Submit</button>
                        </div>
                    </div>
                </form>

                <!-- Back to Login Link -->
                <p class="mb-0">
                    <a href="login.php" class="text-center">Back to Login</a>
                </p>
            </div>
        </div>
    </div>

<!-- jQuery -->
<script src="plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="dist/js/adminlte.min.js?v=3.2.0"></script>
</body>
</html>
