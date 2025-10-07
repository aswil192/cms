<?php
include('connection.php');
session_start();

// Enhanced session check
if(!isset($_SESSION['type']) && !isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Determine user type
$userType = isset($_SESSION['type']) ? $_SESSION['type'] : (isset($_SESSION['user']) ? $_SESSION['user'] : '');

// Check if user is logged in as client
if($userType != 'client') {
    header("Location: login.php");
    exit();
}

// Ensure client_id is set
$client_id = isset($_SESSION['uid']) ? $_SESSION['uid'] : 0;
if($client_id == 0) {
    // Try to get client_id from users table
    $email = isset($_SESSION['email']) ? $_SESSION['email'] : '';
    if($email) {
        $user_query = mysqli_query($con, "SELECT id FROM users WHERE email='$email' AND type='client'");
        if($user_query && mysqli_num_rows($user_query) > 0) {
            $user_data = mysqli_fetch_assoc($user_query);
            $client_id = $user_data['id'];
            $_SESSION['uid'] = $client_id;
        }
    }
}

if($client_id == 0) {
    // Redirect to login if client_id still not found
    header("Location: login.php");
    exit();
}

include('header.php');
?>

<!-- Header Start -->
<div class="container-fluid bg-breadcrumb">
    <div class="container text-center py-5" style="max-width: 900px;">
        <h3 class="text-white display-5 mb-4">My Projects</h3>
        <?php if($userType == 'client') { ?>
        <a href="add_project.php" class="btn btn-primary btn-lg mt-3">
            <i class="fas fa-plus me-2"></i>Add New Project
        </a>
        <?php } ?>
    </div>
</div>
<!-- Header End -->

<!-- Projects Start -->
<div class="container-fluid py-5">
    <div class="container py-5">
        <?php
        if(isset($_SESSION['success'])) {
            echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>'.$_SESSION['success'].'
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                  </div>';
            unset($_SESSION['success']);
        }
        
        if(isset($_SESSION['error'])) {
            echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>'.$_SESSION['error'].'
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                  </div>';
            unset($_SESSION['error']);
        }
        ?>
        
        <div class="row g-4">
            <?php
            $query = "SELECT * FROM projects WHERE client_id='$client_id' ORDER BY created_at DESC";
            $result = mysqli_query($con, $query);

            if($result && mysqli_num_rows($result) > 0) {
                while ($project = mysqli_fetch_assoc($result)) {
                    $status_class = '';
                    switch($project['status']) {
                        case 'Completed':
                            $status_class = 'success';
                            break;
                        case 'In Progress':
                            $status_class = 'warning';
                            break;
                        case 'Pending':
                            $status_class = 'secondary';
                            break;
                        default:
                            $status_class = 'info';
                    }
                    
                    echo '
                    <div class="col-md-6 col-lg-4">
                        <div class="card dashboard-card border-0 h-100">
                            <div class="card-img-top">';
                    if(!empty($project['image'])) {
                        echo '<img src="admin/projects/uploads/' . $project['image'] . '" class="project-image" alt="Project Image" style="height: 200px; object-fit: cover;">';
                    } else {
                        echo '<img src="img/default-project.jpg" class="project-image" alt="Default Project Image" style="height: 200px; object-fit: cover;">';
                    }
                    echo '</div>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title">' . htmlspecialchars($project['name']) . '</h5>
                                <div class="project-details mb-3">
                                    <p class="mb-2"><strong>Type:</strong> ' . htmlspecialchars($project['project_type']) . '</p>
                                    <p class="mb-2"><strong>Status:</strong> 
                                        <span class="badge bg-' . $status_class . '">' . htmlspecialchars($project['status']) . '</span>
                                    </p>
                                    <p class="mb-2"><strong>Budget:</strong> $' . number_format($project['budget']) . '</p>
                                    <p class="mb-0"><strong>Location:</strong> ' . htmlspecialchars($project['location']) . '</p>
                                </div>
                                <div class="mt-auto">
                                    <a href="view_project.php?id='.$project['id'].'" class="btn btn-primary w-100">
                                        <i class="fas fa-eye me-2"></i>View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>';
                }
            } else {
                echo '<div class="col-12 text-center">
                        <div class="card dashboard-card border-0">
                            <div class="card-body py-5">
                                <i class="fas fa-folder-open fa-4x text-muted mb-4"></i>
                                <h4 class="text-muted mb-3">No Projects Found</h4>
                                <p class="text-muted mb-4">You haven\'t submitted any projects yet. Click the button below to get started!</p>
                                <a href="add_project.php" class="btn btn-primary btn-lg">
                                    <i class="fas fa-plus me-2"></i>Add Your First Project
                                </a>
                            </div>
                        </div>
                      </div>';
            }
            ?>
        </div>
    </div>
</div>
<!-- Projects End -->

<?php
include('footer.php');
?>