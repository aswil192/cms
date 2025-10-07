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

// Get client ID from URL parameter
if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: all_clients.php");
    exit();
}

$client_id = mysqli_real_escape_string($con, $_GET['id']);

// Fetch client details
$client_query = mysqli_query($con, 
    "SELECT users.*,
            (SELECT COUNT(*) FROM projects WHERE projects.client_id = users.id) as project_count,
            (SELECT COUNT(*) FROM projects WHERE projects.client_id = users.id AND projects.status = 'In Progress') as active_projects,
            (SELECT COUNT(*) FROM projects WHERE projects.client_id = users.id AND projects.status = 'Completed') as completed_projects
     FROM users 
     WHERE users.id = '$client_id'");

if(!$client_query || mysqli_num_rows($client_query) == 0) {
    header("Location: all_clients.php");
    exit();
}

$client = mysqli_fetch_assoc($client_query);

// Check what columns exist in projects table
$check_project_columns = mysqli_query($con, "SHOW COLUMNS FROM projects");
$project_columns = array();
if($check_project_columns) {
    while($column = mysqli_fetch_assoc($check_project_columns)) {
        $project_columns[] = $column['Field'];
    }
}

// Determine the correct project name field
$project_name_field = 'title'; // Default field name
if (in_array('project_name', $project_columns)) {
    $project_name_field = 'project_name';
} elseif (in_array('name', $project_columns)) {
    $project_name_field = 'name';
} elseif (in_array('title', $project_columns)) {
    $project_name_field = 'title';
}

// Fetch client's projects with correct field name
$projects_query = mysqli_query($con, 
    "SELECT * FROM projects 
     WHERE client_id = '$client_id' 
     ORDER BY created_at DESC");

// Check what columns exist in users table
$check_user_columns = mysqli_query($con, "SHOW COLUMNS FROM users");
$user_columns = array();
if($check_user_columns) {
    while($column = mysqli_fetch_assoc($check_user_columns)) {
        $user_columns[] = $column['Field'];
    }
}

$has_status_column = in_array('status', $user_columns);
$has_created_at_column = in_array('created_at', $user_columns);

// Handle status update
if(isset($_POST['update_status']) && $has_status_column) {
    $new_status = mysqli_real_escape_string($con, $_POST['status']);
    
    $update_query = "UPDATE users SET status = '$new_status' WHERE id = '$client_id'";
    if(mysqli_query($con, $update_query)) {
        $success = "Client status updated successfully!";
        // Refresh client data
        $client_query = mysqli_query($con, 
            "SELECT users.*,
                    (SELECT COUNT(*) FROM projects WHERE projects.client_id = users.id) as project_count,
                    (SELECT COUNT(*) FROM projects WHERE projects.client_id = users.id AND projects.status = 'In Progress') as active_projects,
                    (SELECT COUNT(*) FROM projects WHERE projects.client_id = users.id AND projects.status = 'Completed') as completed_projects
             FROM users 
             WHERE users.id = '$client_id'");
        $client = mysqli_fetch_assoc($client_query);
    } else {
        $error = "Error updating client status: " . mysqli_error($con);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Details - <?php echo htmlspecialchars($client['name']); ?> - Admin Panel</title>
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
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        .bg-active { background-color: #28a745; color: #fff; }
        .bg-inactive { background-color: #6c757d; color: #fff; }
        .bg-suspended { background-color: #dc3545; color: #fff; }
        .client-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 2rem;
            margin: 0 auto;
        }
        .info-label {
            font-weight: 600;
            color: #6c757d;
            margin-bottom: 5px;
        }
        .info-value {
            font-size: 1.1rem;
            margin-bottom: 15px;
        }
        .project-status-badge {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .bg-in-progress { background-color: #17a2b8; color: #fff; }
        .bg-completed { background-color: #28a745; color: #fff; }
        .bg-pending { background-color: #ffc107; color: #212529; }
        .bg-cancelled { background-color: #dc3545; color: #fff; }
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
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="all_clients.php"><i class="fas fa-users me-2"></i>Clients</a></li>
                                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($client['name']); ?></li>
                                </ol>
                            </nav>
                            <h1 class="h3 mb-0">Client Details</h1>
                            <p class="text-muted mb-0">View detailed information about <?php echo htmlspecialchars($client['name']); ?></p>
                        </div>
                        <div class="col-md-6 text-end">
                            <a href="all_clients.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to Clients
                            </a>
                            <?php if($has_status_column): ?>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#statusModal">
                                <i class="fas fa-sync-alt me-2"></i>Update Status
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alerts -->
    <?php if(isset($success)): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if(isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Client Information -->
        <div class="col-md-4">
            <div class="card card-dashboard">
                <div class="card-header">
                    <h5 class="card-title mb-0">Client Information</h5>
                </div>
                <div class="card-body text-center">
                    <div class="client-avatar mb-3">
                        <?php echo strtoupper(substr($client['name'], 0, 1)); ?>
                    </div>
                    <h3><?php echo htmlspecialchars($client['name']); ?></h3>
                    
                    <?php if($has_status_column): ?>
                    <div class="mb-3">
                        <?php
                        $status_class = 'bg-secondary';
                        if(isset($client['status'])) {
                            switch($client['status']) {
                                case 'Active':
                                    $status_class = 'bg-active';
                                    break;
                                case 'Inactive':
                                    $status_class = 'bg-inactive';
                                    break;
                                case 'Suspended':
                                    $status_class = 'bg-suspended';
                                    break;
                            }
                        }
                        ?>
                        <span class="status-badge <?php echo $status_class; ?>">
                            <?php echo isset($client['status']) ? $client['status'] : 'Active'; ?>
                        </span>
                    </div>
                    <?php endif; ?>

                    <div class="text-start">
                        <div class="info-label">Client ID</div>
                        <div class="info-value">#<?php echo $client['id']; ?> (C<?php echo str_pad($client['id'], 4, '0', STR_PAD_LEFT); ?>)</div>

                        <div class="info-label">Email Address</div>
                        <div class="info-value">
                            <i class="fas fa-envelope text-muted me-2"></i>
                            <?php echo htmlspecialchars($client['email']); ?>
                        </div>

                        <div class="info-label">Phone Number</div>
                        <div class="info-value">
                            <i class="fas fa-phone text-muted me-2"></i>
                            <?php echo !empty($client['phone']) ? htmlspecialchars($client['phone']) : 'Not provided'; ?>
                        </div>

                        <?php if($has_created_at_column && !empty($client['created_at'])): ?>
                        <div class="info-label">Member Since</div>
                        <div class="info-value">
                            <i class="fas fa-calendar text-muted me-2"></i>
                            <?php echo date('F d, Y', strtotime($client['created_at'])); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Project Statistics -->
            <div class="card card-dashboard">
                <div class="card-header">
                    <h5 class="card-title mb-0">Project Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="border rounded p-3">
                                <h3 class="text-primary"><?php echo $client['project_count']; ?></h3>
                                <small class="text-muted">Total Projects</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border rounded p-3">
                                <h3 class="text-warning"><?php echo $client['active_projects']; ?></h3>
                                <small class="text-muted">Active</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border rounded p-3">
                                <h3 class="text-success"><?php echo $client['completed_projects']; ?></h3>
                                <small class="text-muted">Completed</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Client Projects -->
        <div class="col-md-8">
            <div class="card card-dashboard">
                <div class="card-header">
                    <h5 class="card-title mb-0">Client Projects</h5>
                </div>
                <div class="card-body">
                    <?php if($projects_query && mysqli_num_rows($projects_query) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Project ID</th>
                                        <th>Project Name</th>
                                        <th>Status</th>
                                        <th>Start Date</th>
                                        <th>Budget</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($project = mysqli_fetch_assoc($projects_query)): ?>
                                        <tr>
                                            <td><strong>#<?php echo $project['id']; ?></strong></td>
                                            <td>
                                                <strong>
                                                    <?php 
                                                    // Safely display project name using the correct field
                                                    if (isset($project[$project_name_field]) && !empty($project[$project_name_field])) {
                                                        echo htmlspecialchars($project[$project_name_field]);
                                                    } else {
                                                        echo 'Unnamed Project';
                                                    }
                                                    ?>
                                                </strong>
                                                <?php if(!empty($project['description'])): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars(substr($project['description'], 0, 50)); ?><?php echo strlen($project['description']) > 50 ? '...' : ''; ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php
                                                $project_status_class = 'bg-secondary';
                                                switch($project['status']) {
                                                    case 'In Progress':
                                                        $project_status_class = 'bg-in-progress';
                                                        break;
                                                    case 'Completed':
                                                        $project_status_class = 'bg-completed';
                                                        break;
                                                    case 'Pending':
                                                        $project_status_class = 'bg-pending';
                                                        break;
                                                    case 'Cancelled':
                                                        $project_status_class = 'bg-cancelled';
                                                        break;
                                                }
                                                ?>
                                                <span class="project-status-badge <?php echo $project_status_class; ?>">
                                                    <?php echo $project['status']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php echo !empty($project['start_date']) ? date('M d, Y', strtotime($project['start_date'])) : 'Not set'; ?>
                                            </td>
                                            <td>
                                                <?php echo !empty($project['budget']) ? '$' . number_format($project['budget'], 2) : 'Not set'; ?>
                                            </td>
                                            <td>
                                                <a href="project_details.php?id=<?php echo $project['id']; ?>" class="btn btn-info btn-sm" title="View Project">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="edit_project.php?id=<?php echo $project['id']; ?>" class="btn btn-warning btn-sm" title="Edit Project">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-folder-open fa-3x mb-3"></i>
                            <br>
                            <h5>No Projects Found</h5>
                            <p>This client doesn't have any projects yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Activity (Optional - can be expanded later) -->
            <div class="card card-dashboard">
                <div class="card-header">
                    <h5 class="card-title mb-0">Recent Activity</h5>
                </div>
                <div class="card-body">
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-chart-line fa-3x mb-3"></i>
                        <br>
                        <p>Activity tracking will be available in future updates.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if($has_status_column): ?>
<!-- Status Update Modal -->
<div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Update Client Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="client_id" value="<?php echo $client_id; ?>">
                    <div class="mb-3">
                        <label class="form-label">Client: <strong><?php echo htmlspecialchars($client['name']); ?></strong></label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Select Status:</label>
                        <select class="form-select" name="status" required>
                            <option value="Active" <?php echo (isset($client['status']) && $client['status'] == 'Active') ? 'selected' : ''; ?>>Active</option>
                            <option value="Inactive" <?php echo (isset($client['status']) && $client['status'] == 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                            <option value="Suspended" <?php echo (isset($client['status']) && $client['status'] == 'Suspended') ? 'selected' : ''; ?>>Suspended</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Prevent form resubmission on page refresh
    if(window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
    
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