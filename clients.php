<?php
session_start();

// Enhanced session check for project manager
if(!isset($_SESSION['uid']) || (!isset($_SESSION['user_type']) && !isset($_SESSION['type']))) {
    header("Location: login.php");
    exit();
}

// Check if user is project manager (compatible with both session variable names)
$is_pm = false;
if(isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'project_manager') {
    $is_pm = true;
} elseif(isset($_SESSION['type']) && $_SESSION['type'] == 'project_manager') {
    $is_pm = true;
}

if(!$is_pm) {
    header("Location: login.php");
    exit();
}

include("connection.php");
$pm_id = isset($_SESSION['uid']) ? $_SESSION['uid'] : (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0);
?>

<?php include("menu.php"); ?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card card-dashboard">
                <div class="card-body">
                    <h1 class="h3 mb-0">My Clients</h1>
                    <p class="text-muted mb-0">Clients with projects assigned to you</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Clients Table -->
    <div class="row">
        <div class="col-12">
            <div class="card card-dashboard">
                <div class="card-header">
                    <h5 class="card-title mb-0">Client List</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Client ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Projects</th>
                                    <th>Status</th>
                                    
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Corrected query using users table as clients
                                $clients_query = mysqli_query($con, 
                                    "SELECT u.*, COUNT(p.id) as project_count,
                                     (SELECT COUNT(*) FROM projects p2 
                                      INNER JOIN project_manager_assignments pma2 ON p2.id = pma2.project_id 
                                      WHERE p2.client_id = u.id AND pma2.project_manager_id = '$pm_id' AND p2.status = 'In Progress') as active_projects
                                     FROM users u 
                                     INNER JOIN projects p ON u.id = p.client_id 
                                     INNER JOIN project_manager_assignments pma ON p.id = pma.project_id 
                                     WHERE pma.project_manager_id = '$pm_id' 
                                     GROUP BY u.id");
                                
                                if($clients_query && mysqli_num_rows($clients_query) > 0) {
                                    while($client = mysqli_fetch_assoc($clients_query)) {
                                        $active_projects = $client['active_projects'];
                                        $total_projects = $client['project_count'];
                                        
                                        // Status indicator
                                        $status_badge = $active_projects > 0 ? 
                                            '<span class="badge bg-success">Active</span>' : 
                                            '<span class="badge bg-secondary">No Active</span>';
                                        ?>
                                        <tr>
                                            <td><strong>#<?php echo $client['id']; ?></strong></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm me-3">
                                                        <div class="avatar-title bg-primary text-white rounded-circle">
                                                            <?php echo strtoupper(substr($client['name'], 0, 1)); ?>
                                                        </div>
                                                    </div>
                                                    <div>
                                                        <strong><?php echo $client['name']; ?></strong>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <a href="mailto:<?php echo $client['email']; ?>" class="text-decoration-none">
                                                    <i class="fas fa-envelope me-1 text-muted"></i>
                                                    <?php echo $client['email']; ?>
                                                </a>
                                            </td>
                                            <td>
                                                <?php if(!empty($client['phone'])): ?>
                                                    <a href="tel:<?php echo $client['phone']; ?>" class="text-decoration-none">
                                                        <i class="fas fa-phone me-1 text-muted"></i>
                                                        <?php echo $client['phone']; ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">Not provided</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span class="badge bg-primary me-2"><?php echo $total_projects; ?></span>
                                                    <small class="text-muted">
                                                        <?php echo $active_projects; ?> active
                                                    </small>
                                                </div>
                                            </td>
                                            <td>
                                                <?php echo $status_badge; ?>
                                            </td>
                                            <td>
                                                
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                } else {
                                    echo '<tr>
                                            <td colspan="7" class="text-center py-4 text-muted">
                                                <i class="fas fa-users fa-3x mb-3"></i><br>
                                                No clients found for your projects.
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

    <!-- Client Statistics -->
    <div class="row mt-4">
        <div class="col-md-3">
            <div class="card card-dashboard">
                <div class="card-body text-center">
                    <?php
                    $total_clients_query = mysqli_query($con, 
                        "SELECT COUNT(DISTINCT u.id) as total_clients 
                         FROM users u 
                         INNER JOIN projects p ON u.id = p.client_id 
                         INNER JOIN project_manager_assignments pma ON p.id = pma.project_id 
                         WHERE pma.project_manager_id = '$pm_id'");
                    if($total_clients_query) {
                        $total_clients = mysqli_fetch_assoc($total_clients_query);
                        echo '<h3 class="text-primary">'.$total_clients['total_clients'].'</h3>';
                    } else {
                        echo '<h3 class="text-primary">0</h3>';
                    }
                    ?>
                    <p class="text-muted mb-0">Total Clients</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-dashboard">
                <div class="card-body text-center">
                    <?php
                    $active_clients_query = mysqli_query($con, 
                        "SELECT COUNT(DISTINCT u.id) as active_clients 
                         FROM users u 
                         INNER JOIN projects p ON u.id = p.client_id 
                         INNER JOIN project_manager_assignments pma ON p.id = pma.project_id 
                         WHERE pma.project_manager_id = '$pm_id' AND p.status = 'In Progress'");
                    if($active_clients_query) {
                        $active_clients = mysqli_fetch_assoc($active_clients_query);
                        echo '<h3 class="text-success">'.$active_clients['active_clients'].'</h3>';
                    } else {
                        echo '<h3 class="text-success">0</h3>';
                    }
                    ?>
                    <p class="text-muted mb-0">Active Clients</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-dashboard">
                <div class="card-body text-center">
                    <?php
                    $total_projects_query = mysqli_query($con, 
                        "SELECT COUNT(*) as total_projects 
                         FROM projects p 
                         INNER JOIN project_manager_assignments pma ON p.id = pma.project_id 
                         WHERE pma.project_manager_id = '$pm_id'");
                    if($total_projects_query) {
                        $total_projects = mysqli_fetch_assoc($total_projects_query);
                        echo '<h3 class="text-warning">'.$total_projects['total_projects'].'</h3>';
                    } else {
                        echo '<h3 class="text-warning">0</h3>';
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
                    $avg_projects_query = mysqli_query($con, 
                        "SELECT COUNT(p.id) / COUNT(DISTINCT u.id) as avg_projects 
                         FROM users u 
                         INNER JOIN projects p ON u.id = p.client_id 
                         INNER JOIN project_manager_assignments pma ON p.id = pma.project_id 
                         WHERE pma.project_manager_id = '$pm_id'");
                    if($avg_projects_query) {
                        $avg_projects = mysqli_fetch_assoc($avg_projects_query);
                        $avg_projects = round($avg_projects['avg_projects'], 1);
                        echo '<h3 class="text-info">'.$avg_projects.'</h3>';
                    } else {
                        echo '<h3 class="text-info">0</h3>';
                    }
                    ?>
                    <p class="text-muted mb-0">Avg Projects/Client</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include("footer.php"); ?>