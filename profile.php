<?php
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

// Use the session UID directly
$pm_id = isset($_SESSION['uid']) ? $_SESSION['uid'] : null;

if(!$pm_id) {
    die("Project manager ID not found in session. Please login again.");
}

// Fetch project manager details from the database using session UID
$pm_query = mysqli_query($con, "SELECT * FROM project_managers WHERE id = '$pm_id'");

if(!$pm_query) {
    die("Database query failed: " . mysqli_error($con));
}

if(mysqli_num_rows($pm_query) == 0) {
    die("Project manager with ID $pm_id not found in database.");
}

$pm_data = mysqli_fetch_assoc($pm_query);

// Handle profile update
if(isset($_POST['update_profile'])) {
    $name = mysqli_real_escape_string($con, $_POST['name']);
    $email = mysqli_real_escape_string($con, $_POST['email']);
    $phone = mysqli_real_escape_string($con, $_POST['phone']);
    $address = mysqli_real_escape_string($con, $_POST['address']);
    
    // Handle file upload (profile picture)
    $profile_picture = isset($pm_data['image']) ? $pm_data['image'] : '';
    if(isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $target_dir = "uploads/pm_profiles/";
        if(!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
        $new_filename = "pm_" . $pm_id . "_" . time() . "." . $file_extension;
        $target_file = $target_dir . $new_filename;
        
        // Check file size (max 2MB)
        if($_FILES['profile_picture']['size'] > 2000000) {
            $error = "File size must be less than 2MB";
        } elseif(move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_file)) {
            // Delete old profile picture if exists
            if($profile_picture && file_exists($target_dir . $profile_picture)) {
                unlink($target_dir . $profile_picture);
            }
            $profile_picture = $new_filename;
        }
    }
    
    // Update password if provided
    $password_update = "";
    if(!empty($_POST['new_password'])) {
        if($_POST['new_password'] === $_POST['confirm_password']) {
            $plain_password = mysqli_real_escape_string($con, $_POST['new_password']);
            $password_update = ", password = '$plain_password'";
        } else {
            $error = "Passwords do not match";
        }
    }
    
    if(!isset($error)) {
        $update_query = "UPDATE project_managers SET 
                        name = '$name', 
                        email = '$email', 
                        phone = '$phone', 
                        address = '$address'";
        
        // Add image update if changed
        if(isset($profile_picture)) {
            $update_query .= ", image = '$profile_picture'";
        }
        
        // Add password update if changed
        $update_query .= " $password_update WHERE id = '$pm_id'";
        
        if(mysqli_query($con, $update_query)) {
            $success = "Profile updated successfully!";
            // Refresh data from database
            $pm_query = mysqli_query($con, "SELECT * FROM project_managers WHERE id = '$pm_id'");
            $pm_data = mysqli_fetch_assoc($pm_query);
            
            // Update session username if name was changed
            $_SESSION['username'] = $name;
        } else {
            $error = "Error updating profile: " . mysqli_error($con);
        }
    }
}

// Handle password update separately
if(isset($_POST['update_password'])) {
    if(!empty($_POST['new_password'])) {
        if($_POST['new_password'] === $_POST['confirm_password']) {
            $plain_password = mysqli_real_escape_string($con, $_POST['new_password']);
            $update_password_query = "UPDATE project_managers SET password = '$plain_password' WHERE id = '$pm_id'";
            
            if(mysqli_query($con, $update_password_query)) {
                $success = "Password updated successfully!";
            } else {
                $error = "Error updating password: " . mysqli_error($con);
            }
        } else {
            $error = "Passwords do not match";
        }
    } else {
        $error = "Please enter a new password";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Project Manager</title>
    <style>
        /* Simple CSS without transitions or GPU optimizations */
        body {
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
        }
        .container-fluid {
            padding: 20px;
        }
        .card-dashboard {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            border: 1px solid #e3e6f0;
        }
        .card-header {
            background: #f8f9fa;
            border-bottom: 1px solid #e3e6f0;
            padding: 15px 20px;
        }
        .card-body {
            padding: 20px;
        }
        img {
            display: block;
            max-width: 100%;
            height: auto;
        }
    </style>
</head>
<body>
<?php include("menu.php"); ?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card card-dashboard">
                <div class="card-body">
                    <h1 class="h3 mb-0">My Profile</h1>
                    <p class="text-muted mb-0">Manage your account information and settings</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Profile Header -->
        <div class="col-md-12 mb-4">
            <div class="card card-dashboard">
                <div class="card-body text-center">
                    <?php
                    $profile_image = isset($pm_data['image']) && !empty($pm_data['image']) ? 
                        "uploads/pm_profiles/" . $pm_data['image'] : 
                        "img/default-avatar.png";
                    ?>
                    <div class="d-flex justify-content-center mb-3">
                        <img src="<?php echo $profile_image; ?>" 
                             class="rounded-circle" 
                             style="width: 150px; height: 150px; object-fit: cover;"
                             onerror="this.src='img/default-avatar.png'"
                             alt="Profile Picture">
                    </div>
                    <h2 class="mb-2"><?php echo htmlspecialchars($pm_data['name']); ?></h2>
                    <p class="lead text-muted mb-1">Project Manager</p>
                    <p class="text-muted mb-0">Manager ID: PM<?php echo str_pad($pm_data['id'], 4, '0', STR_PAD_LEFT); ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Personal Information -->
        <div class="col-md-8">
            <div class="card card-dashboard">
                <div class="card-header">
                    <h5 class="card-title mb-0">Personal Information</h5>
                </div>
                <div class="card-body">
                    <?php if(isset($success)): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <?php echo $success; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if(isset($error)): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <?php echo $error; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Full Name *</label>
                                    <input type="text" class="form-control" name="name" 
                                           value="<?php echo htmlspecialchars($pm_data['name']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Email Address *</label>
                                    <input type="email" class="form-control" name="email" 
                                           value="<?php echo htmlspecialchars($pm_data['email']); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Phone Number</label>
                                    <input type="text" class="form-control" name="phone" 
                                           value="<?php echo htmlspecialchars($pm_data['phone']); ?>" 
                                           placeholder="Enter phone number">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Account Created</label>
                                    <input type="text" class="form-control" 
                                           value="<?php echo date('F j, Y g:i A', strtotime($pm_data['created_at'])); ?>" 
                                           disabled readonly>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" name="address" rows="3" 
                                      placeholder="Enter your complete address"><?php echo htmlspecialchars($pm_data['address']); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Profile Picture</label>
                            <input type="file" class="form-control" name="profile_picture" accept="image/*">
                            <div class="form-text">Max file size: 2MB (JPEG, PNG, JPG, GIF)</div>
                        </div>
                        
                        <div class="mb-3">
                            <button type="submit" name="update_profile" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Update Profile
                            </button>
                            <a href="pm_dashboard.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Change Password -->
            <div class="card card-dashboard mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Change Password</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">New Password</label>
                                    <input type="password" class="form-control" name="new_password" 
                                           placeholder="Enter new password" minlength="6">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Confirm Password</label>
                                    <input type="password" class="form-control" name="confirm_password" 
                                           placeholder="Confirm new password">
                                </div>
                            </div>
                        </div>
                        <button type="submit" name="update_password" class="btn btn-warning">
                            <i class="fas fa-key me-2"></i>Change Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Statistics Sidebar -->
        <div class="col-md-4">
            <!-- Account Summary -->
            <div class="card card-dashboard mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0">Account Summary</h6>
                </div>
                <div class="card-body">
                    <p><strong>Manager ID:</strong><br>PM<?php echo str_pad($pm_data['id'], 4, '0', STR_PAD_LEFT); ?></p>
                    <p><strong>Email:</strong><br><?php echo htmlspecialchars($pm_data['email']); ?></p>
                    <p><strong>Phone:</strong><br><?php echo $pm_data['phone'] ? htmlspecialchars($pm_data['phone']) : 'Not provided'; ?></p>
                    <p><strong>Member Since:</strong><br><?php echo date('M d, Y', strtotime($pm_data['created_at'])); ?></p>
                    <p><strong>Status:</strong><br><span class="badge bg-success">Active</span></p>
                </div>
            </div>
            
            <!-- Project Statistics -->
            <div class="card card-dashboard mb-4">
                <div class="card-header">
                    <h6 class="card-title mb-0">Project Statistics</h6>
                </div>
                <div class="card-body">
                    <?php
                    // Get project statistics from database
                    $stats_query = mysqli_query($con, 
                        "SELECT 
                            COUNT(*) as total_projects,
                            SUM(CASE WHEN p.status = 'Completed' THEN 1 ELSE 0 END) as completed,
                            SUM(CASE WHEN p.status = 'In Progress' THEN 1 ELSE 0 END) as in_progress,
                            SUM(CASE WHEN p.status = 'Pending' THEN 1 ELSE 0 END) as pending
                         FROM project_manager_assignments pma 
                         INNER JOIN projects p ON pma.project_id = p.id 
                         WHERE pma.project_manager_id = '$pm_id'");
                    
                    if($stats_query && mysqli_num_rows($stats_query) > 0) {
                        $stats = mysqli_fetch_assoc($stats_query);
                        ?>
                        <p><strong>Total Projects:</strong><br><?php echo $stats['total_projects']; ?></p>
                        <p><strong>Completed:</strong><br><?php echo $stats['completed']; ?></p>
                        <p><strong>In Progress:</strong><br><?php echo $stats['in_progress']; ?></p>
                        <p><strong>Pending:</strong><br><?php echo $stats['pending']; ?></p>
                        <?php
                    } else {
                        echo '<p class="text-muted">No project assignments yet</p>';
                    }
                    ?>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="card card-dashboard">
                <div class="card-header">
                    <h6 class="card-title mb-0">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="assigned_projects.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-project-diagram me-2"></i>My Projects
                        </a>
                        <a href="project_progress.php" class="btn btn-success btn-sm">
                            <i class="fas fa-tasks me-2"></i>Progress Updates
                        </a>
                        <a href="clients.php" class="btn btn-info btn-sm">
                            <i class="fas fa-users me-2"></i>My Clients
                        </a>
                        <a href="resources.php" class="btn btn-warning btn-sm">
                            <i class="fas fa-tools me-2"></i>Resources
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Simple JavaScript without heavy optimizations
document.addEventListener('DOMContentLoaded', function() {
    // Basic password validation
    const newPassword = document.querySelector('input[name="new_password"]');
    const confirmPassword = document.querySelector('input[name="confirm_password"]');
    
    function validatePassword() {
        if(newPassword && confirmPassword && newPassword.value !== confirmPassword.value) {
            confirmPassword.setCustomValidity("Passwords do not match");
        } else if(confirmPassword) {
            confirmPassword.setCustomValidity("");
        }
    }
    
    if(newPassword && confirmPassword) {
        newPassword.addEventListener('input', validatePassword);
        confirmPassword.addEventListener('input', validatePassword);
    }
    
    // Basic image validation
    const profilePictureInput = document.querySelector('input[name="profile_picture"]');
    if(profilePictureInput) {
        profilePictureInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if(file) {
                // Validate file type
                const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
                if(!validTypes.includes(file.type)) {
                    alert('Please select a valid image file (JPEG, PNG, GIF)');
                    this.value = '';
                    return;
                }
                
                // Validate file size (2MB)
                if(file.size > 2000000) {
                    alert('File size must be less than 2MB');
                    this.value = '';
                    return;
                }
            }
        });
    }
    
    // Prevent form resubmission on page refresh
    if(window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
});
</script>

<?php include("footer.php"); ?>
</body>
</html>