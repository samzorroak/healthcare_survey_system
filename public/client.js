newPageTitle = 'Manage Clients';
document.title = newPageTitle;

$(document).ready(function () {
  $(document).ready(function () {
    $("#addClientForm").submit(function (event) {
      event.preventDefault(); // Prevent default form submission

      let formData = new FormData(this); // Collect form data
      formData.append("action", "create");
      formData.append("entity", "client");

      $.ajax({
        url: "manage_entity.php",
        type: "POST",
        data: formData,
        dataType: "json",
        processData: false,
        contentType: false,
        success: function (response) {
          console.log("Server Response:", response);

          if (response.success) {
            alert("Client added successfully!");
            $("#addClientModal").modal("hide"); // Close modal
            location.reload(); // Refresh page to update list
          } else {
            alert("Error: " + response.message);
          }
        },
        error: function () {
          alert("Failed to add client. Please try again.");
        },
      });
    });
  });

  // View Client
  $(document).on("click", ".view-client-btn", function () {
    let clientId = $(this).data("id");

    $.ajax({
      url: "manage_entity.php",
      type: "POST",
      data: { action: "read_single", entity: "client", id: clientId },
      dataType: "json",
      success: function (response) {
        if (response.error) {
          alert(response.error);
        } else {
          let clientDetails = `
                  <p><strong>Organization Name:</strong> ${response.organization_name}</p>
                  <p><strong>Contact Person Name:</strong> ${response.contact_person_name}</p>
                  <p><strong>Contact Person Email:</strong> ${response.contact_person_email}</p>
                  <p><strong>Contact Person Mobile:</strong> ${response.contact_person_mobile}</p>
                  <p><strong>Adress:</strong> ${response.address_line_1}, ${response.address_line_2}</p>
                  <p><strong>Location:</strong> ${response.city}, ${response.state}, ${response.country}</p>
                  <p><strong>Zip:</strong> ${response.zip}</p>
                  <p><strong>Status:</strong> ${response.status}</p>
              `;
          // Display the agreement file link
          if (response.agreement_file) {
            let filePath = "uploads/agreements/" + response.agreement_file;
            // Provide a link to download the agreement
            clientDetails += `<p><strong>Agreement File:</strong> <a href='${filePath}' target='_blank'>Download/View Agreement</a></p>`;
          } else {
            clientDetails += `<p>No agreement file uploaded.</p>`;
          }
          $("#clientDetails").html(clientDetails);
          $("#viewClientModal").modal("show");
        }
      },
      error: function () {
        alert("Failed to retrieve client data.");
      },
    });
  });

  // Edit Client
  $(document).on("click", ".edit-client-btn", function () {
    let clientId = $(this).data("id");

    $.ajax({
      url: "manage_entity.php",
      type: "POST",
      data: { action: "read_single", entity: "client", id: clientId },
      dataType: "json",
      success: function (response) {
        if (!response.success) {
          alert(response.error || "Error retrieving client details.");
          return;
        }

        let client = response.data;

        $("#editClientId").val(client.id);
        $("#editOrganizationName").val(client.organization_name);
        $("#editContactPersonName").val(client.contact_person_name);
        $("#editContactPersonEmail").val(client.contact_person_email);
        $("#editContactPersonMobile").val(client.contact_person_mobile);
        $("#editAddressLine1").val(client.address_line_1);
        $("#editAddressLine2").val(client.address_line_2);
        $("#editCity").val(client.city);
        $("#editState").val(client.state);
        $("#editCountry").val(client.country);
        $("#editZip").val(client.zip);
        $("#editStatus").val(client.status);

        // Handle Agreement File
        if (client.agreement_file) {
          $("#existingAgreementFile").html(
            `<a href="uploads/agreements/${client.agreement_file}" target="_blank">View Agreement</a>`
          );
        } else {
          $("#existingAgreementFile").html("No agreement file uploaded.");
        }

        $("#editClientModal").modal("show");
      },
      error: function () {
        alert("Failed to retrieve client data.");
      },
    });
  });

  $("#updateClientBtn").click(function () {
    let formData = new FormData();

    formData.append("action", "update");
    formData.append("entity", "client");
    formData.append("id", $("#editClientId").val());
    formData.append("organization_name", $("#editOrganizationName").val());
    formData.append("contact_person_name", $("#editContactPersonName").val());
    formData.append("contact_person_email", $("#editContactPersonEmail").val());
    formData.append(
      "contact_person_mobile",
      $("#editContactPersonMobile").val()
    );
    formData.append("address_line_1", $("#editAddressLine1").val());
    formData.append("address_line_2", $("#editAddressLine2").val());
    formData.append("zip", $("#editZip").val());
    formData.append("city", $("#editCity").val());
    formData.append("state", $("#editState").val());
    formData.append("country", $("#editCountry").val());
    formData.append("status", $("#editStatus").val());

    let agreementFile = $("#editAgreementFile")[0].files[0];
    if (agreementFile) {
      formData.append("agreement_file", agreementFile);
    }

    $.ajax({
      url: "manage_entity.php",
      type: "POST",
      data: formData,
      dataType: "json",
      processData: false,
      contentType: false,
      success: function (response) {
        console.log("Server Response:", response);
        alert("Client Updated Sucessfully");
        if (response.success) {
          $("#editClientModal").modal("hide");
          location.reload();
        }
      },
      error: function () {
        alert("Failed to update client.");
      },
    });
  });

  // Delete Client
  $(document).on("click", ".remove-client-btn", function () {
    let clientId = $(this).data("id");

    if (confirm("Are you sure you want to delete this client?")) {
      $.ajax({
        url: "manage_entity.php",
        type: "POST",
        data: { action: "delete", entity: "client", id: clientId },
        dataType: "json",
        success: function (response) {
          console.log("Server Response:", response);

          if (response.success) {
            alert("Client deleted successfully!");
            location.reload(); // Refresh page to update list
          } else {
            alert("Error: " + response.message);
          }
        },
        error: function () {
          alert("Failed to delete client. Please try again.");
        },
      });
    }
  });

  let sortColumn = "id"; // Default sorting column
    let sortOrder = "ASC"; // Default sorting order (ASC or DESC)

    // Function to load clients with sorting
    function loadClients(sortColumn = "id", sortOrder = "ASC") {
        $.ajax({
            url: "manage_entity.php",
            type: "POST",
            data: { action: "fetch_sorted", entity: "client", sortColumn: sortColumn, sortOrder: sortOrder },
            dataType: "json",
            success: function (response) {
                if (response.success) {
                    let clients = response.data;
                    let tableBody = "";

                    clients.forEach((client) => {
                        tableBody += `
                            <tr>
                                <td>${client.id}</td>
                                <td>${client.organization_name}</td>
                                <td>${client.contact_person_name}</td>
                                <td>${client.contact_person_email}</td>
                                <td>${client.contact_person_mobile}</td>
                                <td>${client.status}</td>
                                <td>
                                    <button class="btn btn-info view-client-btn" data-id="${client.id}">View</button>
                                    <button class="btn btn-warning edit-client-btn" data-id="${client.id}">Edit</button>
                                    <button class="btn btn-danger delete-client-btn" data-id="${client.id}">Delete</button>
                                </td>
                            </tr>
                        `;
                    });

                    $("#clientTable").html(tableBody);
                } else {
                    $("#clientTable").html("<tr><td colspan='7' class='text-center text-danger'>No clients found</td></tr>");
                }
            },
            error: function () {
                alert("Failed to fetch clients.");
            }
        });
    }

    // Sort when clicking table headers
    $(".sort-btn").on("click", function () {
        let column = $(this).data("column");

        // Toggle sort order
        sortOrder = (sortColumn === column && sortOrder === "ASC") ? "DESC" : "ASC";
        sortColumn = column;

        loadClients(sortColumn, sortOrder);
    });

  // Search Client
  $("#searchClient").on("keyup", function () {
    let query = $(this).val().trim();

    if (query.length === 0) {
      loadAllClients(); // Reload all clients when search is cleared
      return;
    }

    $.ajax({
      url: "manage_entity.php",
      type: "POST",
      data: { action: "search", entity: "client", query: query },
      dataType: "json",
      success: function (response) {
        if (response.success) {
          let clients = response.data;
          let tableBody = "";

          clients.forEach((client) => {
            tableBody += `
                      <tr>
                          <td>${client.id}</td>
                          <td>${client.organization_name}</td>
                          <td>${client.contact_person_name}</td>
                          <td>${client.contact_person_email}</td>
                          <td>${client.contact_person_mobile}</td>
                          <td>${client.status}</td>
                          <td>
                              <button class="btn btn-info view-client-btn" data-id="${client.id}">View</button>
                              <button class="btn btn-warning edit-client-btn" data-id="${client.id}">Edit</button>
                              <button class="btn btn-danger delete-client-btn" data-id="${client.id}">Delete</button>
                          </td>
                      </tr>
                  `;
          });

          $("#clientTable").html(tableBody);
        } else {
          $("#clientTable").html(
            "<tr><td colspan='7' class='text-center text-danger'>No clients found</td></tr>"
          );
        }
      },
      error: function () {
        alert("Failed to fetch clients.");
      },
    });
  });

  // Load all clients when the page loads
  function loadAllClients() {
    $.ajax({
      url: "manage_entity.php",
      type: "POST",
      data: { action: "fetch_all", entity: "client" },
      dataType: "json",
      success: function (response) {
        $('#clientTable').html(response);
      }    
    });
  }

  // Reload all clients when search input is cleared
  $("#searchClient").on("input", function () {
    if ($(this).val().trim() === "") {
      loadAllClients();
    }
  });

});
