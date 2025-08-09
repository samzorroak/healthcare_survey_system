<?php
include 'header.php';
require_once 'EntityManager.php';

$conn = Database::getInstance();

$surveyManager = new EntityManager('surveys');
$surveys = $surveyManager->fetchActive();

$participantManagaer = new EntityManager('participants');
$participants = $participantManagaer->fetchAll();
?>

<style>
    .slider-box {
        border: 1px solid #ccc;
        /* Adds border around the selection box */
        border-radius: 5px;
        padding: 10px;
        max-height: 200px;
        /* Set the maximum height */
        overflow-y: auto;
        /* Enables scrolling when content exceeds height */
        background-color: #f9f9f9;
    }
</style>

<script src="payout.js"></script>

<body class="hold-transition sidebar-mini">

    <div class="wrapper">
        <?php include 'navbar.php'; ?>
        <?php include 'sidebar.php'; ?>

        <div class="content-wrapper">
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1>Payout Management</h1>
                        </div>
                    </div>
                </div>
            </section>

            <section class="content">
                <div class="container">
                    <!-- Survey List -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Survey Payout List</h3>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered" id="surveyTable">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Organization</th>
                                        <th>Amount</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="surveyBody"></tbody>
                            </table>

                            <div id="participantSection" class="mt-4" style="display:none;">
                                <div id="participantTableBody"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <!-- Verify Survey Modal -->
    <div class="modal fade" id="verifySurveyModal" tabindex="-1" aria-labelledby="verifySurveyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="verifySurveyModalLabel">Verify Survey Responses</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="surveyResponsesContainer">
                        <!-- Loaded dynamically via JS -->
                    </div>
                </div>
                <div class="modal-footer">
                    <input type="hidden" id="verify_participant_id">
                    <input type="hidden" id="verify_survey_id">
                    <button type="button" class="btn btn-success" id="markVerifiedBtn">Mark as Verified</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Participants Modal -->
    <div class="modal fade" id="viewParticipantsModal" tabindex="-1" role="dialog" aria-labelledby="participantsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Survey Participants</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Mobile</th>
                                <th>Completed</th>
                                <th>Amount (₹)</th>
                                <th>Paid</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="participantTableBody">
                            <!-- Participant rows will be populated here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Participant Responses Modal -->
    <div class="modal fade" id="participantResponsesModal" tabindex="-1" role="dialog" aria-labelledby="participantResponsesLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="participantResponsesLabel">Participant Responses</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="participantResponsesBody">
                    <!-- Responses will be loaded here -->
                </div>
            </div>
        </div>
    </div>

</body>