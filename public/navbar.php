<!-- AdminLTE CSS -->
<link rel="stylesheet" href="dist/css/adminlte.min2.css">
<!-- Font Awesome -->
<link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">

<script>
  function confirmLogout(event) {
    var userConfirmation = confirm("Are you sure you want to logout?");
    
    if (userConfirmation) {
      // Proceed with logout
      window.location.href = 'logout.php'; // Redirect to logout.php
    } else {
      // Prevent logout if Cancel is selected
      event.preventDefault(); // Prevent the default action
      return false;
    }
  }
</script>


<nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
        </li>
        <li class="nav-item">
            <a href="user.php" class="nav-link">Users</a>
        </li>
        <li class="nav-item">
            <a href="contact.php" class="nav-link">Contact</a>
        </li>      
    </ul>

    <!-- Navbar Right Section -->
    <ul class="navbar-nav ml-auto">
        <li class="nav-item">
            <span class="navbar-text mr-3">
                Welcome, <?= htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>
            </span>
        </li>
        <li class="nav-item">
            <button onclick="confirmLogout()" class="btn btn-danger">Logout</button>
        </li>
    </ul>
</nav>


