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

// First, check what columns exist in the users table
$check_columns = mysqli_query($con, "SHOW COLUMNS FROM users");
$columns = array();
if($check_columns) {
    while($column = mysqli_fetch_assoc($check_columns)) {
        $columns[] = $column['Field'];
    }
}

// Check if status column exists
$has_status_column = in_array('status', $columns);
$has_created_at_column = in_array('created_at', $columns);
$has_address_column = in_array('address', $columns);

// Determine which column to use for sorting
$order_column = $has_created_at_column ? 'created_at' : 'id';

// Handle client status update
if(isset($_POST['update_status']) && $has_status_column) {
    $client_id = mysqli_real_escape_string($con, $_POST['client_id']);
    $new_status = mysqli_real_escape_string($con, $_POST['status']);
    
    $update_query = "UPDATE users SET status = '$new_status' WHERE id = '$client_id'";
    if(mysqli_query($con, $update_query)) {
        $success = "Client status updated successfully!";
    } else {
        $error = "Error updating client status: " . mysqli_error($con);
    }
}

// Handle client deletion
if(isset($_POST['delete_client'])) {
    $client_id = mysqli_real_escape_string($con, $_POST['client_id']);
    
    // First check if client exists and has no projects
    $check_client = mysqli_query($con, "SELECT * FROM users WHERE id = '$client_id'");
    $check_projects = mysqli_query($con, "SELECT COUNT(*) as project_count FROM projects WHERE client_id = '$client_id'");
    
    if($check_client && mysqli_num_rows($check_client) > 0) {
        $projects_data = mysqli_fetch_assoc($check_projects);
        
        if($projects_data['project_count'] > 0) {
            $error = "Cannot delete client! Client has " . $projects_data['project_count'] . " active project(s).";
        } else {
            $delete_query = "DELETE FROM users WHERE id = '$client_id'";
            if(mysqli_query($con, $delete_query)) {
                $success = "Client deleted successfully!";
            } else {
                $error = "Error deleting client: " . mysqli_error($con);
            }
        }
    } else {
        $error = "Client not found!";
    }
}

// Handle add new client
if(isset($_POST['add_client'])) {
    $name = mysqli_real_escape_string($con, $_POST['name']);
    $email = mysqli_real_escape_string($con, $_POST['email']);
    $phone = mysqli_real_escape_string($con, $_POST['phone']);
    $password = mysqli_real_escape_string($con, $_POST['password']);
    
    // Check if email already exists
    $check_email = mysqli_query($con, "SELECT * FROM users WHERE email = '$email'");
    if($check_email && mysqli_num_rows($check_email) > 0) {
        $error = "Email address already exists!";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Build insert query based on available columns
        $insert_fields = "name, email, phone, password";
        $insert_values = "'$name', '$email', '$phone', '$hashed_password'";
        
        if($has_status_column) {
            $insert_fields .= ", status";
            $insert_values .= ", 'Active'";
        }
        
        if($has_created_at_column) {
            $insert_fields .= ", created_at";
            $insert_values .= ", NOW()";
        }
        
        $insert_query = "INSERT INTO users ($insert_fields) VALUES ($insert_values)";
        
        if(mysqli_query($con, $insert_query)) {
            $success = "Client added successfully!";
        } else {
            $error = "Error adding client: " . mysqli_error($con);
        }
    }
}

// Search functionality
$search = '';
$where_condition = "WHERE 1=1";

if(isset($_GET['search']) && !empty($_GET['search'])) {
    $search = mysqli_real_escape_string($con, $_GET['search']);
    $search_conditions = array("name LIKE '%$search%'", "email LIKE '%$search%'", "phone LIKE '%$search%'");
    
    if($has_status_column) {
        $search_conditions[] = "status LIKE '%$search%'";
    }
    
    if($has_address_column) {
        $search_conditions[] = "address LIKE '%$search%'";
    }
    
    $where_condition .= " AND (" . implode(" OR ", $search_conditions) . ")";
}

// Fetch all clients
$clients_query = mysqli_query($con, 
    "SELECT users.*, 
            (SELECT COUNT(*) FROM projects WHERE projects.client_id = users.id) as project_count,
            (SELECT COUNT(*) FROM projects WHERE projects.client_id = users.id AND projects.status = 'In Progress') as active_projects
     FROM users 
     $where_condition
     ORDER BY $order_column DESC");

// Check if query was successful
if(!$clients_query) {
    $error = "Database error: " . mysqli_error($con);
}

// Get statistics
$total_clients = 0;
$active_clients = 0;
$inactive_clients = 0;
$clients_with_projects = 0;

$stats_query = mysqli_query($con, "SELECT COUNT(*) as total FROM users");
if($stats_query) {
    $total_data = mysqli_fetch_assoc($stats_query);
    $total_clients = $total_data['total'];
}

if($has_status_column) {
    $active_query = mysqli_query($con, "SELECT COUNT(*) as active FROM users WHERE status = 'Active'");
    if($active_query) {
        $active_data = mysqli_fetch_assoc($active_query);
        $active_clients = $active_data['active'];
    }

    $inactive_query = mysqli_query($con, "SELECT COUNT(*) as inactive FROM users WHERE status = 'Inactive'");
    if($inactive_query) {
        $inactive_data = mysqli_fetch_assoc($inactive_query);
        $inactive_clients = $inactive_data['inactive'];
    }
} else {
    // If no status column, all clients are considered active
    $active_clients = $total_clients;
    $inactive_clients = 0;
}

$projects_query = mysqli_query($con, "SELECT COUNT(DISTINCT client_id) as with_projects FROM projects");
if($projects_query) {
    $projects_data = mysqli_fetch_assoc($projects_query);
    $clients_with_projects = $projects_data['with_projects'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Clients - Admin Panel</title>
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
        .bg-active { background-color: #28a745; color: #fff; }
        .bg-inactive { background-color: #6c757d; color: #fff; }
        .bg-suspended { background-color: #dc3545; color: #fff; }
        .action-buttons .btn {
            margin: 2px;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(0,0,0,0.02);
        }
        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2rem;
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
                            <h1 class="h3 mb-0">Manage Clients</h1>
                            <p class="text-muted mb-0">View and manage all clients in the system</p>
                        </div>
                        <div class="col-md-6 text-end">
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addClientModal">
                                <i class="fas fa-plus me-2"></i>Add New Client
                            </button>
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
                    <form method="GET" action="all_clients.php">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="input-group">
                                    <input type="text" class="form-control" name="search" placeholder="Search clients by name, email, phone<?php echo $has_status_column ? ', or status' : ''; ?>..." value="<?php echo htmlspecialchars($search); ?>">
                                    <button class="btn btn-primary" type="submit">
                                        <i class="fas fa-search me-2"></i>Search
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <a href="all_clients.php" class="btn btn-secondary w-100">
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

    <!-- Clients Table -->
    <div class="row">
        <div class="col-12">
            <div class="card card-dashboard">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        All Clients 
                        <?php if(!$clients_query): ?>
                            (Error loading clients)
                        <?php else: ?>
                            (<?php echo mysqli_num_rows($clients_query); ?> found)
                        <?php endif; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if(!$clients_query): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Error loading clients from database. Please check your database connection and table structure.
                            <?php if(isset($error)) echo "<br><small>Error: $error</small>"; ?>
                        </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Client ID</th>
                                    <th>Client</th>
                                    <th>Contact Info</th>
                                    <th>Projects</th>
                                    <?php if($has_status_column): ?>
                                        <th>Status</th>
                                    <?php endif; ?>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if(mysqli_num_rows($clients_query) > 0) {
                                    while($client = mysqli_fetch_assoc($clients_query)) {
                                        // Get first letter for avatar
                                        $avatar_letter = strtoupper(substr($client['name'], 0, 1));
                                        ?>
                                        <tr>
                                            <td><strong>#<?php echo $client['id']; ?></strong></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar me-3">
                                                        <?php echo $avatar_letter; ?>
                                                    </div>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($client['name']); ?></strong>
                                                        <br><small class="text-muted">Client ID: C<?php echo str_pad($client['id'], 4, '0', STR_PAD_LEFT); ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <i class="fas fa-envelope text-muted me-2"></i><?php echo htmlspecialchars($client['email']); ?>
                                                    <br>
                                                    <i class="fas fa-phone text-muted me-2"></i>
                                                    <?php echo !empty($client['phone']) ? htmlspecialchars($client['phone']) : '<span class="text-muted">Not provided</span>'; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="text-center">
                                                    <strong><?php echo $client['project_count']; ?></strong>
                                                    <br>
                                                    <small class="text-muted">
                                                        <?php echo $client['active_projects']; ?> active
                                                    </small>
                                                </div>
                                            </td>
                                            <?php if($has_status_column): ?>
                                                <td>
                                                    <?php
                                                    // Determine status badge class only if status column exists
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
                                                </td>
                                            <?php endif; ?>
                                            <td>
                                                <div class="action-buttons">
                                                    <!-- View Client Details -->
                                                    <a href="client_details.php?id=<?php echo $client['id']; ?>" class="btn btn-info btn-sm" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    
                                                    <?php if($has_status_column): ?>
                                                    <!-- Status Update -->
                                                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#statusModal<?php echo $client['id']; ?>" title="Update Status">
                                                        <i class="fas fa-sync-alt"></i>
                                                    </button>
                                                    <?php endif; ?>
                                                    
                                                    <!-- Delete Client -->
                                                    <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $client['id']; ?>" title="Delete Client">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>

                                                <?php if($has_status_column): ?>
                                                <!-- Status Update Modal -->
                                                <div class="modal fade" id="statusModal<?php echo $client['id']; ?>" tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <form method="POST">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Update Client Status</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="client_id" value="<?php echo $client['id']; ?>">
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

                                                <!-- Delete Confirmation Modal -->
                                                <div class="modal fade" id="deleteModal<?php echo $client['id']; ?>" tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <form method="POST">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title text-danger">Confirm Deletion</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="client_id" value="<?php echo $client['id']; ?>">
                                                                    <p>Are you sure you want to delete the client:</p>
                                                                    <p><strong>"<?php echo htmlspecialchars($client['name']); ?>"</strong>?</p>
                                                                    
                                                                    <?php if($client['project_count'] > 0): ?>
                                                                        <div class="alert alert-warning">
                                                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                                                            This client has <?php echo $client['project_count']; ?> project(s). 
                                                                            You cannot delete clients with active projects.
                                                                        </div>
                                                                    <?php else: ?>
                                                                        <p class="text-danger"><small>This action cannot be undone. All client data will be permanently deleted.</small></p>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                    <?php if($client['project_count'] == 0): ?>
                                                                        <button type="submit" name="delete_client" class="btn btn-danger">Delete Client</button>
                                                                    <?php endif; ?>
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
                                    $colspan = $has_status_column ? 5 : 4;
                                    echo '<tr><td colspan="' . $colspan . '" class="text-center py-4 text-muted">';
                                    if(!empty($search)) {
                                        echo '<i class="fas fa-search fa-3x mb-3"></i><br>No clients found matching your search criteria.';
                                    } else {
                                        echo '<i class="fas fa-users fa-3x mb-3"></i><br>No clients found in the system.';
                                    }
                                    echo '</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Summary -->
    <div class="row mt-4">
        <div class="col-md-3">
            <div class="card card-dashboard text-white bg-primary text-center">
                <div class="card-body">
                    <h3><?php echo $total_clients; ?></h3>
                    <p>Total Clients</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-dashboard text-white bg-success text-center">
                <div class="card-body">
                    <h3><?php echo $active_clients; ?></h3>
                    <p>Active Clients</p>
                </div>
            </div>
        </div>
        <?php if($has_status_column): ?>
        <div class="col-md-3">
            <div class="card card-dashboard text-white bg-warning text-center">
                <div class="card-body">
                    <h3><?php echo $inactive_clients; ?></h3>
                    <p>Inactive Clients</p>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="col-md-3">
            <div class="card card-dashboard text-white bg-info text-center">
                <div class="card-body">
                    <h3><?php echo $total_clients; ?></h3>
                    <p>All Clients Active</p>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <div class="col-md-3">
            <div class="card card-dashboard text-white bg-info text-center">
                <div class="card-body">
                    <h3><?php echo $clients_with_projects; ?></h3>
                    <p>Clients with Projects</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Client Modal -->
<div class="modal fade" id="addClientModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Client</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Full Name *</label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Email Address *</label>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Phone Number</label>
                                <input type="text" class="form-control" name="phone">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Password *</label>
                                <input type="password" class="form-control" name="password" required minlength="6">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_client" class="btn btn-primary">Add Client</button>
                </div>
            </form>
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