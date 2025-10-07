<?php
// resource_reports.php
// Resource analytics and reporting page

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
    die("Database connection failed: " . mysqli_error($con));
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

// Add missing columns if they don't exist
$check_status_column = mysqli_query($con, "SHOW COLUMNS FROM resources LIKE 'status'");
if(mysqli_num_rows($check_status_column) == 0) {
    $alter_query = "ALTER TABLE resources ADD COLUMN status VARCHAR(50) DEFAULT 'pending'";
    mysqli_query($con, $alter_query);
}

$check_requested_column = mysqli_query($con, "SHOW COLUMNS FROM resources LIKE 'requested_by'");
if(mysqli_num_rows($check_requested_column) == 0) {
    $alter_query = "ALTER TABLE resources ADD COLUMN requested_by INT(11)";
    mysqli_query($con, $alter_query);
}

$check_date_column = mysqli_query($con, "SHOW COLUMNS FROM resources LIKE 'request_date'");
if(mysqli_num_rows($check_date_column) == 0) {
    $alter_query = "ALTER TABLE resources ADD COLUMN request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
    mysqli_query($con, $alter_query);
}

$check_urgency_column = mysqli_query($con, "SHOW COLUMNS FROM resources LIKE 'urgency'");
if(mysqli_num_rows($check_urgency_column) == 0) {
    $alter_query = "ALTER TABLE resources ADD COLUMN urgency VARCHAR(20) DEFAULT 'normal'";
    mysqli_query($con, $alter_query);
}

// Get resource statistics for the selected period
$total_resources_query = "SELECT COUNT(*) as total,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
        COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved,
        COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected,
        COUNT(CASE WHEN status = 'delivered' THEN 1 END) as delivered,
        SUM(quantity) as total_quantity,
        SUM(cost * quantity) as total_cost
 FROM resources 
 WHERE DATE(request_date) BETWEEN '$start_date' AND '$end_date'";

$total_resources = mysqli_query($con, $total_resources_query);
if(!$total_resources) {
    die("Total resources query failed: " . mysqli_error($con));
}
$total_data = mysqli_fetch_assoc($total_resources);

// Status breakdown for charts
$status_breakdown_query = "SELECT status, COUNT(*) as count, 
        SUM(cost * quantity) as amount
 FROM resources 
 WHERE DATE(request_date) BETWEEN '$start_date' AND '$end_date'
 GROUP BY status";

$status_breakdown = mysqli_query($con, $status_breakdown_query);
if(!$status_breakdown) {
    die("Status breakdown query failed: " . mysqli_error($con));
}

// Type breakdown
$type_breakdown_query = "SELECT type, 
        COUNT(*) as count, 
        SUM(quantity) as total_quantity,
        SUM(cost * quantity) as total_cost
 FROM resources 
 WHERE DATE(request_date) BETWEEN '$start_date' AND '$end_date'
 GROUP BY type
 ORDER BY total_cost DESC";

$type_breakdown = mysqli_query($con, $type_breakdown_query);
if(!$type_breakdown) {
    die("Type breakdown query failed: " . mysqli_error($con));
}

// Monthly trends (last 6 months)
$monthly_trends_query = "SELECT DATE_FORMAT(request_date, '%Y-%m') as month,
        COUNT(*) as count,
        SUM(cost * quantity) as amount,
        COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved
 FROM resources 
 WHERE request_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
 GROUP BY DATE_FORMAT(request_date, '%Y-%m')
 ORDER BY month DESC";

$monthly_trends = mysqli_query($con, $monthly_trends_query);
if(!$monthly_trends) {
    die("Monthly trends query failed: " . mysqli_error($con));
}

// Top projects by resource cost
$top_projects_query = "SELECT p.name as project_name,
        COUNT(r.id) as resource_count,
        SUM(r.cost * r.quantity) as total_cost,
        pm.name as manager_name
 FROM resources r
 LEFT JOIN projects p ON r.project_id = p.id
 LEFT JOIN project_managers pm ON p.project_manager_id = pm.id
 WHERE DATE(r.request_date) BETWEEN '$start_date' AND '$end_date'
 GROUP BY r.project_id
 HAVING total_cost > 0
 ORDER BY total_cost DESC
 LIMIT 10";

$top_projects = mysqli_query($con, $top_projects_query);
if(!$top_projects) {
    // If projects table doesn't exist, create empty result
    $top_projects = false;
}

// Top project managers by resource requests
$top_managers_query = "SELECT pm.name, pm.email,
        COUNT(r.id) as request_count,
        SUM(r.cost * r.quantity) as total_cost,
        COUNT(CASE WHEN r.status = 'approved' THEN 1 END) as approved_count
 FROM resources r
 LEFT JOIN projects p ON r.project_id = p.id
 LEFT JOIN project_managers pm ON p.project_manager_id = pm.id
 WHERE DATE(r.request_date) BETWEEN '$start_date' AND '$end_date'
 AND pm.id IS NOT NULL
 GROUP BY pm.id
 ORDER BY total_cost DESC
 LIMIT 10";

$top_managers = mysqli_query($con, $top_managers_query);
if(!$top_managers) {
    $top_managers = false;
}

// Urgency breakdown
$urgency_breakdown_query = "SELECT urgency, 
        COUNT(*) as count,
        SUM(cost * quantity) as amount
 FROM resources 
 WHERE DATE(request_date) BETWEEN '$start_date' AND '$end_date'
 GROUP BY urgency";

$urgency_breakdown = mysqli_query($con, $urgency_breakdown_query);
if(!$urgency_breakdown) {
    // If urgency column doesn't exist, create empty result
    $urgency_breakdown = false;
}

// Daily resources for chart
$daily_resources_query = "SELECT DATE(request_date) as date,
        COUNT(*) as count,
        SUM(cost * quantity) as amount
 FROM resources 
 WHERE DATE(request_date) BETWEEN '$start_date' AND '$end_date'
 GROUP BY DATE(request_date)
 ORDER BY date";

$daily_resources = mysqli_query($con, $daily_resources_query);
if(!$daily_resources) {
    die("Daily resources query failed: " . mysqli_error($con));
}

// Approval rate
$approval_rate_query = "SELECT 
        COUNT(*) as total,
        COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved,
        ROUND((COUNT(CASE WHEN status = 'approved' THEN 1 END) * 100.0 / COUNT(*)), 2) as approval_rate
 FROM resources 
 WHERE DATE(request_date) BETWEEN '$start_date' AND '$end_date'";

$approval_rate = mysqli_query($con, $approval_rate_query);
if(!$approval_rate) {
    die("Approval rate query failed: " . mysqli_error($con));
}
$approval_data = mysqli_fetch_assoc($approval_rate);

// Prepare data for charts
$daily_labels = [];
$daily_data = [];
$daily_amounts = [];

while($row = mysqli_fetch_assoc($daily_resources)) {
    $daily_labels[] = date('M j', strtotime($row['date']));
    $daily_data[] = $row['count'];
    $daily_amounts[] = $row['amount'];
}

// Monthly data for trends chart
$monthly_labels = [];
$monthly_data = [];
$monthly_amounts = [];
$monthly_approved = [];

mysqli_data_seek($monthly_trends, 0);
while($row = mysqli_fetch_assoc($monthly_trends)) {
    $monthly_labels[] = date('M Y', strtotime($row['month'] . '-01'));
    $monthly_data[] = $row['count'];
    $monthly_amounts[] = $row['amount'];
    $monthly_approved[] = $row['approved'];
}

// Status data for pie chart
$status_labels = [];
$status_data = [];
$status_amounts = [];
$status_colors = ['#ffc107', '#28a745', '#dc3545', '#17a2b8']; // pending, approved, rejected, delivered

mysqli_data_seek($status_breakdown, 0);
while($row = mysqli_fetch_assoc($status_breakdown)) {
    $status_labels[] = ucfirst($row['status']) . ' (' . $row['count'] . ')';
    $status_data[] = $row['count'];
    $status_amounts[] = $row['amount'];
}

// Type data for chart
$type_labels = [];
$type_data = [];
$type_amounts = [];

mysqli_data_seek($type_breakdown, 0);
while($row = mysqli_fetch_assoc($type_breakdown)) {
    $type_labels[] = ucfirst($row['type']) . ' (' . $row['count'] . ')';
    $type_data[] = $row['total_cost'];
    $type_amounts[] = $row['total_quantity'];
}

// Urgency data
$urgency_labels = [];
$urgency_data = [];
$urgency_colors = ['#28a745', '#ffc107', '#dc3545']; // low, normal, high

if($urgency_breakdown) {
    mysqli_data_seek($urgency_breakdown, 0);
    while($row = mysqli_fetch_assoc($urgency_breakdown)) {
        $urgency_labels[] = ucfirst($row['urgency']) . ' (' . $row['count'] . ')';
        $urgency_data[] = $row['count'];
    }
}

// If no data found, set default values
if(empty($daily_labels)) {
    $daily_labels = ['No Data'];
    $daily_data = [0];
    $daily_amounts = [0];
}

if(empty($monthly_labels)) {
    $monthly_labels = ['No Data'];
    $monthly_data = [0];
    $monthly_amounts = [0];
    $monthly_approved = [0];
}

if(empty($status_labels)) {
    $status_labels = ['No Resources'];
    $status_data = [1];
    $status_amounts = [0];
}

if(empty($type_labels)) {
    $type_labels = ['No Data'];
    $type_data = [0];
    $type_amounts = [0];
}

// Fix for older PHP versions - replace null coalescing with ternary
$total_cost_amount = isset($total_data['total_cost']) ? $total_data['total_cost'] : 0;
$total_quantity_amount = isset($total_data['total_quantity']) ? $total_data['total_quantity'] : 0;
$approval_rate_value = isset($approval_data['approval_rate']) ? $approval_data['approval_rate'] : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resource Reports - Admin Panel</title>
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
        .table-hover tbody tr:hover {
            background-color: rgba(0,0,0,0.02);
        }
        .progress {
            height: 8px;
        }
        .no-data {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
        .no-data i {
            font-size: 3rem;
            margin-bottom: 15px;
        }
        .cost-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            background-color: #28a745;
            color: white;
        }
        .type-badge {
            background-color: #6c757d;
            color: white;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 0.7rem;
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
                            <h1 class="h3 mb-0">Resource Reports & Analytics</h1>
                            <p class="text-muted mb-0">Comprehensive resource request analysis and insights</p>
                        </div>
                        <div class="col-md-6 text-end">
                            <button class="btn btn-success" onclick="window.print()">
                                <i class="fas fa-print me-2"></i>Print Report
                            </button>
                            <a href="admin_resources.php" class="btn btn-primary">
                                <i class="fas fa-arrow-left me-2"></i>Back to Resources
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
                    <form method="GET" action="resource_reports.php">
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
                                    <option value="financial" <?php echo $report_type == 'financial' ? 'selected' : ''; ?>>Financial Analysis</option>
                                    <option value="performance" <?php echo $report_type == 'performance' ? 'selected' : ''; ?>>Performance Metrics</option>
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
                    <p>Total Requests</p>
                    <small>Period: <?php echo date('M j', strtotime($start_date)) . ' - ' . date('M j', strtotime($end_date)); ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card bg-info">
                <div class="card-body text-center">
                    <h2>$<?php echo number_format($total_cost_amount, 2); ?></h2>
                    <p>Total Cost</p>
                    <small>All Resource Requests</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card bg-success">
                <div class="card-body text-center">
                    <h2><?php echo number_format($approval_rate_value); ?>%</h2>
                    <p>Approval Rate</p>
                    <small>Requests Approved</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card bg-warning">
                <div class="card-body text-center">
                    <h2><?php echo number_format($total_data['pending']); ?></h2>
                    <p>Pending</p>
                    <small>Awaiting Review</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="row mb-4">
        <!-- Daily Resources Chart -->
        <div class="col-md-8">
            <div class="card card-dashboard">
                <div class="card-header">
                    <h5 class="card-title mb-0">Daily Resource Requests (<?php echo date('M j', strtotime($start_date)) . ' - ' . date('M j', strtotime($end_date)); ?>)</h5>
                </div>
                <div class="card-body">
                    <?php if($total_data['total'] > 0): ?>
                        <div class="chart-container">
                            <canvas id="dailyChart"></canvas>
                        </div>
                    <?php else: ?>
                        <div class="no-data">
                            <i class="fas fa-chart-line"></i>
                            <h4>No Resource Data Available</h4>
                            <p>No resource requests found for the selected period.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Request Status Distribution -->
        <div class="col-md-4">
            <div class="card card-dashboard">
                <div class="card-header">
                    <h5 class="card-title mb-0">Request Status Distribution</h5>
                </div>
                <div class="card-body">
                    <?php if($total_data['total'] > 0): ?>
                        <div class="chart-container">
                            <canvas id="statusChart"></canvas>
                        </div>
                    <?php else: ?>
                        <div class="no-data">
                            <i class="fas fa-chart-pie"></i>
                            <h4>No Status Data</h4>
                            <p>No resource requests to display status distribution.</p>
                        </div>
                    <?php endif; ?>
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
                    <?php if($total_data['total'] > 0): ?>
                        <div class="chart-container">
                            <canvas id="monthlyChart"></canvas>
                        </div>
                    <?php else: ?>
                        <div class="no-data">
                            <i class="fas fa-chart-bar"></i>
                            <h4>No Trend Data</h4>
                            <p>No resource data available for trend analysis.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Resource Types Breakdown -->
        <div class="col-md-6">
            <div class="card card-dashboard">
                <div class="card-header">
                    <h5 class="card-title mb-0">Resource Types by Cost</h5>
                </div>
                <div class="card-body">
                    <?php if($total_data['total'] > 0): ?>
                        <div class="chart-container">
                            <canvas id="typeChart"></canvas>
                        </div>
                    <?php else: ?>
                        <div class="no-data">
                            <i class="fas fa-boxes"></i>
                            <h4>No Type Data</h4>
                            <p>No resource type data available.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Projects Table -->
    <?php if($top_projects && mysqli_num_rows($top_projects) > 0): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card card-dashboard">
                <div class="card-header">
                    <h5 class="card-title mb-0">Top Projects by Resource Cost</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Project Name</th>
                                    <th>Project Manager</th>
                                    <th>Resource Requests</th>
                                    <th>Total Cost</th>
                                    <th>Avg Cost per Request</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                mysqli_data_seek($top_projects, 0);
                                while($project = mysqli_fetch_assoc($top_projects)) {
                                    $avg_cost = $project['resource_count'] > 0 ? $project['total_cost'] / $project['resource_count'] : 0;
                                    ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($project['project_name']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($project['manager_name']); ?></td>
                                        <td><?php echo number_format($project['resource_count']); ?></td>
                                        <td class="text-success">
                                            <strong>$<?php echo number_format($project['total_cost'], 2); ?></strong>
                                        </td>
                                        <td>$<?php echo number_format($avg_cost, 2); ?></td>
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
    </div>
    <?php endif; ?>

    <!-- Top Project Managers -->
    <?php if($top_managers && mysqli_num_rows($top_managers) > 0): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card card-dashboard">
                <div class="card-header">
                    <h5 class="card-title mb-0">Top Project Managers by Resource Requests</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Manager Name</th>
                                    <th>Email</th>
                                    <th>Total Requests</th>
                                    <th>Approved</th>
                                    <th>Total Cost</th>
                                    <th>Approval Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                mysqli_data_seek($top_managers, 0);
                                while($manager = mysqli_fetch_assoc($top_managers)) {
                                    $manager_approval_rate = $manager['request_count'] > 0 ? 
                                        round(($manager['approved_count'] / $manager['request_count']) * 100, 2) : 0;
                                    ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($manager['name']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($manager['email']); ?></td>
                                        <td><?php echo number_format($manager['request_count']); ?></td>
                                        <td class="text-success"><?php echo number_format($manager['approved_count']); ?></td>
                                        <td class="text-info">$<?php echo number_format($manager['total_cost'], 2); ?></td>
                                        <td>
                                            <span class="cost-badge">
                                                <?php echo number_format($manager_approval_rate); ?>%
                                            </span>
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
    </div>
    <?php endif; ?>

    <!-- Detailed Statistics -->
    <div class="row">
        <div class="col-md-6">
            <div class="card card-dashboard">
                <div class="card-header">
                    <h5 class="card-title mb-0">Resource Type Breakdown</h5>
                </div>
                <div class="card-body">
                    <?php if($total_data['total'] > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Resource Type</th>
                                        <th>Requests</th>
                                        <th>Quantity</th>
                                        <th>Total Cost</th>
                                        <th>Avg Cost</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    mysqli_data_seek($type_breakdown, 0);
                                    while($type = mysqli_fetch_assoc($type_breakdown)) {
                                        $avg_type_cost = $type['count'] > 0 ? $type['total_cost'] / $type['count'] : 0;
                                        ?>
                                        <tr>
                                            <td>
                                                <span class="type-badge">
                                                    <?php echo htmlspecialchars(ucfirst($type['type'])); ?>
                                                </span>
                                            </td>
                                            <td><?php echo number_format($type['count']); ?></td>
                                            <td><?php echo number_format($type['total_quantity']); ?></td>
                                            <td class="text-success">$<?php echo number_format($type['total_cost'], 2); ?></td>
                                            <td>$<?php echo number_format($avg_type_cost, 2); ?></td>
                                        </tr>
                                        <?php
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="no-data">
                            <i class="fas fa-list"></i>
                            <h4>No Type Breakdown</h4>
                            <p>No resource type data available for analysis.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card card-dashboard">
                <div class="card-header">
                    <h5 class="card-title mb-0">Performance Summary</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6 mb-3">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h4 class="text-primary"><?php echo number_format($approval_rate_value); ?>%</h4>
                                    <small>Overall Approval Rate</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h4 class="text-info">$<?php echo number_format($total_cost_amount, 2); ?></h4>
                                    <small>Total Resource Cost</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h4 class="text-success"><?php echo number_format($total_data['approved']); ?></h4>
                                    <small>Approved Requests</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h4 class="text-warning"><?php echo number_format($total_data['pending']); ?></h4>
                                    <small>Pending Review</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php if($total_data['total'] > 0): ?>
                        <?php if($approval_rate_value >= 80): ?>
                            <div class="alert alert-success mt-3">
                                <i class="fas fa-trophy me-2"></i>
                                <strong>Excellent Performance!</strong> Your resource approval rate is above 80%.
                            </div>
                        <?php elseif($approval_rate_value >= 60): ?>
                            <div class="alert alert-warning mt-3">
                                <i class="fas fa-chart-line me-2"></i>
                                <strong>Good Performance!</strong> Consider optimizing resource requests to reach 80%+ approval rate.
                            </div>
                        <?php else: ?>
                            <div class="alert alert-danger mt-3">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Needs Improvement!</strong> Focus on reviewing pending resource requests to improve approval rate.
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="alert alert-info mt-3">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>No Data Available</strong> for performance analysis in the selected period.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if($total_data['total'] > 0): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Daily Resources Chart
    const dailyCtx = document.getElementById('dailyChart').getContext('2d');
    new Chart(dailyCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($daily_labels); ?>,
            datasets: [{
                label: 'Number of Requests',
                data: <?php echo json_encode($daily_data); ?>,
                backgroundColor: 'rgba(102, 126, 234, 0.8)',
                borderColor: '#667eea',
                borderWidth: 1
            }, {
                label: 'Total Cost ($)',
                data: <?php echo json_encode($daily_amounts); ?>,
                backgroundColor: 'rgba(40, 167, 69, 0.8)',
                borderColor: '#28a745',
                borderWidth: 1,
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
                        text: 'Number of Requests'
                    }
                },
                y1: {
                    beginAtZero: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Total Cost ($)'
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
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_reverse($monthly_labels)); ?>,
            datasets: [{
                label: 'Total Requests',
                data: <?php echo json_encode(array_reverse($monthly_data)); ?>,
                borderColor: '#667eea',
                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }, {
                label: 'Total Cost ($)',
                data: <?php echo json_encode(array_reverse($monthly_amounts)); ?>,
                borderColor: '#28a745',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
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
                    beginAtZero: true
                },
                y1: {
                    beginAtZero: true,
                    position: 'right',
                    grid: {
                        drawOnChartArea: false
                    }
                }
            }
        }
    });

    // Resource Types Chart
    const typeCtx = document.getElementById('typeChart').getContext('2d');
    new Chart(typeCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($type_labels); ?>,
            datasets: [{
                label: 'Total Cost ($)',
                data: <?php echo json_encode($type_data); ?>,
                backgroundColor: 'rgba(255, 193, 7, 0.8)',
                borderColor: '#ffc107',
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
});
</script>
<?php endif; ?>

<?php include("footer.php"); ?>
</body>
</html>