<!-- AdminLTE CSS -->
<link rel="stylesheet" href="dist/css/adminlte.min2.css">
<!-- Font Awesome -->
<link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">

<script>
  function confirmLogout(event) {
    var userName = "<?= isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'User' ?>";
    var userConfirmation = confirm("Hello " + userName + ", are you sure you want to logout?");
    
    if (userConfirmation) {
      window.location.href = 'logout.php'; // Redirect to logout page
    } else {
      event.preventDefault(); // Prevent logout if canceled
      return false;
    }
  }
</script>

<nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
        </li>
    </ul>

    <!-- Navbar Right Section -->
    <ul class="navbar-nav ml-auto">
        <li class="nav-item">
            <span class="navbar-text mr-3">
                Welcome, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Participant'); ?>
            </span>
        </li>
        <li class="nav-item">
            <button onclick="confirmLogout()" class="btn btn-danger">Logout</button>
        </li>
    </ul>
</nav>
