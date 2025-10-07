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
    <!-- Welcome Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card card-dashboard">
                <div class="card-body">
                    <h1 class="h3 mb-0">Welcome back, <?php echo $_SESSION['name']; ?>!</h1>
                    <p class="text-muted">Here's what's happening with your projects today.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card card-dashboard">
                <div class="card-body stat-card primary">
                    <?php
                    $total_projects = mysqli_query($con, 
                        "SELECT COUNT(*) as total FROM project_manager_assignments WHERE project_manager_id = '$pm_id'");
                    if($total_projects) {
                        $total = mysqli_fetch_assoc($total_projects);
                        echo '<h2>'.$total['total'].'</h2>';
                    } else {
                        echo '<h2>0</h2>';
                    }
                    ?>
                    <p class="text-muted">Total Projects</p>
                    <i class="fas fa-project-diagram fa-2x text-primary"></i>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6">
            <div class="card card-dashboard">
                <div class="card-body stat-card success">
                    <?php
                    $active_projects = mysqli_query($con, 
                        "SELECT COUNT(*) as active FROM project_manager_assignments pma 
                         INNER JOIN projects p ON pma.project_id = p.id 
                         WHERE pma.project_manager_id = '$pm_id' AND p.status = 'In Progress'");
                    if($active_projects) {
                        $active = mysqli_fetch_assoc($active_projects);
                        echo '<h2>'.$active['active'].'</h2>';
                    } else {
                        echo '<h2>0</h2>';
                    }
                    ?>
                    <p class="text-muted">Active Projects</p>
                    <i class="fas fa-play-circle fa-2x text-success"></i>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6">
            <div class="card card-dashboard">
                <div class="card-body stat-card warning">
                    <?php
                    $completed_projects = mysqli_query($con, 
                        "SELECT COUNT(*) as completed FROM project_manager_assignments pma 
                         INNER JOIN projects p ON pma.project_id = p.id 
                         WHERE pma.project_manager_id = '$pm_id' AND p.status = 'Completed'");
                    if($completed_projects) {
                        $completed = mysqli_fetch_assoc($completed_projects);
                        echo '<h2>'.$completed['completed'].'</h2>';
                    } else {
                        echo '<h2>0</h2>';
                    }
                    ?>
                    <p class="text-muted">Completed</p>
                    <i class="fas fa-check-circle fa-2x text-warning"></i>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6">
            <div class="card card-dashboard">
                <div class="card-body stat-card danger">
                    <?php
                    $pending_projects = mysqli_query($con, 
                        "SELECT COUNT(*) as pending FROM project_manager_assignments pma 
                         INNER JOIN projects p ON pma.project_id = p.id 
                         WHERE pma.project_manager_id = '$pm_id' AND p.status = 'Pending'");
                    if($pending_projects) {
                        $pending = mysqli_fetch_assoc($pending_projects);
                        echo '<h2>'.$pending['pending'].'</h2>';
                    } else {
                        echo '<h2>0</h2>';
                    }
                    ?>
                    <p class="text-muted">Pending Projects</p>
                    <i class="fas fa-clock fa-2x text-danger"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Projects -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card card-dashboard">
                <div class="card-header">
                    <h5 class="card-title mb-0">Recent Projects</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Project Name</th>
                                    <th>Client</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Status</th>
                                    <th>Progress</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Corrected query with users table as clients
                                $recent_projects = mysqli_query($con, 
                                    "SELECT p.*, u.name as client_name,
                                     (SELECT progress_percentage FROM progress_updates 
                                      WHERE project_id = p.id ORDER BY update_date DESC LIMIT 1) as progress
                                     FROM projects p 
                                     INNER JOIN users u ON p.client_id = u.id 
                                     INNER JOIN project_manager_assignments pma ON p.id = pma.project_id 
                                     WHERE pma.project_manager_id = '$pm_id' 
                                     ORDER BY p.created_at DESC LIMIT 5");
                                
                                if($recent_projects && mysqli_num_rows($recent_projects) > 0) {
                                    while($project = mysqli_fetch_assoc($recent_projects)) {
                                        $progress = $project['progress'] ? $project['progress'] : 0;
                                        $status_class = '';
                                        if($project['status'] == 'Completed') {
                                            $status_class = 'badge bg-success';
                                        } elseif($project['status'] == 'In Progress') {
                                            $status_class = 'badge bg-primary';
                                        } else {
                                            $status_class = 'badge bg-warning';
                                        }
                                        echo '<tr>
                                                <td><strong>'.$project['name'].'</strong></td>
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
                                              </tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="6" class="text-center py-4 text-muted">No projects assigned yet</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include("footer.php"); ?>