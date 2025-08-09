<?php
session_start();
include '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'participant') {
    header("Location: login.php");
    exit();
}

$participantId = $_SESSION['user_id'];
$participantName = $_SESSION['user_name'] ?? 'Participant';

$conn = Database::getInstance();

$surveyId = $_GET['survey_id'] ?? null;
if (!$surveyId) {
    die("Survey ID missing.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['survey_id'])) {
    $survey_id = $_POST['survey_id'];
    $responses = $_POST['responses'] ?? [];

    try {
        $conn->beginTransaction();

        // Delete existing responses in case of re-submission
        $stmt = $conn->prepare("DELETE FROM responses WHERE survey_id = ? AND participant_id = ?");
        $stmt->execute([$survey_id, $participantId]);

        // Insert each response
        foreach ($responses as $question_id => $answer) {
            if (is_array($answer)) {
                foreach ($answer as $ans) {
                    $stmt = $conn->prepare("INSERT INTO responses (survey_id, participant_id, question_id, answer_id) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$survey_id, $participantId, $question_id, $ans]);
                }
            } else {
                $stmt = $conn->prepare("INSERT INTO responses (survey_id, participant_id, question_id, answer_id) VALUES (?, ?, ?, ?)");
                $stmt->execute([$survey_id, $participantId, $question_id, $answer]);
            }
        }

        // Mark survey as completed
        $stmt = $conn->prepare("UPDATE survey_participants SET completed = 1 WHERE survey_id = ? AND participant_id = ?");
        $stmt->execute([$survey_id, $participantId]);

        $conn->commit();
        header("Location: participant_survey.php?status=completed");
        exit();
    } catch (Exception $e) {
        $conn->rollBack();
        die("Error saving responses: " . $e->getMessage());
    }
}

// Fetch survey and questions
$stmt = $conn->prepare("SELECT title, description FROM surveys WHERE id = ?");
$stmt->execute([$surveyId]);
$survey = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $conn->prepare("SELECT * FROM questions WHERE survey_id = ?");
$stmt->execute([$surveyId]);
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch options
$optionsMap = [];
foreach ($questions as $q) {
    $stmt = $conn->prepare("SELECT * FROM answers WHERE question_id = ?");
    $stmt->execute([$q['id']]);
    $optionsMap[$q['id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Survey Questionnaire</title>
    <link rel="stylesheet" href="dist/css/adminlte.min2.css">
    <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
    <?php include 'participant_navbar.php'; ?>
    <?php include 'participant_sidebar.php'; ?>

    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <h1><?php echo htmlspecialchars($survey['title']); ?></h1>
                <p><?php echo nl2br(htmlspecialchars($survey['description'])); ?></p>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <form method="POST" action="">
                    <input type="hidden" name="survey_id" value="<?php echo $surveyId; ?>" />
                    <?php foreach ($questions as $q): ?>
                        <div class="card mb-3">
                            <div class="card-header">
                                <strong><?php echo htmlspecialchars($q['question_text']); ?></strong>
                                <?php if ($q['required']) echo '<span class="text-danger">*</span>'; ?>
                            </div>
                            <div class="card-body">
                                <?php foreach ($optionsMap[$q['id']] as $opt): ?>
                                    <div class="form-check">
                                        <input
                                            class="form-check-input"
                                            type="<?php echo $q['question_type'] === 'multiple' ? 'checkbox' : 'radio'; ?>"
                                            name="responses[<?php echo $q['id']; ?>]<?php echo $q['question_type'] === 'multiple' ? '[]' : ''; ?>"
                                            value="<?php echo $opt['id']; ?>"
                                            id="opt_<?php echo $opt['id']; ?>"
                                            <?php echo $q['required'] ? 'required' : ''; ?>
                                        />
                                        <label class="form-check-label" for="opt_<?php echo $opt['id']; ?>">
                                            <?php echo htmlspecialchars($opt['answer_text']); ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <button type="submit" class="btn btn-success">Submit Survey</button>
                </form>
            </div>
        </section>
    </div>
</div>

<script src="plugins/jquery/jquery.min.js"></script>
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="dist/js/adminlte.min.js"></script>
</body>
</html>
