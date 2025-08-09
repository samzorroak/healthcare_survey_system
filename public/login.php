
<?php
session_start();
include '../config.php'; // Ensure config.php is properly included

class Login {
    private $conn;

    public function __construct() {
        $this->conn = Database::getInstance(); // Get the database connection instance
    }

    public function authenticateUser($emailOrPan, $password) {
        $emailPattern = "/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/";
        $panPattern = "/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/";
        $userType = null;

        if (preg_match($emailPattern, $emailOrPan)) {
            $stmt = $this->conn->prepare("SELECT `id`, `name` as `full_name`, `email`, `password`, `permissions` FROM users WHERE email = ?");
            $userType = 'admin';
        } elseif (preg_match($panPattern, $emailOrPan)) {
            $stmt = $this->conn->prepare("SELECT * FROM participants WHERE pan = ?");
            $userType = 'participant';
        } else {
            return ["success" => false, "message" => "Invalid Email or PAN format."];
        }

        try {
            $stmt->execute([$emailOrPan]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                return ["success" => false, "message" => "Account not found."];
            }

            // Password Check
            if ($userType === 'admin') {
                // Admin password check
                if (password_verify($password, $user['password'])) {
                    $this->setSession($user, 'admin');
                    header('Location: dashboard.php');
                    exit();
                }
            } else {
                // Participant password check logic
                $expectedPassword = substr($user['pan'], 0, 4) . date('Y', strtotime($user['dob']));
                if ($password === $expectedPassword) {
                    $this->setSession($user, 'participant');
                    header('Location: participant_dashboard.php');
                    exit();
                }
            }

            return ["success" => false, "message" => "Invalid login credentials."];
        } catch (Exception $e) {
            return ["success" => false, "message" => "Error during login: " . $e->getMessage()];
        }
    }

    private function setSession($user, $userType) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'] ?? null; 
        $_SESSION['user_name'] = $user['full_name'] ?? null;
        $_SESSION['user_type'] = $userType;
        if ($userType === 'admin') {
            $_SESSION['permissions'] = $user['permissions'] ?? []; // Store permissions for admin users
        }
    }
}

// Handle login request
$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailOrPan = trim($_POST['email']);
    $password = trim($_POST['password']);

    $login = new Login();
    $response = $login->authenticateUser($emailOrPan, $password);
    
    if (!$response['success']) {
        $error = $response['message'];
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Survey System | Log in</title>

  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
  <!-- icheck bootstrap -->
  <link rel="stylesheet" href="plugins/icheck-bootstrap/icheck-bootstrap.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="dist/css/adminlte.min2.css">
  <style>
    .error-message {
      color: red;
      font-size: 14px;
      display: none;
    }
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
      <p class="login-box-msg">Sign in to start your session</p>

      <form action="login.php" method="post" onsubmit="return validateInput()">
        <div class="input-group mb-3">
          <input type="text" class="form-control" placeholder="Email/PAN Card" name="email" id="emailOrPan" required>
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-envelope"></span>
            </div>
          </div>
        </div>
        <p class="error-message" id="error-message">Invalid Email or PAN format</p>

        <div class="input-group mb-3">
          <input type="password" class="form-control" placeholder="Password" name="password" required>
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-lock"></span>
            </div>
          </div>
        </div>
        
        <div class="row">
          <div class="col">
            <button type="submit" class="btn btn-primary btn-block">Sign In</button>
          </div>
        </div>
      </form>

      <p class="mb-1">
        <a href="forgot-password.php">I forgot my password</a>
      </p>

      <?php if (!empty($error)) { ?>
        <p class="text-danger"><?php echo $error; ?></p>
      <?php } ?>
    </div>
  </div>
</div>

<!-- jQuery -->
<script src="plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="dist/js/adminlte.min.js?v=3.2.0"></script>

<script>
  function validateInput() {
    let input = document.getElementById("emailOrPan").value.trim();
    let errorMessage = document.getElementById("error-message");

    let emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
    let panPattern = /^[A-Z]{5}[0-9]{4}[A-Z]{1}$/;

    if (emailPattern.test(input) || panPattern.test(input)) {
        errorMessage.style.display = "none";
        return true;
    } else {
        errorMessage.style.display = "block";
        return false;
    }
}

</script>
</body>
</html>
