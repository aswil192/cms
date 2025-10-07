<?php
session_start();
include('header.php');
include('connection.php');

// Debug session (remove after testing)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Enhanced session check for client
if(!isset($_SESSION['uid']) || (!isset($_SESSION['type']) && !isset($_SESSION['user_type']))) {
    header("Location: login.php");
    exit();
}

// Check if user is client (compatible with both session variable names)
$is_client = false;
if(isset($_SESSION['type']) && $_SESSION['type'] == 'client') {
    $is_client = true;
} elseif(isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'client') {
    $is_client = true;
}

if(!$is_client) {
    header("Location: login.php");
    exit();
}

// Get client ID from session
$client_id = isset($_SESSION['uid']) ? $_SESSION['uid'] : (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0);
?>

<!-- Header Start -->
<div class="container-fluid bg-breadcrumb">
    <div class="container text-center py-5" style="max-width: 900px;">
        <h4 class="text-white display-4 mb-4 wow fadeInDown" data-wow-delay="0.1s">Add New Project</h4>
        <ol class="breadcrumb d-flex justify-content-center mb-0 wow fadeInDown" data-wow-delay="0.3s">
            <li class="breadcrumb-item"><a href="index1.php">Home</a></li>
            <li class="breadcrumb-item"><a href="projects.php">Projects</a></li>
            <li class="breadcrumb-item active text-primary">Add Project</li>
        </ol>    
    </div>
</div>
<!-- Header End -->

<!-- Contact Start -->
<div class="container-fluid contact bg-light py-5">
    <div class="container py-5">
        <div class="row g-5 justify-content-center">
            <div class="col-lg-8 wow fadeInUp" data-wow-delay="0.2s">
                <div class="bg-white p-5 rounded shadow-sm">
                    <h3 class="mb-4">Submit Your Construction Project</h3>
                    
                    <?php
                    if(isset($_SESSION['success'])) {
                        echo '<div class="alert alert-success">'.$_SESSION['success'].'</div>';
                        unset($_SESSION['success']);
                    }
                    if(isset($_SESSION['error'])) {
                        echo '<div class="alert alert-danger">'.$_SESSION['error'].'</div>';
                        unset($_SESSION['error']);
                    }
                    ?>
                    
                    <form method="POST" action="save_project.php" enctype="multipart/form-data">
                        <input type="hidden" name="client_id" value="<?php echo $client_id; ?>">
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" name="project_name" placeholder="Project Name" required>
                                    <label for="project_name">Project Name *</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" name="location" placeholder="Project Location" required>
                                    <label for="location">Project Location *</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="number" class="form-control" name="budget" placeholder="Estimated Budget" step="0.01" min="0" required>
                                    <label for="budget">Estimated Budget (₹) *</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="number" class="form-control" name="duration" placeholder="Estimated Duration" min="1" required>
                                    <label for="duration">Estimated Duration (Days) *</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="date" class="form-control" name="start_date" min="<?php echo date('Y-m-d'); ?>" required>
                                    <label for="start_date">Preferred Start Date *</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <select class="form-select" name="project_type" required>
                                        <option value="">Select Project Type</option>
                                        <option value="Residential">Residential</option>
                                        <option value="Commercial">Commercial</option>
                                        <option value="Industrial">Industrial</option>
                                        <option value="Renovation">Renovation</option>
                                        <option value="Other">Other</option>
                                    </select>
                                    <label for="project_type">Project Type *</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-floating">
                                    <textarea class="form-control" name="description" placeholder="Project Description" style="height: 100px" required></textarea>
                                    <label for="description">Project Description *</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-floating">
                                    <textarea class="form-control" name="requirements" placeholder="Specific Requirements" style="height: 100px"></textarea>
                                    <label for="requirements">Specific Requirements</label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="mb-3">
                                    <label for="project_image" class="form-label">Project Reference Image (Optional)</label>
                                    <input type="file" class="form-control" name="project_image" accept="image/*">
                                    <small class="text-muted">Max file size: 2MB. Supported only JPEG</small>
                                </div>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary py-3 w-100">Submit Project</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Contact End -->

<?php
include('footer.php');
?>