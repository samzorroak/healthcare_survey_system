<?php
include 'header.php';
require_once 'EntityManager.php';

$conn = Database::getInstance();

$UserId = $_SESSION['user_id'];
$UserName = $_SESSION['user_name'] ?? 'User';
$UserPermissions = $_SESSION['permissions'] ?? [];
$perm = 'view_reports';

//checking if "view_report" permission exists
if (!(str_contains($UserPermissions, $perm))) {
  echo '<script>alert("You do not have permission to view the report page."); history.back();</script>';
}

$surveyManager = new EntityManager('surveys');
$surveys = $surveyManager->fetchAll();
?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  let chartInstance = null;

  function renderChart(type, labels, data) {
    const ctx = document.getElementById('reportChart').getContext('2d');

    // Destroy existing chart to avoid overlay
    if (chartInstance) {
      chartInstance.destroy();
    }

    // Build new chart
    chartInstance = new Chart(ctx, {
      type: type,
      data: {
        labels: labels,
        datasets: [{
          label: 'Survey Results',
          data: data,
          backgroundColor: ['#36a2eb', '#ff6384', '#4bc0c0', '#ffcd56', '#9966ff'],
          borderColor: '#ccc',
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            display: type !== 'bar' // Show legend only for pie/line
          }
        },
        scales: type === 'bar' || type === 'line' ? {
          y: {
            beginAtZero: true
          }
        } : {}
      }
    });
  }

  // Handle dropdown change
  $('#graphType').on('change', function() {
    const selectedType = $(this).val();
    // Use cached or fetched labels/data
    renderChart(selectedType, window.chartLabels, window.chartData);
  });

  // Fetch data from backend and render chart
  function loadChartData(survey_id) {
    $.post('manage_entity.php', {
      action: 'fetch_report_data',
      survey_id: survey_id
    }, function(response) {
      if (response.success) {
        window.chartLabels = response.labels; // Cache for reuse
        window.chartData = response.data;
        renderChart($('#graphType').val(), response.labels, response.data);
      } else {
        alert('Error loading report data');
      }
    }, 'json');
  }

  // Example call (you may call this when survey is selected/viewed)
  // loadChartData(3); // Load survey ID 3 chart
</script>


<script src="report.js"></script>

<body class="hold-transition sidebar-mini">
  <div class="wrapper">
    <?php include 'navbar.php'; ?>
    <?php include 'sidebar.php'; ?>

    <div class="content-wrapper">
      <section class="content-header">
        <div class="container-fluid">
          <h1>Survey Reports</h1>
        </div>
      </section>

      <section class="content">
        <div class="container">
          <!-- Survey List Table -->
          <div class="card">
            <div class="card-header">
              <h3 class="card-title">Survey List</h3>
            </div>
            <div class="card-body">
              <table class="table table-bordered" id="surveyTable">
                <thead>
                  <tr>
                    <th>Survey</th>
                    <th>Organization</th>
                    <th>Amount</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($surveys as $survey): ?>
                    <?php
                    $stmt = $conn->prepare("SELECT organization_name FROM clients WHERE id = ?");
                    $stmt->execute([$survey['client_id']]);
                    $client = $stmt->fetch(PDO::FETCH_ASSOC);
                    ?>
                    <tr>
                      <td><?= htmlspecialchars($survey['title']) ?></td>
                      <td><?= htmlspecialchars($client['organization_name']) ?></td>
                      <td>₹<?= htmlspecialchars($survey['amount']) ?></td>
                      <td><?= htmlspecialchars($survey['start_date']) ?></td>
                      <td><?= htmlspecialchars($survey['end_date']) ?></td>
                      <td>
                        <button class="btn btn-info view-report-btn" data-id="<?= $survey['id'] ?>">View Report</button>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table> <br>
              <div class="form-group">
                <label for="graphType">Select Graph Type:</label>
                <select id="graphType" class="form-control">
                  <option value="bar">Bar Chart</option>
                  <option value="line">Line Chart</option>
                  <option value="pie">Pie Chart</option>
                  <option value="doughnut">Doughnut Chart</option>
                  <option value="polarArea">Polar Area</option>
                  <option value="radar">Radar Chart</option>
                </select>
                <div class="row mt-3">
                  <div class="col-md-4">
                    <label>From Date:</label>
                    <input type="date" id="startDate" class="form-control">
                  </div>
                  <div class="col-md-4">
                    <label>To Date:</label>
                    <input type="date" id="endDate" class="form-control">
                  </div>
                  <div class="col-md-4">
                    <label>&nbsp;</label>
                    <button class="btn btn-primary btn-block" id="filterPaymentsBtn">Filter Disbursed Amount</button>
                  </div>
                </div>

              </div>
            </div>
          </div>

          <!-- Survey Report Section -->
          <div id="reportSection" style="display:none;" class="mt-4">
            <div class="card">
              <div class="card-header">
                <h4 id="reportSurveyTitle">Survey Report</h4>
                <p class="mb-0"><strong>Organization:</strong> <span id="reportClientName"></span></p>
              </div>
              <div class="card-body">
                <div class="row">
                  <!-- Total Budget -->
                  <div class="col-md-6 col-lg-4">
                    <div class="info-box bg-primary">
                      <span class="info-box-icon"><i class="fas fa-coins"></i></span>
                      <div class="info-box-content">
                        <span class="info-box-text">Total Budget</span>
                        <span class="info-box-number">₹<span id="reportAmount">0</span></span>
                        <div class="progress">
                          <div class="progress-bar" style="width: 100%"></div>
                        </div>
                        <span class="progress-description">Allocated by Client</span>
                      </div>
                    </div>
                  </div>

                  <!-- Amount Paid -->
                  <div class="col-md-6 col-lg-4">
                    <div class="info-box bg-success">
                      <span class="info-box-icon"><i class="fas fa-money-bill-wave"></i></span>
                      <div class="info-box-content">
                        <span class="info-box-text">Amount Paid</span>
                        <span class="info-box-number">₹<span id="reportAmountPaid">0</span></span>
                        <div class="progress">
                          <div id="paidProgress" class="progress-bar" style="width: 0%"></div>
                        </div>
                        <span class="progress-description" id="paidDescription">0% of Budget Disbursed</span>
                      </div>
                    </div>
                  </div>

                  <!-- Amount Remaining -->
                  <div class="col-md-6 col-lg-4">
                    <div class="info-box bg-warning">
                      <span class="info-box-icon"><i class="fas fa-wallet"></i></span>
                      <div class="info-box-content">
                        <span class="info-box-text">Amount Remaining</span>
                        <span class="info-box-number">₹<span id="reportAmountRemaining">0</span></span>
                        <div class="progress">
                          <div id="remainingProgress" class="progress-bar" style="width: 0%"></div>
                        </div>
                        <span class="progress-description" id="remainingDescription">0% Remaining Budget</span>
                      </div>
                    </div>
                  </div>

                  <!-- Participants Completed -->
                  <div class="col-md-6 col-lg-4">
                    <div class="info-box bg-info">
                      <span class="info-box-icon"><i class="fas fa-users"></i></span>
                      <div class="info-box-content">
                        <span class="info-box-text">Participants Completed</span>
                        <span class="info-box-number" id="reportCompleted">0</span>
                        <div class="progress">
                          <div class="progress-bar" style="width: 100%"></div>
                        </div>
                        <span class="progress-description">Total Completions</span>
                      </div>
                    </div>
                  </div>

                  <!-- Date Range Disbursed -->
                  <div class="col-md-6 col-lg-4">
                    <div class="info-box bg-secondary">
                      <span class="info-box-icon"><i class="fas fa-calendar-check"></i></span>
                      <div class="info-box-content">
                        <span class="info-box-text">Disbursed (Range)</span>
                        <span class="info-box-number" id="disbursedRangeAmount">₹0</span>
                        <div class="progress">
                          <div class="progress-bar" style="width: 100%"></div>
                        </div>
                        <span class="progress-description">Based on selected dates</span>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Charts -->
                <div id="reportCharts" class="mt-4"></div>
              </div>
            </div>
          </div>

        </div>
      </section>
    </div>
  </div>
</body>