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

        <!-- Dashboard Section -->
        <li class="nav-item">
          <a href="dashboard.php" class="nav-link <?= ($current_page == 'dashboard.php') ? 'active' : '' ?>">
            <i class="nav-icon fas fa-tachometer-alt"></i>
            <p>Dashboard</p>
          </a>
        </li>

        <!-- Surveys Section -->
        <li class="nav-item">
          <a href="survey.php" class="nav-link <?= ($current_page == 'survey.php') ? 'active' : '' ?>">
            <i class="nav-icon fas fa-clipboard-list"></i>
            <p>Surveys</p>
          </a>
        </li>

        <!-- Participants Section -->
        <li class="nav-item">
          <a href="participant.php" class="nav-link <?= ($current_page == 'participant.php') ? 'active' : '' ?>">
            <i class="nav-icon fas fa-users"></i>
            <p>Participants</p>
          </a>
        </li>

        <!-- Clients Section -->
        <li class="nav-item">
          <a href="client.php" class="nav-link <?= ($current_page == 'client.php') ? 'active' : '' ?>">
            <i class="nav-icon fas fa-briefcase"></i>
            <p>Clients</p>
          </a>
        </li>

        <!-- Agreements Section -->
        <li class="nav-item">
          <a href="agreement.php" class="nav-link <?= ($current_page == 'agreement.php') ? 'active' : '' ?>">
            <i class="nav-icon fas fa-file-signature"></i>
            <p>Agreements</p>
          </a>
        </li>

        <!-- Payouts Section -->
        <li class="nav-item">
          <a href="payout.php" class="nav-link <?= ($current_page == 'payout.php') ? 'active' : '' ?>">
            <i class="nav-icon fas fa-dollar-sign"></i>
            <p>Payouts</p>
          </a>
        </li>

        <!-- Reports Section -->
        <li class="nav-item">
          <a href="report.php" class="nav-link <?= ($current_page == 'report.php') ? 'active' : '' ?>">
            <i class="nav-icon fas fa-chart-line"></i>
            <p>Reports</p>
          </a>
        </li>

        <!-- Settings Section -->
        <li class="nav-item">
          <a href="setting.php" class="nav-link <?= ($current_page == 'setting.php') ? 'active' : '' ?>">
            <i class="nav-icon fas fa-cogs"></i>
            <p>Settings</p>
          </a>
        </li>
      </ul>
    </nav>
  </div>
</aside>
