<?php
include 'header.php';
require_once 'EntityManager.php';

$clientManager = new EntityManager('clients'); // Table name
$clients = $clientManager->fetchAll();
?>

<script src="client.js"></script>

<body class="hold-transition sidebar-mini">
<div class="wrapper">
        <!-- Navbar -->
        <?php include 'navbar.php'; ?>

        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>
    <!-- Content Wrapper -->
    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>Manage Clients</h1>
                    </div>
                    <div class="col-sm-6 text-right">
                        <button class="btn btn-primary" data-toggle="modal" data-target="#addClientModal">
                            <i class="fas fa-building"></i> Add New Client
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Client List</h3>
                    </div>
                    <div class="card-body">
                        <input type="text" id="searchClient" class="form-control" placeholder="Search by Name, ID, or Contact Person">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>ID
                                        <button class="btn btn-sm btn-secondary sort-btn" data-column="id">⇅</button>
                                    </th>
                                    <th>Organization Name
                                        <button class="btn btn-sm btn-secondary sort-btn" data-column="organization_name">⇅</button>
                                    </th>
                                    <th>Contact Person
                                        <button class="btn btn-sm btn-secondary sort-btn" data-column="contact_person_name">⇅</button>
                                    </th>
                                    <th>Email</th>
                                    <th>Mobile</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="clientTable">
                                <?php foreach ($clients as $client): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($client['id']); ?></td>
                                        <td><?php echo htmlspecialchars($client['organization_name']); ?></td>
                                        <td><?php echo htmlspecialchars($client['contact_person_name']); ?></td>
                                        <td><?php echo htmlspecialchars($client['contact_person_email']); ?></td>
                                        <td><?php echo htmlspecialchars($client['contact_person_mobile']); ?></td>
                                        <td>
                                            <button class="btn btn-info view-client-btn" data-id="<?php echo $client['id']; ?>">View</button>
                                            <button class="btn btn-warning edit-client-btn" data-id="<?php echo $client['id']; ?>">Edit</button>
                                            <button class="btn btn-danger remove-client-btn" data-id="<?php echo $client['id']; ?>">Delete</button>
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

<!-- Add Client Modal -->
<div class="modal fade" id="addClientModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Add New Client</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="addClientForm">
                    <div class="form-group">
                        <label>Organization Name:</label>
                        <input type="text" class="form-control" name="organization_name" required>
                    </div>
                    <div class="form-group">
                        <label>Contact Person Name:</label>
                        <input type="text" class="form-control" name="contact_person_name" required>
                    </div>
                    <div class="form-group">
                        <label>Contact Person Email:</label>
                        <input type="email" class="form-control" name="contact_person_email" required>
                    </div>
                    <div class="form-group">
                        <label>Contact Person Mobile:</label>
                        <input type="text" class="form-control" name="contact_person_mobile" required pattern="[0-9]{10}" title="Enter a valid 10-digit mobile number">
                    </div>
                    <div class="form-group">
                        <label>Address Line 1:</label>
                        <input type="text" class="form-control" name="address_line_1" required>
                    </div>
                    <div class="form-group">
                        <label>Address Line 2:</label>
                        <input type="text" class="form-control" name="address_line_2">
                    </div>
                    <div class="form-group">
                        <label>Zip:</label>
                        <input type="text" class="form-control" name="zip" required>
                    </div>
                    <div class="form-group">
                        <label>City:</label>
                        <input type="text" class="form-control" name="city" required>
                    </div>
                    <div class="form-group">
                        <label>State:</label>
                        <input type="text" class="form-control" name="state" required>
                    </div>
                    <div class="form-group">
                        <label>Country:</label>
                        <input type="text" class="form-control" name="country" required>
                    </div>
                    <div class="form-group">
                        <label>Agreement File:</label>
                        <input type="file" class="form-control" name="agreement_file" accept=".pdf,.docx,.txt">
                    </div>
                    <div class="form-group">
                        <label>Status:</label>
                        <select class="form-control" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Add Client</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- View Client Details Modal -->
<div class="modal fade" id="viewClientModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Client Details</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div id="clientDetails"></div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Client Modal -->
<div class="modal fade" id="editClientModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Edit Client Details</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <!-- Edit Client Form -->
                <form id="editClientForm">
                    <input type="hidden" name="id" id="editClientId">

                    <div class="form-group">
                        <label for="editOrganizationName">Organization Name:</label>
                        <input type="text" class="form-control" name="organization_name" id="editOrganizationName" required>
                    </div>

                    <div class="form-group">
                        <label for="editContactPersonName">Contact Person Name:</label>
                        <input type="text" class="form-control" name="contact_person_name" id="editContactPersonName" required>
                    </div>

                    <div class="form-group">
                        <label for="editContactPersonEmail">Contact Person Email:</label>
                        <input type="email" class="form-control" name="contact_person_email" id="editContactPersonEmail" required>
                    </div>

                    <div class="form-group">
                        <label for="editContactPersonMobile">Contact Person Mobile:</label>
                        <input type="text" class="form-control" name="contact_person_mobile" id="editContactPersonMobile" required pattern="[0-9]{10}" title="Enter a valid 10-digit mobile number">
                    </div>

                    <div class="form-group">
                        <label for="editAddressLine1">Address Line 1:</label>
                        <input type="text" class="form-control" name="address_line_1" id="editAddressLine1" required>
                    </div>

                    <div class="form-group">
                        <label for="editAddressLine2">Address Line 2:</label>
                        <input type="text" class="form-control" name="address_line_2" id="editAddressLine2">
                    </div>

                    <div class="form-group">
                        <label for="editZip">Zip:</label>
                        <input type="text" class="form-control" name="zip" id="editZip" required>
                    </div>

                    <div class="form-group">
                        <label for="editCity">City:</label>
                        <input type="text" class="form-control" name="city" id="editCity" required>
                    </div>

                    <div class="form-group">
                        <label for="editState">State:</label>
                        <input type="text" class="form-control" name="state" id="editState" required>
                    </div>

                    <div class="form-group">
                        <label for="editCountry">Country:</label>
                        <input type="text" class="form-control" name="country" id="editCountry" required>
                    </div>

                    <div class="form-group">
                        <div id="existingAgreementFile"></div>
                        <input type="file" id="editAgreementFile" name="agreement_file">
                    </div>

                    <div class="form-group">
                        <label for="editStatus">Status:</label>
                        <select class="form-control" name="status" id="editStatus">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block" id="updateClientBtn">Update Client</button>
                </form>
            </div>
        </div>
    </div>
</div>

</body>
</html>
