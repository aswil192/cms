<?php
session_start();

// Redirect to login if not authenticated as admin
if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    header("Location: login.php");
    exit();
}

include("connection.php");
?>

<?php include("admin_menu.php"); ?>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card card-dashboard">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="h3 mb-0">All Projects</h1>
                            <p class="text-muted mb-0">Manage and view all construction projects</p>
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card card-dashboard">
                <div class="card-body text-center">
                    <?php
                    $total_query = mysqli_query($con, "SELECT COUNT(*) as total FROM projects");
                    $total = mysqli_fetch_assoc($total_query);
                    ?>
                    <h3 class="text-primary"><?php echo $total['total']; ?></h3>
                    <p class="text-muted mb-0">Total Projects</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-dashboard">
                <div class="card-body text-center">
                    <?php
                    $active_query = mysqli_query($con, "SELECT COUNT(*) as active FROM projects WHERE status = 'In Progress'");
                    $active = mysqli_fetch_assoc($active_query);
                    ?>
                    <h3 class="text-success"><?php echo $active['active']; ?></h3>
                    <p class="text-muted mb-0">Active Projects</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-dashboard">
                <div class="card-body text-center">
                    <?php
                    $completed_query = mysqli_query($con, "SELECT COUNT(*) as completed FROM projects WHERE status = 'Completed'");
                    $completed = mysqli_fetch_assoc($completed_query);
                    ?>
                    <h3 class="text-warning"><?php echo $completed['completed']; ?></h3>
                    <p class="text-muted mb-0">Completed</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-dashboard">
                <div class="card-body text-center">
                    <?php
                    $pending_query = mysqli_query($con, "SELECT COUNT(*) as pending FROM projects WHERE status = 'Pending'");
                    $pending = mysqli_fetch_assoc($pending_query);
                    ?>
                    <h3 class="text-danger"><?php echo $pending['pending']; ?></h3>
                    <p class="text-muted mb-0">Pending</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Projects Table -->
    <div class="row">
        <div class="col-12">
            <div class="card card-dashboard">
                <div class="card-header">
                    <h5 class="card-title mb-0">Projects List</h5>
                </div>
                <div class="card-body">
                    <!-- Search and Filter -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="searchInput" placeholder="Search projects...">
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="statusFilter">
                                <option value="">All Status</option>
                                <option value="Pending">Pending</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Completed">Completed</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="typeFilter">
                                <option value="">All Types</option>
                                <option value="Residential">Residential</option>
                                <option value="Commercial">Commercial</option>
                                <option value="Industrial">Industrial</option>
                                <option value="Renovation">Renovation</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover" id="projectsTable">
                            <thead class="table-light">
                                <tr>
                                    <th>Project ID</th>
                                    <th>Project Name</th>
                                    <th>Client</th>
                                    <th>Location</th>
                                    <th>Type</th>
                                    <th>Budget</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Status</th>
                                    <th>Project Manager</th>
                                    
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $projects_query = mysqli_query($con, 
                                    "SELECT p.*, u.name as client_name, pm.name as pm_name,
                                     (SELECT progress_percentage FROM progress_updates 
                                      WHERE project_id = p.id ORDER BY update_date DESC LIMIT 1) as progress
                                     FROM projects p 
                                     LEFT JOIN users u ON p.client_id = u.id 
                                     LEFT JOIN project_managers pm ON p.project_manager_id = pm.id 
                                     ORDER BY p.created_at DESC");

                                if($projects_query && mysqli_num_rows($projects_query) > 0) {
                                    while($project = mysqli_fetch_assoc($projects_query)) {
                                        $progress = $project['progress'] ? $project['progress'] : 0;
                                        
                                        // Status badge
                                        $status_class = '';
                                        switch($project['status']) {
                                            case 'Completed':
                                                $status_class = 'bg-success';
                                                break;
                                            case 'In Progress':
                                                $status_class = 'bg-primary';
                                                break;
                                            case 'Pending':
                                                $status_class = 'bg-warning';
                                                break;
                                            default:
                                                $status_class = 'bg-secondary';
                                        }

                                        // Progress bar color
                                        $progress_class = '';
                                        if ($progress >= 90) {
                                            $progress_class = 'bg-success';
                                        } elseif ($progress >= 50) {
                                            $progress_class = 'bg-primary';
                                        } else {
                                            $progress_class = 'bg-warning';
                                        }
                                        ?>
                                        <tr>
                                            <td><strong>#<?php echo $project['id']; ?></strong></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php if(!empty($project['image'])): ?>
                                                        <img src="<?php echo $project['image']; ?>" alt="Project Image" 
                                                             class="rounded me-3" style="width: 40px; height: 40px; object-fit: cover;">
                                                    <?php else: ?>
                                                        <div class="bg-light rounded me-3 d-flex align-items-center justify-content-center" 
                                                             style="width: 40px; height: 40px;">
                                                            <i class="fas fa-project-diagram text-muted"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($project['name']); ?></strong>
                                                        <br>
                                                        <small class="text-muted">
                                                            Progress: 
                                                            <div class="progress" style="height: 5px; width: 80px; display: inline-block; margin-left: 5px;">
                                                                <div class="progress-bar <?php echo $progress_class; ?>" 
                                                                     style="width: <?php echo $progress; ?>%"></div>
                                                            </div>
                                                            <?php echo $progress; ?>%
                                                        </small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if($project['client_name']): ?>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar-sm me-2">
                                                            <div class="avatar-title bg-primary text-white rounded-circle">
                                                                <?php echo strtoupper(substr($project['client_name'], 0, 1)); ?>
                                                            </div>
                                                        </div>
                                                        <?php echo htmlspecialchars($project['client_name']); ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">Not assigned</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $project['location'] ? htmlspecialchars($project['location']) : '<span class="text-muted">N/A</span>'; ?></td>
                                            <td>
                                                <span class="badge bg-info"><?php echo $project['project_type'] ?: 'Not specified'; ?></span>
                                            </td>
                                            <td>
                                                <strong>₹<?php echo number_format($project['budget']); ?></strong>
                                            </td>
                                            <td>
                                                <small><?php echo date('M d, Y', strtotime($project['start_date'])); ?></small>
                                            </td>
                                            <td>
                                                <?php if($project['end_date']): ?>
                                                    <small><?php echo date('M d, Y', strtotime($project['end_date'])); ?></small>
                                                    <?php
                                                    $days_left = ceil((strtotime($project['end_date']) - time()) / (60 * 60 * 24));
                                                    if($days_left < 7 && $project['status'] == 'In Progress') {
                                                        echo '<br><small class="text-danger"><i class="fas fa-exclamation-triangle"></i> ' . $days_left . ' days left</small>';
                                                    }
                                                    ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Not set</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo $status_class; ?>"><?php echo $project['status']; ?></span>
                                            </td>
                                            <td>
                                                <?php if($project['pm_name']): ?>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar-sm me-2">
                                                            <div class="avatar-title bg-success text-white rounded-circle">
                                                                <?php echo strtoupper(substr($project['pm_name'], 0, 1)); ?>
                                                            </div>
                                                        </div>
                                                        <?php echo htmlspecialchars($project['pm_name']); ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">Not assigned</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    
                                                    <ul class="dropdown-menu">
                                                        
                                                        <li>
                                                            <a class="dropdown-item" href="edit_project.php?id=<?php echo $project['id']; ?>">
                                                                <i class="fas fa-edit me-2"></i>Edit Project
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item" href="assign_pm.php?project_id=<?php echo $project['id']; ?>">
                                                                <i class="fas fa-user-tie me-2"></i>Assign PM
                                                            </a>
                                                        </li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <a class="dropdown-item text-danger" href="#" 
                                                               onclick="confirmDelete(<?php echo $project['id']; ?>)">
                                                                <i class="fas fa-trash me-2"></i>Delete
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                } else {
                                    echo '<tr>
                                            <td colspan="11" class="text-center py-4">
                                                <i class="fas fa-project-diagram fa-3x text-muted mb-3"></i><br>
                                                <h5 class="text-muted">No Projects Found</h5>
                                                <p class="text-muted">Get started by adding your first project.</p>
                                                <a href="add_project.php" class="btn btn-primary">Add New Project</a>
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
</div>

<script>
// Search functionality
document.getElementById('searchInput').addEventListener('keyup', function() {
    const searchText = this.value.toLowerCase();
    const rows = document.querySelectorAll('#projectsTable tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchText) ? '' : 'none';
    });
});

// Filter functionality
document.getElementById('statusFilter').addEventListener('change', filterTable);
document.getElementById('typeFilter').addEventListener('change', filterTable);

function filterTable() {
    const statusFilter = document.getElementById('statusFilter').value;
    const typeFilter = document.getElementById('typeFilter').value;
    const rows = document.querySelectorAll('#projectsTable tbody tr');
    
    rows.forEach(row => {
        const status = row.cells[8].textContent.trim();
        const type = row.cells[4].textContent.trim();
        
        const statusMatch = !statusFilter || status === statusFilter;
        const typeMatch = !typeFilter || type === typeFilter;
        
        row.style.display = statusMatch && typeMatch ? '' : 'none';
    });
}

// Delete confirmation
function confirmDelete(projectId) {
    if(confirm('Are you sure you want to delete this project? This action cannot be undone.')) {
        window.location.href = 'delete_project.php?id=' + projectId;
    }
}

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

<?php include("footer.php"); ?>