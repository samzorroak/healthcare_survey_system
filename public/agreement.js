newPageTitle = "Manage Agreements";
document.title = newPageTitle;

$(document).ready(function () {
  // View Agreement
  $(document).on("click", ".view-agreement-btn", function () {
    let id = $(this).data("id");

    $.ajax({
      url: "manage_entity.php",
      type: "POST",
      data: { action: "view", entity: "agreement", id: id },
      dataType: "json",
      success: function (response) {
        if (response.error) {
          alert(response.error);
        } else {
          // Show agreement details in a modal
          var agreementDetails = `
                <p><strong>Survey Name:</strong> ${response.title}</p>
                <p><strong>Agreement Id:</strong> ${id}</p>
                <p><strong>Type:</strong> ${response.type}</p>
                <p><strong>Content:</strong> ${response.content}</p>
            `;
          $("#agreementDetails").html(agreementDetails); // Update the HTML with details
          $("#viewAgreementModal").modal("show"); // Show the modal
        }
      },
      error: function () {
        alert("Failed to retrieve agreement data.");
      },
    });
  });

  $(".remove-agreement-btn").click(function () {
    if (confirm("Are you sure you want to delete this agreement?")) {
      let id = $(this).data("id");

      $.ajax({
        url: "manage_entity.php",
        type: "POST",
        data: { action: "remove", entity: "agreement", id: id },
        dataType: "json",
        success: function (response) {
          location.reload(); // Refresh the page to reflect changes
          alert(response.message);
        },
        error: function () {
          alert("Error deleting agreement.");
        },
      });
    }
  });

  //Edit Agreement
  $(document).on("click", ".edit-agreement-btn", function () {
    let id = $(this).data("id");
    $("#editNotice").show();

    $.ajax({
      url: "manage_entity.php",
      type: "POST",
      data: { action: "view", entity: "agreement", id: id },
      dataType: "json",
      success: function (response) {
        if (response.success) {
          const data = response.data;

          $('select[name="survey_id"]').val(data.survey_id);
          $("#docType").val(data.type).trigger("change");
          tinymce.get("content").setContent(data.content);
          $("#hidden_content").val(data.content);

          $("#agreement_id").val(data.id);
          $("html, body").animate({ scrollTop: $("form").offset().top }, 500);
        } else {
          alert("Failed to fetch agreement data.");
        }
      },
      error: function (xhr) {
        console.error(xhr.responseText);
        alert("AJAX error while loading agreement.");
      },
    });
  });
});
