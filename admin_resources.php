<?php
// admin_resources.php
// Admin page to view and manage all resources requested by project managers

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

// Handle resource status update
if(isset($_POST['update_status'])) {
    $resource_id = mysqli_real_escape_string($con, $_POST['resource_id']);
    $new_status = mysqli_real_escape_string($con, $_POST['status']);
    
    $update_query = "UPDATE resources SET status = '$new_status' WHERE id = '$resource_id'";
    if(mysqli_query($con, $update_query)) {
        $success = "Resource status updated successfully!";
    } else {
        $error = "Error updating resource status: " . mysqli_error($con);
    }
}

// Handle resource cost update
if(isset($_POST['update_cost'])) {
    $resource_id = mysqli_real_escape_string($con, $_POST['resource_id']);
    $new_cost = mysqli_real_escape_string($con, $_POST['cost']);
    
    $update_query = "UPDATE resources SET cost = '$new_cost' WHERE id = '$resource_id'";
    if(mysqli_query($con, $update_query)) {
        $success = "Resource cost updated successfully!";
    } else {
        $error = "Error updating resource cost: " . mysqli_error($con);
    }
}

// Handle resource quantity update
if(isset($_POST['update_quantity'])) {
    $resource_id = mysqli_real_escape_string($con, $_POST['resource_id']);
    $new_quantity = mysqli_real_escape_string($con, $_POST['quantity']);
    
    $update_query = "UPDATE resources SET quantity = '$new_quantity' WHERE id = '$resource_id'";
    if(mysqli_query($con, $update_query)) {
        $success = "Resource quantity updated successfully!";
    } else {
        $error = "Error updating resource quantity: " . mysqli_error($con);
    }
}

// Handle resource deletion
if(isset($_POST['delete_resource'])) {
    $resource_id = mysqli_real_escape_string($con, $_POST['resource_id']);
    
    // First check if resource exists
    $check_resource = mysqli_query($con, "SELECT * FROM resources WHERE id = '$resource_id'");
    if(mysqli_num_rows($check_resource) > 0) {
        $delete_query = "DELETE FROM resources WHERE id = '$resource_id'";
        if(mysqli_query($con, $delete_query)) {
            $success = "Resource deleted successfully!";
        } else {
            $error = "Error deleting resource: " . mysqli_error($con);
        }
    } else {
        $error = "Resource not found!";
    }
}

// Add status column if it doesn't exist in resources table
$check_status_column = mysqli_query($con, "SHOW COLUMNS FROM resources LIKE 'status'");
if(mysqli_num_rows($check_status_column) == 0) {
    $alter_query = "ALTER TABLE resources ADD COLUMN status VARCHAR(50) DEFAULT 'pending'";
    mysqli_query($con, $alter_query);
}

// Add requested_by column if it doesn't exist (to track which project manager requested)
$check_requested_column = mysqli_query($con, "SHOW COLUMNS FROM resources LIKE 'requested_by'");
if(mysqli_num_rows($check_requested_column) == 0) {
    $alter_query = "ALTER TABLE resources ADD COLUMN requested_by INT(11)";
    mysqli_query($con, $alter_query);
}

// Add request_date column if it doesn't exist
$check_date_column = mysqli_query($con, "SHOW COLUMNS FROM resources LIKE 'request_date'");
if(mysqli_num_rows($check_date_column) == 0) {
    $alter_query = "ALTER TABLE resources ADD COLUMN request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
    mysqli_query($con, $alter_query);
}

// Search and filter functionality
$search = '';
$type_filter = '';
$status_filter = '';
$where_condition = 'WHERE 1=1';

if(isset($_GET['search']) && !empty($_GET['search'])) {
    $search = mysqli_real_escape_string($con, $_GET['search']);
    $where_condition .= " AND (r.name LIKE '%$search%' OR r.type LIKE '%$search%' OR pm.name LIKE '%$search%' OR p.name LIKE '%$search%')";
}

if(isset($_GET['type']) && !empty($_GET['type']) && $_GET['type'] != 'all') {
    $type_filter = mysqli_real_escape_string($con, $_GET['type']);
    $where_condition .= " AND r.type = '$type_filter'";
}

if(isset($_GET['status']) && !empty($_GET['status']) && $_GET['status'] != 'all') {
    $status_filter = mysqli_real_escape_string($con, $_GET['status']);
    $where_condition .= " AND r.status = '$status_filter'";
}

// Fetch all resources with project and manager information
$resources_query = mysqli_query($con, 
    "SELECT r.*, 
            p.name as project_name,
            pm.name as manager_name,
            pm.email as manager_email,
            pm.phone as manager_phone
     FROM resources r 
     LEFT JOIN projects p ON r.project_id = p.id
     LEFT JOIN project_managers pm ON p.project_manager_id = pm.id
     $where_condition
     ORDER BY r.request_date DESC");

// Get unique resource types for filter
$types_query = mysqli_query($con, "SELECT DISTINCT type FROM resources WHERE type IS NOT NULL AND type != ''");

// Get resource statistics
$total_resources = mysqli_query($con, "SELECT COUNT(*) as total FROM resources");
$total_data = mysqli_fetch_assoc($total_resources);

$pending_resources = mysqli_query($con, "SELECT COUNT(*) as pending FROM resources WHERE status = 'pending'");
$pending_data = mysqli_fetch_assoc($pending_resources);

$approved_resources = mysqli_query($con, "SELECT COUNT(*) as approved FROM resources WHERE status = 'approved'");
$approved_data = mysqli_fetch_assoc($approved_resources);

$total_cost = mysqli_query($con, "SELECT SUM(CAST(REPLACE(cost, '$', '') AS DECIMAL(10,2))) as total_cost FROM resources WHERE status = 'approved'");
$cost_data = mysqli_fetch_assoc($total_cost);

// Fix for older PHP versions - replace null coalescing with ternary
$total_cost_amount = isset($cost_data['total_cost']) ? $cost_data['total_cost'] : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Resources - Admin Panel</title>
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
        .bg-approved { background-color: #28a745; color: #fff; }
        .bg-rejected { background-color: #dc3545; color: #fff; }
        .bg-delivered { background-color: #17a2b8; color: #fff; }
        .action-buttons .btn {
            margin: 2px;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(0,0,0,0.02);
        }
        .resource-info {
            font-size: 0.85rem;
        }
        .cost-amount {
            font-weight: 600;
            font-size: 1.1rem;
        }
        .stat-card {
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .type-badge {
            background-color: #6c757d;
            color: white;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 0.7rem;
        }
        .urgent-badge {
            background-color: #dc3545;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.7rem;
            margin-left: 5px;
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
                            <h1 class="h3 mb-0">Manage Resources</h1>
                            <p class="text-muted mb-0">View and manage all resources requested by project managers</p>
                        </div>
                        <div class="col-md-6 text-end">
                            <a href="resource_reports.php" class="btn btn-info">
                                <i class="fas fa-chart-bar me-2"></i>Resource Reports
                            </a>
                            
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

        <!-- Resource Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card card-dashboard text-white bg-primary text-center stat-card">
                <div class="card-body">
                    <h3><?php echo number_format($total_data['total']); ?></h3>
                    <p>Total Resources</p>
                    <small>All Resources</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-dashboard text-white bg-warning text-center stat-card">
                <div class="card-body">
                    <h3><?php echo number_format($pending_data['pending']); ?></h3>
                    <p>Pending</p>
                    <small>Awaiting Approval</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-dashboard text-white bg-success text-center stat-card">
                <div class="card-body">
                    <h3><?php echo number_format($approved_data['approved']); ?></h3>
                    <p>Approved</p>
                    <small>Resources Approved</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-dashboard text-white bg-info text-center stat-card">
                <div class="card-body">
                    <h3>₹<?php echo number_format($total_cost_amount, 2); ?></h3>
                    <p>Total Cost</p>
                    <small>Approved Resources</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Search and Filter -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card card-dashboard">
                <div class="card-body">
                    <form method="GET" action="admin_resources.php">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="input-group">
                                    <input type="text" class="form-control" name="search" placeholder="Search by resource name, type, project, or manager..." value="<?php echo htmlspecialchars($search); ?>">
                                    <button class="btn btn-primary" type="submit">
                                        <i class="fas fa-search me-2"></i>Search
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" name="type">
                                    <option value="all" <?php echo $type_filter == 'all' ? 'selected' : ''; ?>>All Types</option>
                                    <?php
                                    if(mysqli_num_rows($types_query) > 0) {
                                        mysqli_data_seek($types_query, 0);
                                        while($type = mysqli_fetch_assoc($types_query)) {
                                            echo '<option value="' . htmlspecialchars($type['type']) . '" ' . ($type_filter == $type['type'] ? 'selected' : '') . '>' . htmlspecialchars(ucfirst($type['type'])) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" name="status">
                                    <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Statuses</option>
                                    <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="approved" <?php echo $status_filter == 'approved' ? 'selected' : ''; ?>>Approved</option>
                                    <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                    <option value="delivered" <?php echo $status_filter == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <a href="admin_resources.php" class="btn btn-secondary w-100">
                                    <i class="fas fa-refresh me-2"></i>Reset
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

    <!-- Resources Table -->
    <div class="row">
        <div class="col-12">
            <div class="card card-dashboard">
                <div class="card-header">
                    <h5 class="card-title mb-0">All Resources (<?php echo mysqli_num_rows($resources_query); ?> found)</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Resource ID</th>
                                    <th>Resource Name</th>
                                    <th>Type</th>
                                    <th>Project</th>
                                    <th>Project Manager</th>
                                    <th>Quantity</th>
                                    <th>Cost</th>
                                    <th>Status</th>
                                    <th>Request Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if(mysqli_num_rows($resources_query) > 0) {
                                    while($resource = mysqli_fetch_assoc($resources_query)) {
                                        // Determine status badge class
                                        $status_class = '';
                                        switch($resource['status']) {
                                            case 'pending':
                                                $status_class = 'bg-pending';
                                                break;
                                            case 'approved':
                                                $status_class = 'bg-approved';
                                                break;
                                            case 'rejected':
                                                $status_class = 'bg-rejected';
                                                break;
                                            case 'delivered':
                                                $status_class = 'bg-delivered';
                                                break;
                                            default:
                                                $status_class = 'bg-secondary';
                                        }
                                        
                                        // Check if resource is urgent (requested within last 2 days and pending)
                                        $request_date = new DateTime($resource['request_date']);
                                        $today = new DateTime();
                                        $interval = $today->diff($request_date);
                                        $is_urgent = $interval->days <= 2 && $resource['status'] == 'pending';
                                        ?>
                                        <tr>
                                            <td>
                                                <strong>#<?php echo $resource['id']; ?></strong>
                                                <?php if($is_urgent): ?>
                                                    <span class="urgent-badge">URGENT</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($resource['name']); ?></strong>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="type-badge">
                                                    <?php echo !empty($resource['type']) ? htmlspecialchars(ucfirst($resource['type'])) : 'Not Specified'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if(!empty($resource['project_name'])): ?>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($resource['project_name']); ?></strong>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">No project</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if(!empty($resource['manager_name'])): ?>
                                                    <div class="resource-info">
                                                        <strong><?php echo htmlspecialchars($resource['manager_name']); ?></strong>
                                                        <br><small class="text-muted"><?php echo htmlspecialchars($resource['manager_email']); ?></small>
                                                        <?php if(!empty($resource['manager_phone'])): ?>
                                                            <br><small class="text-muted"><?php echo htmlspecialchars($resource['manager_phone']); ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">Not assigned</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong><?php echo number_format($resource['quantity']); ?></strong>
                                            </td>
                                            <td class="cost-amount text-success">
                                                ₹<?php echo number_format($resource['cost'], 2); ?>
                                            </td>
                                            <td>
                                                <span class="status-badge <?php echo $status_class; ?>">
                                                    <?php echo ucfirst($resource['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php echo date('M d, Y', strtotime($resource['request_date'])); ?>
                                                <br><small class="text-muted"><?php echo $interval->days; ?> days ago</small>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <!-- View Details -->
                                                    <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#viewModal<?php echo $resource['id']; ?>" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    
                                                    <!-- Update Status -->
                                                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#statusModal<?php echo $resource['id']; ?>" title="Update Status">
                                                        <i class="fas fa-sync-alt"></i>
                                                    </button>
                                                    
                                                    <!-- Update Cost -->
                                                    <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#costModal<?php echo $resource['id']; ?>" title="Update Cost">
                                                        <i class="fas fa-dollar-sign"></i>
                                                    </button>
                                                    
                                                    <!-- Update Quantity -->
                                                    <button type="button" class="btn btn-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#quantityModal<?php echo $resource['id']; ?>" title="Update Quantity">
                                                        <i class="fas fa-calculator"></i>
                                                    </button>
                                                    
                                                    <!-- Delete Resource -->
                                                    <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $resource['id']; ?>" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>

                                                <!-- View Details Modal -->
                                                <div class="modal fade" id="viewModal<?php echo $resource['id']; ?>" tabindex="-1">
                                                    <div class="modal-dialog modal-lg">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Resource Details #<?php echo $resource['id']; ?></h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="row">
                                                                    <div class="col-md-6">
                                                                        <h6>Resource Information</h6>
                                                                        <p><strong>Name:</strong> <?php echo htmlspecialchars($resource['name']); ?></p>
                                                                        <p><strong>Type:</strong> <?php echo !empty($resource['type']) ? htmlspecialchars(ucfirst($resource['type'])) : 'Not Specified'; ?></p>
                                                                        <p><strong>Quantity:</strong> <?php echo number_format($resource['quantity']); ?></p>
                                                                        <p><strong>Cost:</strong> ₹<?php echo number_format($resource['cost'], 2); ?></p>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <h6>Project Information</h6>
                                                                        <?php if(!empty($resource['project_name'])): ?>
                                                                            <p><strong>Project:</strong> <?php echo htmlspecialchars($resource['project_name']); ?></p>
                                                                        <?php else: ?>
                                                                            <p><strong>Project:</strong> <span class="text-muted">Not assigned</span></p>
                                                                        <?php endif; ?>
                                                                        <?php if(!empty($resource['manager_name'])): ?>
                                                                            <p><strong>Manager:</strong> <?php echo htmlspecialchars($resource['manager_name']); ?></p>
                                                                            <p><strong>Email:</strong> <?php echo htmlspecialchars($resource['manager_email']); ?></p>
                                                                            <?php if(!empty($resource['manager_phone'])): ?>
                                                                                <p><strong>Phone:</strong> <?php echo htmlspecialchars($resource['manager_phone']); ?></p>
                                                                            <?php endif; ?>
                                                                        <?php else: ?>
                                                                            <p><strong>Manager:</strong> <span class="text-muted">Not assigned</span></p>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                                <div class="row mt-3">
                                                                    <div class="col-12">
                                                                        <h6>Status Information</h6>
                                                                        <p><strong>Status:</strong> <span class="status-badge <?php echo $status_class; ?>"><?php echo ucfirst($resource['status']); ?></span></p>
                                                                        <p><strong>Request Date:</strong> <?php echo date('M d, Y H:i', strtotime($resource['request_date'])); ?></p>
                                                                        <?php if($is_urgent): ?>
                                                                            <div class="alert alert-warning">
                                                                                <i class="fas fa-exclamation-triangle me-2"></i>
                                                                                <strong>Urgent Request:</strong> This resource was requested recently and requires attention.
                                                                            </div>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Status Update Modal -->
                                                <div class="modal fade" id="statusModal<?php echo $resource['id']; ?>" tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <form method="POST">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Update Resource Status</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="resource_id" value="<?php echo $resource['id']; ?>">
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Resource: <strong><?php echo htmlspecialchars($resource['name']); ?></strong></label>
                                                                        <br><label class="form-label">Type: <strong><?php echo !empty($resource['type']) ? htmlspecialchars(ucfirst($resource['type'])) : 'Not Specified'; ?></strong></label>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Select Status:</label>
                                                                        <select class="form-select" name="status" required>
                                                                            <option value="pending" <?php echo $resource['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                                            <option value="approved" <?php echo $resource['status'] == 'approved' ? 'selected' : ''; ?>>Approved</option>
                                                                            <option value="rejected" <?php echo $resource['status'] == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                                                            <option value="delivered" <?php echo $resource['status'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
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

                                                <!-- Cost Update Modal -->
                                                <div class="modal fade" id="costModal<?php echo $resource['id']; ?>" tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <form method="POST">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Update Resource Cost</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="resource_id" value="<?php echo $resource['id']; ?>">
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Resource: <strong><?php echo htmlspecialchars($resource['name']); ?></strong></label>
                                                                        <br><label class="form-label">Current Cost: <strong>$<?php echo number_format($resource['cost'], 2); ?></strong></label>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label">New Cost:</label>
                                                                        <div class="input-group">
                                                                            <span class="input-group-text">$</span>
                                                                            <input type="number" class="form-control" name="cost" value="<?php echo $resource['cost']; ?>" step="0.01" min="0" required>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                    <button type="submit" name="update_cost" class="btn btn-primary">Update Cost</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Quantity Update Modal -->
                                                <div class="modal fade" id="quantityModal<?php echo $resource['id']; ?>" tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <form method="POST">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Update Resource Quantity</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="resource_id" value="<?php echo $resource['id']; ?>">
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Resource: <strong><?php echo htmlspecialchars($resource['name']); ?></strong></label>
                                                                        <br><label class="form-label">Current Quantity: <strong><?php echo number_format($resource['quantity']); ?></strong></label>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label">New Quantity:</label>
                                                                        <input type="number" class="form-control" name="quantity" value="<?php echo $resource['quantity']; ?>" min="1" required>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                    <button type="submit" name="update_quantity" class="btn btn-primary">Update Quantity</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Delete Confirmation Modal -->
                                                <div class="modal fade" id="deleteModal<?php echo $resource['id']; ?>" tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <form method="POST">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title text-danger">Confirm Deletion</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="resource_id" value="<?php echo $resource['id']; ?>">
                                                                    <p>Are you sure you want to delete this resource?</p>
                                                                    <p><strong>Resource ID:</strong> #<?php echo $resource['id']; ?></p>
                                                                    <p><strong>Name:</strong> <?php echo htmlspecialchars($resource['name']); ?></p>
                                                                    <p><strong>Type:</strong> <?php echo !empty($resource['type']) ? htmlspecialchars(ucfirst($resource['type'])) : 'Not Specified'; ?></p>
                                                                    <p class="text-danger"><small>This action cannot be undone. The resource record will be permanently deleted.</small></p>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                    <button type="submit" name="delete_resource" class="btn btn-danger">Delete Resource</button>
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
                                    if(!empty($search) || !empty($type_filter) || !empty($status_filter)) {
                                        echo '<i class="fas fa-search fa-3x mb-3"></i><br>No resources found matching your search criteria.';
                                    } else {
                                        echo '<i class="fas fa-boxes fa-3x mb-3"></i><br>No resources found in the system.';
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