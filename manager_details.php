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

// Get manager ID from URL parameter
if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: all_managers.php");
    exit();
}

$manager_id = mysqli_real_escape_string($con, $_GET['id']);

// Fetch manager details
$manager_query = mysqli_query($con, 
    "SELECT * FROM project_managers 
     WHERE id = '$manager_id'");

if(!$manager_query || mysqli_num_rows($manager_query) == 0) {
    header("Location: all_managers.php");
    exit();
}

$manager = mysqli_fetch_assoc($manager_query);

// Try to fetch manager's projects (if projects table has manager relationship)
$projects_query = null;
$project_count = 0;
$active_projects = 0;
$completed_projects = 0;
$relationship_configured = false;

// Check if project_manager_id column exists in projects table
$check_relationship = mysqli_query($con, "SHOW COLUMNS FROM projects LIKE 'project_manager_id'");
if($check_relationship && mysqli_num_rows($check_relationship) > 0) {
    $relationship_configured = true;
    
    // Fetch projects assigned to this manager
    $projects_query = mysqli_query($con, 
        "SELECT * FROM projects 
         WHERE project_manager_id = '$manager_id' 
         ORDER BY created_at DESC");
    
    // Get project statistics
    $stats_query = mysqli_query($con, 
        "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed
         FROM projects 
         WHERE project_manager_id = '$manager_id'");
    
    if($stats_query) {
        $stats_data = mysqli_fetch_assoc($stats_query);
        $project_count = $stats_data['total'];
        $active_projects = $stats_data['active'];
        $completed_projects = $stats_data['completed'];
    }
}

// Handle manager update
if(isset($_POST['update_manager'])) {
    $name = mysqli_real_escape_string($con, $_POST['name']);
    $email = mysqli_real_escape_string($con, $_POST['email']);
    $phone = mysqli_real_escape_string($con, $_POST['phone']);
    $address = mysqli_real_escape_string($con, $_POST['address']);
    
    // Check if email already exists (excluding current manager)
    $check_email = mysqli_query($con, "SELECT * FROM project_managers WHERE email = '$email' AND id != '$manager_id'");
    if($check_email && mysqli_num_rows($check_email) > 0) {
        $error = "Email address already exists!";
    } else {
        // Handle image upload
        $image_name = $manager['image'];
        if(isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $image = $_FILES['image'];
            $image_ext = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));
            $allowed_ext = array('jpg', 'jpeg', 'png', 'gif');
            
            if(in_array($image_ext, $allowed_ext)) {
                // Delete old image if exists
                if(!empty($manager['image'])) {
                    $old_image_path = "uploads/managers/" . $manager['image'];
                    if(file_exists($old_image_path)) {
                        unlink($old_image_path);
                    }
                }
                
                $image_name = 'pm_' . time() . '_' . uniqid() . '.' . $image_ext;
                $upload_path = "uploads/managers/" . $image_name;
                
                if(!is_dir('uploads/managers')) {
                    mkdir('uploads/managers', 0777, true);
                }
                
                if(!move_uploaded_file($image['tmp_name'], $upload_path)) {
                    $error = "Failed to upload image.";
                    $image_name = $manager['image']; // Keep old image
                }
            } else {
                $error = "Invalid image format. Allowed: JPG, JPEG, PNG, GIF.";
            }
        }
        
        if(!isset($error)) {
            $update_query = "UPDATE project_managers 
                            SET name = '$name', email = '$email', phone = '$phone', 
                                address = '$address', image = '$image_name'
                            WHERE id = '$manager_id'";
            
            if(mysqli_query($con, $update_query)) {
                $success = "Manager details updated successfully!";
                // Refresh manager data
                $manager_query = mysqli_query($con, "SELECT * FROM project_managers WHERE id = '$manager_id'");
                $manager = mysqli_fetch_assoc($manager_query);
            } else {
                $error = "Error updating manager: " . mysqli_error($con);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Details - <?php echo htmlspecialchars($manager['name']); ?> - Admin Panel</title>
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
        .manager-avatar-large {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid #e3e6f0;
            margin: 0 auto;
        }
        .avatar-placeholder-large {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 3rem;
            border: 5px solid #e3e6f0;
            margin: 0 auto;
        }
        .info-label {
            font-weight: 600;
            color: #6c757d;
            margin-bottom: 5px;
        }
        .info-value {
            font-size: 1.1rem;
            margin-bottom: 15px;
        }
        .project-status-badge {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .bg-in-progress { background-color: #17a2b8; color: #fff; }
        .bg-completed { background-color: #28a745; color: #fff; }
        .bg-pending { background-color: #ffc107; color: #212529; }
        .bg-cancelled { background-color: #dc3545; color: #fff; }
        .stats-card {
            text-align: center;
            padding: 20px;
        }
        .stats-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .stats-label {
            font-size: 0.9rem;
            color: #6c757d;
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
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="all_managers.php"><i class="fas fa-user-tie me-2"></i>Project Managers</a></li>
                                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($manager['name']); ?></li>
                                </ol>
                            </nav>
                            <h1 class="h3 mb-0">Manager Details</h1>
                            <p class="text-muted mb-0">View detailed information about <?php echo htmlspecialchars($manager['name']); ?></p>
                        </div>
                        <div class="col-md-6 text-end">
                            <a href="all_managers.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to Managers
                            </a>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editManagerModal">
                                <i class="fas fa-edit me-2"></i>Edit Manager
                            </button>
                        </div>
                    </div>
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

    <div class="row">
        <!-- Manager Information -->
        <div class="col-md-4">
            <div class="card card-dashboard">
                <div class="card-header">
                    <h5 class="card-title mb-0">Manager Information</h5>
                </div>
                <div class="card-body text-center">
                    <?php if(!empty($manager['image'])): ?>
                        <img src="uploads/managers/<?php echo htmlspecialchars($manager['image']); ?>" 
                             alt="<?php echo htmlspecialchars($manager['name']); ?>" 
                             class="manager-avatar-large mb-3"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="avatar-placeholder-large mb-3" style="display: none;">
                            <?php echo strtoupper(substr($manager['name'], 0, 1)); ?>
                        </div>
                    <?php else: ?>
                        <div class="avatar-placeholder-large mb-3">
                            <?php echo strtoupper(substr($manager['name'], 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                    
                    <h3><?php echo htmlspecialchars($manager['name']); ?></h3>
                    <p class="text-muted">Project Manager</p>
                    
                    <div class="text-start mt-4">
                        <div class="info-label">Manager ID</div>
                        <div class="info-value">#<?php echo $manager['id']; ?> (PM<?php echo str_pad($manager['id'], 4, '0', STR_PAD_LEFT); ?>)</div>

                        <div class="info-label">Email Address</div>
                        <div class="info-value">
                            <i class="fas fa-envelope text-muted me-2"></i>
                            <?php echo htmlspecialchars($manager['email']); ?>
                        </div>

                        <div class="info-label">Phone Number</div>
                        <div class="info-value">
                            <i class="fas fa-phone text-muted me-2"></i>
                            <?php echo !empty($manager['phone']) ? htmlspecialchars($manager['phone']) : 'Not provided'; ?>
                        </div>

                        <div class="info-label">Address</div>
                        <div class="info-value">
                            <i class="fas fa-map-marker-alt text-muted me-2"></i>
                            <?php echo !empty($manager['address']) ? nl2br(htmlspecialchars($manager['address'])) : 'Not provided'; ?>
                        </div>

                        <div class="info-label">Member Since</div>
                        <div class="info-value">
                            <i class="fas fa-calendar text-muted me-2"></i>
                            <?php echo date('F d, Y', strtotime($manager['created_at'])); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Project Statistics -->
            <div class="card card-dashboard">
                <div class="card-header">
                    <h5 class="card-title mb-0">Project Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="stats-card">
                                <div class="stats-number text-primary"><?php echo $project_count; ?></div>
                                <div class="stats-label">Total Projects</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stats-card">
                                <div class="stats-number text-warning"><?php echo $active_projects; ?></div>
                                <div class="stats-label">Active</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stats-card">
                                <div class="stats-number text-success"><?php echo $completed_projects; ?></div>
                                <div class="stats-label">Completed</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Manager Projects -->
        <div class="col-md-8">
            <div class="card card-dashboard">
                <div class="card-header">
                    <h5 class="card-title mb-0">Assigned Projects</h5>
                </div>
                <div class="card-body">
                    <?php if($relationship_configured && $projects_query && mysqli_num_rows($projects_query) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Project ID</th>
                                        <th>Project Name</th>
                                        <th>Status</th>
                                        <th>Start Date</th>
                                        <th>Budget</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($project = mysqli_fetch_assoc($projects_query)): ?>
                                        <tr>
                                            <td><strong>#<?php echo $project['id']; ?></strong></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($project['project_name']); ?></strong>
                                                <?php if(!empty($project['description'])): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars(substr($project['description'], 0, 50)); ?><?php echo strlen($project['description']) > 50 ? '...' : ''; ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php
                                                $project_status_class = 'bg-secondary';
                                                switch($project['status']) {
                                                    case 'In Progress':
                                                        $project_status_class = 'bg-in-progress';
                                                        break;
                                                    case 'Completed':
                                                        $project_status_class = 'bg-completed';
                                                        break;
                                                    case 'Pending':
                                                        $project_status_class = 'bg-pending';
                                                        break;
                                                    case 'Cancelled':
                                                        $project_status_class = 'bg-cancelled';
                                                        break;
                                                }
                                                ?>
                                                <span class="project-status-badge <?php echo $project_status_class; ?>">
                                                    <?php echo $project['status']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php echo !empty($project['start_date']) ? date('M d, Y', strtotime($project['start_date'])) : 'Not set'; ?>
                                            </td>
                                            <td>
                                                <?php echo !empty($project['budget']) ? '$' . number_format($project['budget'], 2) : 'Not set'; ?>
                                            </td>
                                            <td>
                                                <a href="view_project.php?id=<?php echo $project['id']; ?>" class="btn btn-info btn-sm" title="View Project">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="edit_project.php?id=<?php echo $project['id']; ?>" class="btn btn-warning btn-sm" title="Edit Project">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php elseif(!$relationship_configured): ?>
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-info-circle fa-3x mb-3"></i>
                            <br>
                            <h5>Project Relationship Not Configured</h5>
                            <p>To view assigned projects, please set up the project-manager relationship in your database.</p>
                            <a href="all_managers.php" class="btn btn-primary mt-2">
                                <i class="fas fa-cog me-2"></i>Setup Relationship
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-folder-open fa-3x mb-3"></i>
                            <br>
                            <h5>No Projects Assigned</h5>
                            <p>This manager doesn't have any assigned projects yet.</p>
                            <a href="all_projects.php" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Assign Projects
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="card card-dashboard">
                <div class="card-header">
                    <h5 class="card-title mb-0">Recent Activity</h5>
                </div>
                <div class="card-body">
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-chart-line fa-3x mb-3"></i>
                        <br>
                        <p>Activity tracking will be available in future updates.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Manager Modal -->
<div class="modal fade" id="editManagerModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Project Manager</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Full Name *</label>
                                <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($manager['name']); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Email Address *</label>
                                <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($manager['email']); ?>" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Phone Number</label>
                                <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($manager['phone']); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Profile Image</label>
                                <input type="file" class="form-control" name="image" accept="image/*">
                                <small class="text-muted">Leave empty to keep current image</small>
                                <?php if(!empty($manager['image'])): ?>
                                    <div class="mt-2">
                                        <small>Current: <?php echo htmlspecialchars($manager['image']); ?></small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea class="form-control" name="address" rows="3" placeholder="Full address including street, city, state, and zip code"><?php echo htmlspecialchars($manager['address']); ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_manager" class="btn btn-primary">Update Manager</button>
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

    // Image preview for edit modal
    const imageInput = document.querySelector('input[name="image"]');
    if(imageInput) {
        imageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if(file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Create preview if it doesn't exist
                    let preview = imageInput.closest('.mb-3').querySelector('.image-preview');
                    if(!preview) {
                        preview = document.createElement('div');
                        preview.className = 'image-preview mt-2';
                        imageInput.closest('.mb-3').appendChild(preview);
                    }
                    preview.innerHTML = `<img src="${e.target.result}" class="img-thumbnail" style="max-height: 100px;">`;
                }
                reader.readAsDataURL(file);
            }
        });
    }
});
</script>

<?php include("footer.php"); ?>
</body>
</html>