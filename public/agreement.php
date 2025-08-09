<?php
include 'header.php';

// Get the database connection instance
$conn = Database::getInstance();

// Fetch existing agreements
$stmt = $conn->query("SELECT a.id, s.title AS survey_title, a.type, a.content FROM agreements a 
                      LEFT JOIN surveys s ON a.survey_id = s.id");
$agreements = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $survey_id = $_POST['survey_id'];
    $agreement_content = $_POST['content'];
    $type =  $_POST['docType'];

    try {
        $stmt = $conn->prepare("INSERT INTO agreements (`survey_id`, `type`, `content`) VALUES (?, ?, ?) 
                                ON DUPLICATE KEY UPDATE content = VALUES(content)");
        $stmt->execute([$survey_id, $type, $agreement_content]);
        echo "<script>alert('Agreement saved successfully!'); window.location.href='agreement.php';</script>";
    } catch (Exception $e) {
        echo "<script>alert('Error saving agreement: " . $e->getMessage() . "');</script>";
    }
}

?>
<script src="https://cdn.tiny.cloud/1/3j32bd2me4p71voapdmhou2968sfh7a5015rhpd2bn560v51/tinymce/7/tinymce.min.js" referrerpolicy="origin"></script>
<style>
    .editor-label {
        font-weight: bold;
        margin-top: 10px;
    }

    .editor-wrapper {
        margin-bottom: 20px;
    }

    .container {
        max-width: 900px;
        margin: auto;
        padding: 20px;
    }
</style>

<script>
    tinymce.init({
        selector: '.editor',
        height: 250,
        menubar: false,
        plugins: 'lists link',
        toolbar: 'undo redo | bold italic | bullist numlist | alignleft aligncenter alignright alignjustify',
        setup: function(editor) {
                editor.on('init', function() {
                    this.setContent(document.getElementById('hidden_content').value);
                });
        }
    });
</script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const docTypeSelect = document.getElementById("docType");
    const docLabel = document.getElementById("docLabel");

    function updateLabel(value) {
        switch (value) {
            case "pa":
                docLabel.innerHTML = 'Participant Agreement (Dynamic Variables: <code>{date}</code>, <code>{participant_name}</code>, <code>{signature}</code>)';
                break;
            case "wl":
                docLabel.innerHTML = 'Welcome Letter';
                break;
            case "cl":
                docLabel.innerHTML = 'Consent Letter (Dynamic Variables: <code>{participant_name}</code>, <code>{amount}</code>)';
                break;
            default:
                docLabel.innerHTML = 'Agreement/Letter Content (Select Agreement/Letter Type First)';
        }
    }

    // Initialize on load
    updateLabel(docTypeSelect.value);

    // Update on change
    docTypeSelect.addEventListener("change", function () {
        updateLabel(this.value);
    });
});
</script>
<script src="agreement.js"></script>

<body class="hold-transition sidebar-mini">
    <div class="wrapper">
        <?php include 'navbar.php'; ?>
        <?php include 'sidebar.php'; ?>
        <div class="content-wrapper">
            <section class="content-header">
                <div class="container-fluid">
                    <div class="row mb-2">
                        <div class="col-sm-6">
                            <h1>Manage Agreements</h1>
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
                            <form method="post">
                                <label>Select Survey:</label>
                                <select name="survey_id" required>
                                    <option value="">Select Survey</option>
                                    <?php
                                    $survey_stmt = $conn->query("SELECT id, title FROM surveys");
                                    while ($survey = $survey_stmt->fetch(PDO::FETCH_ASSOC)) {
                                        echo "<option value='{$survey['id']}'>{$survey['title']}</option>";
                                    }
                                    ?>
                                </select>

                                <label>Select Document Type:</label>
                                <select name="docType" id="docType" required>
                                    <option value="">Select Agreement/Letter Type</option>
                                    <option value="pa">Participant Agreement</option>
                                    <option value="wl">Welcome Letter</option>
                                    <option value="cl">Consent Letter</option>
                                </select>

                                <div class="form-group mt-2">
                                    <label id="docLabel"></label>
                                    <p class="text-info" id="editNotice" style="display:none;">You are editing an existing agreement.</p>
                                    <textarea id="content" name="content" class="editor form-control" placeholder="Agreement/Letter Content..." rows="5"></textarea>
                                    <input type="hidden" id="hidden_content" value="">
                                    <input type="hidden" id="agreement_id" name="agreement_id" value="">
                                </div>

                                <button type="submit">Save Agreement</button>
                            </form>
                        </div>
                    </div>
                </div>
            </section>
            <section class="content">
                <div class="container-fluid">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Agreements List</h3>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered">
                                <tr>
                                    <th>Survey</th>
                                    <th>Agreement Type</th>
                                    <th>Agreement Content</th>
                                    <th>Action</th>
                                </tr>
                                <?php foreach ($agreements as $agreement): ?>
                                    <tr>
                                        <td><?= $agreement['survey_title'] ?></td>
                                        <td>
                                            <?php
                                            switch ($agreement['type']) {
                                                case 'pa':
                                                    echo 'Participant Agreement';
                                                    break;
                                                case 'wl':
                                                    echo 'Welcome Letter';
                                                    break;
                                                case 'cl':
                                                    echo 'Consent Letter';
                                                    break;
                                                default:
                                                    echo 'Unknown Type';
                                            }
                                            ?>
                                        </td>
                                        <td><?= substr(strip_tags($agreement['content']), 0, 50) ?>...</td>

                                        <td>
                                            <button class="btn btn-info view-agreement-btn" data-id="<?php echo $agreement['id']; ?>">View</button>
                                            <button class="btn btn-warning edit-agreement-btn" data-id="<?php echo $agreement['id']; ?>">Edit</button>
                                            <button class="btn btn-danger remove-agreement-btn" data-id="<?php echo $agreement['id']; ?>">Delete</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</body>

</html>

<!-- View Agreement Details Modal -->
<div class="modal fade" id="viewAgreementModal" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Agreement/Letter Details</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="agreementDetails"></div>
            </div>
        </div>
    </div>
</div>