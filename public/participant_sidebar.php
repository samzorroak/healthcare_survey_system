<aside class="main-sidebar sidebar-dark-primary elevation-4">
  <a href="#" class="brand-link">
    <span class="brand-text font-weight-light">Survey System</span>
  </a>

  <div class="sidebar">
    <nav class="mt-2">
      <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
        <?php
        $current_page = basename($_SERVER['PHP_SELF']); // Get the current page filename
        ?>

        <!-- Homepage Section -->
        <li class="nav-item">
          <a href="participant_dashboard.php" class="nav-link <?= ($current_page == 'participant_dashboard.php') ? 'active' : '' ?>">
            <i class="nav-icon fas fa-home"></i>
            <p>Homepage</p>
          </a>
        </li>

        <!-- My Surveys Section -->
        <li class="nav-item">
          <a href="participant_survey.php" class="nav-link <?= ($current_page == 'participant_survey.php') ? 'active' : '' ?>">
            <i class="nav-icon fas fa-clipboard-list"></i>
            <p>My Surveys</p>
          </a>
        </li>

        <!-- My Payouts Section -->
        <li class="nav-item">
          <a href="participant_payout.php" class="nav-link <?= ($current_page == 'participant_payout.php') ? 'active' : '' ?>">
            <i class="nav-icon fas fa-dollar-sign"></i>
            <p>My Payouts</p>
          </a>
        </li>

        <!-- Profile Section -->
        <li class="nav-item">
          <a href="participant_profile.php" class="nav-link <?= ($current_page == 'participant_profile.php') ? 'active' : '' ?>">
            <i class="nav-icon fas fa-user"></i>
            <p>My Profile</p>
          </a>
        </li>

        <!-- Settings Section -->
        <li class="nav-item">
          <a href="participant_setting.php" class="nav-link <?= ($current_page == 'participant_setting.php') ? 'active' : '' ?>">
            <i class="nav-icon fas fa-cogs"></i>
            <p>Settings</p>
          </a>
        </li>
      </ul>
    </nav>
  </div>
</aside>
