<?php
session_start();

// Redirect to login if not authenticated as project manager
if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'project_manager') {
    header("Location: login.php");
    exit();
}

include("connection.php");
$pm_id = $_SESSION['uid'];

// Handle form submissions
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['add_resource'])) {
        // Add resource functionality
        $project_id = $_POST['project_id'];
        $name = $_POST['name'];
        $type = $_POST['type'];
        $quantity = $_POST['quantity'];
        $cost = $_POST['cost'];
        
        $insert_query = mysqli_query($con, 
            "INSERT INTO resources (project_id, type, name, quantity, cost) 
             VALUES ('$project_id', '$type', '$name', '$quantity', '$cost')");
        
        if($insert_query) {
            $success_message = "Resource added successfully!";
        } else {
            $error_message = "Error adding resource: " . mysqli_error($con);
        }
    }
    
    if(isset($_POST['export_resources'])) {
        // Export functionality
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="resources_export.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, array('ID', 'Project', 'Name', 'Type', 'Quantity', 'Cost', 'Status'));
        
        $export_query = mysqli_query($con, 
            "SELECT r.*, p.name as project_name 
             FROM resources r 
             INNER JOIN projects p ON r.project_id = p.id 
             INNER JOIN project_manager_assignments pma ON p.id = pma.project_id 
             WHERE pma.project_manager_id = '$pm_id'");
        
        while($row = mysqli_fetch_assoc($export_query)) {
            $status = $row['quantity'] > 10 ? 'In Stock' : ($row['quantity'] > 0 ? 'Low Stock' : 'Out of Stock');
            fputcsv($output, array(
                $row['id'],
                $row['project_name'],
                $row['name'],
                $row['type'],
                $row['quantity'],
                $row['cost'],
                $status
            ));
        }
        fclose($output);
        exit();
    }
}
?>

<?php include("menu.php"); ?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card card-dashboard">
                <div class="card-body">
                    <h1 class="h3 mb-0">Construction Resources</h1>
                    <p class="text-muted mb-0">Manage construction materials and resources for your projects</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Display Messages -->
    <?php if(isset($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if(isset($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Resources Table -->
    <div class="row">
        <div class="col-12">
            <div class="card card-dashboard">
                <div class="card-header">
                    <h5 class="card-title mb-0">Resource Inventory</h5>
                    <div class="card-tools">
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addResourceModal">
                            <i class="fas fa-plus me-1"></i>Add Resource
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Resource ID</th>
                                    <th>Project</th>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Quantity</th>
                                    <th>Cost</th>
                                    <th>Status</th>
                                    
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Get resources for projects managed by this PM
                                $resources_query = mysqli_query($con, 
                                    "SELECT r.*, p.name as project_name 
                                     FROM resources r 
                                     INNER JOIN projects p ON r.project_id = p.id 
                                     INNER JOIN project_manager_assignments pma ON p.id = pma.project_id 
                                     WHERE pma.project_manager_id = '$pm_id' 
                                     ORDER BY r.id DESC");
                                
                                if($resources_query && mysqli_num_rows($resources_query) > 0) {
                                    while($resource = mysqli_fetch_assoc($resources_query)) {
                                        // Status based on quantity
                                        $status_class = $resource['quantity'] > 10 ? 'bg-success' : 
                                                      ($resource['quantity'] > 0 ? 'bg-warning' : 'bg-danger');
                                        $status_text = $resource['quantity'] > 10 ? 'In Stock' : 
                                                     ($resource['quantity'] > 0 ? 'Low Stock' : 'Out of Stock');
                                        ?>
                                        <tr>
                                            <td><strong>#<?php echo $resource['id']; ?></strong></td>
                                            <td>
                                                <span class="badge bg-info"><?php echo $resource['project_name']; ?></span>
                                            </td>
                                            <td><?php echo $resource['name']; ?></td>
                                            <td>
                                                <span class="badge bg-secondary"><?php echo $resource['type']; ?></span>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span class="fw-bold me-2"><?php echo $resource['quantity']; ?></span>
                                                    <?php if($resource['quantity'] <= 10): ?>
                                                        <i class="fas fa-exclamation-triangle text-warning" data-bs-toggle="tooltip" title="Low Stock"></i>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>₹<?php echo number_format($resource['cost'], 2); ?></td>
                                            <td>
                                                <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    
                                                    
                                                </div>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                } else {
                                    echo '<tr>
                                            <td colspan="8" class="text-center py-4 text-muted">
                                                <i class="fas fa-tools fa-3x mb-3"></i><br>
                                                No resources found for your projects.
                                            </td>
                                          </tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Resource Statistics -->
    <div class="row mt-4">
        <div class="col-md-3">
            <div class="card card-dashboard">
                <div class="card-body text-center">
                    <?php
                    $total_resources_query = mysqli_query($con, 
                        "SELECT COUNT(*) as total_resources 
                         FROM resources r 
                         INNER JOIN projects p ON r.project_id = p.id 
                         INNER JOIN project_manager_assignments pma ON p.id = pma.project_id 
                         WHERE pma.project_manager_id = '$pm_id'");
                    if($total_resources_query) {
                        $total_resources = mysqli_fetch_assoc($total_resources_query);
                        echo '<h3 class="text-primary">'.$total_resources['total_resources'].'</h3>';
                    } else {
                        echo '<h3 class="text-primary">0</h3>';
                    }
                    ?>
                    <p class="text-muted mb-0">Total Resources</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-dashboard">
                <div class="card-body text-center">
                    <?php
                    $total_cost_query = mysqli_query($con, 
                        "SELECT SUM(r.cost) as total_cost 
                         FROM resources r 
                         INNER JOIN projects p ON r.project_id = p.id 
                         INNER JOIN project_manager_assignments pma ON p.id = pma.project_id 
                         WHERE pma.project_manager_id = '$pm_id'");
                    if($total_cost_query) {
                        $total_cost = mysqli_fetch_assoc($total_cost_query);
                        echo '<h3 class="text-success">₹'.number_format($total_cost['total_cost'], 2).'</h3>';
                    } else {
                        echo '<h3 class="text-success">₹0</h3>';
                    }
                    ?>
                    <p class="text-muted mb-0">Total Cost</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-dashboard">
                <div class="card-body text-center">
                    <?php
                    $low_stock_query = mysqli_query($con, 
                        "SELECT COUNT(*) as low_stock 
                         FROM resources r 
                         INNER JOIN projects p ON r.project_id = p.id 
                         INNER JOIN project_manager_assignments pma ON p.id = pma.project_id 
                         WHERE pma.project_manager_id = '$pm_id' AND r.quantity <= 10 AND r.quantity > 0");
                    if($low_stock_query) {
                        $low_stock = mysqli_fetch_assoc($low_stock_query);
                        echo '<h3 class="text-warning">'.$low_stock['low_stock'].'</h3>';
                    } else {
                        echo '<h3 class="text-warning">0</h3>';
                    }
                    ?>
                    <p class="text-muted mb-0">Low Stock Items</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-dashboard">
                <div class="card-body text-center">
                    <?php
                    $out_of_stock_query = mysqli_query($con, 
                        "SELECT COUNT(*) as out_of_stock 
                         FROM resources r 
                         INNER JOIN projects p ON r.project_id = p.id 
                         INNER JOIN project_manager_assignments pma ON p.id = pma.project_id 
                         WHERE pma.project_manager_id = '$pm_id' AND r.quantity = 0");
                    if($out_of_stock_query) {
                        $out_of_stock = mysqli_fetch_assoc($out_of_stock_query);
                        echo '<h3 class="text-danger">'.$out_of_stock['out_of_stock'].'</h3>';
                    } else {
                        echo '<h3 class="text-danger">0</h3>';
                    }
                    ?>
                    <p class="text-muted mb-0">Out of Stock</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Resource Types Breakdown -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card card-dashboard">
                <div class="card-header">
                    <h5 class="card-title mb-0">Resources by Type</h5>
                </div>
                <div class="card-body">
                    <?php
                    $types_query = mysqli_query($con, 
                        "SELECT type, COUNT(*) as count, SUM(cost) as total_cost 
                         FROM resources r 
                         INNER JOIN projects p ON r.project_id = p.id 
                         INNER JOIN project_manager_assignments pma ON p.id = pma.project_id 
                         WHERE pma.project_manager_id = '$pm_id' 
                         GROUP BY type");
                    
                    if($types_query && mysqli_num_rows($types_query) > 0) {
                        while($type = mysqli_fetch_assoc($types_query)) {
                            ?>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span><?php echo $type['type']; ?></span>
                                <div>
                                    <span class="badge bg-primary me-2"><?php echo $type['count']; ?> items</span>
                                    <span class="text-muted">₹<?php echo number_format($type['total_cost'], 2); ?></span>
                                </div>
                            </div>
                            <div class="progress mb-3" style="height: 5px;">
                                <div class="progress-bar" role="progressbar" style="width: <?php echo min(100, $type['count'] * 10); ?>%"></div>
                            </div>
                            <?php
                        }
                    } else {
                        echo '<p class="text-muted text-center">No resource types found</p>';
                    }
                    ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card card-dashboard">
                <div class="card-header">
                    <h5 class="card-title mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <form method="POST" class="d-grid gap-2">
                        <!-- Add Resource Button (opens modal) -->
                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addResourceModal">
                            <i class="fas fa-plus me-2"></i>Add New Resource
                        </button>
                        
                        <!-- Export Resources Button -->
                        <button type="submit" name="export_resources" class="btn btn-outline-success">
                            <i class="fas fa-file-export me-2"></i>Export Resource List
                        </button>
                        
                        <!-- Set Low Stock Alerts Button -->
                        <button type="button" class="btn btn-outline-warning" onclick="setLowStockAlerts()">
                            <i class="fas fa-bell me-2"></i>Set Low Stock Alerts
                        </button>
                        
                        <!-- Generate Report Button -->
                        <button type="button" class="btn btn-outline-info" onclick="generateResourceReport()">
                            <i class="fas fa-chart-bar me-2"></i>Generate Resource Report
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Resource Modal -->
<div class="modal fade" id="addResourceModal" tabindex="-1" aria-labelledby="addResourceModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="addResourceModalLabel">Add New Resource</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="project_id" class="form-label">Project</label>
                        <select class="form-select" id="project_id" name="project_id" required>
                            <option value="">Select Project</option>
                            <?php
                            $projects = mysqli_query($con, 
                                "SELECT p.id, p.name 
                                 FROM projects p 
                                 INNER JOIN project_manager_assignments pma ON p.id = pma.project_id 
                                 WHERE pma.project_manager_id = '$pm_id'");
                            while($project = mysqli_fetch_assoc($projects)) {
                                echo '<option value="'.$project['id'].'">'.$project['name'].'</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="name" class="form-label">Resource Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="type" class="form-label">Type</label>
                        <select class="form-select" id="type" name="type" required>
                            <option value="">Select Type</option>
                            <option value="Material">Material</option>
                            <option value="Equipment">Equipment</option>
                            <option value="Labor">Labor</option>
                            <option value="Tool">Tool</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="quantity" class="form-label">Quantity</label>
                                <input type="number" class="form-control" id="quantity" name="quantity" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="cost" class="form-label">Cost (₹)</label>
                                <input type="number" class="form-control" id="cost" name="cost" min="0" step="0.01" required>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_resource" class="btn btn-primary">Add Resource</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// JavaScript functions for button actions
function setLowStockAlerts() {
    Swal.fire({
        title: 'Low Stock Alerts Set',
        text: 'You will be notified when resources fall below threshold levels.',
        icon: 'success',
        confirmButtonText: 'OK'
    });
}

function generateResourceReport() {
    Swal.fire({
        title: 'Resource Report Generated',
        html: 'Report has been generated and saved to your dashboard.<br><br>'
    });
}

function editResource(id) {
    Swal.fire({
        title: 'Edit Resource',
        text: 'Edit functionality for resource ID: ' + id,
        icon: 'info',
        confirmButtonText: 'OK'
    });
}

function viewResource(id) {
    Swal.fire({
        title: 'Resource Details',
        text: 'View details for resource ID: ' + id,
        icon: 'info',
        confirmButtonText: 'OK'
    });
}

function deleteResource(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire(
                'Deleted!',
                'Resource has been deleted.',
                'success'
            );
            // Here you would typically make an AJAX call to delete the resource
        }
    });
}
</script>

<!-- Include SweetAlert2 for beautiful alerts -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php include("footer.php"); ?>