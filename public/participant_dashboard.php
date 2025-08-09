<?php
session_start();
include '../config.php';

class ParticipantDashboard {
    private $conn;
    public $completed_surveys;
    public $pending_surveys;

    public function __construct() {
        $this->conn = Database::getInstance(); // Get database connection instance
        $this->fetchStatistics();
    }

    private function fetchStatistics() {
        $participantId = $_SESSION['user_id'] ?? 0;

        try {
            $this->completed_surveys = $this->fetchCount("SELECT COUNT(*) FROM survey_participants WHERE participant_id = ? AND completed = '1'", [$participantId]);
            $this->pending_surveys = $this->fetchCount("SELECT COUNT(*) FROM survey_participants WHERE participant_id = ? AND completed = '0'", [$participantId]);
        } catch (Exception $e) {
            die('Error fetching data: ' . $e->getMessage());
        }
    }

    private function fetchCount($query, $params) {
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }
}

// If the participant is not logged in, redirect to login page
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'participant') {
    header("Location: login.php");
    exit();
}

// Prevent caching so back button won't take users to the dashboard after logout
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Create dashboard object to retrieve stats
$dashboard = new ParticipantDashboard();
$participantName = $_SESSION['user_name'] ?? 'Participant';  // Display participant's name
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Survey System - Participant Dashboard</title>
    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="dist/css/adminlte.min2.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
    <!-- Custom CSS -->
    <style>
        .dashboard-card {
            margin-bottom: 20px;
        }
        .dashboard-card .card-header {
            background-color: #f4f6f9;
            font-weight: bold;
        }
        .card-footer {
            background-color: #f9f9f9;
        }
        .card-body p {
            font-size: 16px;
            line-height: 1.5;
        }
    </style>
</head>
<body class="hold-transition sidebar-mini">
    
    <div class="wrapper">
        <!-- Navbar -->
        <?php include 'participant_navbar.php'; ?>

        <!-- Sidebar -->
        <?php include 'participant_sidebar.php'; ?>

        <!-- Content Wrapper -->
        <div class="content-wrapper">
            <!-- Content Header -->
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1>Welcome, <?php echo htmlspecialchars($participantName); ?>!</h1>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Main content -->
            <section class="content">
                <div class="container-fluid">
                    <div class="row">
                        <!-- Participant Overview Card -->
                        <div class="col-lg-6 col-12">
                            <div class="card dashboard-card">
                                <div class="card-header">
                                    Your Surveys
                                </div>
                                <div class="card-body">
                                    <p><strong>Completed Surveys:</strong> <?php echo $dashboard->completed_surveys; ?></p>
                                    <p><strong>Pending Surveys:</strong> <?php echo $dashboard->pending_surveys; ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- Important Links/Actions -->
                        <div class="col-lg-6 col-12">
                            <div class="card dashboard-card">
                                <div class="card-header">
                                    Quick Actions
                                </div>
                                <div class="card-body">
                                    <ul>
                                        <li><a href="participant_survey.php">View Available Surveys</a></li>
                                        <li><a href="participant_payout.php">Check Your Payouts</a></li>
                                        <li><a href="participant_profile.php">Manage Profile</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Graphical Representations -->
                    <div class="row">
                        <div class="col-12">
                            <!-- Placeholder for charts or additional participant-specific content -->
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <!-- AdminLTE Scripts -->
    <script src="plugins/jquery/jquery.min.js"></script>
    <script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="dist/js/adminlte.min.js"></script>
</body>
</html>
