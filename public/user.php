<?php
include 'header.php';
require_once 'EntityManager.php';

$userManager = new EntityManager('users'); // Table name
$users = $userManager->fetchAll();

$UserId = $_SESSION['user_id'];
$UserName = $_SESSION['user_name'] ?? 'User';
$UserPermissions = $_SESSION['permissions'] ?? [];
$perm = 'manage_users';
// $permission = json_encode($UserPermissions);
?>

<script src="user.js"></script>

<script>
    $(document).ready(function() {
        // When the Add User modal is closed, reset the form and clear errors
        $('#addUserModal').on('hidden.bs.modal', function() {
            $('#addUserForm')[0].reset(); // Reset all form fields
        });
    });
</script>

<div class="wrapper">
    <?php include 'navbar.php'; ?>
    <?php include 'sidebar.php'; ?>
    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>Manage Users</h1>
                    </div>
                    <div class="col-sm-6 text-right">
                        <button class="btn btn-primary" data-toggle="modal" data-target="#addUserModal">
                            <i class="fas fa-user-plus"></i> Add New User
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
                        <h3 class="card-title">User List</h3>
                    </div>
                    <div class="card-body">
                        <input type="text" id="searchUser" class="form-control" placeholder="Search by Name, ID, or Permission">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>
                                        ID
                                        <button class="btn btn-sm btn-secondary sort-btn" data-column="id">⇅</button>
                                    </th>
                                    <th>
                                        Name
                                        <button class="btn btn-sm btn-secondary sort-btn" data-column="name">⇅</button>
                                    </th>
                                    <th>Email</th>
                                    <th>Permissions</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="userTable">
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['id']); ?></td>
                                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo htmlspecialchars($user['permissions']); ?></td>
                                        <td>
                                            <button class="btn btn-info view-user-btn" data-id="<?php echo $user['id']; ?>">
                                                View</button>
                                            <button class="btn btn-warning edit-user-btn" data-id="<?php echo $user['id']; ?>">
                                                Edit</button>
                                            <button class="btn btn-danger removeUser" data-id="<?php echo $user['id']; ?>">
                                                Delete</button>
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

<!-- View User Details Modal -->
<div class="modal fade" id="viewUserModal" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">User Details</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="userDetails"></div>
            </div>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Add New User</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="addUserForm">
                    <div class="form-group">
                        <label>Name:</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="form-group">
                        <label>Email:</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="form-group">
                        <label>Mobile:</label>
                        <input type="text" class="form-control" name="mobile" required pattern="[0-9]{10}" title="Enter a valid 10-digit mobile number">
                    </div>
                    <div class="form-group">
                        <label>Password:</label>
                        <input type="password" class="form-control" name="password" id="password" required>
                    </div>
                    <div class="form-group">
                        <label>Confirm Password:</label>
                        <input type="password" class="form-control" name="confirm_password" id="confirm_password" required>
                        <small id="passwordError" class="text-danger" style="display: none;">Passwords do not match</small>
                    </div>
                    <div class="form-group">
                        <label>Permissions:</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="permissions[]" value="manage_users">
                            <label class="form-check-label">Manage Users</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="permissions[]" value="manage_surveys">
                            <label class="form-check-label">Manage Surveys</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="permissions[]" value="view_reports">
                            <label class="form-check-label">View Reports</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Status:</label>
                        <select class="form-control" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <?php if (str_contains($UserPermissions, $perm)): ?>
                        <button type="submit" class="btn btn-primary btn-block">Add User</button>
                    <?php else: ?>
                        <button type="submit" class="btn btn-primary btn-block" disabled>Add User</button>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit User</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <form id="editUserForm">
                    <input type="hidden" id="userId">

                    <div class="form-group">
                        <label>Name:</label>
                        <input type="text" id="userName" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Email:</label>
                        <input type="email" id="userEmail" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Mobile:</label>
                        <input type="text" class="form-control" id="userMobile" name="mobile">
                    </div>

                    <div class="form-group">
                        <label>Permissions:</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="permissions[]" value="manage_users">
                            <label class="form-check-label">Manage Users</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="permissions[]" value="manage_surveys">
                            <label class="form-check-label">Manage Surveys</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="permissions[]" value="view_reports">
                            <label class="form-check-label">View Reports</label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Status:</label>
                        <select class="form-control" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <?php if (str_contains($UserPermissions, $perm)): ?>
                        <button type="button" class="btn btn-primary" id="updateUserBtn">Update</button>
                    <?php else: ?>
                        <button type="button" class="btn btn-primary" id="updateUserBtn" disabled>Update</button>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</div>