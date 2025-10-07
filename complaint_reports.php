<?php
// complaint_reports.php
// Complaint analytics and reporting page

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

// Get complaint statistics for the selected period
$total_complaints_query = "SELECT COUNT(*) as total,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
        COUNT(CASE WHEN status = 'responded' THEN 1 END) as responded,
        COUNT(CASE WHEN status = 'resolved' THEN 1 END) as resolved,
        COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected
 FROM complaints 
 WHERE DATE(complaint_date) BETWEEN '$start_date' AND '$end_date'";

$total_complaints = mysqli_query($con, $total_complaints_query);

if(!$total_complaints) {
    die("Query failed: " . mysqli_error($con));
}

$total_data = mysqli_fetch_assoc($total_complaints);

// Status breakdown for charts
$status_breakdown_query = "SELECT status, COUNT(*) as count
     FROM complaints 
     WHERE DATE(complaint_date) BETWEEN '$start_date' AND '$end_date'
     GROUP BY status";

$status_breakdown = mysqli_query($con, $status_breakdown_query);

if(!$status_breakdown) {
    die("Status breakdown query failed: " . mysqli_error($con));
}

// Monthly trends (last 6 months)
$monthly_trends_query = "SELECT DATE_FORMAT(complaint_date, '%Y-%m') as month,
        COUNT(*) as count,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
        COUNT(CASE WHEN status = 'resolved' THEN 1 END) as resolved
 FROM complaints 
 WHERE complaint_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
 GROUP BY DATE_FORMAT(complaint_date, '%Y-%m')
 ORDER BY month DESC";

$monthly_trends = mysqli_query($con, $monthly_trends_query);

if(!$monthly_trends) {
    die("Monthly trends query failed: " . mysqli_error($con));
}

// Top clients with most complaints
$top_clients_query = "SELECT u.name, u.email, 
        COUNT(c.id) as complaint_count,
        COUNT(CASE WHEN c.status = 'resolved' THEN 1 END) as resolved_count
 FROM complaints c
 INNER JOIN users u ON c.client_id = u.id
 WHERE DATE(c.complaint_date) BETWEEN '$start_date' AND '$end_date'
 GROUP BY c.client_id
 ORDER BY complaint_count DESC
 LIMIT 10";

$top_clients = mysqli_query($con, $top_clients_query);

if(!$top_clients) {
    die("Top clients query failed: " . mysqli_error($con));
}

// Projects with most complaints
$projects_complaints_query = "SELECT p.name as project_name,
        COUNT(c.id) as complaint_count,
        COUNT(CASE WHEN c.status = 'resolved' THEN 1 END) as resolved_count
 FROM complaints c
 LEFT JOIN projects p ON c.project_id = p.id
 WHERE DATE(c.complaint_date) BETWEEN '$start_date' AND '$end_date'
 GROUP BY c.project_id
 HAVING complaint_count > 0
 ORDER BY complaint_count DESC
 LIMIT 10";

$projects_complaints = mysqli_query($con, $projects_complaints_query);

if(!$projects_complaints) {
    // If projects table doesn't exist, create empty result
    $projects_complaints = false;
}

// Simplified response time analysis (using complaint_date as response date for responded/resolved complaints)
$response_time_query = "SELECT 
    AVG(DATEDIFF(
        CASE 
            WHEN status IN ('responded', 'resolved') THEN complaint_date 
            ELSE NULL 
        END,
        complaint_date
    )) as avg_response_days,
    MAX(DATEDIFF(
        CASE 
            WHEN status IN ('responded', 'resolved') THEN complaint_date 
            ELSE NULL 
        END,
        complaint_date
    )) as max_response_days
 FROM complaints
 WHERE DATE(complaint_date) BETWEEN '$start_date' AND '$end_date'
 AND status IN ('responded', 'resolved')";

$response_time = mysqli_query($con, $response_time_query);

if(!$response_time) {
    $avg_response_days = 0;
    $max_response_days = 0;
} else {
    $response_data = mysqli_fetch_assoc($response_time);
    $avg_response_days = isset($response_data['avg_response_days']) ? $response_data['avg_response_days'] : 0;
    $max_response_days = isset($response_data['max_response_days']) ? $response_data['max_response_days'] : 0;
}

// Daily complaints for chart
$daily_complaints_query = "SELECT DATE(complaint_date) as date,
        COUNT(*) as count,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
        COUNT(CASE WHEN status = 'resolved' THEN 1 END) as resolved
 FROM complaints 
 WHERE DATE(complaint_date) BETWEEN '$start_date' AND '$end_date'
 GROUP BY DATE(complaint_date)
 ORDER BY date";

$daily_complaints = mysqli_query($con, $daily_complaints_query);

if(!$daily_complaints) {
    die("Daily complaints query failed: " . mysqli_error($con));
}

// Complaint resolution rate
$resolution_rate_query = "SELECT 
    COUNT(*) as total,
    COUNT(CASE WHEN status = 'resolved' THEN 1 END) as resolved,
    ROUND((COUNT(CASE WHEN status = 'resolved' THEN 1 END) * 100.0 / COUNT(*)), 2) as resolution_rate
 FROM complaints 
 WHERE DATE(complaint_date) BETWEEN '$start_date' AND '$end_date'";

$resolution_rate = mysqli_query($con, $resolution_rate_query);

if(!$resolution_rate) {
    die("Resolution rate query failed: " . mysqli_error($con));
}

$resolution_data = mysqli_fetch_assoc($resolution_rate);

// Prepare data for charts
$daily_labels = [];
$daily_data = [];
$daily_pending = [];
$daily_resolved = [];

while($row = mysqli_fetch_assoc($daily_complaints)) {
    $daily_labels[] = date('M j', strtotime($row['date']));
    $daily_data[] = $row['count'];
    $daily_pending[] = $row['pending'];
    $daily_resolved[] = $row['resolved'];
}

// Monthly data for trends chart
$monthly_labels = [];
$monthly_data = [];
$monthly_pending = [];
$monthly_resolved = [];

mysqli_data_seek($monthly_trends, 0);
while($row = mysqli_fetch_assoc($monthly_trends)) {
    $monthly_labels[] = date('M Y', strtotime($row['month'] . '-01'));
    $monthly_data[] = $row['count'];
    $monthly_pending[] = $row['pending'];
    $monthly_resolved[] = $row['resolved'];
}

// Status data for pie chart
$status_labels = [];
$status_data = [];
$status_colors = ['#ffc107', '#17a2b8', '#28a745', '#dc3545']; // pending, responded, resolved, rejected

mysqli_data_seek($status_breakdown, 0);
while($row = mysqli_fetch_assoc($status_breakdown)) {
    $status_labels[] = ucfirst($row['status']) . ' (' . $row['count'] . ')';
    $status_data[] = $row['count'];
}

// If no complaints found, set default values
if(empty($daily_labels)) {
    $daily_labels = ['No Data'];
    $daily_data = [0];
    $daily_pending = [0];
    $daily_resolved = [0];
}

if(empty($monthly_labels)) {
    $monthly_labels = ['No Data'];
    $monthly_data = [0];
    $monthly_pending = [0];
    $monthly_resolved = [0];
}

if(empty($status_labels)) {
    $status_labels = ['No Complaints'];
    $status_data = [1];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complaint Reports - Admin Panel</title>
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
        .resolution-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .bg-high { background-color: #28a745; color: white; }
        .bg-medium { background-color: #ffc107; color: black; }
        .bg-low { background-color: #dc3545; color: white; }
        .no-data {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
        .no-data i {
            font-size: 3rem;
            margin-bottom: 15px;
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
                            <h1 class="h3 mb-0">Complaint Reports & Analytics</h1>
                            <p class="text-muted mb-0">Comprehensive complaint analysis and insights</p>
                        </div>
                        <div class="col-md-6 text-end">
                            <button class="btn btn-success" onclick="window.print()">
                                <i class="fas fa-print me-2"></i>Print Report
                            </button>
                            <a href="view_complaints.php" class="btn btn-primary">
                                <i class="fas fa-arrow-left me-2"></i>Back to Complaints
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
                    <form method="GET" action="complaint_reports.php">
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
                    <p>Total Complaints</p>
                    <small>Period: <?php echo date('M j', strtotime($start_date)) . ' - ' . date('M j', strtotime($end_date)); ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card bg-warning">
                <div class="card-body text-center">
                    <h2><?php echo number_format($total_data['pending']); ?></h2>
                    <p>Pending</p>
                    <small>Awaiting Response</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card bg-success">
                <div class="card-body text-center">
                    <h2><?php echo number_format($total_data['resolved']); ?></h2>
                    <p>Resolved</p>
                    <small>Successfully Closed</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card bg-info">
                <div class="card-body text-center">
                    <h2><?php echo number_format($resolution_data['resolution_rate']); ?>%</h2>
                    <p>Resolution Rate</p>
                    <small>Overall Performance</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="row mb-4">
        <!-- Daily Complaints Chart -->
        <div class="col-md-8">
            <div class="card card-dashboard">
                <div class="card-header">
                    <h5 class="card-title mb-0">Daily Complaints Trend (<?php echo date('M j', strtotime($start_date)) . ' - ' . date('M j', strtotime($end_date)); ?>)</h5>
                </div>
                <div class="card-body">
                    <?php if($total_data['total'] > 0): ?>
                        <div class="chart-container">
                            <canvas id="dailyChart"></canvas>
                        </div>
                    <?php else: ?>
                        <div class="no-data">
                            <i class="fas fa-chart-line"></i>
                            <h4>No Complaint Data Available</h4>
                            <p>No complaints found for the selected period.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Complaint Status Distribution -->
        <div class="col-md-4">
            <div class="card card-dashboard">
                <div class="card-header">
                    <h5 class="card-title mb-0">Complaint Status Distribution</h5>
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
                            <p>No complaints to display status distribution.</p>
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
                            <p>No complaint data available for trend analysis.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Performance Metrics -->
        <div class="col-md-6">
            <div class="card card-dashboard">
                <div class="card-header">
                    <h5 class="card-title mb-0">Performance Metrics</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-6 mb-3">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h3 class="text-primary"><?php echo number_format($resolution_data['resolution_rate']); ?>%</h3>
                                    <p class="mb-0">Resolution Rate</p>
                                    <small class="text-muted">Complaints Successfully Resolved</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h3 class="text-info"><?php echo number_format($avg_response_days, 1); ?> days</h3>
                                    <p class="mb-0">Avg Response Time</p>
                                    <small class="text-muted">Days to First Response</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h3 class="text-warning"><?php echo number_format($total_data['pending']); ?></h3>
                                    <p class="mb-0">Pending Complaints</p>
                                    <small class="text-muted">Require Attention</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h3 class="text-success"><?php echo number_format($total_data['resolved']); ?></h3>
                                    <p class="mb-0">Resolved This Period</p>
                                    <small class="text-muted">Successful Closures</small>
                                </div>
                            </div>
                        </div>
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
                    <h5 class="card-title mb-0">Top Clients with Most Complaints</h5>
                </div>
                <div class="card-body">
                    <?php if(mysqli_num_rows($top_clients) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Rank</th>
                                        <th>Client Name</th>
                                        <th>Email</th>
                                        <th>Total Complaints</th>
                                        <th>Resolved</th>
                                        <th>Resolution Rate</th>
                                        <th>Last Complaint</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $rank = 1;
                                    mysqli_data_seek($top_clients, 0);
                                    while($client = mysqli_fetch_assoc($top_clients)) {
                                        $client_resolution_rate = $client['complaint_count'] > 0 ? 
                                            round(($client['resolved_count'] / $client['complaint_count']) * 100, 2) : 0;
                                        
                                        $last_complaint = mysqli_query($con, 
                                            "SELECT MAX(complaint_date) as last_complaint 
                                             FROM complaints 
                                             WHERE client_id = (SELECT id FROM users WHERE email = '" . $client['email'] . "')");
                                        $last_complaint_data = mysqli_fetch_assoc($last_complaint);
                                        ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-primary">#<?php echo $rank; ?></span>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($client['name']); ?></strong>
                                            </td>
                                            <td><?php echo htmlspecialchars($client['email']); ?></td>
                                            <td><?php echo number_format($client['complaint_count']); ?></td>
                                            <td><?php echo number_format($client['resolved_count']); ?></td>
                                            <td>
                                                <span class="resolution-badge 
                                                    <?php 
                                                    if($client_resolution_rate >= 80) echo 'bg-high';
                                                    elseif($client_resolution_rate >= 50) echo 'bg-medium';
                                                    else echo 'bg-low';
                                                    ?>
                                                ">
                                                    <?php echo number_format($client_resolution_rate); ?>%
                                                </span>
                                            </td>
                                            <td>
                                                <?php 
                                                if($last_complaint_data['last_complaint']) {
                                                    echo date('M j, Y', strtotime($last_complaint_data['last_complaint']));
                                                } else {
                                                    echo 'N/A';
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                        <?php
                                        $rank++;
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="no-data">
                            <i class="fas fa-users"></i>
                            <h4>No Client Data</h4>
                            <p>No complaint data available for client analysis.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Projects with Most Complaints -->
    <?php if($projects_complaints && mysqli_num_rows($projects_complaints) > 0): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card card-dashboard">
                <div class="card-header">
                    <h5 class="card-title mb-0">Projects with Most Complaints</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Project Name</th>
                                    <th>Total Complaints</th>
                                    <th>Resolved</th>
                                    <th>Pending</th>
                                    <th>Resolution Rate</th>
                                    <th>Priority Level</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                mysqli_data_seek($projects_complaints, 0);
                                while($project = mysqli_fetch_assoc($projects_complaints)) {
                                    $pending_count = $project['complaint_count'] - $project['resolved_count'];
                                    $project_resolution_rate = $project['complaint_count'] > 0 ? 
                                        round(($project['resolved_count'] / $project['complaint_count']) * 100, 2) : 0;
                                    
                                    $priority_level = '';
                                    $priority_class = '';
                                    if($project['complaint_count'] >= 10) {
                                        $priority_level = 'High';
                                        $priority_class = 'bg-danger';
                                    } elseif($project['complaint_count'] >= 5) {
                                        $priority_level = 'Medium';
                                        $priority_class = 'bg-warning';
                                    } else {
                                        $priority_level = 'Low';
                                        $priority_class = 'bg-success';
                                    }
                                    ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo !empty($project['project_name']) ? htmlspecialchars($project['project_name']) : 'No Project'; ?></strong>
                                        </td>
                                        <td><?php echo number_format($project['complaint_count']); ?></td>
                                        <td class="text-success"><?php echo number_format($project['resolved_count']); ?></td>
                                        <td class="text-warning"><?php echo number_format($pending_count); ?></td>
                                        <td>
                                            <span class="resolution-badge 
                                                <?php 
                                                if($project_resolution_rate >= 80) echo 'bg-high';
                                                elseif($project_resolution_rate >= 50) echo 'bg-medium';
                                                else echo 'bg-low';
                                                ?>
                                            ">
                                                <?php echo number_format($project_resolution_rate); ?>%
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $priority_class; ?>">
                                                <?php echo $priority_level; ?>
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
                    <h5 class="card-title mb-0">Complaint Status Breakdown</h5>
                </div>
                <div class="card-body">
                    <?php if($total_data['total'] > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Status</th>
                                        <th>Count</th>
                                        <th>Percentage</th>
                                        <th>Trend</th>
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
                                                        case 'pending': echo 'bg-warning'; break;
                                                        case 'responded': echo 'bg-info'; break;
                                                        case 'resolved': echo 'bg-success'; break;
                                                        case 'rejected': echo 'bg-danger'; break;
                                                        default: echo 'bg-secondary';
                                                    }
                                                    ?>
                                                ">
                                                    <?php echo ucfirst($status['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo number_format($status['count']); ?></td>
                                            <td><?php echo number_format($percentage, 1); ?>%</td>
                                            <td>
                                                <div class="progress" style="height: 8px;">
                                                    <div class="progress-bar 
                                                        <?php 
                                                        switch($status['status']) {
                                                            case 'pending': echo 'bg-warning'; break;
                                                            case 'responded': echo 'bg-info'; break;
                                                            case 'resolved': echo 'bg-success'; break;
                                                            case 'rejected': echo 'bg-danger'; break;
                                                            default: echo 'bg-secondary';
                                                        }
                                                        ?>
                                                    " style="width: <?php echo $percentage; ?>%"></div>
                                                </div>
                                            </td>
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
                            <h4>No Status Breakdown</h4>
                            <p>No complaints available for status analysis.</p>
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
                                    <h4 class="text-primary"><?php echo number_format($resolution_data['resolution_rate']); ?>%</h4>
                                    <small>Overall Resolution Rate</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h4 class="text-info"><?php echo number_format($avg_response_days, 1); ?></h4>
                                    <small>Avg Response Days</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h4 class="text-success"><?php echo number_format($total_data['resolved']); ?></h4>
                                    <small>Complaints Resolved</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h4 class="text-warning"><?php echo number_format($total_data['pending']); ?></h4>
                                    <small>Pending Action</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php if($total_data['total'] > 0): ?>
                        <?php if($resolution_data['resolution_rate'] >= 80): ?>
                            <div class="alert alert-success mt-3">
                                <i class="fas fa-trophy me-2"></i>
                                <strong>Excellent Performance!</strong> Your resolution rate is above 80%.
                            </div>
                        <?php elseif($resolution_data['resolution_rate'] >= 60): ?>
                            <div class="alert alert-warning mt-3">
                                <i class="fas fa-chart-line me-2"></i>
                                <strong>Good Performance!</strong> Consider improving response times to reach 80%+ resolution rate.
                            </div>
                        <?php else: ?>
                            <div class="alert alert-danger mt-3">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Needs Improvement!</strong> Focus on resolving pending complaints to improve resolution rate.
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
    // Daily Complaints Chart
    const dailyCtx = document.getElementById('dailyChart').getContext('2d');
    new Chart(dailyCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($daily_labels); ?>,
            datasets: [{
                label: 'Total Complaints',
                data: <?php echo json_encode($daily_data); ?>,
                borderColor: '#667eea',
                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }, {
                label: 'Pending',
                data: <?php echo json_encode($daily_pending); ?>,
                borderColor: '#ffc107',
                backgroundColor: 'rgba(255, 193, 7, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }, {
                label: 'Resolved',
                data: <?php echo json_encode($daily_resolved); ?>,
                borderColor: '#28a745',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
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
                        text: 'Number of Complaints'
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
                label: 'Total Complaints',
                data: <?php echo json_encode(array_reverse($monthly_data)); ?>,
                backgroundColor: 'rgba(102, 126, 234, 0.8)',
                borderColor: '#667eea',
                borderWidth: 1
            }, {
                label: 'Resolved',
                data: <?php echo json_encode(array_reverse($monthly_resolved)); ?>,
                backgroundColor: 'rgba(40, 167, 69, 0.8)',
                borderColor: '#28a745',
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