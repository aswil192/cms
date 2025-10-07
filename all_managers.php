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

// Handle database relationship setup
if(isset($_POST['setup_relationship'])) {
    // Check if column already exists
    $check_column = mysqli_query($con, "SHOW COLUMNS FROM projects LIKE 'project_manager_id'");
    
    if($check_column && mysqli_num_rows($check_column) > 0) {
        $success = "Project-manager relationship is already configured!";
    } else {
        // Add the column
        $alter_query = "ALTER TABLE projects ADD COLUMN project_manager_id INT NULL AFTER client_id";
        if(mysqli_query($con, $alter_query)) {
            // Try to add foreign key constraint
            $fk_query = "ALTER TABLE projects ADD CONSTRAINT fk_project_manager 
                        FOREIGN KEY (project_manager_id) REFERENCES project_managers(id) ON DELETE SET NULL";
            if(mysqli_query($con, $fk_query)) {
                $success = "Project-manager relationship configured successfully!";
            } else {
                $success = "Project-manager column added successfully! (Foreign key may require database privileges)";
            }
        } else {
            $error = "Error adding project_manager_id column: " . mysqli_error($con);
        }
    }
}

// Check if relationship is configured
$relationship_configured = false;
$check_relationship = mysqli_query($con, "SHOW COLUMNS FROM projects LIKE 'project_manager_id'");
if($check_relationship && mysqli_num_rows($check_relationship) > 0) {
    $relationship_configured = true;
}

// Handle manager deletion
if(isset($_POST['delete_manager'])) {
    $manager_id = mysqli_real_escape_string($con, $_POST['manager_id']);
    
    // First check if manager exists
    $check_manager = mysqli_query($con, "SELECT * FROM project_managers WHERE id = '$manager_id'");
    
    if($check_manager && mysqli_num_rows($check_manager) > 0) {
        $manager_data = mysqli_fetch_assoc($check_manager);
        
        // Check if manager has projects (using a safe approach)
        $has_projects = false;
        $project_count = 0;
        
        if($relationship_configured) {
            // Check projects if relationship is configured
            $check_projects = mysqli_query($con, "SELECT COUNT(*) as project_count FROM projects WHERE project_manager_id = '$manager_id'");
            if($check_projects) {
                $projects_data = mysqli_fetch_assoc($check_projects);
                $project_count = $projects_data['project_count'];
                $has_projects = $project_count > 0;
            }
        }
        
        if($has_projects) {
            $error = "Cannot delete manager! Manager has " . $project_count . " assigned project(s).";
        } else {
            // Delete the manager's image file if exists
            if(!empty($manager_data['image'])) {
                $image_path = "uploads/managers/" . $manager_data['image'];
                if(file_exists($image_path)) {
                    unlink($image_path);
                }
            }
            
            $delete_query = "DELETE FROM project_managers WHERE id = '$manager_id'";
            if(mysqli_query($con, $delete_query)) {
                $success = "Project manager deleted successfully!";
            } else {
                $error = "Error deleting project manager: " . mysqli_error($con);
            }
        }
    } else {
        $error = "Project manager not found!";
    }
}

// Handle add new manager
if(isset($_POST['add_manager'])) {
    $name = mysqli_real_escape_string($con, $_POST['name']);
    $email = mysqli_real_escape_string($con, $_POST['email']);
    $phone = mysqli_real_escape_string($con, $_POST['phone']);
    $password = mysqli_real_escape_string($con, $_POST['password']);
    $address = mysqli_real_escape_string($con, $_POST['address']);
    
    // Check if email already exists
    $check_email = mysqli_query($con, "SELECT * FROM project_managers WHERE email = '$email'");
    if($check_email && mysqli_num_rows($check_email) > 0) {
        $error = "Email address already exists!";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Handle image upload
        $image_name = '';
        if(isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $image = $_FILES['image'];
            $image_ext = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));
            $allowed_ext = array('jpg', 'jpeg', 'png', 'gif');
            
            if(in_array($image_ext, $allowed_ext)) {
                $image_name = 'pm_' . time() . '_' . uniqid() . '.' . $image_ext;
                $upload_path = "uploads/managers/" . $image_name;
                
                if(!is_dir('uploads/managers')) {
                    mkdir('uploads/managers', 0777, true);
                }
                
                if(!move_uploaded_file($image['tmp_name'], $upload_path)) {
                    $error = "Failed to upload image.";
                    $image_name = '';
                }
            } else {
                $error = "Invalid image format. Allowed: JPG, JPEG, PNG, GIF.";
            }
        }
        
        if(!isset($error)) {
            $insert_query = "INSERT INTO project_managers (name, email, phone, password, address, image, created_at) 
                             VALUES ('$name', '$email', '$phone', '$hashed_password', '$address', '$image_name', NOW())";
            
            if(mysqli_query($con, $insert_query)) {
                $success = "Project manager added successfully!";
            } else {
                $error = "Error adding project manager: " . mysqli_error($con);
            }
        }
    }
}

// Search functionality
$search = '';
$where_condition = "";

if(isset($_GET['search']) && !empty($_GET['search'])) {
    $search = mysqli_real_escape_string($con, $_GET['search']);
    $where_condition = "WHERE (pm.name LIKE '%$search%' OR pm.email LIKE '%$search%' OR pm.phone LIKE '%$search%' OR pm.address LIKE '%$search%')";
}

// Fetch all project managers with project counts if relationship is configured
if($relationship_configured) {
    $managers_query = mysqli_query($con, 
        "SELECT pm.*, COUNT(p.id) as project_count 
         FROM project_managers pm 
         LEFT JOIN projects p ON pm.id = p.project_manager_id 
         $where_condition
         GROUP BY pm.id 
         ORDER BY pm.created_at DESC");
} else {
    // For non-relationship case, use different WHERE condition
    if(!empty($where_condition)) {
        // Replace pm with project_managers table name
        $where_condition = str_replace("pm.", "", $where_condition);
        $where_condition = str_replace("WHERE (name", "WHERE (project_managers.name", $where_condition);
    } else {
        $where_condition = "WHERE 1=1";
    }
    
    $managers_query = mysqli_query($con, 
        "SELECT * FROM project_managers 
         $where_condition
         ORDER BY created_at DESC");
}

// Check if query was successful
if(!$managers_query) {
    $error = "Database error: " . mysqli_error($con);
}

// Get statistics
$total_managers = 0;
$managers_with_projects = 0;
$available_managers = 0;

if($relationship_configured) {
    $stats_query = mysqli_query($con, 
        "SELECT COUNT(*) as total, 
                COUNT(DISTINCT p.project_manager_id) as with_projects 
         FROM project_managers pm 
         LEFT JOIN projects p ON pm.id = p.project_manager_id");
    if($stats_query) {
        $stats_data = mysqli_fetch_assoc($stats_query);
        $total_managers = $stats_data['total'];
        $managers_with_projects = $stats_data['with_projects'];
        $available_managers = $total_managers - $managers_with_projects;
    }
} else {
    $stats_query = mysqli_query($con, "SELECT COUNT(*) as total FROM project_managers");
    if($stats_query) {
        $total_data = mysqli_fetch_assoc($stats_query);
        $total_managers = $total_data['total'];
        $managers_with_projects = 0;
        $available_managers = $total_managers;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Project Managers - Admin Panel</title>
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
        .action-buttons .btn {
            margin: 2px;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(0,0,0,0.02);
        }
        .manager-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #e3e6f0;
        }
        .avatar-placeholder {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2rem;
            border: 3px solid #e3e6f0;
        }
        .info-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .bg-info-light { background-color: #e3f2fd; color: #1976d2; }
        .project-count { 
            font-size: 0.75rem; 
            margin-top: 2px;
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
                            <h1 class="h3 mb-0">Manage Project Managers</h1>
                            <p class="text-muted mb-0">View and manage all project managers in the system</p>
                        </div>
                        <div class="col-md-6 text-end">
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addManagerModal">
                                <i class="fas fa-plus me-2"></i>Add New Manager
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
                    <form method="GET" action="all_managers.php">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="input-group">
                                    <input type="text" class="form-control" name="search" placeholder="Search managers by name, email, phone, or address..." value="<?php echo htmlspecialchars($search); ?>">
                                    <button class="btn btn-primary" type="submit">
                                        <i class="fas fa-search me-2"></i>Search
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <a href="all_managers.php" class="btn btn-secondary w-100">
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

    <!-- Database Relationship Status -->
    <?php if(!$relationship_configured): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>Setup Required:</strong> Project-manager relationship is not configured.
        <form method="POST" style="display: inline;">
            <button type="submit" name="setup_relationship" class="btn btn-sm btn-outline-danger ms-2">
                <i class="fas fa-cog me-1"></i>Setup Relationship Now
            </button>
        </form>
    </div>
    <?php else: ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle me-2"></i>
        <strong>Relationship Configured:</strong> Project-manager relationship is active and working.
    </div>
    <?php endif; ?>

    <!-- Managers Table -->
    <div class="row">
        <div class="col-12">
            <div class="card card-dashboard">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        All Project Managers 
                        <?php if(!$managers_query): ?>
                            (Error loading managers)
                        <?php else: ?>
                            (<?php echo mysqli_num_rows($managers_query); ?> found)
                        <?php endif; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if(!$managers_query): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Error loading project managers from database. Please check your database connection and table structure.
                            <?php if(isset($error)) echo "<br><small>Error: $error</small>"; ?>
                        </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Manager ID</th>
                                    <th>Manager</th>
                                    <th>Contact Info</th>
                                    <th>Address</th>
                                    <th>Status</th>
                                    <th>Registered</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if(mysqli_num_rows($managers_query) > 0) {
                                    while($manager = mysqli_fetch_assoc($managers_query)) {
                                        // Get first letter for avatar placeholder
                                        $avatar_letter = strtoupper(substr($manager['name'], 0, 1));
                                        ?>
                                        <tr>
                                            <td><strong>#<?php echo $manager['id']; ?></strong></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php if(!empty($manager['image'])): ?>
                                                        <img src="uploads/managers/<?php echo htmlspecialchars($manager['image']); ?>" 
                                                             alt="<?php echo htmlspecialchars($manager['name']); ?>" 
                                                             class="manager-avatar me-3"
                                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                        <div class="avatar-placeholder me-3" style="display: none;">
                                                            <?php echo $avatar_letter; ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="avatar-placeholder me-3">
                                                            <?php echo $avatar_letter; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($manager['name']); ?></strong>
                                                        <br><small class="text-muted">Manager ID: PM<?php echo str_pad($manager['id'], 4, '0', STR_PAD_LEFT); ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <i class="fas fa-envelope text-muted me-2"></i><?php echo htmlspecialchars($manager['email']); ?>
                                                    <br>
                                                    <i class="fas fa-phone text-muted me-2"></i>
                                                    <?php echo !empty($manager['phone']) ? htmlspecialchars($manager['phone']) : '<span class="text-muted">Not provided</span>'; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if(!empty($manager['address'])): ?>
                                                    <small><?php echo htmlspecialchars(substr($manager['address'], 0, 50)); ?><?php echo strlen($manager['address']) > 50 ? '...' : ''; ?></small>
                                                <?php else: ?>
                                                    <span class="text-muted">Not provided</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="info-badge bg-info-light">Active</span>
                                                <?php if($relationship_configured && isset($manager['project_count'])): ?>
                                                    <br><small class="project-count text-muted">Projects: <?php echo $manager['project_count']; ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($manager['created_at'])); ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <!-- View Manager Details -->
                                                    <a href="manager_details.php?id=<?php echo $manager['id']; ?>" class="btn btn-info btn-sm" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    
                                                    <!-- Delete Manager -->
                                                    <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $manager['id']; ?>" title="Delete Manager">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>

                                                <!-- Delete Confirmation Modal -->
                                                <div class="modal fade" id="deleteModal<?php echo $manager['id']; ?>" tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <form method="POST">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title text-danger">Confirm Deletion</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="manager_id" value="<?php echo $manager['id']; ?>">
                                                                    <p>Are you sure you want to delete the project manager:</p>
                                                                    <p><strong>"<?php echo htmlspecialchars($manager['name']); ?>"</strong>?</p>
                                                                    
                                                                    <?php if($relationship_configured && isset($manager['project_count']) && $manager['project_count'] > 0): ?>
                                                                    <div class="alert alert-danger">
                                                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                                                        <strong>Cannot Delete:</strong> This manager has <?php echo $manager['project_count']; ?> assigned project(s).
                                                                    </div>
                                                                    <?php else: ?>
                                                                    <div class="alert alert-warning">
                                                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                                                        <strong>Warning:</strong> This action cannot be undone.
                                                                    </div>
                                                                    <?php endif; ?>
                                                                    
                                                                    <p class="text-danger"><small>All manager data will be permanently deleted.</small></p>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                    <?php if(!$relationship_configured || (isset($manager['project_count']) && $manager['project_count'] == 0)): ?>
                                                                    <button type="submit" name="delete_manager" class="btn btn-danger">Delete Manager</button>
                                                                    <?php else: ?>
                                                                    <button type="button" class="btn btn-danger" disabled>Cannot Delete (Has Projects)</button>
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
                                    echo '<tr><td colspan="7" class="text-center py-4 text-muted">';
                                    if(!empty($search)) {
                                        echo '<i class="fas fa-search fa-3x mb-3"></i><br>No project managers found matching your search criteria.';
                                    } else {
                                        echo '<i class="fas fa-user-tie fa-3x mb-3"></i><br>No project managers found in the system.';
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
        <div class="col-md-4">
            <div class="card card-dashboard text-white bg-primary text-center">
                <div class="card-body">
                    <h3><?php echo $total_managers; ?></h3>
                    <p>Total Managers</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-dashboard text-white bg-success text-center">
                <div class="card-body">
                    <h3><?php echo $managers_with_projects; ?></h3>
                    <p>Managers with Projects</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-dashboard text-white bg-info text-center">
                <div class="card-body">
                    <h3><?php echo $available_managers; ?></h3>
                    <p>Available Managers</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Manager Modal -->
<div class="modal fade" id="addManagerModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Project Manager</h5>
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
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Profile Image</label>
                                <input type="file" class="form-control" name="image" accept="image/*">
                                <small class="text-muted">Optional: JPG, JPEG, PNG, GIF (Max: 2MB)</small>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea class="form-control" name="address" rows="3" placeholder="Full address including street, city, state, and zip code"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_manager" class="btn btn-primary">Add Manager</button>
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