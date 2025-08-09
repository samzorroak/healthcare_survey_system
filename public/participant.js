newPageTitle = 'Manage Participants';
document.title = newPageTitle;

$(document).ready(function () {
    // Add Participant
    $("#addParticipantForm").submit(function (event) {
      event.preventDefault(); // Prevent default form submission
  
      let formData = new FormData(this); // Collect form data
      formData.append("action", "create");
      formData.append("entity", "participant");
  
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
            alert("Participant added successfully!");
            $("#addParticipantModal").modal("hide"); // Close modal
            location.reload(); // Refresh page to update list
          } else {
            alert("Error: " + response.message);
          }
        },
        error: function () {
          alert("Successfully added participant.");
          $("#addParticipantModal").modal("hide");
          location.reload();
        },
      });
    });
  
    // View Participant
    $(document).on("click", ".view-participant-btn", function () {
      let participantId = $(this).data("id");

      console.log("Fetching details for Participant ID:", participantId);
  
      $.ajax({
        url: "manage_entity.php",
        type: "POST",
        data: { action: "read_single", entity: "participant", id: participantId },
        dataType: "json",
        success: function (response) {
          if (response.error) {
            alert(response.error);
          } else {
            let participantDetails = `
                    <p><strong>Full Name:</strong> ${response.full_name}</p>
                    <p><strong>Email:</strong> ${response.email}</p>
                    <p><strong>Mobile:</strong> ${response.mobile}</p>
                    <p><strong>Date of Birth:</strong> ${response.dob}</p>
                    <p><strong>Qualification:</strong> ${response.qualification}</p>
                    <p><strong>Specialization:</strong> ${response.specialization}</p>
                    <p><strong>Registration ID:</strong> ${response.registration_id}</p>
                    <p><strong>Address:</strong> ${response.address}, ${response.city}, ${response.state}, ${response.country}</p>
                    <p><strong>Zip:</strong> ${response.zip}</p>
                    <p><strong>Status:</strong> ${response.status}</p>
                `;
  
            if (response.cancel_cheque) {
              let filePath = "uploads/cancel_cheques/" + response.cancel_cheque;
              participantDetails += `<p><strong>Cancelled Cheque:</strong> <a href='${filePath}' target='_blank'>View</a></p>`;
            } else {
              participantDetails += `<p>No Cancelled Cheque uploaded.</p>`;
            }
  
            $("#participantDetails").html(participantDetails);
            $("#viewParticipantModal").modal("show");
          }
        },
        error: function () {
          alert("Failed to retrieve participant data.");
        },
      });
    });
  
    // Edit Participant
    $(document).on("click", ".edit-participant-btn", function () {
      let participantId = $(this).data("id");
  
      $.ajax({
        url: "manage_entity.php",
        type: "POST",
        data: { action: "read_single", entity: "participant", id: participantId },
        dataType: "json",
        success: function (response) {
          if (!response.success) {
            alert(response.error || "Error retrieving participant details.");
            return;
          }
  
          let participant = response.data;
  
          $("#editId").val(participant.id);
          $("#editFullName").val(participant.full_name);
          $("#editEmail").val(participant.email);
          $("#editMobile").val(participant.mobile);
          $("#editDob").val(participant.dob);
          $("#editQualification").val(participant.qualification);
          $("#editSpecialization").val(participant.specialization);
          $("#editPanNumber").val(participant.pan);
          $("#editRegistrationId").val(participant.registration_id);
          $("#editAddress").val(participant.address);
          $("#editCity").val(participant.city);
          $("#editState").val(participant.state);
          $("#editCountry").val(participant.country);
          $("#editZip").val(participant.zip);
          $("#editStatus").val(participant.status);
  
          if (participant.cancel_cheque) {
            $("#existingChequeFile").html(
              `<a href="uploads/cancel_cheques/${participant.cancel_cheque}" target="_blank">View Cheque</a>`
            );
          } else {
            $("#existingChequeFile").html("No cancelled cheque uploaded.");
          }
  
          $("#editParticipantModal").modal("show");
        },
        error: function () {
          alert("Failed to retrieve participant data.");
        },
      });
    });
  
    $("#updateParticipantBtn").click(function () {
      let formData = new FormData();
  
      formData.append("action", "update");
      formData.append("entity", "participant");
      formData.append("id", $("#editId").val());
      formData.append("full_name", $("#editFullName").val());
      formData.append("email", $("#editEmail").val());
      formData.append("mobile", $("#editMobile").val());
      formData.append("dob", $("#editDob").val());
      formData.append("qualification", $("#editQualification").val());
      formData.append("specialization", $("#editSpecialization").val());
      formData.append("pan", $("#editPanNumber").val());
      formData.append("registration_id", $("#editRegistrationId").val());
      formData.append("address", $("#editAddress").val());
      formData.append("zip", $("#editZip").val());
      formData.append("city", $("#editCity").val());
      formData.append("state", $("#editState").val());
      formData.append("country", $("#editCountry").val());
      formData.append("status", $("#editStatus").val());
  
      let chequeFile = $("#editCancelCheque")[0].files[0];
      if (chequeFile) {
        formData.append("cancel_cheque", chequeFile);
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
          alert("Participant Updated Successfully");
          if (response.success) {
            $("#editParticipantModal").modal("hide");
            location.reload();
          }
        },
        error: function () {
          alert("Participant Updated Successfully");
        },
      });
    });
  
    // Delete Participant
    $(document).on("click", ".remove-participant-btn", function () {
      let participantId = $(this).data("id");
  
      if (confirm("Are you sure you want to delete this participant?")) {
        $.ajax({
          url: "manage_entity.php",
          type: "POST",
          data: { action: "delete", entity: "participant", id: participantId },
          dataType: "json",
          success: function (response) {
            console.log("Server Response:", response);
  
            if (response.success) {
              alert("participant deleted successfully!");
              location.reload(); // Refresh page to update list
            } else {
              alert("Error: " + response.message);
            }
          },
          error: function () {
            alert("Failed to delete participant. Please try again.");
          },
        });
      }
    });

    let sortColumn = "id"; // Default sorting column
    let sortOrder = "ASC"; // Default sorting order (ASC or DESC)

    // Function to load participants with sorting
    function loadParticipants(sortColumn = "id", sortOrder = "ASC") {
      $.ajax({
          url: "manage_entity.php",
          type: "POST",
          data: { action: "fetch_sorted", entity: "participant", sortColumn: sortColumn, sortOrder: sortOrder },
          dataType: "json",
          success: function (response) {
              if (response.success) {
                  let participants = response.data;
                  let tableBody = "";

                  participants.forEach((participant) => {
                      tableBody += `
                          <tr>
                              <td>${participant.id}</td>
                              <td>${participant.full_name}</td>
                              <td>${participant.email}</td>
                              <td>${participant.mobile}</td>
                              <td>${participant.qualification}</td>
                              <td>${participant.specialization}</td>
                              <td>
                                  <button class="btn btn-info view-participant-btn" data-id="${participant.id}">View</button>
                                  <button class="btn btn-warning edit-participant-btn" data-id="${participant.id}">Edit</button>
                                  <button class="btn btn-danger remove-participant-btn" data-id="${participant.id}">Delete</button>
                              </td>
                          </tr>
                      `;
                  });

                  $("#participantTable").html(tableBody);
              } else {
                  $("#participantTable").html("<tr><td colspan='6' class='text-center text-danger'>No participants found</td></tr>");
              }
          },
          error: function () {
              alert("Failed to fetch participants.");
          }
      });
  }

  // Sort when clicking table headers
  $(".sort-btn").on("click", function () {
      let column = $(this).data("column");

      // Toggle sort order
      sortOrder = (sortColumn === column && sortOrder === "ASC") ? "DESC" : "ASC";
      sortColumn = column;

      loadParticipants(sortColumn, sortOrder);
  });

// Search Participant
$("#searchParticipant").on("keyup", function () {
  let query = $(this).val().trim();

  if (query.length === 0) {
    loadAllParticipants(); // Reload all participants when search is cleared
    return;
  }

  $.ajax({
    url: "manage_entity.php",
    type: "POST",
    data: { action: "search", entity: "participant", query: query },
    dataType: "json",
    success: function (response) {
      if (response.success) {
        let participants = response.data;
        let tableBody = "";

        participants.forEach((participant) => {
          tableBody += `
                    <tr>
                        <td>${participant.id}</td>
                        <td>${participant.full_name}</td>
                        <td>${participant.email}</td>
                        <td>${participant.mobile}</td>
                        <td>${participant.qualification}</td>
                        <td>${participant.specialization}</td>
                        <td>
                            <button class="btn btn-info view-participant-btn" data-id="${participant.id}">View</button>
                            <button class="btn btn-warning edit-participant-btn" data-id="${participant.id}">Edit</button>
                            <button class="btn btn-danger remove-participant-btn" data-id="${participant.id}">Delete</button>
                        </td>
                    </tr>
                `;
        });

        $("#participantTable").html(tableBody);
      } else {
        $("#participantTable").html(
          "<tr><td colspan='6' class='text-center text-danger'>No participants found</td></tr>"
        );
      }
    },
    error: function () {
      alert("Failed to fetch participants.");
    },
  });
});

// Load all participants when the page loads
function loadAllParticipants() {
  $.ajax({
    url: "manage_entity.php",
    type: "POST",
    data: { action: "fetch_all", entity: "participant" },
    dataType: "json",
    success: function (response) {
      if (response.success) {
        let participants = response.data;
        let tableBody = "";

        participants.forEach((participant) => {
          tableBody += `
                    <tr>
                        <td>${participant.id}</td>
                        <td>${participant.full_name}</td>
                        <td>${participant.email}</td>
                        <td>${participant.mobile}</td>
                        <td>${participant.qualification}</td>
                        <td>${participant.specialization}</td>
                        <td>
                            <button class="btn btn-info view-participant-btn" data-id="${participant.id}">View</button>
                            <button class="btn btn-warning edit-participant-btn" data-id="${participant.id}">Edit</button>
                            <button class="btn btn-danger remove-participant-btn" data-id="${participant.id}">Delete</button>
                        </td>
                    </tr>
                `;
        });

        $("#participantTable").html(tableBody);
      }
    },
    error: function () {
      alert("Failed to load participants.");
    },
  });
}

// Reload all participants when search input is cleared
$("#searchParticipant").on("input", function () {
  if ($(this).val().trim() === "") {
    loadAllParticipants();
  }
});


loadAllParticipants(); // Load all participants on page load
});