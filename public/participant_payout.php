<?php
session_start();
include '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'participant') {
    header("Location: login.php");
    exit();
}

$participantId = $_SESSION['user_id'];
$participantName = $_SESSION['user_name'] ?? 'Participant';

// Fetch payout data
try {
    $conn = Database::getInstance();
    $stmt = $conn->prepare("
        SELECT s.title, s.description, sp.payment_amount, sp.completed, sp.payment_status
        FROM survey_participants sp
        INNER JOIN surveys s ON sp.survey_id = s.id
        WHERE sp.participant_id = ?
    ");
    $stmt->execute([$participantId]);
    $payouts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error fetching payouts: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Payouts</title>
    <link rel="stylesheet" href="dist/css/adminlte.min2.css">
    <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
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
    </style>
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
    <!-- Navbar -->
    <?php include 'participant_navbar.php'; ?>
    <!-- Sidebar -->
    <?php include 'participant_sidebar.php'; ?>

    <div class="content-wrapper">
        <!-- Content Header -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>Payout Summary - <?php echo htmlspecialchars($participantName); ?></h1>
                    </div>
                </div>
            </div>
        </section>

        <!-- Main Content -->
        <section class="content">
            <div class="container-fluid">
                <div class="card dashboard-card">
                    <div class="card-header">
                        Your Completed Surveys & Payments
                    </div>
                    <div class="card-body table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Survey Title</th>
                                    <th>Description</th>
                                    <th>Amount</th>
                                    <th>Completed</th>
                                    <th>Payment Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($payouts) > 0): ?>
                                    <?php foreach ($payouts as $payout): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($payout['title']); ?></td>
                                            <td><?php echo htmlspecialchars($payout['description']); ?></td>
                                            <td>₹<?php echo number_format($payout['payment_amount'], 2); ?></td>
                                            <td>
                                                <?php echo $payout['completed'] ? '<span class="text-success">Yes</span>' : '<span class="text-danger">No</span>'; ?>
                                            </td>
                                            <td>
                                                <?php
                                                    $status = $payout['payment_status'] ?? 'Pending';
                                                    echo $status === 'Done'
                                                        ? '<span class="badge badge-success">Paid</span>'
                                                        : '<span class="badge badge-warning">Pending</span>';
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">No surveys or payouts found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer text-muted">
                        Payout details are shown only for completed surveys.
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<!-- Scripts -->
<script src="plugins/jquery/jquery.min.js"></script>
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="dist/js/adminlte.min.js"></script>
</body>
</html>
