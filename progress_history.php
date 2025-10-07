<?php
session_start();

// Redirect to login if not authenticated as project manager
if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'project_manager') {
    header("Location: login.php");
    exit();
}

include("connection.php");

// Check if project_id is provided
if(!isset($_GET['project_id']) || empty($_GET['project_id'])) {
    header("Location: project_progress.php");
    exit();
}

$project_id = mysqli_real_escape_string($con, $_GET['project_id']);
$pm_id = $_SESSION['uid'];

// Verify that the project belongs to this project manager and get project details
$project_query = mysqli_query($con, 
    "SELECT p.*, u.name as client_name,
     (SELECT progress_percentage FROM progress_updates 
      WHERE project_id = p.id ORDER BY update_date DESC LIMIT 1) as current_progress
     FROM projects p 
     INNER JOIN project_manager_assignments pma ON p.id = pma.project_id 
     INNER JOIN users u ON p.client_id = u.id
     WHERE p.id = '$project_id' AND pma.project_manager_id = '$pm_id'");

if(!$project_query || mysqli_num_rows($project_query) == 0) {
    header("Location: project_progress.php");
    exit();
}

$project = mysqli_fetch_assoc($project_query);
$current_progress = $project['current_progress'] ? $project['current_progress'] : 0;
?>

<?php include("menu.php"); ?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card card-dashboard">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="h3 mb-0">Progress History</h1>
                            <p class="text-muted mb-0">Project: <?php echo $project['name']; ?></p>
                        </div>
                        <div class="btn-group">
                            <a href="project_progress.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Back to Projects
                            </a>
                            <a href="update_progress.php?project_id=<?php echo $project_id; ?>" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i>Add Update
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Project Summary -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card card-dashboard">
                <div class="card-body text-center">
                    <h3 class="text-primary"><?php echo $current_progress; ?>%</h3>
                    <p class="text-muted mb-0">Current Progress</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-dashboard">
                <div class="card-body text-center">
                    <?php
                    $total_updates_query = mysqli_query($con, 
                        "SELECT COUNT(*) as total_updates FROM progress_updates 
                         WHERE project_id = '$project_id'");
                    $total_updates = mysqli_fetch_assoc($total_updates_query);
                    ?>
                    <h3 class="text-success"><?php echo $total_updates['total_updates']; ?></h3>
                    <p class="text-muted mb-0">Total Updates</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-dashboard">
                <div class="card-body text-center">
                    <?php
                    $days_left = ceil((strtotime($project['end_date']) - time()) / (60 * 60 * 24));
                    $days_left = $days_left > 0 ? $days_left : 0;
                    ?>
                    <h3 class="<?php echo $days_left < 7 ? 'text-danger' : 'text-warning'; ?>"><?php echo $days_left; ?></h3>
                    <p class="text-muted mb-0">Days Remaining</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Progress Timeline -->
    <div class="row">
        <div class="col-12">
            <div class="card card-dashboard">
                <div class="card-header">
                    <h5 class="card-title mb-0">Progress Timeline</h5>
                </div>
                <div class="card-body">
                    <?php
                    // Get all progress updates for this project, ordered by date (newest first)
                    $progress_query = mysqli_query($con, 
                        "SELECT pu.*, pm.name as updated_by_name 
                         FROM progress_updates pu
                         LEFT JOIN project_managers pm ON pu.project_manager_id = pm.id
                         WHERE pu.project_id = '$project_id' 
                         ORDER BY pu.update_date DESC, pu.id DESC");

                    if($progress_query && mysqli_num_rows($progress_query) > 0) {
                        ?>
                        <div class="timeline">
                            <?php
                            $previous_date = null;
                            while($update = mysqli_fetch_assoc($progress_query)) {
                                $current_date = date('M d, Y', strtotime($update['update_date']));
                                
                                // Show date header if it's different from previous
                                if($current_date != $previous_date) {
                                    ?>
                                    <div class="timeline-date-header">
                                        <span class="badge bg-primary"><?php echo $current_date; ?></span>
                                    </div>
                                    <?php
                                    $previous_date = $current_date;
                                }
                                ?>
                                
                                <div class="timeline-item">
                                    <div class="timeline-marker">
                                        <div class="progress-marker 
                                            <?php 
                                            if($update['progress_percentage'] >= 90) echo 'bg-success';
                                            elseif($update['progress_percentage'] >= 50) echo 'bg-primary';
                                            else echo 'bg-warning';
                                            ?>">
                                            <?php echo $update['progress_percentage']; ?>%
                                        </div>
                                    </div>
                                    <div class="timeline-content">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <h6 class="mb-0">Progress Update: <?php echo $update['progress_percentage']; ?>%</h6>
                                                    <small class="text-muted">
                                                        <?php echo date('h:i A', strtotime($update['update_date'])); ?>
                                                    </small>
                                                </div>
                                                
                                                <?php if(!empty($update['description'])): ?>
                                                    <p class="mb-2"><?php echo nl2br(htmlspecialchars($update['description'])); ?></p>
                                                <?php else: ?>
                                                    <p class="mb-2 text-muted"><em>No description provided</em></p>
                                                <?php endif; ?>
                                                
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <small class="text-muted">
                                                        Updated by: <strong><?php echo $update['updated_by_name'] ?: 'Project Manager'; ?></strong>
                                                    </small>
                                                    <div class="progress-indicator">
                                                        <div class="progress" style="height: 6px; width: 100px;">
                                                            <div class="progress-bar 
                                                                <?php 
                                                                if($update['progress_percentage'] >= 90) echo 'bg-success';
                                                                elseif($update['progress_percentage'] >= 50) echo 'bg-primary';
                                                                else echo 'bg-warning';
                                                                ?>" 
                                                                role="progressbar" 
                                                                style="width: <?php echo $update['progress_percentage']; ?>%" 
                                                                aria-valuenow="<?php echo $update['progress_percentage']; ?>" 
                                                                aria-valuemin="0" 
                                                                aria-valuemax="100">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php
                            }
                            ?>
                        </div>
                        <?php
                    } else {
                        ?>
                        <div class="text-center py-5">
                            <i class="fas fa-history fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">No Progress Updates</h4>
                            <p class="text-muted">No progress updates have been recorded for this project yet.</p>
                            <a href="update_progress.php?project_id=<?php echo $project_id; ?>" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i>Add First Update
                            </a>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Progress Statistics -->
    <div class="row mt-4">
        <div class="col-lg-6">
            <div class="card card-dashboard">
                <div class="card-header">
                    <h5 class="card-title mb-0">Progress Statistics</h5>
                </div>
                <div class="card-body">
                    <?php
                    // Get progress statistics
                    $stats_query = mysqli_query($con, 
                        "SELECT 
                            MIN(progress_percentage) as min_progress,
                            MAX(progress_percentage) as max_progress,
                            AVG(progress_percentage) as avg_progress,
                            COUNT(*) as total_updates,
                            MIN(update_date) as first_update,
                            MAX(update_date) as last_update
                         FROM progress_updates 
                         WHERE project_id = '$project_id'");
                    
                    if($stats_query && $stats = mysqli_fetch_assoc($stats_query)) {
                        ?>
                        <div class="row text-center">
                            <div class="col-6 mb-3">
                                <div class="border-end">
                                    <small class="text-muted d-block">First Progress</small>
                                    <strong class="text-primary"><?php echo $stats['min_progress'] ?: 0; ?>%</strong>
                                </div>
                            </div>
                            <div class="col-6 mb-3">
                                <small class="text-muted d-block">Current Progress</small>
                                <strong class="text-success"><?php echo $stats['max_progress'] ?: 0; ?>%</strong>
                            </div>
                            <div class="col-6">
                                <div class="border-end">
                                    <small class="text-muted d-block">Average Progress</small>
                                    <strong class="text-warning"><?php echo round($stats['avg_progress'] ?: 0); ?>%</strong>
                                </div>
                            </div>
                            <div class="col-6">
                                <small class="text-muted d-block">Total Updates</small>
                                <strong class="text-info"><?php echo $stats['total_updates']; ?></strong>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="row">
                            <div class="col-12">
                                <small class="text-muted d-block">Project Duration</small>
                                <div class="d-flex justify-content-between">
                                    <small>Start: <?php echo date('M d, Y', strtotime($project['start_date'])); ?></small>
                                    <small>End: <?php echo date('M d, Y', strtotime($project['end_date'])); ?></small>
                                </div>
                                <div class="progress mt-2" style="height: 8px;">
                                    <?php
                                    $total_days = ceil((strtotime($project['end_date']) - strtotime($project['start_date'])) / (60 * 60 * 24));
                                    $days_passed = ceil((time() - strtotime($project['start_date'])) / (60 * 60 * 24));
                                    $days_passed = max(0, min($days_passed, $total_days));
                                    $completion_ratio = ($total_days > 0) ? ($days_passed / $total_days) * 100 : 0;
                                    ?>
                                    <div class="progress-bar bg-info" role="progressbar" 
                                         style="width: <?php echo $completion_ratio; ?>%" 
                                         aria-valuenow="<?php echo $completion_ratio; ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between mt-1">
                                    <small><?php echo $days_passed; ?> days passed</small>
                                    <small><?php echo $total_days - $days_passed; ?> days left</small>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6">
            <div class="card card-dashboard">
                <div class="card-header">
                    <h5 class="card-title mb-0">Project Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Project Name:</strong><br>
                        <?php echo $project['name']; ?>
                    </div>
                    <div class="mb-3">
                        <strong>Client:</strong><br>
                        <?php echo $project['client_name']; ?>
                    </div>
                    <div class="mb-3">
                        <strong>Location:</strong><br>
                        <?php echo $project['location'] ?: 'Not specified'; ?>
                    </div>
                    <div class="mb-3">
                        <strong>Project Type:</strong><br>
                        <?php echo $project['project_type'] ?: 'Not specified'; ?>
                    </div>
                    <div class="mb-3">
                        <strong>Budget:</strong><br>
                        ₹<?php echo number_format($project['budget']); ?>
                    </div>
                    <div class="mb-3">
                        <strong>Current Status:</strong><br>
                        <span class="badge 
                            <?php 
                            if($project['status'] == 'Completed') echo 'bg-success';
                            elseif($project['status'] == 'In Progress') echo 'bg-primary';
                            else echo 'bg-warning';
                            ?>">
                            <?php echo $project['status']; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e9ecef;
}

.timeline-date-header {
    text-align: center;
    margin: 20px 0;
    position: relative;
}

.timeline-date-header::before {
    content: '';
    position: absolute;
    left: -30px;
    top: 50%;
    width: 60px;
    height: 1px;
    background: #dee2e6;
}

.timeline-item {
    position: relative;
    margin-bottom: 30px;
}

.timeline-marker {
    position: absolute;
    left: -30px;
    top: 0;
    z-index: 2;
}

.progress-marker {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 10px;
    font-weight: bold;
    border: 3px solid white;
    box-shadow: 0 0 0 3px #e9ecef;
}

.timeline-content {
    margin-left: 0;
}

.timeline-item .card {
    border-left: 3px solid #007bff;
}

.timeline-item:nth-child(odd) .card {
    border-left-color: #28a745;
}

.timeline-item:nth-child(even) .card {
    border-left-color: #ffc107;
}
</style>

<?php include("footer.php"); ?>