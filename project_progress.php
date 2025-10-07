<?php
session_start();

// Redirect to login if not authenticated as project manager
if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'project_manager') {
    header("Location: login.php");
    exit();
}

include("connection.php");
$pm_id = $_SESSION['uid'];
?>

<?php include("menu.php"); ?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card card-dashboard">
                <div class="card-body">
                    <h1 class="h3 mb-0">Project Progress Tracking</h1>
                    <p class="text-muted mb-0">Monitor and update project progress</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Progress Cards -->
    <div class="row">
        <?php
        // Corrected query with users table as clients
        $projects_query = mysqli_query($con, 
            "SELECT p.*, u.name as client_name,
             (SELECT progress_percentage FROM progress_updates 
              WHERE project_id = p.id ORDER BY update_date DESC LIMIT 1) as current_progress
             FROM projects p 
             INNER JOIN project_manager_assignments pma ON p.id = pma.project_id 
             INNER JOIN users u ON p.client_id = u.id
             WHERE pma.project_manager_id = '$pm_id'");
        
        if($projects_query && mysqli_num_rows($projects_query) > 0) {
            while($project = mysqli_fetch_assoc($projects_query)) {
                $progress = $project['current_progress'] ? $project['current_progress'] : 0;
                $days_left = ceil((strtotime($project['end_date']) - time()) / (60 * 60 * 24));
                $days_left = $days_left > 0 ? $days_left : 0;
                
                // Progress bar color based on percentage
                $progress_class = '';
                if ($progress >= 90) {
                    $progress_class = 'bg-success';
                } elseif ($progress >= 50) {
                    $progress_class = 'bg-primary';
                } else {
                    $progress_class = 'bg-warning';
                }
                
                // Status badge
                $status_class = '';
                if($project['status'] == 'Completed') {
                    $status_class = 'badge bg-success';
                } elseif($project['status'] == 'In Progress') {
                    $status_class = 'badge bg-primary';
                } else {
                    $status_class = 'badge bg-warning';
                }
                ?>
                <div class="col-xl-4 col-md-6 mb-4">
                    <div class="card card-dashboard h-100">
                        <div class="card-header">
                            <h5 class="card-title mb-0"><?php echo $project['name']; ?></h5>
                            <small class="text-muted">Client: <?php echo $project['client_name']; ?></small>
                        </div>
                        <div class="card-body">
                            <!-- Progress Bar -->
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-sm">Progress</span>
                                    <span class="text-sm fw-bold"><?php echo $progress; ?>%</span>
                                </div>
                                <div class="progress" style="height: 10px;">
                                    <div class="progress-bar <?php echo $progress_class; ?>" role="progressbar" 
                                         style="width: <?php echo $progress; ?>%" 
                                         aria-valuenow="<?php echo $progress; ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Project Details -->
                            <div class="project-details">
                                <div class="row text-center">
                                    <div class="col-6">
                                        <div class="border-end">
                                            <small class="text-muted d-block">Status</small>
                                            <span class="<?php echo $status_class; ?>"><?php echo $project['status']; ?></span>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted d-block">Days Left</small>
                                        <strong class="<?php echo $days_left < 7 ? 'text-danger' : 'text-dark'; ?>">
                                            <?php echo $days_left; ?> days
                                        </strong>
                                    </div>
                                </div>
                                
                                <div class="mt-3">
                                    <small class="text-muted d-block">Timeline</small>
                                    <div class="d-flex justify-content-between">
                                        <small>Start: <?php echo date('M d, Y', strtotime($project['start_date'])); ?></small>
                                        <small>End: <?php echo date('M d, Y', strtotime($project['end_date'])); ?></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent">
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="update_progress.php?project_id=<?php echo $project['id']; ?>" 
                                   class="btn btn-primary btn-sm me-md-2" 
                                   data-bs-toggle="tooltip" title="Update Progress">
                                    <i class="fas fa-edit me-1"></i>Update
                                </a>
                                <a href="progress_history.php?project_id=<?php echo $project['id']; ?>" 
                                   class="btn btn-outline-info btn-sm" 
                                   data-bs-toggle="tooltip" title="View History">
                                    <i class="fas fa-history me-1"></i>History
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
            }
        } else {
            echo '<div class="col-12">
                    <div class="card card-dashboard">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">No Projects Assigned</h4>
                            <p class="text-muted">You don\'t have any projects to track yet.</p>
                        </div>
                    </div>
                  </div>';
        }
        ?>
    </div>

    <!-- Quick Stats -->
    <div class="row mt-4">
        <div class="col-md-3">
            <div class="card card-dashboard">
                <div class="card-body text-center">
                    <?php
                    $total_query = mysqli_query($con, 
                        "SELECT COUNT(*) as total FROM project_manager_assignments WHERE project_manager_id = '$pm_id'");
                    if($total_query) {
                        $total = mysqli_fetch_assoc($total_query);
                        echo '<h3 class="text-primary">'.$total['total'].'</h3>';
                    } else {
                        echo '<h3 class="text-primary">0</h3>';
                    }
                    ?>
                    <p class="text-muted mb-0">Total Projects</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-dashboard">
                <div class="card-body text-center">
                    <?php
                    $avg_progress_query = mysqli_query($con, 
                        "SELECT AVG(pu.progress_percentage) as avg_progress
                         FROM progress_updates pu
                         INNER JOIN project_manager_assignments pma ON pu.project_id = pma.project_id
                         WHERE pma.project_manager_id = '$pm_id'");
                    if($avg_progress_query) {
                        $avg_progress = mysqli_fetch_assoc($avg_progress_query);
                        $avg_progress = round($avg_progress['avg_progress'] ?: 0);
                        echo '<h3 class="text-success">'.$avg_progress.'%</h3>';
                    } else {
                        echo '<h3 class="text-success">0%</h3>';
                    }
                    ?>
                    <p class="text-muted mb-0">Avg Progress</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-dashboard">
                <div class="card-body text-center">
                    <?php
                    $behind_query = mysqli_query($con, 
                        "SELECT COUNT(*) as behind FROM projects p
                         INNER JOIN project_manager_assignments pma ON p.id = pma.project_id
                         WHERE pma.project_manager_id = '$pm_id'
                         AND p.status = 'In Progress'
                         AND DATEDIFF(p.end_date, CURDATE()) < 7");
                    if($behind_query) {
                        $behind = mysqli_fetch_assoc($behind_query);
                        echo '<h3 class="text-warning">'.$behind['behind'].'</h3>';
                    } else {
                        echo '<h3 class="text-warning">0</h3>';
                    }
                    ?>
                    <p class="text-muted mb-0">Near Deadline</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-dashboard">
                <div class="card-body text-center">
                    <?php
                    $completed_query = mysqli_query($con, 
                        "SELECT COUNT(*) as completed FROM project_manager_assignments pma 
                         INNER JOIN projects p ON pma.project_id = p.id 
                         WHERE pma.project_manager_id = '$pm_id' AND p.status = 'Completed'");
                    if($completed_query) {
                        $completed = mysqli_fetch_assoc($completed_query);
                        echo '<h3 class="text-danger">'.$completed['completed'].'</h3>';
                    } else {
                        echo '<h3 class="text-danger">0</h3>';
                    }
                    ?>
                    <p class="text-muted mb-0">Completed</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include("footer.php"); ?>