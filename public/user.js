newPageTitle = 'Manage Users';
document.title = newPageTitle;


$(document).ready(function () {
  // View User
  $(document).on("click", ".view-user-btn", function () {
    let userId = $(this).data("id");

    $.ajax({
      url: "manage_entity.php",
      type: "POST",
      data: { action: "read_single", entity: "user", id: userId },
      dataType: "json",
      success: function (response) {
        if (response.error) {
          alert(response.error);
        } else {
          // Show user details in a modal or a section
          var userDetails = `
                <p><strong>Name:</strong> ${response.name}</p>
                <p><strong>Email:</strong> ${response.email}</p>
                <p><strong>Mobile:</strong> ${response.mobile}</p>
                <p><strong>Permissions:</strong> ${response.permissions}</p>
                <p><strong>Status:</strong> ${response.status}</p>
            `;
          $("#userDetails").html(userDetails); // Update the HTML with user details
          $("#viewUserModal").modal("show"); // Show the modal
        }
      },
      error: function () {
        alert("Failed to retrieve user data.");
      },
    });
  });

  // Add New User
  $("#addUserForm").submit(function (e) {
    e.preventDefault(); // Prevent default form submission

    let formData = $(this).serialize(); // Serialize form data

    // Validate password match
    let password = $("#password").val();
    let confirmPassword = $("#confirm_password").val();
    if (password !== confirmPassword) {
      $("#passwordError").show();
      return;
    } else {
      $("#passwordError").hide();
    }

    $.ajax({
      url: "manage_entity.php",
      type: "POST",
      data: formData + "&action=create&entity=user",
      dataType: "json",
      success: function (response) {
        alert(response.message);
        if (response.success) {
          $("#addUserModal").modal("hide");
          $("#addUserForm")[0].reset();
          window.location.reload(); // Refresh user list
        }
      },
      error: function () {
        alert("Failed to add user.");
      },
    });
  });

  // Edit User
$(document).on("click", ".edit-user-btn", function () {
    let userId = $(this).data("id");

    $.ajax({
        url: "manage_entity.php",
        type: "POST",
        data: { action: "read_single", entity: "user", id: userId },
        dataType: "json",
        success: function (response) {
            if (!response.success) {
                alert(response.error || "Error retrieving user details.");
                return;
            }

            let user = response.data;

            $("#userId").val(user.id);
            $("#userName").val(user.name);
            $("#userEmail").val(user.email);
            $("#userMobile").val(user.mobile);
            $("#userStatus").val(user.status);

            // Clear all checkboxes first
            $("input[name='permissions[]']").prop("checked", false);

            // Mark the existing permissions as checked
            // if (Array.isArray(user.permissions)) {
            //     user.permissions.forEach(function (permission) {
            //         $("input[name='permissions[]'][value='" + permission + "']").prop("checked", true);
            //     });
            // }

            $("#editUserModal").modal("show");
        },
        error: function () {
            alert("Failed to retrieve user data.");
        },
    });
});

 $("#updateUserBtn").click(function () {
    let userId = $("#userId").val();
    let name = $("#userName").val();
    let email = $("#userEmail").val();
    let mobile = $("#userMobile").val();
    let status = $("#userStatus").val();

    // Get unique permissions
    let permissions = Array.from(new Set(
        $("input[name='permissions[]']:checked").map(function () {
            return $(this).val();
        }).get()
    ));

    $.ajax({
        url: "manage_entity.php",
        type: "POST",
        data: {
            action: "update",
            entity: "user",
            id: userId,
            name: name,
            email: email,
            mobile: mobile,
            permissions: permissions,
            status: status,
        },
        dataType: "json",
        success: function (response) {
            alert(response.message);
            if (response.success) {
                $("#editUserModal").modal("hide");
                location.reload();
            }
        },
        error: function () {
            alert("Failed to update user.");
        },
    });
});


  //Delete User
  $(document).on("click", ".removeUser", function () {
    let userId = $(this).data("id");
      if (confirm("Are you sure you want to delete this user?")) {
        $.ajax({
          url: "manage_entity.php",
          type: "POST",
          data: { action: "delete", entity: "user", id: userId },
          dataType: "json",
          success: function (response) {
            alert(response.message);
            location.reload(); // Refresh user list after deletion
          },
          error: function () {
            alert(response.message);
            // alert("Failed to delete user.");
          },
        });
      }
    });

  //Search User
  $("#searchUser").on("keyup", function () {
    let query = $(this).val().trim();

    if (query.length === 0) {
      loadAllUsers(); // Reload all users when search is cleared
      return;
    }

    $.ajax({
      url: "manage_entity.php",
      type: "POST",
      data: { action: "search", entity: "user", query: query },
      dataType: "json",
      success: function (response) {
        if (response.success) {
          let users = response.data;
          let tableBody = "";

          users.forEach((user) => {
            tableBody += `
                        <tr>
                            <td>${user.id}</td>
                            <td>${user.name}</td>
                            <td>${user.email}</td>
                            <td>${user.permissions}</td>
                            <td>
                                <button class="btn btn-info view-user-btn" data-id="${user.id}">View</button>
                                <button class="btn btn-warning edit-user-btn" data-id="${user.id}">Edit</button>
                                <button class="btn btn-danger removeUser" data-id="${user.id}">Delete</button>
                            </td>
                        </tr>
                    `;
          });

          $("#userTable").html(tableBody);
        } else {
          $("#userTable").html(
            "<tr><td colspan='5' class='text-center text-danger'>No users found</td></tr>"
          );
        }
      },
      error: function () {
        alert("Failed to fetch users.");
      },
    });
  });

  function loadAllUsers() {
    $.ajax({
      url: "manage_entity.php",
      type: "POST",
      data: { action: "fetch_all", entity: "user" },
      dataType: "json",
      success: function (response) {
        if (response.success) {
          let users = response.data;
          let tableBody = "";

          users.forEach((user) => {
            tableBody += `
                        <tr>
                            <td>${user.id}</td>
                            <td>${user.name}</td>
                            <td>${user.email}</td>
                            <td>${user.permissions}</td>
                            <td>
                                <button class="btn btn-primary view-user-btn" data-id="${user.id}">View</button>
                                <button class="btn btn-warning edit-user-btn" data-id="${user.id}">Edit</button>
                                <button class="btn btn-danger removeUser" data-id="${user.id}">Delete</button>
                            </td>
                        </tr>
                    `;
          });

          $("#userTable").html(tableBody);
        }
      },
    });
  }

  loadAllUsers(); // Load all users on page load

  let sortColumn = "id";
    let sortOrder = "ASC";

    function loadUsers(sortColumn = "id", sortOrder = "ASC") {
        $.ajax({
            url: "manage_entity.php",
            type: "POST",
            data: { action: "fetch_sorted", entity: "user", sortColumn: sortColumn, sortOrder: sortOrder },
            dataType: "json",
            success: function (response) {
                if (response.success) {
                    let users = response.data;
                    let tableBody = "";

                    users.forEach(user => {
                        tableBody += `
                            <tr>
                                <td>${user.id}</td>
                                <td>${user.name}</td>
                                <td>${user.email}</td>
                                <td>${user.permissions}</td>
                                <td>
                                    <button class="btn btn-info view-user-btn" data-id="${user.id}">View</button>
                                    <button class="btn btn-warning edit-user-btn" data-id="${user.id}">Edit</button>
                                    <button class="btn btn-danger removeUser" data-id="${user.id}">Delete</button>
                                </td>
                            </tr>
                        `;
                    });

                    $("#userTable").html(tableBody);
                } else {
                    $("#userTable").html("<tr><td colspan='5' class='text-center text-danger'>No users found</td></tr>");
                }
            },
            error: function () {
                alert("Failed to fetch users.");
            }
        });
    }

    // Load users initially
    loadUsers();

    // Sorting event handlers
    $(".sort-btn").click(function () {
        let column = $(this).data("column");

        // Toggle sort order
        sortOrder = (sortOrder === "ASC") ? "DESC" : "ASC";

        // Load users with new sorting
        loadUsers(column, sortOrder);
    });
});
