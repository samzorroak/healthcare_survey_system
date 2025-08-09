newPageTitle = "Manage Surveys";
document.title = newPageTitle;

$(document).ready(function () {
  function loadClients() {
    $.ajax({
      url: "manage_entity.php",
      type: "POST",
      data: { action: "get_client", entity: "client" },
      dataType: "json",
      success: function (response) {
        console.log("Clients loaded:", response);
        var clientDropdown = $("#client");
        clientDropdown.empty();

        if (response.success) {
          clientDropdown.append('<option value="">Select Client</option>');
          response.data.forEach(function (client) {
            clientDropdown.append(
              `<option value="${client.id}">${client.organization_name}</option>`
            );
          });
        } else {
          clientDropdown.append(
            '<option value="">No clients available</option>'
          );
        }
      },
      error: function (xhr, status, error) {
        console.error("Error loading clients:", xhr.responseText);
      },
    });
  }

  $("#addSurveyModal").on("show.bs.modal", function () {
    loadClients();
  });

  // Fetch Surveys
  function loadSurveys() {
    $.ajax({
      url: "manage_entity.php",
      type: "POST",
      data: { action: "read", entity: "survey" },
      dataType: "json",
      success: function (response) {
        $("#surveyTable").html(response);
      },
    });
  }
  loadSurveys(); // Load surveys on page load

  // Add Survey
  $("#surveyForm").submit(function (e) {
    e.preventDefault();

    let formData = new FormData(this);
    formData.append("action", "create");
    formData.append("entity", "survey");

    // Get selected client ID
    let clientId = $("#client").val();
    if (!clientId) {
      alert("Please select a client.");
      return;
    }
    formData.append("client_id", clientId);

    // Collect participants
    let participants = [];
    $('input[name="participants[]"]:checked').each(function () {
      participants.push($(this).val());
    });

    if (participants.length === 0) {
      alert("Please select at least one participant.");
      return;
    }

    // Collect questions
    const questionsArray = [];
    $(".question-block").each(function () {
      const questionText = $(this)
        .find('input[name^="questions"][name$="[text]"]')
        .val();
      const questionType = $(this)
        .find('select[name^="questions"][name$="[type]"]')
        .val();
      const required = $(this).find(".question-required").is(":checked")
        ? 1
        : 0;

      const options = [];
      $(this)
        .find('input[name^="questions"][name*="[options]"]')
        .each(function () {
          const optionText = $(this).val();
          if (optionText.trim() !== "") {
            options.push(optionText.trim());
          }
        });

      if (questionText && options.length > 1) {
        questionsArray.push({
          question_text: questionText,
          question_type: questionType,
          required: required,
          options: options,
        });
      }
    });

    formData.append("participants", JSON.stringify(participants));
    formData.append("questions", JSON.stringify(questionsArray));

    $.ajax({
      url: "manage_entity.php",
      type: "POST",
      data: formData,
      dataType: "json",
      contentType: false,
      processData: false,
      success: function (response) {
        console.log("Server Response:", response);
        if (response.success) {
          alert("Survey added successfully!");
          $("#addSurveyModal").modal("hide");
          location.reload();
        } else {
          alert("Error: " + response.message);
        }
      },
      error: function (xhr) {
        console.error("AJAX Error:", xhr.responseText);
        alert("An error occurred while saving the survey.");
      },
    });
  });

  //Search Participants
  $("#searchParticipant").on("keyup", function () {
    let searchValue = $(this).val().toLowerCase();

    $(".participant-item").each(function () {
      let city = $(this).data("city").toLowerCase();
      let state = $(this).data("state").toLowerCase();
      let specialization = $(this).data("specialization").toLowerCase();

      if (
        city.includes(searchValue) ||
        state.includes(searchValue) ||
        specialization.includes(searchValue)
      ) {
        $(this).show(); // Show matching participants
      } else {
        $(this).hide(); // Hide non-matching participants
      }
    });
  });

  // View Survey
  $(document).on("click", ".view-survey-btn", function () {
    let surveyId = $(this).data("id");

    console.log("Fetching details for Survey ID:", surveyId);

    $.ajax({
      url: "manage_entity.php",
      type: "POST",
      data: { action: "read_single", entity: "survey", id: surveyId },
      dataType: "json",
      success: function (response) {
        if (!response.success) {
          alert(response.message || "Failed to retrieve survey data.");
          return;
        }

        let survey = response;
        let participantList =
          response.participants.length > 0
            ? response.participants
                .map(
                  (p) => `
                <li>
                    ${p.full_name} (${p.email}) <br> Email Sent: ${
                    p.email_sent ? "Yes" : "No"
                  } 
                    <br> <a href="${
                      p.invitation_link
                    }" target="_blank">View Invitation</a>
                </li>`
                )
                .join("")
            : "<li>No participants invited yet.</li>";

        // Build questions and options section
        let questionsHTML =
          response.questions.length > 0
            ? response.questions
                .map((q, index) => {
                  let options = q.options
                    .map(
                      (opt) => `<li>${opt.answer_text}</li>` // assuming 'option_text' is the column name
                    )
                    .join("");
                  return `
                    <div class="slider-box mb-3 p-2 border rounded">
                      <p><strong>Q${index + 1}:</strong> (${q.question_type}) ${
                    q.question_text
                  }</p>
                      <ul>${options}</ul>
                    </div>`;
                })
                .join("")
            : "<p>No questions added to this survey.</p>";

        let surveyDetails = `
                <p><strong>Survey Title:</strong> ${survey.title}</p>
                <p><strong>Client:</strong> ${survey.client}</p>
                <p><strong>Description:</strong> ${survey.description}</p>
                <p><strong>Amount:</strong> ${survey.amount}</p>
                <p><strong>Start Date:</strong> ${survey.start_date}</p>
                <p><strong>End Date:</strong> ${survey.end_date}</p>
                <p><strong>Status:</strong> ${survey.status}</p>
                <p><strong>Participants:</strong></p>
                <p><strong>Survey Questions:</strong></p>
                <div class="slider-box">${questionsHTML}</div>
                <hr>
                <div class="slider-box"><ul>${participantList}</ul></div>
            `;

        console.log("Full AJAX response:", response);

        $("#surveyDetails").html(surveyDetails);
        $("#viewSurveyModal").modal("show");
      },
      error: function (xhr) {
        console.error("AJAX Error:", xhr.responseText);
        alert("Failed to retrieve survey data.");
      },
    });
  });

  // Remove a question block (delegated)
  $(document).on("click", ".remove-question", function () {
    $(this).closest(".question-block").remove();
  });

  // Remove an option input (delegated)
  $(document).on("click", ".remove-option", function () {
    $(this).closest(".option-input-group").remove();
  });

  // Open Edit Survey Modal & Load Data
  $(document).on("click", ".edit-survey-btn", function () {
    let surveyId = $(this).data("id"); // Get survey ID from button
    fetchSurveyDetails(surveyId);
  });

  // Fetch Survey Details and Open Modal
  function fetchSurveyDetails(surveyId) {
    $.ajax({
      url: "manage_entity.php",
      type: "POST",
      data: { action: "fetch_survey", entity: "survey", survey_id: surveyId },
      dataType: "json",
      success: function (response) {
        if (response.success) {
          let survey = response.data;
          let selectedParticipants = response.selected_participants || []; // Ensure it's an array
          let questions = response.questions || []; // Ensure it's an array

          $("#editSurveyId").val(survey.id);
          $("#editTitle").val(survey.title);
          $("#editDescription").val(survey.description);
          $("#editAmount").val(survey.amount);
          $("#editStart_date").val(survey.start_date);
          $("#editEnd_date").val(survey.end_date);
          $("#editstatus").val(survey.status);

          let questionContainer = $("#editQuestionContainer");
          questionContainer.empty();

          questions.forEach((question, qIndex) => {
            const questionId = question.id;
            const questionText = question.question_text;
            const questionType = question.question_type;
            const required = question.required;

            // Create question block
            const questionBlock = $(`
              <div class="question-block mb-4" data-question-id="${questionId}">
                  <label>Question ${qIndex + 1}</label>
                  <input type="text" name="questions[${qIndex}][text]" class="form-control question-text" value="${questionText}" required />
                  <select name="questions[${qIndex}][type]" class="form-control question-type mt-2" required>
                      <option value="single" ${
                        questionType === "single" ? "selected" : ""
                      }>Single Choice</option>
                      <option value="multiple" ${
                        questionType === "multiple" ? "selected" : ""
                      }>Multiple Choice</option>
                  </select>
                  <input type="checkbox" class="question-required mt-2" ${
                        required ? "checked" : ""
                  }/>
                  <label class="mt-2">Required</label>
                  <div class="options mt-2"></div>
                  <button type="button" class="btn btn-primary btn-sm add-option mt-2">+ Add Option</button>
                  <button type="button" class="btn btn-danger btn-sm remove-question mt-2 ms-2">Remove Question</button>
              </div>
            `);

            const optionsContainer = questionBlock.find(".options");
            const options = question.options || [];

            options.forEach((opt, optIndex) => {
              const optionInput = $(`
                    <div class="input-group mb-1 option-item" data-option-id="${opt.id}">
                        <input type="text" name="questions[${qIndex}][options][${optIndex}][text]" value="${opt.answer_text}" class="form-control option-text" required />
                        <input type="hidden" name="questions[${qIndex}][options][${optIndex}][id]" value="${opt.id}" />
                        <button type="button" class="btn btn-danger btn remove-option">X</button>
                    </div>
                `);
              optionsContainer.append(optionInput);
            });

            questionBlock.on("click", ".remove-option", function () {
              $(this).closest(".option-item").remove();
            });

            questionBlock.find(".add-option").on("click", function () {
              const optionsContainer = $(this).siblings(".options");
              const optionIndex = optionsContainer.children().length;
              const newOption = `
                <div class="input-group mb-1 option-item">
                    <input type="text" name="questions[${qIndex}][options][${optionIndex}][text]" class="form-control option-text" required placeholder="Option text" />
                    <button type="button" class="btn btn-danger btn remove-option">X</button>
                </div>`;
              optionsContainer.append(newOption);
            });

            $("#editQuestionContainer").append(questionBlock);
            // Remove question
            questionBlock.find(".remove-question").on("click", function () {
              $(this).closest(".question-block").remove();
            });
          });

          // Populate Participant List
          let allParticipants = response.all_participants || [];
          let participantContainer = $("#editParticipantContainer");
          participantContainer.empty();

          allParticipants.forEach((participant) => {
            let isChecked = selectedParticipants.includes(participant.id)
              ? "checked"
              : "";
            let participantHTML = `
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="participants[]" value="${participant.id}" id="participant-${participant.id}" ${isChecked}>
                <label class="form-check-label" for="participant-${participant.id}">
                  <strong>${participant.full_name} (${participant.state}, ${participant.city}, ${participant.specialization})</strong>
                </label>
              </div>`;
            participantContainer.append(participantHTML);
          });

          $("#editSurveyModal").modal("show"); // Open modal
        } else {
          alert("Error loading survey data: " + response.message);
        }
      },
      error: function (xhr) {
        console.error("AJAX Error:", xhr.responseText);
        alert("Error fetching survey details.");
      },
    });
  }

  // Handle Update Survey Submission
  $("#editSurveyForm").submit(function (e) {
    e.preventDefault();

    let formData = new FormData(this);
    formData.append("action", "update");
    formData.append("entity", "survey");

    // Collect participants
    let participants = [];
    $('input[name="participants[]"]:checked').each(function () {
      participants.push($(this).val());
    });

    if (participants.length === 0) {
      alert("Please select at least one participant.");
      return;
    }

    formData.append("participants", JSON.stringify(participants));

    // Collect questions
    const questionsArray = [];
    
    let questionId = $(this).data("question-id") || null;
    $(".question-block").each(function () {
      const questionText = $(this)
        .find('input[name^="questions"][name$="[text]"]')
        .val();
      const questionType = $(this)
        .find('select[name^="questions"][name$="[type]"]')
        .val();
      const required = $(this).find(".question-required").is(":checked")
        ? 1
        : 0;

      const options = [];
      $(this)
        .find('input[name^="questions"][name*="[options]"][name$="[text]"]')
        .each(function () {
          let optionId = $(this).siblings('input[type="hidden"]').val() || null;
          const optionText = $(this).val();
          if (optionText.trim() !== "") {
            options.push({ id: optionId, text: optionText.trim() });
          }
        });



      if (questionText && options.length > 1) {
        questionsArray.push({
          id: questionId,
          question_text: questionText,
          question_type: questionType,
          required: required,
          options: options,
        });
      }
    });

    formData.append("questions", JSON.stringify(questionsArray));

    $.ajax({
      url: "manage_entity.php",
      type: "POST",
      data: formData,
      dataType: "json",
      contentType: false,
      processData: false,
      success: function (response) {
        if (response.success) {
          alert("Survey updated successfully!");
          $("#editSurveyModal").modal("hide");
          location.reload();
        } else {
          alert("Error updating survey: " + response.message);
        }
      },
      error: function (xhr) {
        console.error("AJAX Error:", xhr.responseText);
        alert("An error occurred while updating the survey.");
      },
    });
  });

  // Delete Survey
  $(document).on("click", ".remove-survey-btn", function () {
    const surveyId = $(this).data("id");

    if (confirm("Are you sure you want to delete this survey?")) {
      $.ajax({
        url: "manage_entity.php",
        type: "POST",
        data: { action: "delete", entity: "survey", id: surveyId },
        success: function (response) {
          alert(response.message);
          if (response.success) {
            location.reload();
          }
        },
        error: function () {
          alert(response.message);
        },
      });
    }
  });

  // Send Invitations
  function sendInvitation(surveyId, participantId) {
    $.ajax({
      url: "manage_entity.php?action=send_invitation",
      type: "POST",
      data: { survey_id: surveyId, participant_id: participantId },

      success: function (response) {
        let res = JSON.parse(response);
        if (res.success) {
          alert(`Invitation sent successfully to Survey ID: ${surveyId}`);
        } else {
          alert(`Error sending invitation: ${res.message}`);
        }
      },

      error: function () {
        alert("Error sending invitation.");
      },
    });
  }

  // Handle Checkbox Selection for Invitations
  $('.participant-checkbox-container input[type="checkbox"]').change(
    function () {
      let surveyId = $("#survey_id").val();
      let participantId = $(this).val();

      if (this.checked) {
        sendInvitation(surveyId, participantId); // Send invitation
      }
    }
  );

  let sortColumn = "id"; // Default sorting column
  let sortOrder = "ASC"; // Default sorting order (ASC or DESC)

  // Function to load surveys with sorting
  function loadSurveys(sortColumn = "id", sortOrder = "ASC") {
    $.ajax({
      url: "manage_entity.php",
      type: "POST",
      data: {
        action: "fetch_sorted",
        entity: "survey",
        sortColumn: sortColumn,
        sortOrder: sortOrder,
      },
      dataType: "json",
      success: function (response) {
        if (response.success) {
          let surveys = response.data;
          let tableBody = "";
          surveys.forEach((survey) => {
            tableBody += `
                            <tr>
                                <td>${survey.id}</td>
                                <td>${survey.title}</td>
                                <td>${survey.client_name}</td>
                                <td>${survey.amount}</td>
                                <td>${survey.status}</td>
                                <td>
                                    <button class="btn btn-info view-survey-btn" data-id="${survey.id}">View</button>
                                    <button class="btn btn-warning edit-survey-btn" data-id="${survey.id}">Edit</button>
                                    <button class="btn btn-danger remove-survey-btn" data-id="${survey.id}">Delete</button>
                                </td>
                            </tr>
                        `;
          });

          $("#surveyTable").html(tableBody);
        } else {
          $("#surveyTable").html(
            '<tr><td colspan="6" class="text-center text-danger">No surveys found</td></tr>'
          );
        }
      },
    });
  }

  // Sort on column header button click
  $(".sort-btn").on("click", function () {
    const column = $(this).data("column");
    sortOrder = sortColumn === column && sortOrder === "ASC" ? "DESC" : "ASC";
    sortColumn = column;
    loadSurveys(sortColumn, sortOrder);
  });

  // Search by title, ID, or client
  $("#searchSurvey").on("keyup", function () {
    let query = $(this).val().trim();

    if (query.length === 0) {
      loadAllSurveys(); // Reload all surveys when search is cleared
      return;
    }

    $.ajax({
      url: "manage_entity.php",
      method: "POST",
      data: {
        action: "search",
        entity: "survey",
        query: query,
      },
      dataType: "json",
      success: function (response) {
        if (response.success) {
          let surveys = response.data;
          let tableBody = "";
          surveys.forEach((survey) => {
            tableBody += `
                            <tr>
                                <td>${survey.id}</td>
                                <td>${survey.title}</td>
                                <td>${survey.client_name}</td>
                                <td>${survey.amount}</td>
                                <td>${survey.status}</td>
                                <td>
                                    <button class="btn btn-info view-survey-btn" data-id="${survey.id}">View</button>
                                    <button class="btn btn-warning edit-survey-btn" data-id="${survey.id}">Edit</button>
                                    <button class="btn btn-danger remove-survey-btn" data-id="${survey.id}">Delete</button>
                                </td>
                            </tr>
                        `;
          });

          $("#surveyTable").html(tableBody);
        } else {
          $("#surveyTable").html(
            '<tr><td colspan="6" class="text-center text-danger">No surveys found</td></tr>'
          );
        }
      },
      error: function (xhr, status, error) {
        console.error("AJAX Error:", error);
        $("#surveyTable").html(
          '<tr><td colspan="6" class="text-center text-danger">Search failed. Try again.</td></tr>'
        );
      },
    });
  });

  // Load surveys (initial or sorted)
  function loadAllSurveys() {
    $.ajax({
      url: "manage_entity.php",
      type: "POST",
      data: {
        action: "fetch_all",
        entity: "survey",
      },
      dataType: "json",
      success: function (response) {
        if (response.success) {
          let surveys = response.data;
          let tableBody = "";
          surveys.forEach((survey) => {
            tableBody += `
                            <tr>
                                <td>${survey.id}</td>
                                <td>${survey.title}</td>
                                <td>${survey.client_name}</td>
                                <td>${survey.amount}</td>
                                <td>${survey.status}</td>
                                <td>
                                    <button class="btn btn-info view-survey-btn" data-id="${survey.id}">View</button>
                                    <button class="btn btn-warning edit-survey-btn" data-id="${survey.id}">Edit</button>
                                    <button class="btn btn-danger remove-survey-btn" data-id="${survey.id}">Delete</button>
                                </td>
                            </tr>
                        `;
          });

          $("#surveyTable").html(tableBody);
        }
      },
      error: function (xhr, status, error) {
        console.error("AJAX Error:", error);
        $("#surveyTable").html(
          '<tr><td colspan="6" class="text-center text-danger">Search failed. Try again.</td></tr>'
        );
      },
    });
  }

  // Reload all surveys when search input is cleared
  $("#searchSurvey").on("input", function () {
    if ($(this).val().trim() === "") {
      loadAllSurveys();
    }
  });
  loadAllSurveys(); // Load all surveys on page load
});

let questionCount = 0;

function addQuestion() {
  questionCount++;
  const questionBlock = `
    <div class="question-block mb-4" data-index="${questionCount}">
        <label>Question Text:</label>
        <input type="text" name="questions[${questionCount}][text]" required>
        <br>
        <label>Type:</label>
        <select name="questions[${questionCount}][type]" required>
            <option value="single">Single Choice</option>
            <option value="multiple">Multiple Choice</option>
        </select>
        <br>
        <input type="checkbox" class="question-required mt-2" id="question-required-${questionCount}" />
        <label class="mt-2" for="question-required-${questionCount}">Required</label>
        <label>Options:</label>
        <div class="options-container" id="options-${questionCount}">
            ${generateOptionHtml(questionCount, 0)}
            ${generateOptionHtml(questionCount, 1)}
        </div>
        <br>
        <div class="option-buttons mb-1">
          <button type="button" class="btn btn-primary btn-sm add-option mt-2" onclick="addOption(${questionCount})">+ Add Option</button>
          <button type="button" class="btn btn-sm btn-danger mt-2 remove-question">Remove Question</button>
        </div>
        <hr>
    </div>`;

  document
    .getElementById("questionnaire-section")
    .insertAdjacentHTML("beforeend", questionBlock);
}

function generateOptionHtml(questionIndex, optionIndex) {
  return `
        <div class="option-input-group input-group mb-1">
            <input type="text" name="questions[${questionIndex}][options][${optionIndex}]" class="form-control" required placeholder="Option text">
            <button type="button" class="btn btn-danger btn remove-option">X</button>
        </div>
    `;
}

function addOption(questionIndex) {
  const container = document.getElementById(`options-${questionIndex}`);
  const optionCount = container.children.length;
  container.insertAdjacentHTML(
    "beforeend",
    generateOptionHtml(questionIndex, optionCount)
  );
}

// Generate option row for editing
function generateEditOptionHtml(questionIndex, optionIndex, option) {
  return `
    <div class="input-group mb-1">
      <input type="text" name="questions[${questionIndex}][options][${optionIndex}][text]" class="form-control" value="${option.answer_text}" placeholder="Option text" required />
      ${option.id ? `<input type="hidden" name="questions[${questionIndex}][options][${optionIndex}][id]" value="${option.id}">` : ''}
      <button type="button" class="btn btn-danger btn remove-option">X</button>
    </div>
  `;
}

function addEditOption(questionIndex) {
  const container = document.getElementById(`edit-options-${questionIndex}`);
  const optionCount = container.children.length;
  container.insertAdjacentHTML(
    "beforeend",
    `<div class="input-group mb-1">
        <input type="text" name="questions[${questionIndex}][options][${optionCount}][text]" class="form-control" placeholder="Option text" required />
        <button type="button" class="btn btn-danger btn remove-option">X</button>
    </div>`
  );
}


function addEditQuestion() {
  let qIndex = $("#editQuestionContainer .question-block").length;

  const questionBlock = $(`
    <div class="question-block mb-4" data-index="${qIndex}">
      <label>Question ${qIndex + 1}</label>
      <input type="text" name="questions[${qIndex}][text]" class="form-control" required />
      <select name="questions[${qIndex}][type]" class="form-control mt-2" required>
        <option value="single">Single Choice</option>
        <option value="multiple">Multiple Choice</option>
      </select>
      <input type="checkbox" class="question-required mt-2" id="question-required-${qIndex}" />
      <label class="mt-2" for="question-required-${qIndex}">Required</label>
      <div class="options mt-2" id="edit-options-${qIndex}">
        <div class="input-group mb-1">
            <input type="text" name="questions[${qIndex}][options][0][text]" class="form-control" placeholder="Option 1" required />
            <button type="button" class="btn btn-danger btn remove-option">X</button>
        </div>
        <div class="input-group mb-1">
            <input type="text" name="questions[${qIndex}][options][1][text]" class="form-control" placeholder="Option 2" required />
            <button type="button" class="btn btn-danger btn remove-option">X</button>
        </div>
      </div>
      <div class="option-buttons mb-1">
        <button type="button" class="btn btn-primary btn-sm add-option mt-2">+ Add Option</button>
        <button type="button" class="btn btn-sm btn-danger mt-2 remove-question">Remove Question</button>
      </div>
    </div>`);

  // Add option handler for this specific question block
  questionBlock.find(".add-option").on("click", function () {
    const container = $(this).closest(".question-block").find(".options");
    const qIndex = $(this).closest(".question-block").data("index");
    const optionCount = container.children().length;

    container.append(`
      <div class="input-group mb-1">
        <input type="text" name="questions[${qIndex}][options][${optionCount}][text]" class="form-control" required placeholder="Option text" />
        <button type="button" class="btn btn-danger btn remove-option">X</button>
      </div>
    `);
  });

  // Remove option
  // questionBlock.find(".remove-option").on("click", function () {
  //   $(this).closest(".input-group").remove();
  // });
  $(document).on("click", ".remove-question", function () {
  const questionBlock = $(this).closest(".question-block");
  const questionId = questionBlock.data("question-id");

  if (questionId) {
    const removed = $("#removedQuestions").val();
    let removedArray = removed ? JSON.parse(removed) : [];
    removedArray.push(questionId);
    $("#removedQuestions").val(JSON.stringify(removedArray));
  }

  questionBlock.remove();
});


  // Remove question
  questionBlock.find(".remove-question").on("click", function () {
    $(this).closest(".question-block").remove();
  });

  $("#editQuestionContainer").append(questionBlock);
}
