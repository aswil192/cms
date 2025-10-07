<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if(!isset($_SESSION['user_type'])) {
    header("Location: login.php");
    exit();
}

include("connection.php");

// Check database connection
if(!$con) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Get project ID from URL parameter
if(!isset($_GET['id']) || empty($_GET['id'])) {
    if($_SESSION['user_type'] == 'admin') {
        header("Location: all_projects.php");
    } else {
        header("Location: projects.php");
    }
    exit();
}

$project_id = mysqli_real_escape_string($con, $_GET['id']);

// Check what columns exist in projects table
$check_columns = mysqli_query($con, "SHOW COLUMNS FROM projects");
$columns = array();
if($check_columns) {
    while($column = mysqli_fetch_assoc($check_columns)) {
        $columns[] = $column['Field'];
    }
}

// Determine the correct project name field
$project_name_field = 'title'; // Default field name
if (in_array('project_name', $columns)) {
    $project_name_field = 'project_name';
} elseif (in_array('name', $columns)) {
    $project_name_field = 'name';
} elseif (in_array('title', $columns)) {
    $project_name_field = 'title';
}

// Build query based on available columns
$select_fields = "p.*, u.name as client_name, u.email as client_email, u.phone as client_phone";
if (in_array('pm_id', $columns)) {
    $select_fields .= ", pm.name as pm_name";
}

$query = "SELECT $select_fields 
          FROM projects p 
          LEFT JOIN users u ON p.client_id = u.id";
          
if (in_array('pm_id', $columns)) {
    $query .= " LEFT JOIN project_managers pm ON p.pm_id = pm.id";
}

$query .= " WHERE p.id = '$project_id'";

// Fetch project details
$project_query = mysqli_query($con, $query);

if(!$project_query || mysqli_num_rows($project_query) == 0) {
    if($_SESSION['user_type'] == 'admin') {
        header("Location: all_projects.php");
    } else {
        header("Location: projects.php");
    }
    exit();
}

$project = mysqli_fetch_assoc($project_query);

// Check if user has permission to view this project
if($_SESSION['user_type'] == 'client' && $project['client_id'] != $_SESSION['uid']) {
    header("Location: projects.php");
    exit();
}

// Fetch project tasks if tasks table exists
$tasks = array();
$tasks_table_check = mysqli_query($con, "SHOW TABLES LIKE 'tasks'");
if(mysqli_num_rows($tasks_table_check) > 0) {
    $tasks_query = mysqli_query($con, 
        "SELECT * FROM tasks 
         WHERE project_id = '$project_id' 
         ORDER BY created_at DESC 
         LIMIT 10");
    if($tasks_query) {
        while($task = mysqli_fetch_assoc($tasks_query)) {
            $tasks[] = $task;
        }
    }
}

// Fetch project payments if payment table exists
$payments = array();
$payment_table_check = mysqli_query($con, "SHOW TABLES LIKE 'payment'");
if(mysqli_num_rows($payment_table_check) > 0) {
    $payments_query = mysqli_query($con, 
        "SELECT * FROM payment 
         WHERE project_id = '$project_id' 
         ORDER BY payment_date DESC 
         LIMIT 10");
    if($payments_query) {
        while($payment = mysqli_fetch_assoc($payments_query)) {
            $payments[] = $payment;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Details - <?php echo htmlspecialchars($project[$project_name_field]); ?> - BuildMaster</title>
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
        .status-badge {
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        .bg-in-progress { background-color: #17a2b8; color: #fff; }
        .bg-completed { background-color: #28a745; color: #fff; }
        .bg-pending { background-color: #ffc107; color: #212529; }
        .bg-cancelled { background-color: #dc3545; color: #fff; }
        .bg-planning { background-color: #6f42c1; color: #fff; }
        .info-label {
            font-weight: 600;
            color: #6c757d;
            margin-bottom: 5px;
            font-size: 0.9rem;
        }
        .info-value {
            font-size: 1rem;
            margin-bottom: 15px;
            color: #333;
        }
        .progress {
            height: 10px;
            margin-bottom: 10px;
        }
        .task-status-badge {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .payment-status-badge {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .bg-success { background-color: #28a745; color: #fff; }
        .bg-warning { background-color: #ffc107; color: #212529; }
        .bg-danger { background-color: #dc3545; color: #fff; }
        .bg-info { background-color: #17a2b8; color: #fff; }
    </style>
</head>
<body>
<?php 
if($_SESSION['user_type'] == 'admin') {
    include("admin_menu.php");
} else {
    include("header.php");
}
?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card card-dashboard">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <?php if($_SESSION['user_type'] == 'admin'): ?>
                                        <li class="breadcrumb-item"><a href="all_projects.php"><i class="fas fa-folder me-2"></i>Projects</a></li>
                                    <?php else: ?>
                                        <li class="breadcrumb-item"><a href="projects.php"><i class="fas fa-folder me-2"></i>My Projects</a></li>
                                    <?php endif; ?>
                                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($project[$project_name_field]); ?></li>
                                </ol>
                            </nav>
                            <h1 class="h3 mb-0">Project Details</h1>
                            <p class="text-muted mb-0">View detailed information about this project</p>
                        </div>
                        <div class="col-md-6 text-end">
                            <?php if($_SESSION['user_type'] == 'admin'): ?>
                                <a href="all_projects.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Projects
                                </a>
                            <?php else: ?>
                                <a href="projects.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Back to My Projects
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Project Information -->
        <div class="col-md-8">
            <div class="card card-dashboard">
                <div class="card-header">
                    <h5 class="card-title mb-0">Project Information</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <h3 class="text-primary"><?php echo htmlspecialchars($project[$project_name_field]); ?></h3>
                            <?php if(!empty($project['description'])): ?>
                                <p class="text-muted"><?php echo htmlspecialchars($project['description']); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4 text-end">
                            <?php
                            $status_class = 'bg-secondary';
                            switch($project['status']) {
                                case 'In Progress':
                                    $status_class = 'bg-in-progress';
                                    break;
                                case 'Completed':
                                    $status_class = 'bg-completed';
                                    break;
                                case 'Pending':
                                    $status_class = 'bg-pending';
                                    break;
                                case 'Cancelled':
                                    $status_class = 'bg-cancelled';
                                    break;
                                case 'Planning':
                                    $status_class = 'bg-planning';
                                    break;
                            }
                            ?>
                            <span class="status-badge <?php echo $status_class; ?>">
                                <?php echo $project['status']; ?>
                            </span>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-label">Project ID</div>
                            <div class="info-value">#<?php echo $project['id']; ?></div>

                            <div class="info-label">Start Date</div>
                            <div class="info-value">
                                <?php echo !empty($project['start_date']) ? date('F d, Y', strtotime($project['start_date'])) : 'Not set'; ?>
                            </div>

                            <div class="info-label">Estimated End Date</div>
                            <div class="info-value">
                                <?php echo !empty($project['end_date']) ? date('F d, Y', strtotime($project['end_date'])) : 'Not set'; ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-label">Budget</div>
                            <div class="info-value">
                                <?php echo !empty($project['budget']) ? '$' . number_format($project['budget'], 2) : 'Not set'; ?>
                            </div>

                            <div class="info-label">Location</div>
                            <div class="info-value">
                                <?php echo !empty($project['location']) ? htmlspecialchars($project['location']) : 'Not specified'; ?>
                            </div>

                            <?php if(isset($project['pm_name']) && !empty($project['pm_name'])): ?>
                                <div class="info-label">Project Manager</div>
                                <div class="info-value"><?php echo htmlspecialchars($project['pm_name']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if(!empty($project['progress']) || $project['progress'] === '0'): ?>
                    <div class="mt-4">
                        <div class="info-label">Project Progress</div>
                        <div class="progress">
                            <div class="progress-bar bg-success" role="progressbar" 
                                 style="width: <?php echo $project['progress']; ?>%" 
                                 aria-valuenow="<?php echo $project['progress']; ?>" 
                                 aria-valuemin="0" aria-valuemax="100">
                            </div>
                        </div>
                        <div class="text-end">
                            <small class="text-muted"><?php echo $project['progress']; ?>% Complete</small>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Tasks -->
            <?php if(!empty($tasks)): ?>
            <div class="card card-dashboard mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Recent Tasks</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Task</th>
                                    <th>Status</th>
                                    <th>Due Date</th>
                                    <th>Priority</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($tasks as $task): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($task['title']); ?></strong>
                                            <?php if(!empty($task['description'])): ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars(substr($task['description'], 0, 50)); ?><?php echo strlen($task['description']) > 50 ? '...' : ''; ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $task_status_class = 'bg-secondary';
                                            switch($task['status']) {
                                                case 'Completed':
                                                    $task_status_class = 'bg-success';
                                                    break;
                                                case 'In Progress':
                                                    $task_status_class = 'bg-info';
                                                    break;
                                                case 'Pending':
                                                    $task_status_class = 'bg-warning';
                                                    break;
                                            }
                                            ?>
                                            <span class="task-status-badge <?php echo $task_status_class; ?>">
                                                <?php echo $task['status']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo !empty($task['due_date']) ? date('M d, Y', strtotime($task['due_date'])) : 'Not set'; ?>
                                        </td>
                                        <td>
                                            <?php echo !empty($task['priority']) ? ucfirst($task['priority']) : 'Normal'; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if(count($tasks) >= 10): ?>
                        <div class="text-center mt-3">
                            <a href="project_tasks.php?project_id=<?php echo $project_id; ?>" class="btn btn-sm btn-outline-primary">View All Tasks</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="col-md-4">
            <!-- Client Information -->
            <div class="card card-dashboard">
                <div class="card-header">
                    <h5 class="card-title mb-0">Client Information</h5>
                </div>
                <div class="card-body">
                    <div class="info-label">Client Name</div>
                    <div class="info-value"><?php echo htmlspecialchars($project['client_name']); ?></div>

                    <div class="info-label">Email</div>
                    <div class="info-value"><?php echo htmlspecialchars($project['client_email']); ?></div>

                    <div class="info-label">Phone</div>
                    <div class="info-value">
                        <?php echo !empty($project['client_phone']) ? htmlspecialchars($project['client_phone']) : 'Not provided'; ?>
                    </div>

                    <?php if($_SESSION['user_type'] == 'admin'): ?>
                        <div class="mt-3">
                            <a href="client_details.php?id=<?php echo $project['client_id']; ?>" class="btn btn-sm btn-outline-primary w-100">
                                <i class="fas fa-user me-2"></i>View Client Details
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Payment History -->
            <?php if(!empty($payments)): ?>
            <div class="card card-dashboard mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Recent Payments</h5>
                </div>
                <div class="card-body">
                    <?php foreach($payments as $payment): ?>
                        <div class="border-bottom pb-2 mb-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <strong>$<?php echo number_format($payment['amount'], 2); ?></strong>
                                <span class="payment-status-badge bg-success">Completed</span>
                            </div>
                            <small class="text-muted">
                                <?php echo date('M d, Y', strtotime($payment['payment_date'])); ?>
                            </small>
                        </div>
                    <?php endforeach; ?>
                    <?php if(count($payments) >= 10): ?>
                        <div class="text-center mt-2">
                            <a href="vpayment.php?project_id=<?php echo $project_id; ?>" class="btn btn-sm btn-outline-primary">View All Payments</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-close alerts after 5 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
});
</script>

<?php include("footer.php"); ?>
</body>
</html>