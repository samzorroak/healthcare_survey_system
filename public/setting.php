<?php
include 'header.php';
require_once 'EntityManager.php';

$conn = Database::getInstance();

$UserId = $_SESSION['user_id'];
$UserName = $_SESSION['user_name'] ?? 'User';
$UserPermissions = $_SESSION['permissions'] ?? [];
?>


<script src="report.js"></script>

<body class="hold-transition sidebar-mini">
    <div class="wrapper">
        <?php include 'navbar.php'; ?>
        <?php include 'sidebar.php'; ?>

        <div class="content-wrapper">
            <section class="content-header">
                <div class="container-fluid">
                    <h1>Settings</h1>
                </div>
            </section>

            <section class="content">
                <div class="container">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><b> Welcome, <?= htmlspecialchars($UserName) ?></b></h3>
                        </div>
                        <div class="card-body">
                            Work in Progress
                        </div>
                    </div>

                </div>
            </section>
        </div>
    </div>
</body>