newPageTitle = 'Manage Reports';
document.title = newPageTitle;

$(document).on("click", ".view-report-btn", function () {
  const surveyId = $(this).data("id");

  $.ajax({
    url: "manage_entity.php",
    method: "POST",
    data: {
      action: "fetch_survey_report",
      entity: "report",
      survey_id: surveyId
    },
    dataType: "json",
    success: function (response) {
      if (response.success) {
        const report = response.report;
        $("#reportSurveyTitle").text(report.title);
        $("#reportClientName").text(report.organization_name);
        $("#reportAmount").text(report.amount);
        $("#reportCompleted").text(report.completed_count);
        $("#reportAmountPaid").text(report.amount_paid);
        $("#reportAmountRemaining").text(report.amount_remaining);

        const total = parseFloat(report.amount);
        const paid = parseFloat(report.amount_paid);
        const remaining = parseFloat(report.amount_remaining);

        const paidPercent = total > 0 ? ((paid / total) * 100).toFixed(1) : 0;
        const remainingPercent =
          total > 0 ? ((remaining / total) * 100).toFixed(1) : 0;

        // Update progress bars and descriptions
        $("#paidProgress").css("width", paidPercent + "%");
        $("#paidDescription").text(`${paidPercent}% of Budget Disbursed`);

        $("#remainingProgress").css("width", remainingPercent + "%");
        $("#remainingDescription").text(
          `${remainingPercent}% Remaining Budget`
        );



        const chartsContainer = $("#reportCharts");
        chartsContainer.empty();

        report.questions.forEach((q, index) => {
          const canvasId = `chart-${index}`;
          chartsContainer.append(
            `<div class="mb-4"><h5>${q.question_text}</h5><canvas id="${canvasId}" height="100"></canvas></div>`
          );


          const labels = q.options.map((opt) => opt.answer_text);
          const counts = q.options.map((opt) => opt.response_count);
          const { colors, borderColors } = generateColors(labels.length);

          
          new Chart(document.getElementById(canvasId), {
            type: $("#graphType").val(),
            data: {
              labels: labels,
              datasets: [
                {
                  label: "Responses",
                  data: counts,
                  backgroundColor: colors,
                  borderColor: borderColors,
                  borderWidth: 1,
                },
              ],
            },
            options: {
              responsive: true,
              plugins: {
                legend: {
                  display: true,
                  position: "bottom",
                },
              },
              scales: ["bar", "line"].includes($("#graphType").val())
                ? {
                    y: { beginAtZero: true },
                  }
                : {},
            },
          });
        });

        $("#reportSection").show();
        window.scrollTo({ top: $("#reportSection").offset().top, behavior: 'smooth' });
      } else {
        alert("Error loading report: " + response.message);
      }
    },
    error: function () {
      alert("Failed to fetch report data.");
    }
  });
});

$("#filterPaymentsBtn").on("click", function () {
  const surveyId = $("#reportSurveyTitle").data("id");
  const startDate = $("#startDate").val();
  const endDate = $("#endDate").val();

  if (!startDate || !endDate) {
    alert("Please select both start and end dates.");
    return;
  }

  $.ajax({
    url: "manage_entity.php",
    method: "POST",
    data: {
      action: "fetch_disbursed_amount",
      entity: "report",
      survey_id: surveyId,
      start_date: startDate,
      end_date: endDate
    },
    dataType: "json",
    success: function (response) {
      if (response.success) {
        $("#disbursedRangeAmount").text("₹" + response.amount);
      } else {
        alert("Error: " + response.message);
      }
    }
  });
});

function generateColors(count) {
  const colors = [];
  const borderColors = [];

  for (let i = 0; i < count; i++) {
    const r = Math.floor(Math.random() * 200 + 55);
    const g = Math.floor(Math.random() * 200 + 55);
    const b = Math.floor(Math.random() * 200 + 55);
    colors.push(`rgba(${r}, ${g}, ${b}, 0.7)`);
    borderColors.push(`rgba(${r}, ${g}, ${b}, 1)`);
  }

  return { colors, borderColors };
}


