<?php
session_start();

// Check if user is logged in as project manager using the correct session variable
if(!isset($_SESSION['user']) || $_SESSION['user'] != 'project_manager') {
    header("Location: login.php");
    exit();
}

include("connection.php");
$pm_id = $_SESSION['uid'];
?>

<?php include("menu.php"); ?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card card-dashboard">
                <div class="card-body">
                    <h1 class="h3 mb-0">Payments Received</h1>
                    <p class="text-muted mb-0">Payments for projects managed by you</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Payments Table -->
    <div class="row">
        <div class="col-12">
            <div class="card card-dashboard">
                <div class="card-header">
                    <h5 class="card-title mb-0">Payment History</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Payment ID</th>
                                    <th>Project</th>
                                    <th>Client</th>
                                    <th>Amount</th>
                                    <th>Payment Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // First, let's check if the payment table exists and has data
                                $check_table = mysqli_query($con, "SHOW TABLES LIKE 'payment'");
                                if(mysqli_num_rows($check_table) == 0) {
                                    echo '<tr>
                                            <td colspan="6" class="text-center py-4 text-muted">
                                                <i class="fas fa-exclamation-triangle fa-3x mb-3"></i><br>
                                                Payment table does not exist in the database.
                                            </td>
                                          </tr>';
                                } else {
                                    // Simple query to check payments without complex joins first
                                    $simple_query = "SELECT * FROM payment LIMIT 5";
                                    $simple_result = mysqli_query($con, $simple_query);
                                    
                                    if(!$simple_result) {
                                        echo '<tr>
                                                <td colspan="6" class="text-center py-4 text-muted">
                                                    <i class="fas fa-exclamation-triangle fa-3x mb-3"></i><br>
                                                    Error accessing payment table: ' . mysqli_error($con) . '
                                                </td>
                                              </tr>';
                                    } else if(mysqli_num_rows($simple_result) == 0) {
                                        echo '<tr>
                                                <td colspan="6" class="text-center py-4 text-muted">
                                                    <i class="fas fa-receipt fa-3x mb-3"></i><br>
                                                    No payments found in the system.
                                                </td>
                                              </tr>';
                                    } else {
                                        // Try the main query with simplified joins
                                        $payments_query = "
                                            SELECT 
                                                p.*, 
                                                pr.name as project_name,
                                                u.name as client_name
                                            FROM payment p 
                                            LEFT JOIN projects pr ON p.project_id = pr.id 
                                            LEFT JOIN users u ON p.client_id = u.id 
                                            WHERE p.project_id IN (
                                                SELECT project_id FROM project_manager_assignments 
                                                WHERE project_manager_id = '$pm_id'
                                            )
                                            ORDER BY p.payment_date DESC
                                        ";
                                        
                                        $result = mysqli_query($con, $payments_query);
                                        
                                        if($result && mysqli_num_rows($result) > 0) {
                                            while ($payment = mysqli_fetch_assoc($result)) {
                                                $payment_id = htmlspecialchars($payment['id']);
                                                $project_name = isset($payment['project_name']) ? htmlspecialchars($payment['project_name']) : 'N/A';
                                                $client_name = isset($payment['client_name']) ? htmlspecialchars($payment['client_name']) : 'N/A';
                                                $amount = number_format($payment['amount'], 2);
                                                $payment_date = htmlspecialchars(date('M d, Y H:i', strtotime($payment['payment_date'])));
                                                $status = htmlspecialchars($payment['status']);
                                                
                                                $status_class = $payment['status'] == 'completed' ? 'bg-success' : 'bg-warning';
                                                
                                                echo '<tr>
                                                        <td><strong>#' . $payment_id . '</strong></td>
                                                        <td>' . $project_name . '</td>
                                                        <td>' . $client_name . '</td>
                                                        <td><strong>₹' . $amount . '</strong></td>
                                                        <td>' . $payment_date . '</td>
                                                        <td><span class="badge ' . $status_class . '">' . $status . '</span></td>
                                                      </tr>';
                                            }
                                        } else {
                                            echo '<tr>
                                                    <td colspan="6" class="text-center py-4 text-muted">
                                                        <i class="fas fa-receipt fa-3x mb-3"></i><br>
                                                        No payments found for your projects.
                                                    </td>
                                                  </tr>';
                                        }
                                    }
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Statistics -->
    <div class="row mt-4">
        <div class="col-md-3">
            <div class="card card-dashboard">
                <div class="card-body text-center">
                    <?php
                    $total_payments_query = "SELECT COUNT(*) as total_payments FROM payment 
                                           WHERE project_id IN (
                                               SELECT project_id FROM project_manager_assignments 
                                               WHERE project_manager_id = '$pm_id'
                                           )";
                    $total_result = mysqli_query($con, $total_payments_query);
                    if($total_result) {
                        $total_payments = mysqli_fetch_assoc($total_result);
                        echo '<h3 class="text-primary">' . $total_payments['total_payments'] . '</h3>';
                    } else {
                        echo '<h3 class="text-primary">0</h3>';
                    }
                    ?>
                    <p class="text-muted mb-0">Total Payments</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-dashboard">
                <div class="card-body text-center">
                    <?php
                    $completed_payments_query = "SELECT COUNT(*) as completed_payments FROM payment 
                                               WHERE status = 'completed' 
                                               AND project_id IN (
                                                   SELECT project_id FROM project_manager_assignments 
                                                   WHERE project_manager_id = '$pm_id'
                                               )";
                    $completed_result = mysqli_query($con, $completed_payments_query);
                    if($completed_result) {
                        $completed_payments = mysqli_fetch_assoc($completed_result);
                        echo '<h3 class="text-success">' . $completed_payments['completed_payments'] . '</h3>';
                    } else {
                        echo '<h3 class="text-success">0</h3>';
                    }
                    ?>
                    <p class="text-muted mb-0">Completed</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-dashboard">
                <div class="card-body text-center">
                    <?php
                    $total_amount_query = "SELECT COALESCE(SUM(amount), 0) as total_amount FROM payment 
                                         WHERE status = 'completed'
                                         AND project_id IN (
                                             SELECT project_id FROM project_manager_assignments 
                                             WHERE project_manager_id = '$pm_id'
                                         )";
                    $amount_result = mysqli_query($con, $total_amount_query);
                    if($amount_result) {
                        $total_amount = mysqli_fetch_assoc($amount_result);
                        echo '<h3 class="text-warning">₹' . number_format($total_amount['total_amount'], 2) . '</h3>';
                    } else {
                        echo '<h3 class="text-warning">₹0.00</h3>';
                    }
                    ?>
                    <p class="text-muted mb-0">Total Received</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-dashboard">
                <div class="card-body text-center">
                    <?php
                    $pending_payments_query = "SELECT COUNT(*) as pending_payments FROM payment 
                                             WHERE status != 'completed'
                                             AND project_id IN (
                                                 SELECT project_id FROM project_manager_assignments 
                                                 WHERE project_manager_id = '$pm_id'
                                             )";
                    $pending_result = mysqli_query($con, $pending_payments_query);
                    if($pending_result) {
                        $pending_payments = mysqli_fetch_assoc($pending_result);
                        echo '<h3 class="text-danger">' . $pending_payments['pending_payments'] . '</h3>';
                    } else {
                        echo '<h3 class="text-danger">0</h3>';
                    }
                    ?>
                    <p class="text-muted mb-0">Pending</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include("footer.php"); ?>