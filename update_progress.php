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

// Verify that the project belongs to this project manager
$verify_query = mysqli_query($con, 
    "SELECT p.*, u.name as client_name,
     (SELECT progress_percentage FROM progress_updates 
      WHERE project_id = p.id ORDER BY update_date DESC LIMIT 1) as current_progress
     FROM projects p 
     INNER JOIN project_manager_assignments pma ON p.id = pma.project_id 
     INNER JOIN users u ON p.client_id = u.id
     WHERE p.id = '$project_id' AND pma.project_manager_id = '$pm_id'");

if(!$verify_query || mysqli_num_rows($verify_query) == 0) {
    header("Location: project_progress.php");
    exit();
}

$project = mysqli_fetch_assoc($verify_query);
$current_progress = $project['current_progress'] ? $project['current_progress'] : 0;

// Handle form submission
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $progress_percentage = mysqli_real_escape_string($con, $_POST['progress_percentage']);
    $description = mysqli_real_escape_string($con, $_POST['description']);
    $update_date = date('Y-m-d');
    
    // Insert progress update
    $insert_query = mysqli_query($con, 
        "INSERT INTO progress_updates (project_id, project_manager_id, update_date, description, progress_percentage) 
         VALUES ('$project_id', '$pm_id', '$update_date', '$description', '$progress_percentage')");
    
    if($insert_query) {
        // Update project status based on progress
        $new_status = 'In Progress';
        if($progress_percentage >= 100) {
            $new_status = 'Completed';
        }
        
        $update_status_query = mysqli_query($con, 
            "UPDATE projects SET status = '$new_status' WHERE id = '$project_id'");
        
        $_SESSION['success_message'] = "Progress updated successfully!";
        header("Location: project_progress.php");
        exit();
    } else {
        $error_message = "Failed to update progress. Please try again.";
    }
}
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
                            <h1 class="h3 mb-0">Update Project Progress</h1>
                            <p class="text-muted mb-0">Update progress for: <?php echo $project['name']; ?></p>
                        </div>
                        <a href="project_progress.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i>Back to Projects
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Project Info Card -->
            <div class="card card-dashboard mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Project Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Project Name:</strong> <?php echo $project['name']; ?></p>
                            <p><strong>Client:</strong> <?php echo $project['client_name']; ?></p>
                            <p><strong>Start Date:</strong> <?php echo date('M d, Y', strtotime($project['start_date'])); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Current Progress:</strong> <?php echo $current_progress; ?>%</p>
                            <p><strong>End Date:</strong> <?php echo date('M d, Y', strtotime($project['end_date'])); ?></p>
                            <p><strong>Status:</strong> 
                                <span class="badge <?php 
                                    if($project['status'] == 'Completed') echo 'bg-success';
                                    elseif($project['status'] == 'In Progress') echo 'bg-primary';
                                    else echo 'bg-warning';
                                ?>">
                                    <?php echo $project['status']; ?>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Progress Update Form -->
            <div class="card card-dashboard">
                <div class="card-header">
                    <h5 class="card-title mb-0">Update Progress</h5>
                </div>
                <div class="card-body">
                    <?php if(isset($error_message)): ?>
                        <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <!-- Progress Percentage -->
                        <div class="mb-4">
                            <label for="progress_percentage" class="form-label">
                                <strong>Progress Percentage</strong>
                                <small class="text-muted">(Current: <?php echo $current_progress; ?>%)</small>
                            </label>
                            <input type="range" class="form-range" id="progress_percentage" 
                                   name="progress_percentage" min="0" max="100" 
                                   value="<?php echo $current_progress; ?>" 
                                   oninput="updateProgressValue(this.value)">
                            <div class="d-flex justify-content-between">
                                <small>0%</small>
                                <span id="progressValue" class="fw-bold"><?php echo $current_progress; ?>%</span>
                                <small>100%</small>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="mb-4">
                            <label for="description" class="form-label"><strong>Progress Description</strong></label>
                            <textarea class="form-control" id="description" name="description" 
                                      rows="5" placeholder="Describe the work completed, milestones achieved, challenges faced, etc."></textarea>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="d-flex justify-content-end gap-2">
                            <a href="project_progress.php" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Update Progress
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Recent Progress Updates -->
            <div class="card card-dashboard mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Recent Progress Updates</h5>
                </div>
                <div class="card-body">
                    <?php
                    $history_query = mysqli_query($con, 
                        "SELECT * FROM progress_updates 
                         WHERE project_id = '$project_id' 
                         ORDER BY update_date DESC, id DESC 
                         LIMIT 5");
                    
                    if($history_query && mysqli_num_rows($history_query) > 0) {
                        echo '<div class="table-responsive">';
                        echo '<table class="table table-sm">';
                        echo '<thead>';
                        echo '<tr>';
                        echo '<th>Date</th>';
                        echo '<th>Progress</th>';
                        echo '<th>Description</th>';
                        echo '</tr>';
                        echo '</thead>';
                        echo '<tbody>';
                        
                        while($update = mysqli_fetch_assoc($history_query)) {
                            echo '<tr>';
                            echo '<td>' . date('M d, Y', strtotime($update['update_date'])) . '</td>';
                            echo '<td><span class="badge bg-primary">' . $update['progress_percentage'] . '%</span></td>';
                            echo '<td>' . ($update['description'] ? nl2br(htmlspecialchars($update['description'])) : '<em class="text-muted">No description</em>') . '</td>';
                            echo '</tr>';
                        }
                        
                        echo '</tbody>';
                        echo '</table>';
                        echo '</div>';
                        
                        echo '<div class="text-center mt-3">';
                        echo '<a href="progress_history.php?project_id=' . $project_id . '" class="btn btn-outline-info btn-sm">';
                        echo '<i class="fas fa-history me-1"></i>View Full History';
                        echo '</a>';
                        echo '</div>';
                    } else {
                        echo '<p class="text-muted text-center py-3">No progress updates yet.</p>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function updateProgressValue(value) {
    document.getElementById('progressValue').textContent = value + '%';
    
    // Update progress bar color based on value
    const progressBar = document.querySelector('.form-range');
    if(value >= 90) {
        progressBar.classList.remove('bg-primary', 'bg-warning');
        progressBar.classList.add('bg-success');
    } else if(value >= 50) {
        progressBar.classList.remove('bg-success', 'bg-warning');
        progressBar.classList.add('bg-primary');
    } else {
        progressBar.classList.remove('bg-success', 'bg-primary');
        progressBar.classList.add('bg-warning');
    }
}

// Initialize progress value display
document.addEventListener('DOMContentLoaded', function() {
    const initialValue = document.getElementById('progress_percentage').value;
    updateProgressValue(initialValue);
});
</script>

<?php include("footer.php"); ?>