<?php
include 'header.php';
require_once 'EntityManager.php';

$participantManager = new EntityManager('participants'); // Table name
$participants = $participantManager->fetchAll();
?>

<script src="participant.js"></script>

<body class="hold-transition sidebar-mini">

<div class="wrapper">

    <!-- Navbar -->
    <?php include 'navbar.php'; ?>

    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>Manage Participants</h1>
                    </div>
                    <div class="col-sm-6 text-right">
                        <button class="btn btn-primary" data-toggle="modal" data-target="#addParticipantModal">
                            <i class="fas fa-user-plus"></i> Add New Participant
                        </button>
                    </div>
                </div>
            </div>
        </section>


        <section class="content">
            <div class="container-fluid">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Participant List</h3>
                    </div>
                    <div class="card-body">
                        <input type="text" id="searchParticipant" class="form-control" placeholder="Search by Name, ID, or Specialization">
                        <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>ID
                                <button class="btn btn-sm btn-secondary sort-btn" data-column="id">⇅</button>
                            </th>
                            <th>Full Name
                                <button class="btn btn-sm btn-secondary sort-btn" data-column="full_name">⇅</button>
                            </th>
                            <th>Email</th>
                            <th>Mobile</th>
                            <th>Qualification</th>
                            <th>Specialization
                                <button class="btn btn-sm btn-secondary sort-btn" data-column="specialization">⇅</button>
                            </th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="participantTable">
                        <?php foreach ($participants as $participant): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($participant['id']) ?></td>
                            <td><?php echo htmlspecialchars($participant['full_name']) ?></td>
                            <td><?php echo htmlspecialchars($participant['email']) ?></td>
                            <td><?php echo htmlspecialchars($participant['mobile']) ?></td>
                            <td><?php echo htmlspecialchars($participant['qualification']) ?></td>
                            <td><?php echo htmlspecialchars($participant['specialization']) ?></td>
                            <td>
                                <button class="btn btn-info btn-sm view-participant-btn" data-id="<?php echo $participant['id'] ?>">View</button>
                                <button class="btn btn-warning btn-sm edit-participant-btn" data-id="<?php echo $participant['id'] ?>">Edit</button>
                                <button class="btn btn-danger btn-sm remove-participant-btn" data-id="<?php echo $participant['id'] ?>">Delete</button>
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

<!-- View Participant Details Modal -->
<div class="modal fade" id="viewParticipantModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Participant Details</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div id="participantDetails"></div>
            </div>
        </div>
    </div>
</div>

<!-- Add Participant Modal -->
<div class="modal fade" id="addParticipantModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Add Participant</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="addParticipantForm" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="full_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Mobile</label>
                        <input type="text" name="mobile" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Date of Birth</label>
                        <input type="date" name="dob" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Qualification</label>
                        <input type="text" name="qualification" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Specialization</label>
                        <input type="text" name="specialization" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>PAN Card Number</label>
                        <input type="text" name="pan" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Registration ID</label>
                        <input type="text" name="registration_id" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Upload Cancel Cheque</label>
                        <input type="file" name="cancel_cheque" class="form-control-file" required>
                    </div>
                    <div class="form-group">
                        <label>Address</label>
                        <textarea name="address" class="form-control"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Country</label>
                        <input type="text" name="country" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>State</label>
                        <input type="text" name="state" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>City</label>
                        <input type="text" name="city" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Zip</label>
                        <input type="text" name="zip" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Add Participant</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Participant Modal -->
<div class="modal fade" id="editParticipantModal" tabindex="-1" aria-labelledby="editParticipantModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Participant</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="editParticipantForm" enctype="multipart/form-data">
                    <input type="hidden" id="editId" name="id">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Full Name</label>
                                <input type="text" class="form-control" id="editFullName" name="full_name" required>
                            </div>

                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" class="form-control" id="editEmail" name="email" required>
                            </div>

                            <div class="form-group">
                                <label>Mobile</label>
                                <input type="text" class="form-control" id="editMobile" name="mobile" required>
                            </div>

                            <div class="form-group">
                                <label>Date of Birth</label>
                                <input type="date" class="form-control" id="editDob" name="dob" required>
                            </div>

                            <div class="form-group">
                                <label>Qualification</label>
                                <input type="text" class="form-control" id="editQualification" name="qualification" required>
                            </div>

                            <div class="form-group">
                                <label>Specialization</label>
                                <input type="text" class="form-control" id="editSpecialization" name="specialization" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>PAN Card Number</label>
                                <input type="text" class="form-control" id="editPanNumber" name="pan" required>
                            </div>

                            <div class="form-group">
                                <label>Registration ID</label>
                                <input type="text" class="form-control" id="editRegistrationId" name="registration_id" required>
                            </div>

                            <div class="form-group">
                                <label>Address</label>
                                <textarea class="form-control" id="editAddress" name="address" required></textarea>
                            </div>

                            <div class="form-group">
                                <label>Country</label>
                                <input type="text" class="form-control" id="editCountry" name="country" required>
                            </div>

                            <div class="form-group">
                                <label>State</label>
                                <input type="text" class="form-control" id="editState" name="state" required>
                            </div>

                            <div class="form-group">
                                <label>City</label>
                                <input type="text" class="form-control" id="editCity" name="city" required>
                            </div>

                            <div class="form-group">
                                <label>Zip Code</label>
                                <input type="text" class="form-control" id="editZip" name="zip" required>
                            </div>

                            <div class="form-group">
                                <label>Status</label>
                                <select class="form-control" id="editStatus" name="status" required>
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <div id="existingChequeFile"></div>
                                <input type="file" id="editCancelCheque" name="agreement_file">
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary" id="updateParticipantBtn">Update Participant</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


</body>
</html>