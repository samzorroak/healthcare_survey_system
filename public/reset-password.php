<?php
session_start();
include 'C:\xampp\htdocs\survey_system\config.php';
$error_message = null;
$success_message = null;
  
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $new_password = $_POST['new_password'];
  $confirm_password = $_POST['confirm_password'];
  $email = $_SESSION['email'];
  $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email");
  $stmt->execute(['email' => $email]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);

  // Validate new password
  if (empty($new_password) || empty($confirm_password)) {
      $error_message = 'Please fill in both fields.';
  } elseif ($new_password !== $confirm_password) {
      $error_message = 'Passwords do not match.'; 
  } elseif (password_verify($new_password, $user['password'])) {
      $error_message = 'New password cannot be the same as the old one.';
  } else {
      // Hash the new password
      $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

      // Update the password and clear the reset token
      $updateStmt = $conn->prepare("UPDATE users SET password = :password  WHERE email = :email");
      $updateStmt->execute(['password' => $hashed_password, 'email' => $email]);

      $success_message = 'Your password has been successfully reset. You can now log in with your new password.';
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Survey System | Reset Password</title>

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
  </style>
</head>
<body class="hold-transition login-page">
  <div class="login-box">
  <div class="card card-outline card-primary">
    <div class="card-header text-center">
      <a href="index.html" class="h1"><b>Survey System</b></a>
    </div>
    <div class="card-body">
      <p class="login-box-msg">Reset your password.</p>

      <form action="reset-password.php" method="post">
        <div class="input-group mb-3">
          <input type="password" name="new_password" class="form-control" placeholder="Enter New Password">
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-lock"></span>
            </div>
          </div>
        </div>
        <div class="input-group mb-3">
          <input type="password" name="confirm_password" class="form-control" placeholder="Re-enter New Password">
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-lock"></span>
            </div>
          </div>
        </div>

        <?php if (isset($error_message)) { ?>
          <p class="text-danger"><?php echo $error_message; ?></p>
        <?php } ?>

        <?php if (isset($success_message)) { ?>
          <p class="text-success"><?php echo $success_message; ?></p>
        <?php } ?>

        <div class="row">
          <div class="col-12">
            <button type="submit" name="submit" class="btn btn-primary btn-block">Reset password</button>
          </div>
          <!-- /.col -->
        </div>
      </form>

      <p class="mt-3 mb-1">
        <a href="login.php">Login</a>
      </p>
    </div>
    <!-- /.login-card-body -->
  </div>
</div>
<!-- /.login-box -->

<!-- jQuery -->
<script src="plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="dist/js/adminlte.min.js?v=3.2.0"></script>
</body>
</html>
