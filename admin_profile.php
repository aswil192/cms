<?php
// admin_profile.php
// Admin profile management page

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

// Get current admin data
$admin_id = $_SESSION['user_id'];
$admin_query = mysqli_query($con, "SELECT * FROM users WHERE id = '$admin_id'");
$admin_data = mysqli_fetch_assoc($admin_query);

// Initialize variables
$errors = [];
$success = '';

// Handle profile update
if(isset($_POST['update_profile'])) {
    $name = mysqli_real_escape_string($con, $_POST['name']);
    $email = mysqli_real_escape_string($con, $_POST['email']);
    $phone = mysqli_real_escape_string($con, $_POST['phone']);
    $address = mysqli_real_escape_string($con, $_POST['address']);

    // Validation
    if(empty($name)) {
        $errors[] = "Name is required";
    }
    if(empty($email)) {
        $errors[] = "Email is required";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address";
    }

    // Check if email already exists (excluding current admin)
    $check_email = mysqli_query($con, "SELECT id FROM users WHERE email = '$email' AND id != '$admin_id'");
    if(mysqli_num_rows($check_email) > 0) {
        $errors[] = "Email address is already registered";
    }

    // Handle profile picture upload
    $profile_picture = $admin_data['image']; // Keep existing picture by default
    
    if(isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        $file_name = $_FILES['profile_picture']['name'];
        $file_tmp = $_FILES['profile_picture']['tmp_name'];
        $file_size = $_FILES['profile_picture']['size'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Validate file type
        if(!in_array($file_ext, $allowed_types)) {
            $errors[] = "Only JPG, JPEG, PNG & GIF files are allowed";
        }
        
        // Validate file size (2MB max)
        if($file_size > 2097152) {
            $errors[] = "File size must be less than 2MB";
        }
        
        if(empty($errors)) {
            // Generate unique filename
            $new_filename = "admin_" . $admin_id . "_" . time() . "." . $file_ext;
            $upload_path = "uploads/profiles/" . $new_filename;
            
            // Create uploads directory if it doesn't exist
            if(!is_dir('uploads/profiles')) {
                mkdir('uploads/profiles', 0777, true);
            }
            
            if(move_uploaded_file($file_tmp, $upload_path)) {
                // Delete old profile picture if it exists
                if(!empty($admin_data['image']) && file_exists($admin_data['image'])) {
                    unlink($admin_data['image']);
                }
                $profile_picture = $upload_path;
            } else {
                $errors[] = "Failed to upload profile picture";
            }
        }
    }

    // If no errors, update profile
    if(empty($errors)) {
        $update_query = "UPDATE users SET 
                        name = '$name', 
                        email = '$email', 
                        phone = '$phone', 
                        address = '$address', 
                        image = '$profile_picture' 
                        WHERE id = '$admin_id'";
        
        if(mysqli_query($con, $update_query)) {
            $success = "Profile updated successfully!";
            // Refresh admin data
            $admin_query = mysqli_query($con, "SELECT * FROM users WHERE id = '$admin_id'");
            $admin_data = mysqli_fetch_assoc($admin_query);
        } else {
            $errors[] = "Error updating profile: " . mysqli_error($con);
        }
    }
}

// Handle password change
if(isset($_POST['change_password'])) {
    $current_password = mysqli_real_escape_string($con, $_POST['current_password']);
    $new_password = mysqli_real_escape_string($con, $_POST['new_password']);
    $confirm_password = mysqli_real_escape_string($con, $_POST['confirm_password']);

    // Validation
    if(empty($current_password)) {
        $errors[] = "Current password is required";
    }
    if(empty($new_password)) {
        $errors[] = "New password is required";
    } elseif(strlen($new_password) < 6) {
        $errors[] = "New password must be at least 6 characters long";
    }
    if($new_password !== $confirm_password) {
        $errors[] = "New passwords do not match";
    }

    // Verify current password
    if(empty($errors)) {
        if(!password_verify($current_password, $admin_data['password'])) {
            $errors[] = "Current password is incorrect";
        }
    }

    // If no errors, update password
    if(empty($errors)) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update_password_query = "UPDATE users SET password = '$hashed_password' WHERE id = '$admin_id'";
        
        if(mysqli_query($con, $update_password_query)) {
            $success = "Password changed successfully!";
        } else {
            $errors[] = "Error changing password: " . mysqli_error($con);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile - Construction Management</title>
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
        .profile-picture {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid #e3e6f0;
            background-color: #f8f9fa;
        }
        .profile-picture-placeholder {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
            border: 5px solid #e3e6f0;
        }
        .profile-section {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .nav-pills .nav-link.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .form-section {
            margin-bottom: 30px;
        }
        .stats-card {
            text-align: center;
            padding: 20px;
            border-radius: 10px;
            color: white;
            margin-bottom: 15px;
        }
        .stats-card i {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        .password-strength {
            height: 5px;
            border-radius: 5px;
            margin-top: 5px;
            transition: all 0.3s ease;
        }
        .strength-weak { background-color: #dc3545; width: 25%; }
        .strength-medium { background-color: #ffc107; width: 50%; }
        .strength-strong { background-color: #28a745; width: 75%; }
        .strength-very-strong { background-color: #20c997; width: 100%; }
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
                            <h1 class="h3 mb-0">Admin Profile</h1>
                            <p class="text-muted mb-0">Manage your account settings and preferences</p>
                        </div>
                        <div class="col-md-6 text-end">
                            <span class="badge bg-success">
                                <i class="fas fa-user-shield me-1"></i>Administrator
                            </span>
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

    <div class="row">
        <!-- Left Sidebar - Profile Overview -->
        <div class="col-md-4">
            <div class="card card-dashboard">
                <div class="card-body text-center">
                    <!-- Profile Picture -->
                    <div class="mb-4">
                        <?php if(!empty($admin_data['image'])): ?>
                            <img src="<?php echo htmlspecialchars($admin_data['image']); ?>" alt="Profile Picture" class="profile-picture">
                        <?php else: ?>
                            <div class="profile-picture-placeholder">
                                <i class="fas fa-user-cog"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <h4><?php echo htmlspecialchars($admin_data['name']); ?></h4>
                    <p class="text-muted">Administrator</p>
                    <p class="text-muted"><?php echo htmlspecialchars($admin_data['email']); ?></p>
                    
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="stats-card bg-primary">
                                <i class="fas fa-calendar-alt"></i>
                                <h5>Member Since</h5>
                                <p><?php echo date('M Y', strtotime($admin_data['created_at'])); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="card card-dashboard mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Quick Stats</h5>
                </div>
                <div class="card-body">
                    <?php
                    // Get quick stats
                    $total_projects = mysqli_query($con, "SELECT COUNT(*) as total FROM projects");
                    $projects_data = mysqli_fetch_assoc($total_projects);
                    
                    $total_users = mysqli_query($con, "SELECT COUNT(*) as total FROM users WHERE user_type = 'client'");
                    $users_data = mysqli_fetch_assoc($total_users);
                    
                    $total_managers = mysqli_query($con, "SELECT COUNT(*) as total FROM project_managers");
                    $managers_data = mysqli_fetch_assoc($total_managers);
                    
                    $pending_resources = mysqli_query($con, "SELECT COUNT(*) as total FROM resources WHERE status = 'pending'");
                    $resources_data = mysqli_fetch_assoc($pending_resources);
                    ?>
                    <div class="row text-center">
                        <div class="col-6 mb-3">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h5 class="text-primary"><?php echo $projects_data['total']; ?></h5>
                                    <small>Total Projects</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h5 class="text-success"><?php echo $users_data['total']; ?></h5>
                                    <small>Clients</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h5 class="text-info"><?php echo $managers_data['total']; ?></h5>
                                    <small>Managers</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h5 class="text-warning"><?php echo $resources_data['total']; ?></h5>
                                    <small>Pending Resources</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Content - Profile Management -->
        <div class="col-md-8">
            <!-- Navigation Tabs -->
            <div class="card card-dashboard mb-4">
                <div class="card-body">
                    <ul class="nav nav-pills mb-3" id="profileTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="profile-tab" data-bs-toggle="pill" data-bs-target="#profile" type="button" role="tab">
                                <i class="fas fa-user-edit me-2"></i>Profile Information
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="password-tab" data-bs-toggle="pill" data-bs-target="#password" type="button" role="tab">
                                <i class="fas fa-lock me-2"></i>Change Password
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="security-tab" data-bs-toggle="pill" data-bs-target="#security" type="button" role="tab">
                                <i class="fas fa-shield-alt me-2"></i>Security
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="profileTabsContent">
                        <!-- Profile Information Tab -->
                        <div class="tab-pane fade show active" id="profile" role="tabpanel">
                            <form method="POST" action="admin_profile.php" enctype="multipart/form-data">
                                <div class="form-section">
                                    <h5 class="mb-3"><i class="fas fa-id-card me-2"></i>Personal Information</h5>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($admin_data['name']); ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Email Address <span class="text-danger">*</span></label>
                                                <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($admin_data['email']); ?>" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Phone Number</label>
                                                <input type="tel" class="form-control" name="phone" value="<?php echo htmlspecialchars($admin_data['phone']); ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Profile Picture</label>
                                                <input type="file" class="form-control" name="profile_picture" accept="image/*">
                                                <div class="form-text">Max file size: 2MB. Allowed types: JPG, PNG, GIF</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="mb-3">
                                                <label class="form-label">Address</label>
                                                <textarea class="form-control" name="address" rows="3"><?php echo htmlspecialchars($admin_data['address']); ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <div>
                                        <small class="text-muted">Last updated: <?php echo date('M j, Y g:i A', strtotime($admin_data['created_at'])); ?></small>
                                    </div>
                                    <button type="submit" name="update_profile" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Update Profile
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Change Password Tab -->
                        <div class="tab-pane fade" id="password" role="tabpanel">
                            <form method="POST" action="admin_profile.php">
                                <div class="form-section">
                                    <h5 class="mb-3"><i class="fas fa-key me-2"></i>Change Password</h5>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Current Password <span class="text-danger">*</span></label>
                                                <input type="password" class="form-control" name="current_password" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">New Password <span class="text-danger">*</span></label>
                                                <input type="password" class="form-control" name="new_password" id="newPassword" required>
                                                <div class="password-strength" id="passwordStrength"></div>
                                                <div class="form-text">Password must be at least 6 characters long</div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                                                <input type="password" class="form-control" name="confirm_password" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Password Requirements:</strong> Minimum 6 characters with a mix of letters and numbers for better security.
                                </div>

                                <div class="text-end">
                                    <button type="submit" name="change_password" class="btn btn-primary">
                                        <i class="fas fa-lock me-2"></i>Change Password
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Security Tab -->
                        <div class="tab-pane fade" id="security" role="tabpanel">
                            <div class="form-section">
                                <h5 class="mb-3"><i class="fas fa-shield-alt me-2"></i>Security Settings</h5>
                                
                                <div class="row">
                                    <div class="col-12">
                                        <div class="card bg-light mb-3">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <h6 class="mb-1">Two-Factor Authentication</h6>
                                                        <p class="text-muted mb-0">Add an extra layer of security to your account</p>
                                                    </div>
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox" disabled>
                                                        <label class="form-check-label text-muted">Coming Soon</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-12">
                                        <div class="card bg-light mb-3">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <h6 class="mb-1">Login Sessions</h6>
                                                        <p class="text-muted mb-0">Manage your active login sessions</p>
                                                    </div>
                                                    <button class="btn btn-outline-primary btn-sm" disabled>View Sessions</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-12">
                                        <div class="card bg-light">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <h6 class="mb-1">Account Activity</h6>
                                                        <p class="text-muted mb-0">Review recent account activity</p>
                                                    </div>
                                                    <button class="btn btn-outline-primary btn-sm" disabled>View Activity</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-warning mt-4">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Security Tip:</strong> Always use a strong, unique password and avoid using the same password across multiple sites.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Account Information -->
            <div class="card card-dashboard">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-info-circle me-2"></i>Account Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <th>User ID:</th>
                                    <td>#<?php echo $admin_data['id']; ?></td>
                                </tr>
                                <tr>
                                    <th>Account Type:</th>
                                    <td><span class="badge bg-success">Administrator</span></td>
                                </tr>
                                <tr>
                                    <th>Registration Date:</th>
                                    <td><?php echo date('F j, Y', strtotime($admin_data['created_at'])); ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <th>Last Login:</th>
                                    <td><?php echo isset($_SESSION['last_login']) ? date('F j, Y g:i A', $_SESSION['last_login']) : 'N/A'; ?></td>
                                </tr>
                                <tr>
                                    <th>Account Status:</th>
                                    <td><span class="badge bg-success">Active</span></td>
                                </tr>
                                <tr>
                                    <th>Profile Completion:</th>
                                    <td>
                                        <div class="progress" style="height: 8px;">
                                            <?php
                                            $completion = 0;
                                            if(!empty($admin_data['name'])) $completion += 25;
                                            if(!empty($admin_data['email'])) $completion += 25;
                                            if(!empty($admin_data['phone'])) $completion += 25;
                                            if(!empty($admin_data['address'])) $completion += 25;
                                            ?>
                                            <div class="progress-bar bg-success" style="width: <?php echo $completion; ?>%"></div>
                                        </div>
                                        <small class="text-muted"><?php echo $completion; ?>% Complete</small>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Password strength indicator
    const passwordInput = document.getElementById('newPassword');
    const strengthBar = document.getElementById('passwordStrength');
    
    passwordInput.addEventListener('input', function() {
        const password = this.value;
        let strength = 0;
        
        // Length check
        if (password.length >= 6) strength += 1;
        if (password.length >= 8) strength += 1;
        
        // Character variety checks
        if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength += 1;
        if (password.match(/\d/)) strength += 1;
        if (password.match(/[^a-zA-Z\d]/)) strength += 1;
        
        // Update strength bar
        strengthBar.className = 'password-strength';
        if (password.length === 0) {
            strengthBar.style.width = '0%';
        } else if (strength <= 2) {
            strengthBar.classList.add('strength-weak');
        } else if (strength <= 3) {
            strengthBar.classList.add('strength-medium');
        } else if (strength <= 4) {
            strengthBar.classList.add('strength-strong');
        } else {
            strengthBar.classList.add('strength-very-strong');
        }
    });

    // Auto-close alerts after 5 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);

    // Form submission handling
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            const submitButton = this.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
            }
        });
    });
});
</script>

<?php include("footer.php"); ?>
</body>
</html>