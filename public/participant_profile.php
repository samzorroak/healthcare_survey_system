<?php
session_start();
include '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'participant') {
    header("Location: login.php");
    exit();
}

$participantId = $_SESSION['user_id'];
$conn = Database::getInstance();

// Fetch participant details
$stmt = $conn->prepare("SELECT * FROM participants WHERE id = ?");
$stmt->execute([$participantId]);
$participant = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $mobile = $_POST['mobile'];
    $dob = $_POST['dob'];
    $qualification = $_POST['qualification'];
    $specialization = $_POST['specialization'];
    $address = $_POST['address'];
    $zip = $_POST['zip'];
    $city = $_POST['city'];
    $state = $_POST['state'];
    $country = $_POST['country'];

    $updateStmt = $conn->prepare("UPDATE participants SET full_name = ?, email = ?, mobile = ?, dob = ?, qualification = ?, specialization = ?, address = ?, zip = ?, city = ?, state = ?, country = ? WHERE id = ?");
    $updated = $updateStmt->execute([$full_name, $email, $mobile, $dob, $qualification, $specialization, $address, $zip, $city, $state, $country, $participantId]);

    if ($updated) {
        $_SESSION['user_name'] = $full_name; // Update session name
        $successMsg = "Profile updated successfully.";
    } else {
        $errorMsg = "Failed to update profile.";
    }

    // Refresh data after update
    $stmt->execute([$participantId]);
    $participant = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile</title>
    <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="dist/css/adminlte.min2.css">
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">
    <?php include 'participant_navbar.php'; ?>
    <?php include 'participant_sidebar.php'; ?>

    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <h1>Manage Your Profile</h1>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <?php if (isset($successMsg)): ?>
                    <div class="alert alert-success"><?php echo $successMsg; ?></div>
                <?php elseif (isset($errorMsg)): ?>
                    <div class="alert alert-danger"><?php echo $errorMsg; ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="card card-primary">
                        <div class="card-header">
                            <h3 class="card-title">Profile Information</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>Full Name</label>
                                <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($participant['full_name']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($participant['email']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Mobile</label>
                                <input type="text" name="mobile" class="form-control" value="<?php echo htmlspecialchars($participant['mobile']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Date of Birth</label>
                                <input type="date" name="dob" class="form-control" value="<?php echo htmlspecialchars($participant['dob']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Qualification</label>
                                <input type="text" name="qualification" class="form-control" value="<?php echo htmlspecialchars($participant['qualification']); ?>">
                            </div>
                            <div class="form-group">
                                <label>Specialization</label>
                                <input type="text" name="specialization" class="form-control" value="<?php echo htmlspecialchars($participant['specialization']); ?>">
                            </div>
                            <div class="form-group">
                                <label>Address</label>
                                <textarea name="address" class="form-control"><?php echo htmlspecialchars($participant['address']); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>Zip</label>
                                <input type="text" name="zip" class="form-control" value="<?php echo htmlspecialchars($participant['zip']); ?>">
                            </div>
                            <div class="form-group">
                                <label>City</label>
                                <input type="text" name="city" class="form-control" value="<?php echo htmlspecialchars($participant['city']); ?>">
                            </div>
                            <div class="form-group">
                                <label>State</label>
                                <input type="text" name="state" class="form-control" value="<?php echo htmlspecialchars($participant['state']); ?>">
                            </div>
                            <div class="form-group">
                                <label>Country</label>
                                <input type="text" name="country" class="form-control" value="<?php echo htmlspecialchars($participant['country']); ?>">
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary">Update Profile</button>
                        </div>
                    </div>
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
