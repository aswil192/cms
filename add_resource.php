<?php
// add_resource.php
// Page for project managers to add new resource requests

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in as project manager
if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'project_manager') {
    header("Location: login.php");
    exit();
}

include("connection.php");

// Check database connection
if(!$con) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Get current project manager's ID and projects
$manager_id = $_SESSION['user_id'];
$projects_query = mysqli_query($con, 
    "SELECT p.* 
     FROM projects p 
     WHERE p.project_manager_id = '$manager_id' 
     ORDER BY p.name");

// Common resource types for dropdown
$resource_types = [
    'material' => 'Construction Material',
    'equipment' => 'Equipment',
    'labor' => 'Labor',
    'tool' => 'Tools',
    'safety' => 'Safety Equipment',
    'vehicle' => 'Vehicle',
    'other' => 'Other'
];

// Handle form submission
$errors = [];
$success = '';

if(isset($_POST['add_resource'])) {
    // Validate and sanitize inputs
    $project_id = mysqli_real_escape_string($con, $_POST['project_id']);
    $type = mysqli_real_escape_string($con, $_POST['type']);
    $name = mysqli_real_escape_string($con, $_POST['name']);
    $quantity = mysqli_real_escape_string($con, $_POST['quantity']);
    $cost = mysqli_real_escape_string($con, $_POST['cost']);
    $description = mysqli_real_escape_string($con, $_POST['description']);
    $urgency = mysqli_real_escape_string($con, $_POST['urgency']);

    // Validation
    if(empty($project_id)) {
        $errors[] = "Please select a project";
    }
    if(empty($type)) {
        $errors[] = "Please select resource type";
    }
    if(empty($name)) {
        $errors[] = "Resource name is required";
    }
    if(empty($quantity) || $quantity < 1) {
        $errors[] = "Please enter a valid quantity";
    }
    if(empty($cost) || $cost < 0) {
        $errors[] = "Please enter a valid cost";
    }

    // If no errors, insert into database
    if(empty($errors)) {
        // Add status column if it doesn't exist
        $check_status_column = mysqli_query($con, "SHOW COLUMNS FROM resources LIKE 'status'");
        if(mysqli_num_rows($check_status_column) == 0) {
            $alter_query = "ALTER TABLE resources ADD COLUMN status VARCHAR(50) DEFAULT 'pending'";
            mysqli_query($con, $alter_query);
        }

        // Add requested_by column if it doesn't exist
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

        // Add description column if it doesn't exist
        $check_desc_column = mysqli_query($con, "SHOW COLUMNS FROM resources LIKE 'description'");
        if(mysqli_num_rows($check_desc_column) == 0) {
            $alter_query = "ALTER TABLE resources ADD COLUMN description TEXT";
            mysqli_query($con, $alter_query);
        }

        // Add urgency column if it doesn't exist
        $check_urgency_column = mysqli_query($con, "SHOW COLUMNS FROM resources LIKE 'urgency'");
        if(mysqli_num_rows($check_urgency_column) == 0) {
            $alter_query = "ALTER TABLE resources ADD COLUMN urgency VARCHAR(20) DEFAULT 'normal'";
            mysqli_query($con, $alter_query);
        }

        // Insert the resource request
        $insert_query = "INSERT INTO resources (project_id, type, name, quantity, cost, description, urgency, requested_by, status, request_date) 
                        VALUES ('$project_id', '$type', '$name', '$quantity', '$cost', '$description', '$urgency', '$manager_id', 'pending', NOW())";
        
        if(mysqli_query($con, $insert_query)) {
            $success = "Resource request submitted successfully! It will be reviewed by administration.";
            
            // Clear form fields
            $_POST = array();
        } else {
            $errors[] = "Error submitting resource request: " . mysqli_error($con);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request New Resource - Project Manager</title>
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
        .form-container {
            max-width: 800px;
            margin: 0 auto;
        }
        .urgency-high { border-left: 4px solid #dc3545; }
        .urgency-medium { border-left: 4px solid #ffc107; }
        .urgency-low { border-left: 4px solid #28a745; }
        .resource-type-icon {
            font-size: 1.2rem;
            margin-right: 8px;
        }
        .form-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .form-section h5 {
            color: #495057;
            border-bottom: 2px solid #dee2e6;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
<?php include("project_manager_menu.php"); ?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card card-dashboard">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h1 class="h3 mb-0">Request New Resource</h1>
                            <p class="text-muted mb-0">Submit resource requests for your projects</p>
                        </div>
                        <div class="col-md-6 text-end">
                            <a href="project_manager_resources.php" class="btn btn-primary">
                                <i class="fas fa-arrow-left me-2"></i>Back to My Resources
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alerts -->
    <?php if(!empty($success)): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if(!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <h5><i class="fas fa-exclamation-triangle me-2"></i>Please fix the following errors:</h5>
            <ul class="mb-0">
                <?php foreach($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Resource Request Form -->
    <div class="row justify-content-center">
        <div class="col-12">
            <div class="card card-dashboard form-container">
                <div class="card-header">
                    <h5 class="card-title mb-0">Resource Request Form</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="add_resource.php">
                        <!-- Project Information Section -->
                        <div class="form-section">
                            <h5><i class="fas fa-project-diagram me-2"></i>Project Information</h5>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label">Select Project <span class="text-danger">*</span></label>
                                        <select class="form-select" name="project_id" required>
                                            <option value="">-- Choose Project --</option>
                                            <?php
                                            if(mysqli_num_rows($projects_query) > 0) {
                                                while($project = mysqli_fetch_assoc($projects_query)) {
                                                    $selected = (isset($_POST['project_id']) && $_POST['project_id'] == $project['id']) ? 'selected' : '';
                                                    echo '<option value="' . $project['id'] . '" ' . $selected . '>' . htmlspecialchars($project['name']) . ' (Budget: $' . number_format($project['budget'], 2) . ')</option>';
                                                }
                                            } else {
                                                echo '<option value="" disabled>No projects assigned to you</option>';
                                            }
                                            ?>
                                        </select>
                                        <div class="form-text">Select the project that requires this resource</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Resource Details Section -->
                        <div class="form-section">
                            <h5><i class="fas fa-box me-2"></i>Resource Details</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Resource Type <span class="text-danger">*</span></label>
                                        <select class="form-select" name="type" required>
                                            <option value="">-- Select Type --</option>
                                            <?php foreach($resource_types as $key => $value): ?>
                                                <?php $selected = (isset($_POST['type']) && $_POST['type'] == $key) ? 'selected' : ''; ?>
                                                <option value="<?php echo $key; ?>" <?php echo $selected; ?>>
                                                    <?php echo $value; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Resource Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" placeholder="e.g., Cement Bags, Excavator, Skilled Labor" required>
                                        <div class="form-text">Be specific about the resource needed</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Quantity <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" name="quantity" value="<?php echo isset($_POST['quantity']) ? htmlspecialchars($_POST['quantity']) : ''; ?>" min="1" required>
                                        <div class="form-text">Number of units needed</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Estimated Cost ($) <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" class="form-control" name="cost" value="<?php echo isset($_POST['cost']) ? htmlspecialchars($_POST['cost']) : ''; ?>" step="0.01" min="0" required>
                                        </div>
                                        <div class="form-text">Estimated cost per unit</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Urgency Level <span class="text-danger">*</span></label>
                                        <select class="form-select" name="urgency" required>
                                            <option value="low" <?php echo (isset($_POST['urgency']) && $_POST['urgency'] == 'low') ? 'selected' : ''; ?>>Low Priority</option>
                                            <option value="normal" <?php echo (isset($_POST['urgency']) && $_POST['urgency'] == 'normal' || !isset($_POST['urgency'])) ? 'selected' : ''; ?>>Normal Priority</option>
                                            <option value="high" <?php echo (isset($_POST['urgency']) && $_POST['urgency'] == 'high') ? 'selected' : ''; ?>>High Priority</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-12">
                                    <div class="mb-3">
                                        <label class="form-label">Description / Specifications</label>
                                        <textarea class="form-control" name="description" rows="4" placeholder="Provide detailed specifications, brand preferences, quality requirements, or any special instructions..."><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                                        <div class="form-text">Optional: Add any additional details about the resource requirement</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Urgency Indicators -->
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="card urgency-low">
                                    <div class="card-body text-center">
                                        <i class="fas fa-clock text-success fa-2x mb-2"></i>
                                        <h6>Low Priority</h6>
                                        <small class="text-muted">Can wait 1-2 weeks</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card urgency-medium">
                                    <div class="card-body text-center">
                                        <i class="fas fa-exclamation-circle text-warning fa-2x mb-2"></i>
                                        <h6>Normal Priority</h6>
                                        <small class="text-muted">Needed within 1 week</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card urgency-high">
                                    <div class="card-body text-center">
                                        <i class="fas fa-exclamation-triangle text-danger fa-2x mb-2"></i>
                                        <h6>High Priority</h6>
                                        <small class="text-muted">Urgent - needed immediately</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex justify-content-between">
                                    <a href="project_manager_resources.php" class="btn btn-secondary">
                                        <i class="fas fa-times me-2"></i>Cancel
                                    </a>
                                    <button type="submit" name="add_resource" class="btn btn-primary">
                                        <i class="fas fa-paper-plane me-2"></i>Submit Resource Request
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Tips -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card card-dashboard">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-lightbulb me-2"></i>Tips for Resource Requests</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="d-flex">
                                <i class="fas fa-check-circle text-success me-3 mt-1"></i>
                                <div>
                                    <h6>Be Specific</h6>
                                    <p class="text-muted mb-0">Provide clear specifications and brand preferences when possible</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex">
                                <i class="fas fa-check-circle text-success me-3 mt-1"></i>
                                <div>
                                    <h6>Realistic Timeline</h6>
                                    <p class="text-muted mb-0">Choose appropriate urgency level based on actual project needs</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex">
                                <i class="fas fa-check-circle text-success me-3 mt-1"></i>
                                <div>
                                    <h6>Budget Awareness</h6>
                                    <p class="text-muted mb-0">Consider project budget when estimating costs</p>
                                </div>
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

    // Calculate total cost based on quantity and unit cost
    const quantityInput = document.querySelector('input[name="quantity"]');
    const costInput = document.querySelector('input[name="cost"]');
    
    function updateTotalEstimate() {
        const quantity = parseInt(quantityInput.value) || 0;
        const unitCost = parseFloat(costInput.value) || 0;
        const total = quantity * unitCost;
        
        // You can display this total somewhere if needed
        console.log('Total estimated cost:', total);
    }
    
    quantityInput.addEventListener('input', updateTotalEstimate);
    costInput.addEventListener('input', updateTotalEstimate);
});
</script>

<?php include("footer.php"); ?>
</body>
</html>