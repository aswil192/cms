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

// Handle both user_id and uid for compatibility
if(isset($_SESSION['user_id'])) {
    $manager_id = $_SESSION['user_id'];
} elseif(isset($_SESSION['uid'])) {
    $manager_id = $_SESSION['uid'];
} else {
    header("Location: login.php");
    exit();
}

include("connection.php");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Projects - Project Manager Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="pm_dashboard.php">
            <i class="fas fa-tachometer-alt me-2"></i>Project Manager Panel
        </a>
    </div>
</nav>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">My Assigned Projects</h5>
                </div>
                <div class="card-body">
                    <?php
                    // Fetch all projects assigned to this manager
                    $projects_query = mysqli_query($con, 
                        "SELECT p.*, u.name as client_name, u.email as client_email
                         FROM projects p 
                         LEFT JOIN users u ON p.client_id = u.id 
                         WHERE p.project_manager_id = '$manager_id'
                         ORDER BY p.created_at DESC");
                    
                    if(!$projects_query) {
                        echo "<div class='alert alert-danger'>Database error: " . mysqli_error($con) . "</div>";
                    } elseif(mysqli_num_rows($projects_query) == 0) {
                        echo "<div class='alert alert-warning text-center'>
                                <i class='fas fa-folder-open fa-3x mb-3'></i><br>
                                <h4>No Projects Assigned</h4>
                                <p>You don't have any projects assigned to you yet.</p>
                              </div>";
                    } else {
                        echo "<div class='table-responsive'>
                                <table class='table table-striped'>
                                    <thead>
                                        <tr>
                                            <th>Project ID</th>
                                            <th>Project Name</th>
                                            <th>Client</th>
                                            <th>Progress</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>";
                        
                        while($project = mysqli_fetch_assoc($projects_query)) {
                            $status_badge = '';
                            switch($project['status']) {
                                case 'pending': $status_badge = 'bg-warning'; break;
                                case 'in_progress': $status_badge = 'bg-primary'; break;
                                case 'completed': $status_badge = 'bg-success'; break;
                                case 'on_hold': $status_badge = 'bg-danger'; break;
                                default: $status_badge = 'bg-secondary';
                            }
                            
                            echo "<tr>
                                    <td>#{$project['id']}</td>
                                    <td>{$project['project_name']}</td>
                                    <td>{$project['client_name']}</td>
                                    <td>
                                        <div class='progress' style='height: 20px;'>
                                            <div class='progress-bar' role='progressbar' 
                                                 style='width: {$project['progress']}%' 
                                                 aria-valuenow='{$project['progress']}' 
                                                 aria-valuemin='0' aria-valuemax='100'>
                                                {$project['progress']}%
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class='badge {$status_badge}'>" . ucfirst(str_replace('_', ' ', $project['status'])) . "</span></td>
                                    <td>
                                        <a href='update_progress.php?id={$project['id']}' class='btn btn-primary btn-sm'>
                                            <i class='fas fa-edit me-1'></i>Update Progress
                                        </a>
                                    </td>
                                  </tr>";
                        }
                        
                        echo "</tbody></table></div>";
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>