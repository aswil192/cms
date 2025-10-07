<?php

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - BuildMaster</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .container-fluid {
            padding: 20px;
        }
        .card-dashboard {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            border: 1px solid #e3e6f0;
            transition: transform 0.2s ease;
        }
        .card-dashboard:hover {
            transform: translateY(-2px);
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-bottom: none;
            padding: 15px 20px;
            border-radius: 10px 10px 0 0;
        }
        .card-body {
            padding: 20px;
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .stat-label {
            font-size: 0.9rem;
            color: #000000;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .bg-primary { background-color: #007bff !important; }
        .bg-success { background-color: #28a745 !important; }
        .bg-warning { background-color: #ffc107 !important; }
        .bg-info { background-color: #17a2b8 !important; }
        .bg-danger { background-color: #dc3545 !important; }
        .bg-secondary { background-color: #6c757d !important; }
        .bg-purple { background-color: #6f42c1 !important; }
    </style>
</head>
<body>
<?php include("admin_menu.php"); ?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card card-dashboard">
                <div class="card-body text-center">
                    <h1 class="h2 mb-2">Admin Dashboard</h1>
                    <p class="text-muted mb-0">Welcome back, <?php echo isset($_SESSION['name']) ? $_SESSION['name'] : 'Admin'; ?>! Here's your overview.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row">
        <!-- Total Projects -->
        <div class="col-md-3">
            <div class="card card-dashboard text-white bg-primary">
                <div class="card-body text-center">
                    <?php
                    $total_projects = mysqli_query($con, "SELECT COUNT(*) as total FROM projects");
                    if($total_projects) {
                        $projects_data = mysqli_fetch_assoc($total_projects);
                        echo '<div class="stat-number">' . $projects_data['total'] . '</div>';
                    } else {
                        echo '<div class="stat-number">0</div>';
                    }
                    ?>
                    <div class="stat-label">Total Projects</div>
                    <i class="fas fa-project-diagram fa-2x mt-2"></i>
                </div>
            </div>
        </div>

        <!-- Active Projects -->
        <div class="col-md-3">
            <div class="card card-dashboard text-white bg-success">
                <div class="card-body text-center">
                    <?php
                    $active_projects = mysqli_query($con, "SELECT COUNT(*) as active FROM projects WHERE status = 'In Progress'");
                    if($active_projects) {
                        $active_data = mysqli_fetch_assoc($active_projects);
                        echo '<div class="stat-number">' . $active_data['active'] . '</div>';
                    } else {
                        echo '<div class="stat-number">0</div>';
                    }
                    ?>
                    <div class="stat-label">Active Projects</div>
                    <i class="fas fa-tasks fa-2x mt-2"></i>
                </div>
            </div>
        </div>

        <!-- Total Clients -->
        <div class="col-md-3">
            <div class="card card-dashboard text-white bg-warning">
                <div class="card-body text-center">
                    <?php
                    // Count all users as clients (since users table stores clients)
                    $total_clients = mysqli_query($con, "SELECT COUNT(*) as clients FROM users");
                    
                    if($total_clients && mysqli_num_rows($total_clients) > 0) {
                        $clients_data = mysqli_fetch_assoc($total_clients);
                        echo '<div class="stat-number">' . $clients_data['clients'] . '</div>';
                    } else {
                        echo '<div class="stat-number">0</div>';
                    }
                    ?>
                    <div class="stat-label">Total Clients</div>
                    <i class="fas fa-users fa-2x mt-2"></i>
                </div>
            </div>
        </div>

        <!-- Project Managers -->
        <div class="col-md-3">
            <div class="card card-dashboard text-white bg-info">
                <div class="card-body text-center">
                    <?php
                    $total_pms = mysqli_query($con, "SELECT COUNT(*) as pms FROM project_managers");
                    if($total_pms) {
                        $pms_data = mysqli_fetch_assoc($total_pms);
                        echo '<div class="stat-number">' . $pms_data['pms'] . '</div>';
                    } else {
                        echo '<div class="stat-number">0</div>';
                    }
                    ?>
                    <div class="stat-label">Project Managers</div>
                    <i class="fas fa-user-tie fa-2x mt-2"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Second Row Statistics -->
    <div class="row mt-4">
        <!-- Completed Projects -->
        <div class="col-md-3">
            <div class="card card-dashboard text-white bg-success">
                <div class="card-body text-center">
                    <?php
                    $completed_projects = mysqli_query($con, "SELECT COUNT(*) as completed FROM projects WHERE status = 'Completed'");
                    if($completed_projects) {
                        $completed_data = mysqli_fetch_assoc($completed_projects);
                        echo '<div class="stat-number">' . $completed_data['completed'] . '</div>';
                    } else {
                        echo '<div class="stat-number">0</div>';
                    }
                    ?>
                    <div class="stat-label">Completed Projects</div>
                    <i class="fas fa-check-circle fa-2x mt-2"></i>
                </div>
            </div>
        </div>

        <!-- Pending Projects -->
        <div class="col-md-3">
            <div class="card card-dashboard text-white bg-danger">
                <div class="card-body text-center">
                    <?php
                    $pending_projects = mysqli_query($con, "SELECT COUNT(*) as pending FROM projects WHERE status = 'Pending'");
                    if($pending_projects) {
                        $pending_data = mysqli_fetch_assoc($pending_projects);
                        echo '<div class="stat-number">' . $pending_data['pending'] . '</div>';
                    } else {
                        echo '<div class="stat-number">0</div>';
                    }
                    ?>
                    <div class="stat-label">Pending Projects</div>
                    <i class="fas fa-clock fa-2x mt-2"></i>
                </div>
            </div>
        </div>

        <!-- Total Payments -->
        <div class="col-md-3">
            <div class="card card-dashboard text-white bg-purple">
                <div class="card-body text-center">
                    <?php
                    $total_payments = mysqli_query($con, "SELECT COUNT(*) as payments FROM payment");
                    if($total_payments) {
                        $payments_data = mysqli_fetch_assoc($total_payments);
                        echo '<div class="stat-number">' . $payments_data['payments'] . '</div>';
                    } else {
                        echo '<div class="stat-number">0</div>';
                    }
                    ?>
                    <div class="stat-label">Total Payments</div>
                    <i class="fas fa-money-bill-wave fa-2x mt-2"></i>
                </div>
            </div>
        </div>

        <!-- Revenue -->
        <div class="col-md-3">
            <div class="card card-dashboard text-white bg-secondary">
                <div class="card-body text-center">
                    <?php
                    $total_revenue = mysqli_query($con, "SELECT COALESCE(SUM(amount), 0) as revenue FROM payment WHERE status = 'completed'");
                    if($total_revenue) {
                        $revenue_data = mysqli_fetch_assoc($total_revenue);
                        echo '<div class="stat-number">₹' . number_format($revenue_data['revenue'], 2) . '</div>';
                    } else {
                        echo '<div class="stat-number">₹0.00</div>';
                    }
                    ?>
                    <div class="stat-label">Total Revenue</div>
                    <i class="fas fa-chart-line fa-2x mt-2"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions & Recent Activity -->
    <div class="row mt-4">
        <!-- Quick Actions -->
        <div class="col-md-6">
            <div class="card card-dashboard">
                <div class="card-header">
                    <h5 class="card-title mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <a href="all_projects.php" class="btn btn-primary w-100">
                                <i class="fas fa-project-diagram me-2"></i>Manage Projects
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="all_clients.php" class="btn btn-success w-100">
                                <i class="fas fa-users me-2"></i>Manage Clients
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="all_managers.php" class="btn btn-info w-100">
                                <i class="fas fa-user-plus me-2"></i>Manage Project Manager
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="admin_payments.php" class="btn btn-warning w-100">
                                <i class="fas fa-money-bill me-2"></i>View Payments
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="view_complaints.php" class="btn btn-danger w-100">
                                <i class="fas fa-exclamation-circle me-2"></i>View Complaints
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="admin_resources.php" class="btn btn-secondary w-100">
                                <i class="fas fa-tools me-2"></i>Manage Resources
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Projects -->
        <div class="col-md-6">
            <div class="card card-dashboard">
                <div class="card-header">
                    <h5 class="card-title mb-0">Recent Projects</h5>
                </div>
                <div class="card-body">
                    <?php
                    $recent_projects = mysqli_query($con, 
                        "SELECT p.*, u.name as client_name 
                         FROM projects p 
                         INNER JOIN users u ON p.client_id = u.id 
                         ORDER BY p.created_at DESC 
                         LIMIT 5");
                    
                    if($recent_projects && mysqli_num_rows($recent_projects) > 0) {
                        while($project = mysqli_fetch_assoc($recent_projects)) {
                            $status_badge = '';
                            switch($project['status']) {
                                case 'Completed':
                                    $status_badge = 'bg-success';
                                    break;
                                case 'In Progress':
                                    $status_badge = 'bg-primary';
                                    break;
                                case 'Pending':
                                    $status_badge = 'bg-warning';
                                    break;
                                default:
                                    $status_badge = 'bg-secondary';
                            }
                            ?>
                            <div class="d-flex justify-content-between align-items-center mb-3 p-2 border-bottom">
                                <div>
                                    <strong><?php echo htmlspecialchars($project['name']); ?></strong>
                                    <br>
                                    <small class="text-muted">Client: <?php echo htmlspecialchars($project['client_name']); ?></small>
                                </div>
                                <span class="badge <?php echo $status_badge; ?>"><?php echo $project['status']; ?></span>
                            </div>
                            <?php
                        }
                    } else {
                        echo '<p class="text-muted">No projects found</p>';
                    }
                    ?>
                    <div class="text-center mt-3">
                        <a href="view_all_projects.php" class="btn btn-outline-primary btn-sm">View All Projects</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- System Overview -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card card-dashboard">
                <div class="card-header">
                    <h5 class="card-title mb-0">System Overview</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <i class="fas fa-database fa-2x text-primary mb-2"></i>
                                <h4>
                                    <?php 
                                    $tables = mysqli_query($con, "SHOW TABLES");
                                    if($tables) {
                                        echo mysqli_num_rows($tables);
                                    } else {
                                        echo '0';
                                    }
                                    ?>
                                </h4>
                                <p class="text-muted">Database Tables</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <i class="fas fa-hdd fa-2x text-success mb-2"></i>
                                <h4>
                                    <?php 
                                    $db_size = mysqli_query($con, "SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) as size 
                                    FROM information_schema.TABLES 
                                    WHERE table_schema = DATABASE()");
                                    if($db_size) {
                                        $size_data = mysqli_fetch_assoc($db_size);
                                        echo ($size_data['size'] ? $size_data['size'] : '0') . ' MB';
                                    } else {
                                        echo '0 MB';
                                    }
                                    ?>
                                </h4>
                                <p class="text-muted">Database Size</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <i class="fas fa-calendar-day fa-2x text-warning mb-2"></i>
                                <h4><?php echo date('M d, Y'); ?></h4>
                                <p class="text-muted">Today's Date</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <i class="fas fa-server fa-2x text-info mb-2"></i>
                                <h4>PHP <?php echo phpversion(); ?></h4>
                                <p class="text-muted">PHP Version</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Simple animations for cards
    const cards = document.querySelectorAll('.card-dashboard');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });

    // Prevent form resubmission on page refresh
    if(window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
});
</script>

<?php include("footer.php"); ?>
</body>
</html>