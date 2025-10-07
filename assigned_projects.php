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
                    <h1 class="h3 mb-0">My Assigned Projects</h1>
                    <p class="text-muted">Manage and track all projects assigned to you</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Projects Table -->
    <div class="row">
        <div class="col-12">
            <div class="card card-dashboard">
                <div class="card-header">
                    <h5 class="card-title mb-0">Project List</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Project ID</th>
                                    <th>Project Name</th>
                                    <th>Client</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Status</th>
                                    <th>Progress</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Corrected query with users table as clients
                                $projects_query = mysqli_query($con, 
                                    "SELECT p.*, u.name as client_name, pma.assigned_date,
                                     (SELECT progress_percentage FROM progress_updates 
                                      WHERE project_id = p.id ORDER BY update_date DESC LIMIT 1) as progress
                                     FROM projects p 
                                     INNER JOIN users u ON p.client_id = u.id 
                                     INNER JOIN project_manager_assignments pma ON p.id = pma.project_id 
                                     WHERE pma.project_manager_id = '$pm_id'");
                                
                                if($projects_query && mysqli_num_rows($projects_query) > 0) {
                                    while($project = mysqli_fetch_assoc($projects_query)) {
                                        $progress = $project['progress'] ? $project['progress'] : 0;
                                        
                                        // Status badge classes
                                        $status_class = '';
                                        if($project['status'] == 'Completed') {
                                            $status_class = 'badge bg-success';
                                        } elseif($project['status'] == 'In Progress') {
                                            $status_class = 'badge bg-primary';
                                        } else {
                                            $status_class = 'badge bg-warning';
                                        }
                                        
                                        echo '<tr>
                                                <td><strong>#'.$project['id'].'</strong></td>
                                                <td>'.$project['name'].'</td>
                                                <td>'.$project['client_name'].'</td>
                                                <td>'.date('M d, Y', strtotime($project['start_date'])).'</td>
                                                <td>'.date('M d, Y', strtotime($project['end_date'])).'</td>
                                                <td><span class="'.$status_class.'">'.$project['status'].'</span></td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="progress flex-grow-1 me-2">
                                                            <div class="progress-bar" role="progressbar" style="width: '.$progress.'%"></div>
                                                        </div>
                                                        <small>'.$progress.'%</small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <a href="view_project.php?id='.$project['id'].'" class="btn btn-primary btn-sm" data-bs-toggle="tooltip" title="View Details" target="_blank">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="project_progress.php?project_id='.$project['id'].'" class="btn btn-success btn-sm" data-bs-toggle="tooltip" title="Update Progress">
                                                            <i class="fas fa-chart-line"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                              </tr>';
                                    }
                                } else {
                                    echo '<tr>
                                            <td colspan="8" class="text-center py-4 text-muted">
                                                <i class="fas fa-folder-open fa-3x mb-3"></i><br>
                                                No projects assigned to you yet.
                                            </td>
                                          </tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
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
                    $active_query = mysqli_query($con, 
                        "SELECT COUNT(*) as active FROM project_manager_assignments pma 
                         INNER JOIN projects p ON pma.project_id = p.id 
                         WHERE pma.project_manager_id = '$pm_id' AND p.status = 'In Progress'");
                    if($active_query) {
                        $active = mysqli_fetch_assoc($active_query);
                        echo '<h3 class="text-success">'.$active['active'].'</h3>';
                    } else {
                        echo '<h3 class="text-success">0</h3>';
                    }
                    ?>
                    <p class="text-muted mb-0">Active</p>
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
                        echo '<h3 class="text-warning">'.$completed['completed'].'</h3>';
                    } else {
                        echo '<h3 class="text-warning">0</h3>';
                    }
                    ?>
                    <p class="text-muted mb-0">Completed</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-dashboard">
                <div class="card-body text-center">
                    <?php
                    $pending_query = mysqli_query($con, 
                        "SELECT COUNT(*) as pending FROM project_manager_assignments pma 
                         INNER JOIN projects p ON pma.project_id = p.id 
                         WHERE pma.project_manager_id = '$pm_id' AND p.status = 'Pending'");
                    if($pending_query) {
                        $pending = mysqli_fetch_assoc($pending_query);
                        echo '<h3 class="text-danger">'.$pending['pending'].'</h3>';
                    } else {
                        echo '<h3 class="text-danger">0</h3>';
                    }
                    ?>
                    <p class="text-muted mb-0">Pending</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include("footer.php"); ?>