<?php
include 'header.php';
require_once 'EntityManager.php';

$conn = Database::getInstance();

$surveyManager = new EntityManager('surveys');
$surveys = $surveyManager->fetchAll();

$participantManagaer = new EntityManager('participants');
$participants = $participantManagaer->fetchAll();

 // Auto-update surveys that are past their end date
    $today = date('Y-m-d');
    foreach ($surveys as $i => $survey) {
        if ($survey['end_date'] < $today && strtolower($survey['status']) !== 'inactive') {
            $update = $conn->prepare("UPDATE surveys SET status = 'inactive' WHERE id = ?");
            $update->execute([$survey['id']]);
            $survey['status'] = 'inactive';
        }
    }

$UserId = $_SESSION['user_id'];
$UserName = $_SESSION['user_name'] ?? 'User';
$UserPermissions = $_SESSION['permissions'] ?? [];
$perm = 'manage_surveys';
$permission = json_encode($UserPermissions);
?>

<script src="survey.js"></script>

<style>
    .slider-box {
    border: 1px solid #ccc;  /* Adds border around the selection box */
    border-radius: 5px;
    padding: 10px;
    max-height: 200px;  /* Set the maximum height */
    overflow-y: auto;   /* Enables scrolling when content exceeds height */
    background-color: #f9f9f9;
}
</style>

<body class="hold-transition sidebar-mini">

    <div class="wrapper">
        <?php include 'navbar.php'; ?>
        <?php include 'sidebar.php'; ?>

        <div class="content-wrapper">
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1>Manage Surveys</h1>
                        </div>
                        <div class="col-sm-6 text-right">
                            <button class="btn btn-primary" data-toggle="modal" data-target="#addSurveyModal">
                                <i class="fas fa-clipboard-list"></i> Add New Survey
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <section class="content">
                <div class="container-fluid">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Survey List</h3>
                        </div>
                        <div class="card-body">
                            <input type="text" id="searchSurvey" class="form-control" placeholder="Search by Title, ID, or Client">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>ID
                                            <button class="btn btn-sm btn-secondary sort-btn" data-column="id">⇅</button>
                                        </th>
                                        <th>Title
                                            <button class="btn btn-sm btn-secondary sort-btn" data-column="title">⇅</button>
                                        </th>
                                        <th>Client
                                            <button class="btn btn-sm btn-secondary sort-btn" data-column="client">⇅</button>
                                        </th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="surveyTable">
                                    <?php foreach ($surveys as $survey): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($survey['id']); ?></td>
                                            <td><?php echo htmlspecialchars($survey['title']); ?></td>
                                            <td><?php $stmt = $conn->prepare("SELECT * FROM clients WHERE id = ?");
                                                $stmt->execute([$survey['client_id']]);
                                                $client = $stmt->fetch(PDO::FETCH_ASSOC);
                                                echo htmlspecialchars($client['organization_name']); ?></td>
                                            <td><?php echo htmlspecialchars($survey['amount']); ?></td>
                                            <td><?php echo htmlspecialchars($survey['status']); ?></td>
                                            <td>
                                                <button class="btn btn-info view-survey-btn" data-id="<?php echo $survey['id']; ?>">View</button>
                                                <button class="btn btn-warning edit-survey-btn" data-id="<?php echo $survey['id']; ?>">Edit</button>
                                                <button class="btn btn-danger remove-survey-btn" data-id="<?php echo $survey['id']; ?>">Delete</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <!-- View Survey Details Modal -->
    <div class="modal fade" id="viewSurveyModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Survey Details</h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div id="surveyDetails"></div>
                </div>
            </div>
        </div>
    </div>


    <!-- Add Survey modal -->
    <div class="modal fade" id="addSurveyModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Survey</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="surveyForm">
                        <div class="form-group">
                            <label>Select Client</label>
                            <select name="client" id="client" class="form-control" required></select>
                        </div>
                        <div class="form-group">
                            <label>Survey Title</label>
                            <input type="text" name="title" id="title" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" id="description" class="form-control"></textarea>
                        </div>
                        <div class="form-group">
                            <label>Survey Amount</label>
                            <input type="number" name="amount" id="amount" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Start Date</label>
                            <input type="date" name="start_date" id="start_date" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>End Date</label>
                            <input type="date" name="end_date" id="end_date" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" id="status" class="form-control" required>
                                <option value="active">Active</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>

                        <div class="slider-box">
                            <label>Add Questions</label>
                            <div id="questionnaire-section">
                                <!-- Questions will be appended here -->
                            </div>
                            <button type="button" class="btn btn-secondary" onclick="addQuestion()">+ Add Question</button>
                        </div>

                        <div class="form-group">
                            <label>Search Participants</label>
                            <input type="text" id="searchParticipant" class="form-control" placeholder="Search by city, state, or specialization">
                        </div>

                        <div class="form-group">
                            <label>Allocate Participants</label>
                            <div class="slider-box">
                                <div class="participant-checkbox-container" id="participantContainer">
                                    <?php foreach ($participants as $participant) { ?>
                                        <div class="checkbox participant-item"
                                            data-city="<?= $participant['city'] ?>"
                                            data-state="<?= $participant['state'] ?>"
                                            data-specialization="<?= $participant['specialization'] ?>">
                                            <label>
                                                <input type="checkbox" name="participants[]" value="<?= $participant['id'] ?>">
                                                <?= $participant['full_name'] ?> (<?= $participant['state'] ?>, <?= $participant['city'] ?>, <?= $participant['specialization'] ?>)
                                            </label>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                        <?php if (str_contains($UserPermissions, $perm)): ?>
                            <button type="submit" class="btn btn-primary">Save Survey</button>
                        <?php else: ?>
                            <button type="submit" class="btn btn-primary" disabled>Save Survey</button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Survey modal -->
    <div class="modal fade" id="editSurveyModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Survey Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="editSurveyForm">
                        <input type="hidden" name="id" id="editSurveyId">

                        <div class="form-group">
                            <label>Survey Title</label>
                            <input type="text" name="title" id="editTitle" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" id="editDescription" class="form-control"></textarea>
                        </div>
                        <div class="form-group">
                            <label>Survey Amount</label>
                            <input type="number" name="amount" id="editAmount" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Start Date</label>
                            <input type="date" name="start_date" id="editStart_date" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>End Date</label>
                            <input type="date" name="end_date" id="editEnd_date" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status" id="editStatus" class="form-control" required>
                                <option value="active">Active</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>

                        <div class="slider-box">
                            <div id="editQuestionContainer">
                                <!-- Questions will be appended dynamically -->
                            </div>
                            <input type="hidden" id="removedQuestions" name="removed_questions">
                            <button type="button" class="btn btn-secondary" onclick="addEditQuestion()">+ Add Question</button>
                        
                        </div>


                        <div class="form-group">
                            <label>Allocate Participants</label>
                            <div class="slider-box">
                            <div class="participant-checkbox-container" id="editParticipantContainer">
                                <?php foreach ($selectedParticipants as $participant) { ?>
                                    <div class="checkbox participant-item-edit"
                                        data-city="<?= htmlspecialchars($participant['city'] ?? '') ?>"
                                        data-state="<?= htmlspecialchars($participant['state'] ?? '') ?>"
                                        data-specialization="<?= htmlspecialchars($participant['specialization'] ?? '') ?>">
                                        <label>
                                            <input type="checkbox" name="participants[]" value="<?= $participant['id'] ?>">
                                            <?= $participant['full_name'] ?> (<?= $participant['state'] ?>, <?= $participant['city'] ?>, <?= $participant['specialization'] ?>)
                                        </label>
                                    </div>
                                <?php } ?>
                            </div>
                            </div>
                        </div>
                        <?php if (str_contains($UserPermissions, $perm)): ?>
                            <button type="submit" class="btn btn-primary" id="updateSurveyBtn">Update Survey</button>
                        <?php else: ?>
                            <button type="submit" class="btn btn-primary" id="updateSurveyBtn" disabled>Update Survey</button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="plugins/jquery/jquery.min.js"></script>
    <script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="dist/js/adminlte.min.js"></script>
</body>

</html>