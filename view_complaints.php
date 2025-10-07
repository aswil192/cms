<?php
// view_complaints.php
// Admin page to view and manage all complaints

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

// Handle complaint status update
if(isset($_POST['update_status'])) {
    $complaint_id = mysqli_real_escape_string($con, $_POST['complaint_id']);
    $new_status = mysqli_real_escape_string($con, $_POST['status']);
    
    $update_query = "UPDATE complaints SET status = '$new_status' WHERE id = '$complaint_id'";
    if(mysqli_query($con, $update_query)) {
        $success = "Complaint status updated successfully!";
    } else {
        $error = "Error updating complaint status: " . mysqli_error($con);
    }
}

// Handle response submission
if(isset($_POST['submit_response'])) {
    $complaint_id = mysqli_real_escape_string($con, $_POST['complaint_id']);
    $response_text = mysqli_real_escape_string($con, $_POST['response_text']);
    
    $update_query = "UPDATE complaints SET response = '$response_text', status = 'responded' WHERE id = '$complaint_id'";
    if(mysqli_query($con, $update_query)) {
        $success = "Response submitted successfully!";
    } else {
        $error = "Error submitting response: " . mysqli_error($con);
    }
}

// Handle complaint assignment to project manager
if(isset($_POST['assign_manager'])) {
    $complaint_id = mysqli_real_escape_string($con, $_POST['complaint_id']);
    $manager_id = mysqli_real_escape_string($con, $_POST['manager_id']);
    
    // Update complaint with manager ID (assuming we add this field to complaints table)
    $update_query = "UPDATE complaints SET assigned_manager_id = '$manager_id' WHERE id = '$complaint_id'";
    if(mysqli_query($con, $update_query)) {
        $success = "Complaint assigned to project manager successfully!";
    } else {
        $error = "Error assigning complaint: " . mysqli_error($con);
    }
}

// Handle complaint deletion
if(isset($_POST['delete_complaint'])) {
    $complaint_id = mysqli_real_escape_string($con, $_POST['complaint_id']);
    
    // First check if complaint exists
    $check_complaint = mysqli_query($con, "SELECT * FROM complaints WHERE id = '$complaint_id'");
    if(mysqli_num_rows($check_complaint) > 0) {
        $delete_query = "DELETE FROM complaints WHERE id = '$complaint_id'";
        if(mysqli_query($con, $delete_query)) {
            $success = "Complaint deleted successfully!";
        } else {
            $error = "Error deleting complaint: " . mysqli_error($con);
        }
    } else {
        $error = "Complaint not found!";
    }
}

// Search and filter functionality
$search = '';
$status_filter = '';
$where_condition = 'WHERE 1=1';

if(isset($_GET['search']) && !empty($_GET['search'])) {
    $search = mysqli_real_escape_string($con, $_GET['search']);
    $where_condition .= " AND (u.name LIKE '%$search%' OR u.email LIKE '%$search%' OR c.complaint_text LIKE '%$search%' OR c.status LIKE '%$search%')";
}

if(isset($_GET['status']) && !empty($_GET['status']) && $_GET['status'] != 'all') {
    $status_filter = mysqli_real_escape_string($con, $_GET['status']);
    $where_condition .= " AND c.status = '$status_filter'";
}

// Fetch all complaints with user and project information
$complaints_query = mysqli_query($con, 
    "SELECT c.*, 
            u.name as client_name, 
            u.email as client_email, 
            u.phone as client_phone,
            p.name as project_name,
            pm.name as project_manager_name,
            pm.id as project_manager_id
     FROM complaints c 
     LEFT JOIN users u ON c.client_id = u.id 
     LEFT JOIN projects p ON c.project_id = p.id
     LEFT JOIN project_managers pm ON p.project_manager_id = pm.id
     $where_condition
     ORDER BY c.complaint_date DESC");

// Fetch all project managers for assignment
$managers_query = mysqli_query($con, "SELECT * FROM project_managers ORDER BY name");

// Get complaint statistics
$total_complaints = mysqli_query($con, "SELECT COUNT(*) as total FROM complaints");
$total_data = mysqli_fetch_assoc($total_complaints);

$pending_complaints = mysqli_query($con, "SELECT COUNT(*) as pending FROM complaints WHERE status = 'pending'");
$pending_data = mysqli_fetch_assoc($pending_complaints);

$responded_complaints = mysqli_query($con, "SELECT COUNT(*) as responded FROM complaints WHERE status = 'responded'");
$responded_data = mysqli_fetch_assoc($responded_complaints);

$resolved_complaints = mysqli_query($con, "SELECT COUNT(*) as resolved FROM complaints WHERE status = 'resolved'");
$resolved_data = mysqli_fetch_assoc($resolved_complaints);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Complaints - Admin Panel</title>
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
        .bg-responded { background-color: #17a2b8; color: #fff; }
        .bg-resolved { background-color: #28a745; color: #fff; }
        .bg-rejected { background-color: #dc3545; color: #fff; }
        .action-buttons .btn {
            margin: 2px;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(0,0,0,0.02);
        }
        .complaint-text {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .response-text {
            background-color: #f8f9fa;
            border-left: 4px solid #007bff;
            padding: 10px;
            border-radius: 4px;
        }
        .stat-card {
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
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
                            <h1 class="h3 mb-0">Manage Complaints</h1>
                            <p class="text-muted mb-0">View and respond to client complaints</p>
                        </div>
                        <div class="col-md-6 text-end">
                            <a href="complaint_reports.php" class="btn btn-info">
                                <i class="fas fa-chart-bar me-2"></i>Complaint Reports
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Complaint Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card card-dashboard text-white bg-primary text-center stat-card">
                <div class="card-body">
                    <h3><?php echo number_format($total_data['total']); ?></h3>
                    <p>Total Complaints</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-dashboard text-white bg-warning text-center stat-card">
                <div class="card-body">
                    <h3><?php echo number_format($pending_data['pending']); ?></h3>
                    <p>Pending</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-dashboard text-white bg-info text-center stat-card">
                <div class="card-body">
                    <h3><?php echo number_format($responded_data['responded']); ?></h3>
                    <p>Responded</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-dashboard text-white bg-success text-center stat-card">
                <div class="card-body">
                    <h3><?php echo number_format($resolved_data['resolved']); ?></h3>
                    <p>Resolved</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Search and Filter -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card card-dashboard">
                <div class="card-body">
                    <form method="GET" action="view_complaints.php">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <input type="text" class="form-control" name="search" placeholder="Search by client name, email, complaint text, or status..." value="<?php echo htmlspecialchars($search); ?>">
                                    <button class="btn btn-primary" type="submit">
                                        <i class="fas fa-search me-2"></i>Search
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <select class="form-select" name="status">
                                    <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Statuses</option>
                                    <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="responded" <?php echo $status_filter == 'responded' ? 'selected' : ''; ?>>Responded</option>
                                    <option value="resolved" <?php echo $status_filter == 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                    <option value="rejected" <?php echo $status_filter == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <a href="view_complaints.php" class="btn btn-secondary w-100">
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

    <!-- Complaints Table -->
    <div class="row">
        <div class="col-12">
            <div class="card card-dashboard">
                <div class="card-header">
                    <h5 class="card-title mb-0">All Complaints (<?php echo mysqli_num_rows($complaints_query); ?> found)</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Complaint ID</th>
                                    <th>Client</th>
                                    <th>Project</th>
                                    <th>Complaint</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Assigned To</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if(mysqli_num_rows($complaints_query) > 0) {
                                    while($complaint = mysqli_fetch_assoc($complaints_query)) {
                                        // Determine status badge class
                                        $status_class = '';
                                        switch($complaint['status']) {
                                            case 'pending':
                                                $status_class = 'bg-pending';
                                                break;
                                            case 'responded':
                                                $status_class = 'bg-responded';
                                                break;
                                            case 'resolved':
                                                $status_class = 'bg-resolved';
                                                break;
                                            case 'rejected':
                                                $status_class = 'bg-rejected';
                                                break;
                                            default:
                                                $status_class = 'bg-secondary';
                                        }
                                        
                                        // Check if complaint is urgent (within last 3 days)
                                        $complaint_date = new DateTime($complaint['complaint_date']);
                                        $today = new DateTime();
                                        $interval = $today->diff($complaint_date);
                                        $is_urgent = $interval->days <= 3 && $complaint['status'] == 'pending';
                                        ?>
                                        <tr>
                                            <td>
                                                <strong>#<?php echo $complaint['id']; ?></strong>
                                                <?php if($is_urgent): ?>
                                                    <span class="urgent-badge">URGENT</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($complaint['client_name']); ?></strong>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($complaint['client_email']); ?></small>
                                                    <?php if(!empty($complaint['client_phone'])): ?>
                                                        <br><small class="text-muted"><?php echo htmlspecialchars($complaint['client_phone']); ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if(!empty($complaint['project_name'])): ?>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($complaint['project_name']); ?></strong>
                                                        <?php if(!empty($complaint['project_manager_name'])): ?>
                                                            <br><small class="text-muted">Manager: <?php echo htmlspecialchars($complaint['project_manager_name']); ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">No project</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="complaint-text" title="<?php echo htmlspecialchars($complaint['complaint_text']); ?>">
                                                    <?php echo htmlspecialchars(substr($complaint['complaint_text'], 0, 100)); ?>
                                                    <?php if(strlen($complaint['complaint_text']) > 100): ?>...<?php endif; ?>
                                                </div>
                                                <?php if(!empty($complaint['response'])): ?>
                                                    <div class="response-text mt-2">
                                                        <strong>Response:</strong><br>
                                                        <?php echo htmlspecialchars(substr($complaint['response'], 0, 80)); ?>
                                                        <?php if(strlen($complaint['response']) > 80): ?>...<?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php echo date('M d, Y', strtotime($complaint['complaint_date'])); ?>
                                                <br><small class="text-muted"><?php echo $interval->days; ?> days ago</small>
                                            </td>
                                            <td>
                                                <span class="status-badge <?php echo $status_class; ?>">
                                                    <?php echo ucfirst($complaint['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if(!empty($complaint['project_manager_name'])): ?>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($complaint['project_manager_name']); ?></strong>
                                                        <br><button type="button" class="btn btn-outline-primary btn-sm mt-1" data-bs-toggle="modal" data-bs-target="#assignManagerModal<?php echo $complaint['id']; ?>">
                                                            <i class="fas fa-sync-alt me-1"></i>Reassign
                                                        </button>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">Not assigned</span>
                                                    <br><button type="button" class="btn btn-primary btn-sm mt-1" data-bs-toggle="modal" data-bs-target="#assignManagerModal<?php echo $complaint['id']; ?>">
                                                        <i class="fas fa-user-plus me-1"></i>Assign
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <!-- View Details -->
                                                    <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#viewModal<?php echo $complaint['id']; ?>" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    
                                                    <!-- Respond to Complaint -->
                                                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#responseModal<?php echo $complaint['id']; ?>" title="Respond">
                                                        <i class="fas fa-reply"></i>
                                                    </button>
                                                    
                                                    <!-- Status Update -->
                                                    <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#statusModal<?php echo $complaint['id']; ?>" title="Update Status">
                                                        <i class="fas fa-sync-alt"></i>
                                                    </button>
                                                    
                                                    <!-- Delete Complaint -->
                                                    <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $complaint['id']; ?>" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>

                                                <!-- View Details Modal -->
                                                <div class="modal fade" id="viewModal<?php echo $complaint['id']; ?>" tabindex="-1">
                                                    <div class="modal-dialog modal-lg">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Complaint Details #<?php echo $complaint['id']; ?></h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="row">
                                                                    <div class="col-md-6">
                                                                        <h6>Client Information</h6>
                                                                        <p><strong>Name:</strong> <?php echo htmlspecialchars($complaint['client_name']); ?></p>
                                                                        <p><strong>Email:</strong> <?php echo htmlspecialchars($complaint['client_email']); ?></p>
                                                                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($complaint['client_phone']); ?></p>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <h6>Complaint Information</h6>
                                                                        <p><strong>Date:</strong> <?php echo date('M d, Y', strtotime($complaint['complaint_date'])); ?></p>
                                                                        <p><strong>Status:</strong> <span class="status-badge <?php echo $status_class; ?>"><?php echo ucfirst($complaint['status']); ?></span></p>
                                                                        <?php if(!empty($complaint['project_name'])): ?>
                                                                            <p><strong>Project:</strong> <?php echo htmlspecialchars($complaint['project_name']); ?></p>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                                <div class="row mt-3">
                                                                    <div class="col-12">
                                                                        <h6>Complaint Details</h6>
                                                                        <div class="border p-3 bg-light rounded">
                                                                            <?php echo nl2br(htmlspecialchars($complaint['complaint_text'])); ?>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <?php if(!empty($complaint['response'])): ?>
                                                                <div class="row mt-3">
                                                                    <div class="col-12">
                                                                        <h6>Response</h6>
                                                                        <div class="border p-3 bg-light rounded">
                                                                            <?php echo nl2br(htmlspecialchars($complaint['response'])); ?>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Response Modal -->
                                                <div class="modal fade" id="responseModal<?php echo $complaint['id']; ?>" tabindex="-1">
                                                    <div class="modal-dialog modal-lg">
                                                        <div class="modal-content">
                                                            <form method="POST">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Respond to Complaint #<?php echo $complaint['id']; ?></h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="complaint_id" value="<?php echo $complaint['id']; ?>">
                                                                    
                                                                    <div class="mb-3">
                                                                        <label class="form-label"><strong>Client:</strong> <?php echo htmlspecialchars($complaint['client_name']); ?></label>
                                                                    </div>
                                                                    
                                                                    <div class="mb-3">
                                                                        <label class="form-label"><strong>Complaint:</strong></label>
                                                                        <div class="border p-3 bg-light rounded">
                                                                            <?php echo nl2br(htmlspecialchars($complaint['complaint_text'])); ?>
                                                                        </div>
                                                                    </div>
                                                                    
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Your Response:</label>
                                                                        <textarea class="form-control" name="response_text" rows="6" placeholder="Type your response here..." required><?php echo !empty($complaint['response']) ? htmlspecialchars($complaint['response']) : ''; ?></textarea>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                    <button type="submit" name="submit_response" class="btn btn-primary">Submit Response</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Status Update Modal -->
                                                <div class="modal fade" id="statusModal<?php echo $complaint['id']; ?>" tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <form method="POST">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Update Complaint Status</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="complaint_id" value="<?php echo $complaint['id']; ?>">
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Complaint ID: <strong>#<?php echo $complaint['id']; ?></strong></label>
                                                                        <br><label class="form-label">Client: <strong><?php echo htmlspecialchars($complaint['client_name']); ?></strong></label>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Select Status:</label>
                                                                        <select class="form-select" name="status" required>
                                                                            <option value="pending" <?php echo $complaint['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                                            <option value="responded" <?php echo $complaint['status'] == 'responded' ? 'selected' : ''; ?>>Responded</option>
                                                                            <option value="resolved" <?php echo $complaint['status'] == 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                                                            <option value="rejected" <?php echo $complaint['status'] == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
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
                                                <div class="modal fade" id="assignManagerModal<?php echo $complaint['id']; ?>" tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <form method="POST">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Assign Project Manager</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="complaint_id" value="<?php echo $complaint['id']; ?>">
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Complaint: <strong>#<?php echo $complaint['id']; ?></strong></label>
                                                                        <br><label class="form-label">Client: <strong><?php echo htmlspecialchars($complaint['client_name']); ?></strong></label>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Select Project Manager:</label>
                                                                        <select class="form-select" name="manager_id" required>
                                                                            <option value="">-- Select Manager --</option>
                                                                            <?php 
                                                                            mysqli_data_seek($managers_query, 0);
                                                                            while($manager = mysqli_fetch_assoc($managers_query)): 
                                                                            ?>
                                                                                <option value="<?php echo $manager['id']; ?>" <?php echo $complaint['project_manager_id'] == $manager['id'] ? 'selected' : ''; ?>>
                                                                                    <?php echo htmlspecialchars($manager['name']); ?> (<?php echo htmlspecialchars($manager['email']); ?>)
                                                                                </option>
                                                                            <?php endwhile; ?>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                    <button type="submit" name="assign_manager" class="btn btn-primary">Assign Manager</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Delete Confirmation Modal -->
                                                <div class="modal fade" id="deleteModal<?php echo $complaint['id']; ?>" tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <form method="POST">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title text-danger">Confirm Deletion</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="complaint_id" value="<?php echo $complaint['id']; ?>">
                                                                    <p>Are you sure you want to delete this complaint?</p>
                                                                    <p><strong>Complaint ID:</strong> #<?php echo $complaint['id']; ?></p>
                                                                    <p><strong>Client:</strong> <?php echo htmlspecialchars($complaint['client_name']); ?></p>
                                                                    <p class="text-danger"><small>This action cannot be undone. The complaint record will be permanently deleted.</small></p>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                    <button type="submit" name="delete_complaint" class="btn btn-danger">Delete Complaint</button>
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
                                    echo '<tr><td colspan="8" class="text-center py-4 text-muted">';
                                    if(!empty($search) || !empty($status_filter)) {
                                        echo '<i class="fas fa-search fa-3x mb-3"></i><br>No complaints found matching your search criteria.';
                                    } else {
                                        echo '<i class="fas fa-comments fa-3x mb-3"></i><br>No complaints found in the system.';
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