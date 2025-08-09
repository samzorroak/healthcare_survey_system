newPageTitle = "Manage Payouts";
document.title = newPageTitle;

$(document).ready(function () {
  fetchSurveys();

  function fetchSurveys() {
    $.ajax({
      url: "manage_entity.php",
      method: "POST",
      data: { entity: "payout", action: "fetch_surveys" },
      dataType: "html", // important: don't expect JSON here
      success: function (response) {
        // console.log("Raw response:", response); //See what PHP is returning
        $("#surveyBody").html(response);
      },
      error: function (xhr, status, error) {
        console.error("AJAX Error:", error);
        // console.log("Full response text:", xhr.responseText);
      },
    });
  }

  $(document).on("click", ".view-btn", function () {
    const surveyId = $(this).data("id");
    console.log("Survey ID:", surveyId);

    // Clear any previous content from the modal
    const $tableBody = $("#viewParticipantsModal").find(
      "#participantTableBody"
    );
    $tableBody.empty();

    $.ajax({
      url: "manage_entity.php",
      method: "POST",
      data: {
        entity: "payout",
        action: "fetch_survey_participants",
        survey_id: surveyId,
      },
      dataType: "json",
      success: function (response) {
        console.log("Response:", response);

        if (response.status === "success") {
          const participants = response.data;

          if (!participants || participants.length === 0) {
            $tableBody.html(`
            <tr>
              <td colspan="4" class="text-center text-muted">No participants found</td>
            </tr>
          `);
          } else {
            participants.forEach((p) => {
              // Payment status derived from amount
              const paymentStatus =
                p.payment_status === "Done" ? "Done" : "Pending";
              const buttonLabel = paymentStatus === "Done" ? "Paid" : "Pay";
              const isPaid = paymentStatus === "Done";

              const row = $("<tr></tr>");
              row.append($("<td></td>").text(p.full_name));
              row.append($("<td></td>").text(p.email));
              row.append($("<td></td>").text(p.mobile));
              row.append($("<td></td>").text(p.completed ? "Yes" : "No"));
              row.append($("<td>₹</td>").text(p.payment_amount));
              row.append($("<td></td>").text(p.payment_status));

              const actionBtn = $("<button></button>")
                .addClass("btn btn-sm pay-btn")
                .addClass(isPaid ? "btn-secondary" : "btn-success")
                .text(buttonLabel)
                .attr("data-participant-id", p.participant_id)
                .attr("data-survey-id", p.survey_id)
                .attr("data-amount", p.payment_amount ?? 0)
                .attr("data-paid", isPaid);
              if (!p.completed || p.payment_status === "Done") {
                actionBtn.prop("disabled", true);
              }

              const editBtn = $("<button></button>")
                .addClass("btn btn-sm btn-warning ml-2 edit-payment-btn")
                .text("Edit Amount")
                .attr("data-participant-id", p.participant_id)
                .attr("data-survey-id", p.survey_id)
                .attr("data-amount", p.payment_amount);

              const viewResponseBtn = $("<button></button>")
                .addClass("btn btn-sm btn-info ml-2 view-response-btn")
                .text("View Responses")
                .attr("data-participant-id", p.participant_id)
                .attr("data-survey-id", p.survey_id);

              row.append($("<td></td>").append(actionBtn, editBtn, viewResponseBtn));

              console.log("Participant Data:", p);

              $tableBody.append(row);
            });
          }

          $("#viewParticipantsModal").modal("show");
        } else {
          alert("Failed to fetch participants.");
        }
      },
      error: function (xhr, status, error) {
        console.error("AJAX Error:", error);
        alert("Something went wrong while fetching participants.");
      },
    });
  });

  $(document).on("click", ".pay-btn", function () {
    const $btn = $(this);
    const participantId = $btn.data("participant-id");
    const surveyId = $btn.data("survey-id");
    console.log("Survey ID:", surveyId);
    console.log("Participant ID:", participantId);

    $.ajax({
      url: "manage_entity.php",
      type: "POST",
      dataType: "json", // Let jQuery auto-parse JSON
      data: {
        action: "mark_paid",
        entity: "payout",
        participant_id: participantId,
        survey_id: surveyId,
      },
      success: function (res) {
        if (res.status === "success") {
          $btn
            .removeClass("btn-success")
            .addClass("btn-secondary")
            .text("Paid")
            .prop("disabled", true);
          $("#viewParticipantsModal").modal("show");
        } else {
          alert("Error: " + res.message);
        }
      },
      error: function (xhr) {
        alert("AJAX error: " + xhr.responseText);
      },
    });
  });

  $(document).on("change", ".payment-input, .payment-status", function () {
    const row = $(this).closest(".participant-row");
    const surveyId = row.data("survey-id");
    const participantId = row.data("participant-id");
    const paymentAmount = row.find(".payment-input").val();
    const paymentStatus = row.find(".payment-status").val();

    $.ajax({
      url: "manage_entity.php",
      method: "POST",
      data: {
        entity: "payout",
        action: "update_payment",
        survey_id: surveyId,
        participant_id: participantId,
        payment_amount: paymentAmount,
        payment_status: paymentStatus,
      },
      success: function (res) {
        console.log("Payment updated");
      },
    });
  });

  $(document).on("click", ".verify-btn", function () {
    const participantId = $(this).data("participant-id");
    const surveyId = $(this).data("survey-id");

    $.ajax({
      url: "manage_entity.php",
      method: "POST",
      data: {
        entity: "payout",
        action: "fetch_responses",
        participant_id: participantId,
        survey_id: surveyId,
      },
      success: function (res) {
        alert(res); // You can make a modal popup here if needed
      },
    });
  });

  $(document).on("click", ".edit-payment-btn", function () {
    const participantId = $(this).data("participant-id");
    const surveyId = $(this).data("survey-id");
    const currentAmount = $(this).data("amount");

    const newAmount = prompt("Enter new payment amount (₹):", currentAmount);
    if (newAmount !== null && newAmount.trim() !== "") {
      $.ajax({
        url: "manage_entity.php",
        method: "POST",
        data: {
          entity: "payout",
          action: "update_payment",
          participant_id: participantId,
          survey_id: surveyId,
          payment_amount: newAmount,
        },
        success: function (res) {
          if (res === "success") {
            alert("Payment amount updated!");
            $(".view-btn[data-id='" + surveyId + "']").click(); // refresh participant list
          } else {
            alert("Error updating amount.");
          }
        },
        error: function () {
          alert("AJAX error.");
        },
      });
    }
  });

  $(document).on("click", ".view-response-btn", function () {
    const surveyId = $(this).data("survey-id");
    const participantId = $(this).data("participant-id");

    $.ajax({
      url: "manage_entity.php",
      method: "POST",
      data: {
        action: "fetch_responses",
        entity: "payout",
        survey_id: surveyId,
        participant_id: participantId,
      },
      dataType: "json",
      success: function (response) {
        if (response.status === "success" && Array.isArray(response.data)) {
          const responses = response.data;
          const container = $("#participantResponsesBody");
          container.empty();

          responses.forEach((item, index) => {
            const block = `
            <div class="mb-3">
              <strong>Q${index + 1}: ${item.question_text}</strong><br>
              <span>${item.answer_text}</span>
            </div>
          `;
            container.append(block);
          });

          $("#participantResponsesModal").modal("show");
        } else {
          alert("No responses found.");
        }
      },
      error: function (xhr, status, error) {
        console.error("Error fetching responses:", error);
        alert("Failed to load responses.");
      },
    });
  });

});
