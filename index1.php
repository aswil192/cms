<?php
include('connection.php');
session_start();

// Enhanced session check with better error handling
if(!isset($_SESSION['uid']) && !isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Set consistent session variables if they don't exist
if(!isset($_SESSION['type']) && isset($_SESSION['user'])) {
    $_SESSION['type'] = $_SESSION['user'];
}
if(!isset($_SESSION['user']) && isset($_SESSION['type'])) {
    $_SESSION['user'] = $_SESSION['type'];
}

// Determine user type (using ternary instead of null coalescing for compatibility)
$userType = isset($_SESSION['type']) ? $_SESSION['type'] : (isset($_SESSION['user']) ? $_SESSION['user'] : '');

// Get client's project stats with better error handling
if($userType == 'client') {
    $client_id = isset($_SESSION['uid']) ? $_SESSION['uid'] : 0;
    
    // Add safety check for client_id
    if($client_id > 0) {
        $total_result = mysqli_query($con, "SELECT COUNT(*) as total FROM projects WHERE client_id='$client_id'");
        $total_projects = $total_result ? mysqli_fetch_assoc($total_result) : ['total' => 0];
        
        $pending_result = mysqli_query($con, "SELECT COUNT(*) as total FROM projects WHERE client_id='$client_id' AND status='Pending'");
        $pending_projects = $pending_result ? mysqli_fetch_assoc($pending_result) : ['total' => 0];
        
        $active_result = mysqli_query($con, "SELECT COUNT(*) as total FROM projects WHERE client_id='$client_id' AND status='In Progress'");
        $active_projects = $active_result ? mysqli_fetch_assoc($active_result) : ['total' => 0];
        
        $completed_result = mysqli_query($con, "SELECT COUNT(*) as total FROM projects WHERE client_id='$client_id' AND status='Completed'");
        $completed_projects = $completed_result ? mysqli_fetch_assoc($completed_result) : ['total' => 0];
        
        // Get recent projects for the activity feed
        $recent_projects = mysqli_query($con, "SELECT * FROM projects WHERE client_id='$client_id' ORDER BY created_at DESC LIMIT 5");
        
        // Get support tickets count for the client
        $tickets_result = mysqli_query($con, "SELECT COUNT(*) as total FROM support_tickets WHERE client_id='$client_id'");
        $total_tickets = $tickets_result ? mysqli_fetch_assoc($tickets_result) : ['total' => 0];
        
        $open_tickets_result = mysqli_query($con, "SELECT COUNT(*) as total FROM support_tickets WHERE client_id='$client_id' AND status='open'");
        $open_tickets = $open_tickets_result ? mysqli_fetch_assoc($open_tickets_result) : ['total' => 0];
    } else {
        // Handle case where client_id is not set properly
        $total_projects = ['total' => 0];
        $pending_projects = ['total' => 0];
        $active_projects = ['total' => 0];
        $completed_projects = ['total' => 0];
        $recent_projects = false;
        $total_tickets = ['total' => 0];
        $open_tickets = ['total' => 0];
    }
}

include('header.php');
?>

<!-- Dashboard Start -->
<div class="container-fluid py-5 bg-light">
    <div class="container py-5">
        <!-- Welcome Header -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h2 text-primary mb-2">Welcome back, <?php echo htmlspecialchars(isset($_SESSION['name']) ? $_SESSION['name'] : 'User'); ?>! 👋</h1>
                        <p class="text-muted mb-0">Manage your construction projects efficiently</p>
                    </div>
                    <div class="d-none d-md-block">
                        <a href="add_project.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-plus me-2"></i>Add New Project
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <?php if($userType == 'client') { ?>
        <!-- Stats Cards -->
        <div class="row g-4 mb-5">
            <div class="col-xl-3 col-md-6">
                <div class="card dashboard-card border-0">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-primary bg-opacity-10 p-3 rounded">
                                    <i class="fas fa-clipboard-list text-primary fs-2"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h2 class="stats-number text-primary mb-0"><?php echo isset($total_projects['total']) ? $total_projects['total'] : 0; ?></h2>
                                <p class="text-muted mb-0">Total Projects</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6">
                <div class="card dashboard-card border-0">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-warning bg-opacity-10 p-3 rounded">
                                    <i class="fas fa-clock text-warning fs-2"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h2 class="stats-number text-warning mb-0"><?php echo isset($pending_projects['total']) ? $pending_projects['total'] : 0; ?></h2>
                                <p class="text-muted mb-0">Pending</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6">
                <div class="card dashboard-card border-0">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-info bg-opacity-10 p-3 rounded">
                                    <i class="fas fa-tasks text-info fs-2"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h2 class="stats-number text-info mb-0"><?php echo isset($active_projects['total']) ? $active_projects['total'] : 0; ?></h2>
                                <p class="text-muted mb-0">In Progress</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6">
                <div class="card dashboard-card border-0">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0">
                                <div class="bg-success bg-opacity-10 p-3 rounded">
                                    <i class="fas fa-check-circle text-success fs-2"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h2 class="stats-number text-success mb-0"><?php echo isset($completed_projects['total']) ? $completed_projects['total'] : 0; ?></h2>
                                <p class="text-muted mb-0">Completed</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="row g-4">
            <!-- Quick Actions -->
            <div class="col-lg-4">
                <div class="card dashboard-card border-0 h-100">
                    <div class="card-header bg-transparent border-0 pb-0">
                        <h4 class="card-title mb-3">Quick Actions</h4>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-3">
                            <a href="add_project.php" class="btn quick-action-btn btn-light text-start border">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-plus-circle text-primary me-3 fs-4"></i>
                                    <div class="text-start">
                                        <strong class="d-block">Add New Project</strong>
                                        <small class="text-muted">Submit construction project request</small>
                                    </div>
                                </div>
                            </a>
                            
                            <a href="projects.php" class="btn quick-action-btn btn-light text-start border">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-list-alt text-primary me-3 fs-4"></i>
                                    <div class="text-start">
                                        <strong class="d-block">View All Projects</strong>
                                        <small class="text-muted">See all your projects</small>
                                    </div>
                                </div>
                            </a>
                            
                            <a href="complaint.php" class="btn quick-action-btn btn-light text-start border">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-comment-dots text-warning me-3 fs-4"></i>
                                    <div class="text-start">
                                        <strong class="d-block">Submit Complaint</strong>
                                        <small class="text-muted">Report issues or concerns</small>
                                    </div>
                                </div>
                            </a>
                            
                            <a href="payment.php" class="btn quick-action-btn btn-light text-start border">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-credit-card text-info me-3 fs-4"></i>
                                    <div class="text-start">
                                        <strong class="d-block">Make Payment</strong>
                                        <small class="text-muted">Pay for services</small>
                                    </div>
                                </div>
                            </a>
                            
                            <a href="help.php" class="btn quick-action-btn btn-light text-start border">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-question-circle text-secondary me-3 fs-4"></i>
                                    <div class="text-start">
                                        <strong class="d-block">Help & Support</strong>
                                        <small class="text-muted">Get assistance</small>
                                    </div>
                                </div>
                            </a>
                            
                            <!-- My Tickets Box - ADDED HERE -->
                            <a href="my_tickets.php" class="btn quick-action-btn btn-light text-start border">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-ticket-alt text-info me-3 fs-4"></i>
                                    <div class="text-start">
                                        <strong class="d-block">My Tickets</strong>
                                        <small class="text-muted">
                                            <?php 
                                            echo isset($open_tickets['total']) ? $open_tickets['total'] : 0; 
                                            ?> open tickets
                                        </small>
                                    </div>
                                    <?php if(isset($open_tickets['total']) && $open_tickets['total'] > 0): ?>
                                    <span class="badge bg-danger ms-auto"><?php echo $open_tickets['total']; ?></span>
                                    <?php endif; ?>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Projects -->
            <div class="col-lg-8">
                <div class="card dashboard-card border-0 h-100">
                    <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center pb-3">
                        <h4 class="card-title mb-0">Recent Projects</h4>
                        <a href="projects.php" class="btn btn-outline-primary btn-sm">View All</a>
                    </div>
                    <div class="card-body">
                        <?php
                        if($recent_projects && mysqli_num_rows($recent_projects) > 0) {
                            while($project = mysqli_fetch_assoc($recent_projects)) {
                                $status_color = '';
                                $status_icon = '';
                                switch($project['status']) {
                                    case 'Completed':
                                        $status_color = 'success';
                                        $status_icon = 'fa-check-circle';
                                        break;
                                    case 'In Progress':
                                        $status_color = 'warning';
                                        $status_icon = 'fa-spinner';
                                        break;
                                    default:
                                        $status_color = 'secondary';
                                        $status_icon = 'fa-clock';
                                }
                                
                                $project_image = !empty($project['image']) ? $project['image'] : 'default-project.jpg';
                                
                                echo '
                                <div class="project-item border-bottom pb-3 mb-3">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0">
                                            <img src="admin/projects/uploads/' . $project_image . '" 
                                                 alt="' . htmlspecialchars($project['name']) . '" 
                                                 class="rounded" width="60" height="60" style="object-fit: cover;">
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h5 class="mb-1">' . htmlspecialchars($project['name']) . '</h5>
                                            <div class="d-flex flex-wrap gap-3 align-items-center">
                                                <span class="badge bg-' . $status_color . '">
                                                    <i class="fas ' . $status_icon . ' me-1"></i>' . $project['status'] . '
                                                </span>
                                                <small class="text-muted"><i class="fas fa-map-marker-alt me-1"></i>' . htmlspecialchars($project['location']) . '</small>
                                                <small class="text-muted"><i class="fas fa-rupee-sign me-1"></i>' . number_format($project['budget']) . '</small>
                                            </div>
                                        </div>
                                        <div class="flex-shrink-0">
                                            <a href="view_project.php?id=' . $project['id'] . '" class="btn btn-outline-primary btn-sm">View Details</a>
                                        </div>
                                    </div>
                                </div>';
                            }
                        } else {
                            echo '
                            <div class="text-center py-5">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No Projects Yet</h5>
                                <p class="text-muted mb-4">Get started by submitting your first construction project</p>
                                <a href="add_project.php" class="btn btn-primary">Add Your First Project</a>
                            </div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- How It Works Section -->
        <div class="row g-4 mt-4">
            <div class="col-12">
                <div class="card dashboard-card border-0">
                    <div class="card-header bg-transparent border-0">
                        <h4 class="card-title mb-0">How It Works</h4>
                    </div>
                    <div class="card-body">
                        <div class="row g-4 text-center">
                            <div class="col-md-3">
                                <div class="feature-icon bg-primary bg-opacity-10 mx-auto mb-3">
                                    <i class="fas fa-edit text-primary fs-3"></i>
                                </div>
                                <h6>Submit Project</h6>
                                <p class="text-muted small">Fill out project details and requirements</p>
                            </div>
                            <div class="col-md-3">
                                <div class="feature-icon bg-warning bg-opacity-10 mx-auto mb-3">
                                    <i class="fas fa-search text-warning fs-3"></i>
                                </div>
                                <h6>Review Process</h6>
                                <p class="text-muted small">Our team reviews your project requirements</p>
                            </div>
                            <div class="col-md-3">
                                <div class="feature-icon bg-info bg-opacity-10 mx-auto mb-3">
                                    <i class="fas fa-tasks text-info fs-3"></i>
                                </div>
                                <h6>Track Progress</h6>
                                <p class="text-muted small">Monitor your project's development in real-time</p>
                            </div>
                            <div class="col-md-3">
                                <div class="feature-icon bg-success bg-opacity-10 mx-auto mb-3">
                                    <i class="fas fa-check text-success fs-3"></i>
                                </div>
                                <h6>Completion</h6>
                                <p class="text-muted small">Project delivered and finalized</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php } else { ?>
        <!-- Admin/Project Manager Dashboard -->
        <div class="row">
            <div class="col-12">
                <div class="card dashboard-card border-0">
                    <div class="card-body text-center py-5">
                        <h3 class="text-primary mb-3">Welcome, <?php echo htmlspecialchars(isset($_SESSION['name']) ? $_SESSION['name'] : 'User'); ?>!</h3>
                        <p class="text-muted mb-4">You are logged in as <?php echo htmlspecialchars($userType); ?>.</p>
                        <a href="projects.php" class="btn btn-primary">View Projects</a>
                    </div>
                </div>
            </div>
        </div>
        <?php } ?>
    </div>
</div>
<!-- Dashboard End -->

<?php
include('footer.php');
?>