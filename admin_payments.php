<?php
// admin_payments.php
// Admin page to view all payments with user and project details

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

// Handle payment status update
if(isset($_POST['update_status'])) {
    $payment_id = mysqli_real_escape_string($con, $_POST['payment_id']);
    $new_status = mysqli_real_escape_string($con, $_POST['status']);
    
    $update_query = "UPDATE payment SET status = '$new_status' WHERE id = '$payment_id'";
    if(mysqli_query($con, $update_query)) {
        $success = "Payment status updated successfully!";
    } else {
        $error = "Error updating payment status: " . mysqli_error($con);
    }
}

// Handle payment deletion
if(isset($_POST['delete_payment'])) {
    $payment_id = mysqli_real_escape_string($con, $_POST['payment_id']);
    
    // First check if payment exists
    $check_payment = mysqli_query($con, "SELECT * FROM payment WHERE id = '$payment_id'");
    if(mysqli_num_rows($check_payment) > 0) {
        $delete_query = "DELETE FROM payment WHERE id = '$payment_id'";
        if(mysqli_query($con, $delete_query)) {
            $success = "Payment record deleted successfully!";
        } else {
            $error = "Error deleting payment: " . mysqli_error($con);
        }
    } else {
        $error = "Payment record not found!";
    }
}

// Search and filter functionality
$search = '';
$status_filter = '';
$where_condition = 'WHERE 1=1';

if(isset($_GET['search']) && !empty($_GET['search'])) {
    $search = mysqli_real_escape_string($con, $_GET['search']);
    $where_condition .= " AND (u.name LIKE '%$search%' OR u.email LIKE '%$search%' OR p.card_name LIKE '%$search%' OR p.card_no LIKE '%$search%')";
}

if(isset($_GET['status']) && !empty($_GET['status']) && $_GET['status'] != 'all') {
    $status_filter = mysqli_real_escape_string($con, $_GET['status']);
    $where_condition .= " AND p.status = '$status_filter'";
}

// Fetch all payments with user, project, and manager information
$payments_query = mysqli_query($con, 
    "SELECT p.*, 
            u.name as client_name, 
            u.email as client_email, 
            u.phone as client_phone,
            pm.name as project_manager_name,
            pr.name as project_name,
            pr.id as project_id
     FROM payment p 
     LEFT JOIN users u ON p.client_id = u.id 
     LEFT JOIN projects pr ON p.project_id = pr.id
     LEFT JOIN project_managers pm ON pr.project_manager_id = pm.id
     $where_condition
     ORDER BY p.payment_date DESC");

// Get payment statistics
$total_payments = mysqli_query($con, "SELECT COUNT(*) as total, SUM(amount) as total_amount FROM payment");
$total_data = mysqli_fetch_assoc($total_payments);

$completed_payments = mysqli_query($con, "SELECT COUNT(*) as completed, SUM(amount) as completed_amount FROM payment WHERE status = 'completed'");
$completed_data = mysqli_fetch_assoc($completed_payments);

$pending_payments = mysqli_query($con, "SELECT COUNT(*) as pending, SUM(amount) as pending_amount FROM payment WHERE status = 'pending'");
$pending_data = mysqli_fetch_assoc($pending_payments);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Payments - Admin Panel</title>
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
        .bg-completed { background-color: #28a745; color: #fff; }
        .bg-pending { background-color: #ffc107; color: #000; }
        .bg-failed { background-color: #dc3545; color: #fff; }
        .bg-refunded { background-color: #6c757d; color: #fff; }
        .action-buttons .btn {
            margin: 2px;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(0,0,0,0.02);
        }
        .card-info {
            font-size: 0.85rem;
        }
        .amount {
            font-weight: 600;
            font-size: 1.1rem;
        }
        .stat-card {
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
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
                            <h1 class="h3 mb-0">Manage Payments</h1>
                            <p class="text-muted mb-0">View and manage all payment transactions</p>
                        </div>
                        <div class="col-md-6 text-end">
                            <a href="payment_reports.php" class="btn btn-info">
                                <i class="fas fa-chart-bar me-2"></i>Payment Reports
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card card-dashboard text-white bg-primary text-center stat-card">
                <div class="card-body">
                    <h3><?php echo number_format($total_data['total']); ?></h3>
                    <p>Total Payments</p>
                    <h5>₹<?php echo number_format($total_data['total_amount'], 2); ?></h5>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-dashboard text-white bg-success text-center stat-card">
                <div class="card-body">
                    <h3><?php echo number_format($completed_data['completed']); ?></h3>
                    <p>Completed</p>
                    <h5>₹<?php echo number_format($completed_data['completed_amount'], 2); ?></h5>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-dashboard text-white bg-warning text-center stat-card">
                <div class="card-body">
                    <h3><?php echo number_format($pending_data['pending']); ?></h3>
                    <p>Pending</p>
                    <h5>₹<?php echo number_format($pending_data['pending_amount'], 2); ?></h5>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-dashboard text-white bg-info text-center stat-card">
                <div class="card-body">
                    <?php
                    $today_payments = mysqli_query($con, "SELECT COUNT(*) as today, SUM(amount) as today_amount FROM payment WHERE DATE(payment_date) = CURDATE()");
                    $today_data = mysqli_fetch_assoc($today_payments);
                    ?>
                    <h3><?php echo number_format($today_data['today']); ?></h3>
                    <p>Today's Payments</p>
                    <h5>₹<?php echo number_format($today_data['today_amount'], 2); ?></h5>
                </div>
            </div>
        </div>
    </div>

    <!-- Search and Filter -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card card-dashboard">
                <div class="card-body">
                    <form method="GET" action="admin_payments.php">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <input type="text" class="form-control" name="search" placeholder="Search by client name, email, card name, or card number..." value="<?php echo htmlspecialchars($search); ?>">
                                    <button class="btn btn-primary" type="submit">
                                        <i class="fas fa-search me-2"></i>Search
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <select class="form-select" name="status">
                                    <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Statuses</option>
                                    <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="failed" <?php echo $status_filter == 'failed' ? 'selected' : ''; ?>>Failed</option>
                                    <option value="refunded" <?php echo $status_filter == 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <a href="admin_payments.php" class="btn btn-secondary w-100">
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

    <!-- Payments Table -->
    <div class="row">
        <div class="col-12">
            <div class="card card-dashboard">
                <div class="card-header">
                    <h5 class="card-title mb-0">All Payments (<?php echo mysqli_num_rows($payments_query); ?> found)</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Payment ID</th>
                                    <th>Client</th>
                                    <th>Project</th>
                                    <th>Amount</th>
                                    <th>Card Details</th>
                                    <th>Payment Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if(mysqli_num_rows($payments_query) > 0) {
                                    while($payment = mysqli_fetch_assoc($payments_query)) {
                                        // Determine status badge class
                                        $status_class = '';
                                        switch($payment['status']) {
                                            case 'completed':
                                                $status_class = 'bg-completed';
                                                break;
                                            case 'pending':
                                                $status_class = 'bg-pending';
                                                break;
                                            case 'failed':
                                                $status_class = 'bg-failed';
                                                break;
                                            case 'refunded':
                                                $status_class = 'bg-refunded';
                                                break;
                                            default:
                                                $status_class = 'bg-secondary';
                                        }
                                        
                                        // Mask card number for security
                                        $card_no = $payment['card_no'];
                                        $masked_card = '**** **** **** ' . substr($card_no, -4);
                                        ?>
                                        <tr>
                                            <td><strong>#<?php echo $payment['id']; ?></strong></td>
                                            <td>
                                                <div>
                                                    <strong><?php echo htmlspecialchars($payment['client_name']); ?></strong>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($payment['client_email']); ?></small>
                                                    <?php if(!empty($payment['client_phone'])): ?>
                                                        <br><small class="text-muted"><?php echo htmlspecialchars($payment['client_phone']); ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if(!empty($payment['project_name'])): ?>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($payment['project_name']); ?></strong>
                                                        <?php if(!empty($payment['project_manager_name'])): ?>
                                                            <br><small class="text-muted">Manager: <?php echo htmlspecialchars($payment['project_manager_name']); ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">No project</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="amount text-success">
                                                ₹<?php echo number_format($payment['amount'], 2); ?>
                                            </td>
                                            <td>
                                                <div class="card-info">
                                                    <strong><?php echo htmlspecialchars($payment['card_type']); ?></strong>
                                                    <br><small><?php echo htmlspecialchars($payment['card_name']); ?></small>
                                                    <br><small><?php echo $masked_card; ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <?php echo date('M d, Y', strtotime($payment['payment_date'])); ?>
                                                <br><small class="text-muted"><?php echo date('h:i A', strtotime($payment['payment_date'])); ?></small>
                                            </td>
                                            <td>
                                                <span class="status-badge <?php echo $status_class; ?>">
                                                    <?php echo ucfirst($payment['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <!-- View Details -->
                                                    <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#viewModal<?php echo $payment['id']; ?>" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    
                                                    <!-- Status Update Form -->
                                                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#statusModal<?php echo $payment['id']; ?>" title="Update Status">
                                                        <i class="fas fa-sync-alt"></i>
                                                    </button>
                                                    
                                                    <!-- Delete Payment -->
                                                    <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $payment['id']; ?>" title="Delete Payment">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>

                                                <!-- View Details Modal -->
                                                <div class="modal fade" id="viewModal<?php echo $payment['id']; ?>" tabindex="-1">
                                                    <div class="modal-dialog modal-lg">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Payment Details #<?php echo $payment['id']; ?></h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="row">
                                                                    <div class="col-md-6">
                                                                        <h6>Client Information</h6>
                                                                        <p><strong>Name:</strong> <?php echo htmlspecialchars($payment['client_name']); ?></p>
                                                                        <p><strong>Email:</strong> <?php echo htmlspecialchars($payment['client_email']); ?></p>
                                                                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($payment['client_phone']); ?></p>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <h6>Payment Information</h6>
                                                                        <p><strong>Amount:</strong> $<?php echo number_format($payment['amount'], 2); ?></p>
                                                                        <p><strong>Date:</strong> <?php echo date('M d, Y h:i A', strtotime($payment['payment_date'])); ?></p>
                                                                        <p><strong>Status:</strong> <span class="status-badge <?php echo $status_class; ?>"><?php echo ucfirst($payment['status']); ?></span></p>
                                                                    </div>
                                                                </div>
                                                                <div class="row mt-3">
                                                                    <div class="col-md-6">
                                                                        <h6>Card Details</h6>
                                                                        <p><strong>Type:</strong> <?php echo htmlspecialchars($payment['card_type']); ?></p>
                                                                        <p><strong>Name on Card:</strong> <?php echo htmlspecialchars($payment['card_name']); ?></p>
                                                                        <p><strong>Card Number:</strong> <?php echo $masked_card; ?></p>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <h6>Project Information</h6>
                                                                        <?php if(!empty($payment['project_name'])): ?>
                                                                            <p><strong>Project:</strong> <?php echo htmlspecialchars($payment['project_name']); ?></p>
                                                                            <p><strong>Manager:</strong> <?php echo htmlspecialchars($payment['project_manager_name']); ?></p>
                                                                        <?php else: ?>
                                                                            <p class="text-muted">No project associated</p>
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
                                                <div class="modal fade" id="statusModal<?php echo $payment['id']; ?>" tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <form method="POST">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Update Payment Status</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="payment_id" value="<?php echo $payment['id']; ?>">
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Payment ID: <strong>#<?php echo $payment['id']; ?></strong></label>
                                                                        <br><label class="form-label">Amount: <strong>₹<?php echo number_format($payment['amount'], 2); ?></strong></label>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Select Status:</label>
                                                                        <select class="form-select" name="status" required>
                                                                            <option value="completed" <?php echo $payment['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                                            <option value="pending" <?php echo $payment['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                                            <option value="failed" <?php echo $payment['status'] == 'failed' ? 'selected' : ''; ?>>Failed</option>
                                                                            <option value="refunded" <?php echo $payment['status'] == 'refunded' ? 'selected' : ''; ?>>Refunded</option>
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

                                                <!-- Delete Confirmation Modal -->
                                                <div class="modal fade" id="deleteModal<?php echo $payment['id']; ?>" tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <form method="POST">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title text-danger">Confirm Deletion</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="payment_id" value="<?php echo $payment['id']; ?>">
                                                                    <p>Are you sure you want to delete this payment record?</p>
                                                                    <p><strong>Payment ID:</strong> #<?php echo $payment['id']; ?></p>
                                                                    <p><strong>Amount:</strong> $<?php echo number_format($payment['amount'], 2); ?></p>
                                                                    <p><strong>Client:</strong> <?php echo htmlspecialchars($payment['client_name']); ?></p>
                                                                    <p class="text-danger"><small>This action cannot be undone. The payment record will be permanently deleted.</small></p>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                    <button type="submit" name="delete_payment" class="btn btn-danger">Delete Payment</button>
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
                                        echo '<i class="fas fa-search fa-3x mb-3"></i><br>No payments found matching your search criteria.';
                                    } else {
                                        echo '<i class="fas fa-credit-card fa-3x mb-3"></i><br>No payment records found in the system.';
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