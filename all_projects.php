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

// Handle project status update
if(isset($_POST['update_status'])) {
    $project_id = mysqli_real_escape_string($con, $_POST['project_id']);
    $new_status = mysqli_real_escape_string($con, $_POST['status']);
    
    $update_query = "UPDATE projects SET status = '$new_status' WHERE id = '$project_id'";
    if(mysqli_query($con, $update_query)) {
        $success = "Project status updated successfully!";
    } else {
        $error = "Error updating project status: " . mysqli_error($con);
    }
}

// Handle project manager assignment
if(isset($_POST['assign_manager'])) {
    $project_id = mysqli_real_escape_string($con, $_POST['project_id']);
    $manager_id = mysqli_real_escape_string($con, $_POST['manager_id']);
    
    // Update project with manager ID
    $update_query = "UPDATE projects SET project_manager_id = '$manager_id' WHERE id = '$project_id'";
    if(mysqli_query($con, $update_query)) {
        // Also record in assignments table for history
        $assignment_query = "INSERT INTO project_manager_assignments (project_id, project_manager_id, assigned_date) 
                            VALUES ('$project_id', '$manager_id', NOW())";
        mysqli_query($con, $assignment_query);
        
        $success = "Project manager assigned successfully!";
    } else {
        $error = "Error assigning project manager: " . mysqli_error($con);
    }
}

// Handle project deletion
if(isset($_POST['delete_project'])) {
    $project_id = mysqli_real_escape_string($con, $_POST['project_id']);
    
    // First check if project exists
    $check_project = mysqli_query($con, "SELECT * FROM projects WHERE id = '$project_id'");
    if(mysqli_num_rows($check_project) > 0) {
        $delete_query = "DELETE FROM projects WHERE id = '$project_id'";
        if(mysqli_query($con, $delete_query)) {
            $success = "Project deleted successfully!";
        } else {
            $error = "Error deleting project: " . mysqli_error($con);
        }
    } else {
        $error = "Project not found!";
    }
}

// Search functionality
$search = '';
$where_condition = 'WHERE 1=1';
if(isset($_GET['search']) && !empty($_GET['search'])) {
    $search = mysqli_real_escape_string($con, $_GET['search']);
    $where_condition .= " AND (p.name LIKE '%$search%' OR p.description LIKE '%$search%' OR u.name LIKE '%$search%' OR p.status LIKE '%$search%')";
}

// Fetch all projects with client and manager information
$projects_query = mysqli_query($con, 
    "SELECT p.*, u.name as client_name, u.email as client_email, u.phone as client_phone,
            pm.name as manager_name, pm.email as manager_email, pm.id as manager_id
     FROM projects p 
     INNER JOIN users u ON p.client_id = u.id 
     LEFT JOIN project_managers pm ON p.project_manager_id = pm.id
     $where_condition
     ORDER BY p.created_at DESC");

// Fetch all managers for assignment dropdown
$managers_query = mysqli_query($con, "SELECT * FROM project_managers ORDER BY name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Projects - Admin Panel</title>
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
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .bg-pending { background-color: #ffc107; color: #000; }
        .bg-in-progress { background-color: #17a2b8; color: #fff; }
        .bg-completed { background-color: #28a745; color: #fff; }
        .bg-cancelled { background-color: #dc3545; color: #fff; }
        .action-buttons .btn {
            margin: 2px;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(0,0,0,0.02);
        }
        .manager-info {
            font-size: 0.85rem;
        }
        .assign-btn {
            font-size: 0.75rem;
            padding: 2px 8px;
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
                            <h1 class="h3 mb-0">Manage Projects</h1>
                            <p class="text-muted mb-0">View and manage all projects in the system</p>
                        </div>
                    
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search and Filter -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card card-dashboard">
                <div class="card-body">
                    <form method="GET" action="all_projects.php">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="input-group">
                                    <input type="text" class="form-control" name="search" placeholder="Search projects by name, description, client, or status..." value="<?php echo htmlspecialchars($search); ?>">
                                    <button class="btn btn-primary" type="submit">
                                        <i class="fas fa-search me-2"></i>Search
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <a href="all_projects.php" class="btn btn-secondary w-100">
                                    <i class="fas fa-refresh me-2"></i>Reset Filters
                                </a>
                            </div>
                        </div>
                    </form>
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

    <!-- Projects Table -->
    <div class="row">
        <div class="col-12">
            <div class="card card-dashboard">
                <div class="card-header">
                    <h5 class="card-title mb-0">All Projects (<?php echo mysqli_num_rows($projects_query); ?> found)</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Project ID</th>
                                    <th>Project Name</th>
                                    <th>Client</th>
                                    <th>Project Manager</th>
                                    <th>Budget</th>
                                    <th>Start Date</th>
                                    <th>Deadline</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if(mysqli_num_rows($projects_query) > 0) {
                                    while($project = mysqli_fetch_assoc($projects_query)) {
                                        // Determine status badge class
                                        $status_class = '';
                                        switch($project['status']) {
                                            case 'Pending':
                                                $status_class = 'bg-pending';
                                                break;
                                            case 'In Progress':
                                                $status_class = 'bg-in-progress';
                                                break;
                                            case 'Completed':
                                                $status_class = 'bg-completed';
                                                break;
                                            case 'Cancelled':
                                                $status_class = 'bg-cancelled';
                                                break;
                                            default:
                                                $status_class = 'bg-secondary';
                                        }
                                        ?>
                                        <tr>
                                            <td><strong>#<?php echo $project['id']; ?></strong></td>
                                            <td>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($project['name']); ?></strong>
                                                    <?php if(!empty($project['description'])): ?>
                                                        <br><small class="text-muted"><?php echo substr(htmlspecialchars($project['description']), 0, 50); ?>...</small>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($project['client_name']); ?></strong>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($project['client_email']); ?></small>
                                                    <?php if(!empty($project['client_phone'])): ?>
                                                        <br><small class="text-muted"><?php echo htmlspecialchars($project['client_phone']); ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if(!empty($project['manager_name'])): ?>
                                                    <div class="manager-info">
                                                        <strong><?php echo htmlspecialchars($project['manager_name']); ?></strong>
                                                        <br><small class="text-muted"><?php echo htmlspecialchars($project['manager_email']); ?></small>
                                                        <br><button type="button" class="btn btn-outline-primary btn-sm assign-btn mt-1" data-bs-toggle="modal" data-bs-target="#assignManagerModal<?php echo $project['id']; ?>">
                                                            <i class="fas fa-sync-alt me-1"></i>Reassign
                                                        </button>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">Not assigned</span>
                                                    <br><button type="button" class="btn btn-primary btn-sm assign-btn mt-1" data-bs-toggle="modal" data-bs-target="#assignManagerModal<?php echo $project['id']; ?>">
                                                        <i class="fas fa-user-plus me-1"></i>Assign Manager
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                            <td>₹<?php echo number_format($project['budget'], 2); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($project['start_date'])); ?></td>
                                            <td>
                                                <?php if(!empty($project['end_date'])): ?>
                                                    <?php echo date('M d, Y', strtotime($project['end_date'])); ?>
                                                <?php 
                                                    $today = new DateTime();
                                                    $deadline = new DateTime($project['end_date']);
                                                    if($deadline < $today && $project['status'] != 'Completed') {
                                                        echo '<br><small class="text-danger"><i class="fas fa-exclamation-triangle"></i> Overdue</small>';
                                                    }
                                                ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Not set</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="status-badge <?php echo $status_class; ?>">
                                                    <?php echo $project['status']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($project['created_at'])); ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <!-- View Details -->
                                                    <a href="view_project.php?id=<?php echo $project['id']; ?>" class="btn btn-info btn-sm" title="View Details" target="_blank">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    
                                                    
                                                    
                                                    <!-- Status Update Form -->
                                                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#statusModal<?php echo $project['id']; ?>" title="Update Status">
                                                        <i class="fas fa-sync-alt"></i>
                                                    </button>
                                                    
                                                    <!-- Delete Project -->
                                                    <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $project['id']; ?>" title="Delete Project">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>

                                                <!-- Status Update Modal -->
                                                <div class="modal fade" id="statusModal<?php echo $project['id']; ?>" tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <form method="POST">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Update Project Status</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Project: <strong><?php echo htmlspecialchars($project['name']); ?></strong></label>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Select Status:</label>
                                                                        <select class="form-select" name="status" required>
                                                                            <option value="Pending" <?php echo $project['status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                                                            <option value="In Progress" <?php echo $project['status'] == 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                                                                            <option value="Completed" <?php echo $project['status'] == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                                                            <option value="Cancelled" <?php echo $project['status'] == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
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

                                                <!-- Assign Manager Modal -->
                                                <div class="modal fade" id="assignManagerModal<?php echo $project['id']; ?>" tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <form method="POST">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">
                                                                        <?php echo empty($project['manager_name']) ? 'Assign' : 'Reassign'; ?> Project Manager
                                                                    </h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Project: <strong><?php echo htmlspecialchars($project['name']); ?></strong></label>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Select Project Manager:</label>
                                                                        <select class="form-select" name="manager_id" required>
                                                                            <option value="">-- Select Manager --</option>
                                                                            <?php 
                                                                            // Reset pointer and fetch managers again
                                                                            mysqli_data_seek($managers_query, 0);
                                                                            while($manager = mysqli_fetch_assoc($managers_query)): 
                                                                            ?>
                                                                                <option value="<?php echo $manager['id']; ?>" <?php echo $project['manager_id'] == $manager['id'] ? 'selected' : ''; ?>>
                                                                                    <?php echo htmlspecialchars($manager['name']); ?> (<?php echo htmlspecialchars($manager['email']); ?>)
                                                                                </option>
                                                                            <?php endwhile; ?>
                                                                        </select>
                                                                    </div>
                                                                    <?php if(!empty($project['manager_name'])): ?>
                                                                    <div class="alert alert-info">
                                                                        <i class="fas fa-info-circle me-2"></i>
                                                                        Currently assigned to: <strong><?php echo htmlspecialchars($project['manager_name']); ?></strong>
                                                                    </div>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                    <button type="submit" name="assign_manager" class="btn btn-primary">
                                                                        <?php echo empty($project['manager_name']) ? 'Assign Manager' : 'Reassign Manager'; ?>
                                                                    </button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Delete Confirmation Modal -->
                                                <div class="modal fade" id="deleteModal<?php echo $project['id']; ?>" tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <form method="POST">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title text-danger">Confirm Deletion</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                                                                    <p>Are you sure you want to delete the project:</p>
                                                                    <p><strong>"<?php echo htmlspecialchars($project['name']); ?>"</strong>?</p>
                                                                    <p class="text-danger"><small>This action cannot be undone. All project data will be permanently deleted.</small></p>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                    <button type="submit" name="delete_project" class="btn btn-danger">Delete Project</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                } else {
                                    echo '<tr><td colspan="10" class="text-center py-4 text-muted">';
                                    if(!empty($search)) {
                                        echo '<i class="fas fa-search fa-3x mb-3"></i><br>No projects found matching your search criteria.';
                                    } else {
                                        echo '<i class="fas fa-project-diagram fa-3x mb-3"></i><br>No projects found in the system.';
                                    }
                                    echo '</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Summary -->
    <div class="row mt-4">
        <div class="col-md-3">
            <div class="card card-dashboard text-white bg-primary text-center">
                <div class="card-body">
                    <?php
                    $total = mysqli_query($con, "SELECT COUNT(*) as total FROM projects");
                    $total_data = mysqli_fetch_assoc($total);
                    ?>
                    <h3><?php echo $total_data['total']; ?></h3>
                    <p>Total Projects</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-dashboard text-white bg-success text-center">
                <div class="card-body">
                    <?php
                    $active = mysqli_query($con, "SELECT COUNT(*) as active FROM projects WHERE status = 'In Progress'");
                    $active_data = mysqli_fetch_assoc($active);
                    ?>
                    <h3><?php echo $active_data['active']; ?></h3>
                    <p>Active Projects</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-dashboard text-white bg-warning text-center">
                <div class="card-body">
                    <?php
                    $completed = mysqli_query($con, "SELECT COUNT(*) as completed FROM projects WHERE status = 'Completed'");
                    $completed_data = mysqli_fetch_assoc($completed);
                    ?>
                    <h3><?php echo $completed_data['completed']; ?></h3>
                    <p>Completed</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-dashboard text-white bg-info text-center">
                <div class="card-body">
                    <?php
                    $pending = mysqli_query($con, "SELECT COUNT(*) as pending FROM projects WHERE status = 'Pending'");
                    $pending_data = mysqli_fetch_assoc($pending);
                    ?>
                    <h3><?php echo $pending_data['pending']; ?></h3>
                    <p>Pending</p>
                </div>
            </div>
        </div>
    </div>
</div>

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