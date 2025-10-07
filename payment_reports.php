<?php
// payment_reports.php
// Payment analytics and reporting page

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in as admin
if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    header("Location: login.php");
    exit();
}

include("connection.php");

// Check database connection
if(!$con) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Date range filter
$start_date = isset($_GET['start_date']) ? mysqli_real_escape_string($con, $_GET['start_date']) : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? mysqli_real_escape_string($con, $_GET['end_date']) : date('Y-m-t');
$report_type = isset($_GET['report_type']) ? mysqli_real_escape_string($con, $_GET['report_type']) : 'overview';

// Validate dates
if($start_date > $end_date) {
    $temp = $start_date;
    $start_date = $end_date;
    $end_date = $temp;
}

// Get payment statistics for the selected period
$total_payments = mysqli_query($con, 
    "SELECT COUNT(*) as total, 
            SUM(amount) as total_amount,
            AVG(amount) as avg_amount
     FROM payment 
     WHERE DATE(payment_date) BETWEEN '$start_date' AND '$end_date'");
$total_data = mysqli_fetch_assoc($total_payments);

// Status breakdown
$status_breakdown = mysqli_query($con,
    "SELECT status, 
            COUNT(*) as count, 
            SUM(amount) as amount
     FROM payment 
     WHERE DATE(payment_date) BETWEEN '$start_date' AND '$end_date'
     GROUP BY status");

// Monthly trends (last 6 months)
$monthly_trends = mysqli_query($con,
    "SELECT DATE_FORMAT(payment_date, '%Y-%m') as month,
            COUNT(*) as count,
            SUM(amount) as amount
     FROM payment 
     WHERE payment_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
     GROUP BY DATE_FORMAT(payment_date, '%Y-%m')
     ORDER BY month DESC");

// Top clients by payment amount
$top_clients = mysqli_query($con,
    "SELECT u.name, u.email, 
            COUNT(p.id) as payment_count,
            SUM(p.amount) as total_paid
     FROM payment p
     INNER JOIN users u ON p.client_id = u.id
     WHERE DATE(p.payment_date) BETWEEN '$start_date' AND '$end_date'
     GROUP BY p.client_id
     ORDER BY total_paid DESC
     LIMIT 10");

// Payment methods breakdown
$payment_methods = mysqli_query($con,
    "SELECT card_type, 
            COUNT(*) as count, 
            SUM(amount) as amount
     FROM payment 
     WHERE DATE(payment_date) BETWEEN '$start_date' AND '$end_date'
     GROUP BY card_type");

// Daily payments for chart
$daily_payments = mysqli_query($con,
    "SELECT DATE(payment_date) as date,
            COUNT(*) as count,
            SUM(amount) as amount
     FROM payment 
     WHERE DATE(payment_date) BETWEEN '$start_date' AND '$end_date'
     GROUP BY DATE(payment_date)
     ORDER BY date");

// Prepare data for charts
$daily_labels = [];
$daily_data = [];
$daily_counts = [];

while($row = mysqli_fetch_assoc($daily_payments)) {
    $daily_labels[] = date('M j', strtotime($row['date']));
    $daily_data[] = $row['amount'];
    $daily_counts[] = $row['count'];
}

// Monthly data for trends chart
$monthly_labels = [];
$monthly_data = [];
$monthly_counts = [];

mysqli_data_seek($monthly_trends, 0);
while($row = mysqli_fetch_assoc($monthly_trends)) {
    $monthly_labels[] = date('M Y', strtotime($row['month'] . '-01'));
    $monthly_data[] = $row['amount'];
    $monthly_counts[] = $row['count'];
}

// Status data for pie chart
$status_labels = [];
$status_data = [];
$status_colors = ['#28a745', '#ffc107', '#dc3545', '#6c757d'];

mysqli_data_seek($status_breakdown, 0);
while($row = mysqli_fetch_assoc($status_breakdown)) {
    $status_labels[] = ucfirst($row['status']) . ' (' . $row['count'] . ')';
    $status_data[] = $row['amount'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Reports - Admin Panel</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .card-dashboard {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            border: 1px solid #e3e6f0;
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-bottom: none;
            padding: 15px 20px;
            border-radius: 10px 10px 0 0;
        }
        .stat-card {
            transition: transform 0.2s;
            border: none;
            border-radius: 10px;
            color: white;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
        .report-filter {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }
        .nav-pills .nav-link.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .table-hover tbody tr:hover {
            background-color: rgba(0,0,0,0.02);
        }
        .progress {
            height: 8px;
        }
    </style>
</head>
<body>
<?php include("admin_menu.php"); ?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card card-dashboard">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h1 class="h3 mb-0">Payment Reports & Analytics</h1>
                            <p class="text-muted mb-0">Comprehensive payment analysis and insights</p>
                        </div>
                        <div class="col-md-6 text-end">
                            <button class="btn btn-success" onclick="window.print()">
                                <i class="fas fa-print me-2"></i>Print Report
                            </button>
                            <a href="admin_payments.php" class="btn btn-primary">
                                <i class="fas fa-arrow-left me-2"></i>Back to Payments
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Filters -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card card-dashboard report-filter">
                <div class="card-body">
                    <form method="GET" action="payment_reports.php">
                        <div class="row align-items-end">
                            <div class="col-md-3">
                                <label class="form-label text-white">Start Date</label>
                                <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label text-white">End Date</label>
                                <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label text-white">Report Type</label>
                                <select class="form-select" name="report_type">
                                    <option value="overview" <?php echo $report_type == 'overview' ? 'selected' : ''; ?>>Overview</option>
                                    <option value="detailed" <?php echo $report_type == 'detailed' ? 'selected' : ''; ?>>Detailed Analysis</option>
                                    <option value="trends" <?php echo $report_type == 'trends' ? 'selected' : ''; ?>>Trends</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-light w-100">
                                    <i class="fas fa-filter me-2"></i>Apply Filters
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Key Metrics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card stat-card bg-primary">
                <div class="card-body text-center">
                    <h2><?php echo number_format($total_data['total']); ?></h2>
                    <p>Total Payments</p>
                    <h5>$<?php echo number_format($total_data['total_amount'], 2); ?></h5>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card bg-success">
                <div class="card-body text-center">
                    <?php
                    $completed = mysqli_query($con, 
                        "SELECT COUNT(*) as completed, SUM(amount) as amount 
                         FROM payment 
                         WHERE status = 'completed' 
                         AND DATE(payment_date) BETWEEN '$start_date' AND '$end_date'");
                    $completed_data = mysqli_fetch_assoc($completed);
                    ?>
                    <h2><?php echo number_format($completed_data['completed']); ?></h2>
                    <p>Completed</p>
                    <h5>$<?php echo number_format($completed_data['amount'], 2); ?></h5>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card bg-info">
                <div class="card-body text-center">
                    <h2>$<?php echo number_format($total_data['avg_amount'], 2); ?></h2>
                    <p>Average Payment</p>
                    <h5>Per Transaction</h5>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card bg-warning">
                <div class="card-body text-center">
                    <?php
                    $pending = mysqli_query($con, 
                        "SELECT COUNT(*) as pending, SUM(amount) as amount 
                         FROM payment 
                         WHERE status = 'pending' 
                         AND DATE(payment_date) BETWEEN '$start_date' AND '$end_date'");
                    $pending_data = mysqli_fetch_assoc($pending);
                    ?>
                    <h2><?php echo number_format($pending_data['pending']); ?></h2>
                    <p>Pending</p>
                    <h5>$<?php echo number_format($pending_data['amount'], 2); ?></h5>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="row mb-4">
        <!-- Daily Payments Chart -->
        <div class="col-md-8">
            <div class="card card-dashboard">
                <div class="card-header">
                    <h5 class="card-title mb-0">Daily Payments Trend (<?php echo date('M j', strtotime($start_date)) . ' - ' . date('M j', strtotime($end_date)); ?>)</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="dailyChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Status Distribution -->
        <div class="col-md-4">
            <div class="card card-dashboard">
                <div class="card-header">
                    <h5 class="card-title mb-0">Payment Status Distribution</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Charts -->
    <div class="row mb-4">
        <!-- Monthly Trends -->
        <div class="col-md-6">
            <div class="card card-dashboard">
                <div class="card-header">
                    <h5 class="card-title mb-0">Monthly Trends (Last 6 Months)</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="monthlyChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Methods -->
        <div class="col-md-6">
            <div class="card card-dashboard">
                <div class="card-header">
                    <h5 class="card-title mb-0">Payment Methods Breakdown</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="methodsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Clients Table -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card card-dashboard">
                <div class="card-header">
                    <h5 class="card-title mb-0">Top Clients by Payment Volume</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Rank</th>
                                    <th>Client Name</th>
                                    <th>Email</th>
                                    <th>Payments</th>
                                    <th>Total Paid</th>
                                    <th>Average Payment</th>
                                    <th>Last Payment</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if(mysqli_num_rows($top_clients) > 0) {
                                    $rank = 1;
                                    while($client = mysqli_fetch_assoc($top_clients)) {
                                        $avg_payment = $client['total_paid'] / $client['payment_count'];
                                        $last_payment = mysqli_query($con, 
                                            "SELECT MAX(payment_date) as last_payment 
                                             FROM payment 
                                             WHERE client_id = (SELECT id FROM users WHERE email = '" . $client['email'] . "')");
                                        $last_payment_data = mysqli_fetch_assoc($last_payment);
                                        ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-primary">#<?php echo $rank; ?></span>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($client['name']); ?></strong>
                                            </td>
                                            <td><?php echo htmlspecialchars($client['email']); ?></td>
                                            <td><?php echo number_format($client['payment_count']); ?></td>
                                            <td class="text-success">
                                                <strong>$<?php echo number_format($client['total_paid'], 2); ?></strong>
                                            </td>
                                            <td>$<?php echo number_format($avg_payment, 2); ?></td>
                                            <td>
                                                <?php 
                                                if($last_payment_data['last_payment']) {
                                                    echo date('M j, Y', strtotime($last_payment_data['last_payment']));
                                                } else {
                                                    echo 'N/A';
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                        <?php
                                        $rank++;
                                    }
                                } else {
                                    echo '<tr><td colspan="7" class="text-center py-4 text-muted">No payment data found for the selected period.</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detailed Statistics -->
    <div class="row">
        <div class="col-md-6">
            <div class="card card-dashboard">
                <div class="card-header">
                    <h5 class="card-title mb-0">Payment Status Breakdown</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Status</th>
                                    <th>Count</th>
                                    <th>Amount</th>
                                    <th>Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                mysqli_data_seek($status_breakdown, 0);
                                $total_count = $total_data['total'];
                                while($status = mysqli_fetch_assoc($status_breakdown)) {
                                    $percentage = $total_count > 0 ? ($status['count'] / $total_count) * 100 : 0;
                                    ?>
                                    <tr>
                                        <td>
                                            <span class="badge 
                                                <?php 
                                                switch($status['status']) {
                                                    case 'completed': echo 'bg-success'; break;
                                                    case 'pending': echo 'bg-warning'; break;
                                                    case 'failed': echo 'bg-danger'; break;
                                                    default: echo 'bg-secondary';
                                                }
                                                ?>
                                            ">
                                                <?php echo ucfirst($status['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo number_format($status['count']); ?></td>
                                        <td class="text-success">$<?php echo number_format($status['amount'], 2); ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="progress flex-grow-1 me-2" style="height: 8px;">
                                                    <div class="progress-bar 
                                                        <?php 
                                                        switch($status['status']) {
                                                            case 'completed': echo 'bg-success'; break;
                                                            case 'pending': echo 'bg-warning'; break;
                                                            case 'failed': echo 'bg-danger'; break;
                                                            default: echo 'bg-secondary';
                                                        }
                                                        ?>
                                                    " style="width: <?php echo $percentage; ?>%"></div>
                                                </div>
                                                <span><?php echo number_format($percentage, 1); ?>%</span>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card card-dashboard">
                <div class="card-header">
                    <h5 class="card-title mb-0">Payment Methods Summary</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Card Type</th>
                                    <th>Transactions</th>
                                    <th>Amount</th>
                                    <th>Average</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if(mysqli_num_rows($payment_methods) > 0) {
                                    while($method = mysqli_fetch_assoc($payment_methods)) {
                                        $avg = $method['count'] > 0 ? $method['amount'] / $method['count'] : 0;
                                        ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars(ucfirst($method['card_type'])); ?></strong>
                                            </td>
                                            <td><?php echo number_format($method['count']); ?></td>
                                            <td class="text-success">$<?php echo number_format($method['amount'], 2); ?></td>
                                            <td>$<?php echo number_format($avg, 2); ?></td>
                                        </tr>
                                        <?php
                                    }
                                } else {
                                    echo '<tr><td colspan="4" class="text-center py-4 text-muted">No payment method data available.</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Daily Payments Chart
    const dailyCtx = document.getElementById('dailyChart').getContext('2d');
    new Chart(dailyCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($daily_labels); ?>,
            datasets: [{
                label: 'Payment Amount ($)',
                data: <?php echo json_encode($daily_data); ?>,
                borderColor: '#667eea',
                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }, {
                label: 'Number of Payments',
                data: <?php echo json_encode($daily_counts); ?>,
                borderColor: '#f093fb',
                backgroundColor: 'rgba(240, 147, 251, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Amount ($)'
                    }
                },
                y1: {
                    beginAtZero: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Number of Payments'
                    },
                    grid: {
                        drawOnChartArea: false
                    }
                }
            }
        }
    });

    // Status Distribution Chart
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($status_labels); ?>,
            datasets: [{
                data: <?php echo json_encode($status_data); ?>,
                backgroundColor: <?php echo json_encode($status_colors); ?>,
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });

    // Monthly Trends Chart
    const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
    new Chart(monthlyCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_reverse($monthly_labels)); ?>,
            datasets: [{
                label: 'Monthly Revenue',
                data: <?php echo json_encode(array_reverse($monthly_data)); ?>,
                backgroundColor: 'rgba(102, 126, 234, 0.8)',
                borderColor: '#667eea',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Payment Methods Chart
    const methodsCtx = document.getElementById('methodsChart').getContext('2d');
    
    // Get payment methods data for chart
    const methodData = <?php
        $methods_data = [];
        mysqli_data_seek($payment_methods, 0);
        while($method = mysqli_fetch_assoc($payment_methods)) {
            $methods_data[] = [
                'label' => ucfirst($method['card_type']),
                'count' => $method['count'],
                'amount' => $method['amount']
            ];
        }
        echo json_encode($methods_data);
    ?>;

    new Chart(methodsCtx, {
        type: 'pie',
        data: {
            labels: methodData.map(m => m.label),
            datasets: [{
                data: methodData.map(m => m.amount),
                backgroundColor: [
                    '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', 
                    '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF'
                ],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
});
</script>

<?php include("footer.php"); ?>
</body>
</html>