<?php
session_start();
include '../config.php'; // Ensure config.php is properly included

class Dashboard
{
    private $conn;
    public $active_surveys;
    public $completed_surveys;
    public $pending_surveys;
    public $total_users;
    public $completed_participants;
    public $incompleted_participants;
    public $pending_participants;

    public function __construct()
    {
        $this->conn = Database::getInstance(); // Get database connection instance
        $this->fetchStatistics();
    }

    private function fetchStatistics()
    {
        try {
            $this->active_surveys = $this->fetchCount("SELECT COUNT(*) FROM surveys WHERE status = 'active'");
            $this->completed_surveys = $this->fetchCount("SELECT COUNT(*) FROM surveys WHERE status = 'inactive'");
            $this->pending_surveys = $this->fetchCount("SELECT COUNT(*) FROM surveys WHERE status = 'active'");
            $this->total_users = $this->fetchCount("SELECT COUNT(*) FROM users");
            $this->completed_participants = $this->fetchCount("SELECT COUNT(DISTINCT id) FROM survey_participants WHERE payment_status = 'Done'");
            $this->incompleted_participants = $this->fetchCount("SELECT COUNT(DISTINCT id) FROM survey_participants WHERE payment_status = 'Pending' AND completed = 0");
            $this->pending_participants = $this->fetchCount("SELECT COUNT(DISTINCT id) FROM survey_participants WHERE payment_status = 'Pending' AND completed = 1");
        } catch (Exception $e) {
            die('Error fetching data: ' . $e->getMessage());
        }
    }

    private function fetchCount($query)
    {
        $stmt = $this->conn->query($query);
        return $stmt->fetchColumn();
    }
}

// If the user is not logged in, redirect to login page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userName = $_SESSION['user_name'] ?? 'User';

// Create dashboard object to retrieve stats
$dashboard = new Dashboard();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Survey System - Dashboard</title>
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
        <?php include 'navbar.php'; ?>
        <?php include 'sidebar.php'; ?>

        <div class="content-wrapper">
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1>Welcome, <?= htmlspecialchars($userName) ?></h1>
                        </div>
                    </div>
                </div>
            </section>
            <section class="content">
                <div class="container-fluid">

                    <div class="row">
                        <!-- Completed Participants -->
                        <div class="col-lg-4 col-6">
                            <div class="small-box bg-primary">
                                <div class="inner">
                                    <h3><?php echo $dashboard->completed_participants; ?></h3>
                                    <p>Completed Payments</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-hand-holding-usd"></i>
                                </div>
                                <a href="payout.php" class="small-box-footer">
                                    View Participants <i class="fas fa-arrow-circle-right"></i>
                                </a>
                            </div>
                        </div>

                        <!-- Pending Participants -->
                        <div class="col-lg-4 col-6">
                            <div class="small-box bg-danger">
                                <div class="inner">
                                    <h3><?php echo $dashboard->pending_participants; ?></h3>
                                    <p>Pending Payments</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-hourglass-half"></i>
                                </div>
                                <a href="payout.php" class="small-box-footer">
                                    View Participants <i class="fas fa-arrow-circle-right"></i>
                                </a>
                            </div>
                        </div>
                        

                        <!-- Incompleted Participants -->
                        <div class="col-lg-4 col-6">
                            <div class="small-box bg-secondary">
                                <div class="inner">
                                    <h3><?php echo $dashboard->incompleted_participants; ?></h3>
                                    <p>Incompleted Survey Participants</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-spinner"></i>
                                </div>
                                <a href="payout.php" class="small-box-footer">
                                    View Participants <i class="fas fa-arrow-circle-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Active Surveys -->
                        <div class="col-lg-4 col-6">
                            <div class="small-box bg-info">
                                <div class="inner">
                                    <h3><?php echo $dashboard->active_surveys; ?></h3>
                                    <p>Active Surveys</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-poll"></i>
                                </div>
                                <a href="survey.php" class="small-box-footer">
                                    View Surveys <i class="fas fa-arrow-circle-right"></i>
                                </a>
                            </div>
                        </div>

                        <!-- Completed Surveys -->
                        <div class="col-lg-4 col-6">
                            <div class="small-box bg-success">
                                <div class="inner">
                                    <h3><?php echo $dashboard->completed_surveys; ?></h3>
                                    <p>Completed Surveys</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <a href="survey.php" class="small-box-footer">
                                    View Surveys <i class="fas fa-arrow-circle-right"></i>
                                </a>
                            </div>
                        </div>

                        <!-- Total Users -->
                        <div class="col-lg-4 col-6">
                            <div class="small-box bg-warning">
                                <div class="inner">
                                    <h3><?php echo $dashboard->total_users; ?></h3>
                                    <p>Total Users</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <a href="user.php" class="small-box-footer">
                                    View Users <i class="fas fa-arrow-circle-right"></i>
                                </a>
                            </div>
                        </div>

                    </div>

                    <!-- Graphical Representations -->
                    <div class="row">
                        <!-- Survey Status Chart -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    Survey Status Overview
                                </div>
                                <div class="card-body">
                                    <canvas id="surveyChart"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Participant Payment Chart -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    Participant Payment Status
                                </div>
                                <div class="card-body">
                                    <canvas id="participantChart"></canvas>
                                </div>
                            </div>
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
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        // Use PHP variables directly inside JavaScript
        const surveyData = {
            labels: ['Active', 'Completed', 'Pending'],
            datasets: [{
                label: 'Number of Surveys',
                data: [
                    <?= $dashboard->active_surveys ?>,
                    <?= $dashboard->completed_surveys ?>,
                    <?= $dashboard->pending_surveys ?>
                ],
                backgroundColor: ['#28a745', '#17a2b8', '#ffc107'],
                borderColor: ['#28a745', '#17a2b8', '#ffc107'],
                borderWidth: 1
            }]
        };

        const participantData = {
            labels: ['Completed Payments', 'Pending Payments'],
            datasets: [{
                label: 'Participants',
                data: [
                    <?= $dashboard->completed_participants ?>,
                    <?= $dashboard->pending_participants ?>
                ],
                backgroundColor: ['#007bff', '#dc3545'],
                borderColor: ['#007bff', '#dc3545'],
                borderWidth: 1
            }]
        };

        // Render Survey Chart
        const ctxSurvey = document.getElementById('surveyChart').getContext('2d');
        new Chart(ctxSurvey, {
            type: 'bar',
            data: surveyData,
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        precision: 0
                    }
                }
            }
        });

        // Render Participant Chart
        const ctxParticipant = document.getElementById('participantChart').getContext('2d');
        new Chart(ctxParticipant, {
            type: 'pie',
            data: participantData,
            options: {
                responsive: true
            }
        });
    </script>
</body>
</html>