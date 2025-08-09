<?php
session_start();
include '../config.php'; // Ensure config.php is properly included

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'participant') {
    header("Location: login.php");
    exit();
}

$participantId = $_SESSION['user_id'];
$participantName = $_SESSION['user_name'] ?? 'Participant';

try {
    $conn = Database::getInstance();

    // Fetch all surveys assigned to the participant
    $stmt = $conn->prepare("
        SELECT sp.*, s.title, s.description, s.start_date, s.end_date, s.status AS survey_status
        FROM survey_participants sp
        JOIN surveys s ON sp.survey_id = s.id
        WHERE sp.participant_id = ?
        ORDER BY s.start_date DESC
    ");
    $stmt->execute([$participantId]);
    $surveys = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>My Surveys</title>
    <!-- AdminLTE CSS -->
    <link rel="stylesheet" href="dist/css/adminlte.min2.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
    <!-- jQuery Script -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.2/js/bootstrap.bundle.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <style>
        .survey-card {
            margin-bottom: 20px;
        }

        .survey-card .card-header {
            font-weight: bold;
        }

        .badge {
            font-size: 0.9em;
        }
        #consentModal, #agreementModal, #welcomeModal {
            overflow-y: auto;
        }
</style>
</head>

<body class="hold-transition sidebar-mini">
    <div class="wrapper">
        <?php include 'participant_navbar.php'; ?>
        <?php include 'participant_sidebar.php'; ?>

        <div class="content-wrapper">
            <section class="content-header">
                <div class="container-fluid">
                    <h1>My Surveys</h1>
                </div>
            </section>

            <section class="content">
                <div class="container-fluid">

                    <?php if (count($surveys) === 0): ?>
                        <p>No surveys assigned to you yet.</p>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($surveys as $survey):
                                $status = 'Available';
                                $now = date('Y-m-d');
                                if ($survey['completed'] == 1) {
                                    $status = 'Completed';
                                    $badge = 'success';
                                } elseif ($now < $survey['start_date']) {
                                    $status = 'Upcoming';
                                    $badge = 'warning';
                                } elseif ($now >= $survey['start_date'] && $now <= $survey['end_date']) {
                                    $status = 'Ongoing';
                                    $badge = 'primary';
                                } else {
                                    $status = 'Expired';
                                    $badge = 'danger';
                                }
                            ?>
                                <div class="col-md-6">
                                    <div class="card survey-card">
                                        <div class="card-header">
                                            <?php echo htmlspecialchars($survey['title']); ?>
                                            <span class="badge badge-<?php echo $badge; ?> float-right"><?php echo $status; ?></span>
                                        </div>
                                        <div class="card-body">
                                            <p><?php echo nl2br(htmlspecialchars($survey['description'])); ?></p>
                                            <p><strong>Start:</strong> <?php echo $survey['start_date']; ?> |
                                                <strong>End:</strong> <?php echo $survey['end_date']; ?>
                                            </p>
                                            <p><strong>Compensation:</strong> ₹<?php echo number_format($survey['payment_amount'], 2); ?></p>
                                        </div>
                                        <div class="card-footer">
                                            <?php if (($survey['completed'] == 1)): ?>
                                                <button class="btn btn-success btn-sm" disabled>Survey Completed</button>
                                            <?php elseif ($status == 'Upcoming'): ?>
                                                <button class="btn btn-warning btn-sm" disabled>Survey Not Started</button>
                                            <?php elseif ($status == 'Expired'): ?>
                                                <button class="btn btn-danger btn-sm" disabled>Survey Expired</button>
                                            <?php else: ?>
                                                <button class="btn btn-primary btn-sm take-survey-btn"
                                                    data-survey-id="<?php echo $survey['survey_id']; ?>">
                                                    Take Survey
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                </div>
            </section>
        </div>
    </div>

    <script src="plugins/jquery/jquery.min.js"></script>
    <script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="dist/js/adminlte.min.js"></script>
</body>

<!-- Welcome Modal -->
<div class="modal fade" id="welcomeModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Welcome Letter</h4>
                <h5 class="modal-subtitle"><?php echo htmlspecialchars($survey['title']); ?></h5>
            </div>
            <div class="modal-body">
                <p><b>Dear <?php echo htmlspecialchars($participantName); ?>,</b></p>
                <div id="welcomeContent"></div>
                <p><b>Date of Agreement: <?php echo date('d M, Y');?></b></p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" id="nextToAgreement">Continue</button>
            </div>
        </div>
    </div>
</div>

<!-- Agreement Modal -->
<div class="modal fade" id="agreementModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Participant Agreement</h4>
            </div>
            <div class="modal-body" id="agreementContent"></div>
            <div class="modal-footer">
                <button class="btn btn-success" id="acceptAgreement">I Agree</button>
            </div>
        </div>
    </div>
</div>

<!-- Consent Modal -->
<div class="modal fade" id="consentModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Consent Letter</h4>
            </div>
            <div class="modal-body">
                <div id="consentContent"></div>
                <p><b>Name of Participant: <?php echo htmlspecialchars($participantName); ?></b></p>
                <p><b>Specialization of Participant:</b> <?php echo htmlspecialchars($survey['specialization']); ?></p>
                <p><b>Contact Number:</b> <?php echo htmlspecialchars($survey['mobile']); ?></p>
                <p><b>Email:</b> <?php echo htmlspecialchars($survey['email']); ?></p>
                <p><b>Amount: ₹<?php echo htmlspecialchars($survey['payment_amount']); ?></b></p>

                <input type="checkbox" id="consentCheckbox">
                <label for="consentCheckbox">I <?php echo htmlspecialchars($participantName); ?>, Agree to the Terms and Conditions mentioned above.</label>
            </div>
            <div class="modal-footer">
                <button class="btn btn-success" id="acceptConsent">I Agree</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {
    let selectedSurveyId = null;

    // Handle click on "Take Survey"
    $(".take-survey-btn").on("click", function () {
        selectedSurveyId = $(this).data("survey-id");
        console.log("Selected Survey ID:", selectedSurveyId);

        // Fetch all agreement contents
        $.ajax({
            url: "manage_entity.php",
            type: "POST",
            data: { entity: "agreement",
                    action: "getDocuments",
                    survey_id: selectedSurveyId},
            dataType: "json",
            success: function (response) {
                if (!response.success) {
                    alert("Error fetching documents: " + response.error);
                }
                else{
                    $("#welcomeContent").html(response.welcome_letter || "<p>No Welcome Letter Found.</p>");
                    $("#agreementContent").html(response.participant_agreement || "<p>No Participant Agreement Found.</p>");
                    $("#consentContent").html(response.consent_letter || "<p>No Consent Letter Found.</p>");
                    $("#welcomeModal").modal("show");
                }
            },
            error: function () {
                alert("Failed to fetch documents. Please try again later.");
            }
        });
    });

    // // From Welcome to Agreement
    $("#nextToAgreement").on("click", function () {
        $("#welcomeModal").modal("hide");
        $("#agreementModal").modal("show");
    });

    // // From Agreement to Consent
    $("#acceptAgreement").on("click", function () {
        $("#agreementModal").modal("hide");
        $("#consentModal").modal("show");
    });

    // // Final consent to take survey
    $("#acceptConsent").on("click", function () {
        if (!$("#consentCheckbox").is(":checked")) {
            alert("You must agree to the terms and conditions.");
            return;
        }
        $("#consentModal").modal("hide");
        if (selectedSurveyId) {
            window.location.href = `questionairre.php?survey_id=${selectedSurveyId}`;
        }
    });
});
</script>


</html>