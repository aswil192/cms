<?php
// setup_manager_relationship.php
include("connection.php");

// Check if user is admin
session_start();
if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Handle the setup
if(isset($_POST['setup_relationship'])) {
    // Check if column already exists
    $check_column = mysqli_query($con, "SHOW COLUMNS FROM projects LIKE 'project_manager_id'");
    
    if($check_column && mysqli_num_rows($check_column) > 0) {
        $success = "Project-manager relationship is already configured!";
    } else {
        // Add the column
        $alter_query = "ALTER TABLE projects ADD COLUMN project_manager_id INT NULL AFTER client_id";
        if(mysqli_query($con, $alter_query)) {
            // Try to add foreign key constraint
            $fk_query = "ALTER TABLE projects ADD CONSTRAINT fk_project_manager 
                        FOREIGN KEY (project_manager_id) REFERENCES project_managers(id) ON DELETE SET NULL";
            if(mysqli_query($con, $fk_query)) {
                $success = "Project-manager relationship configured successfully!";
            } else {
                // If foreign key fails, it's okay - we'll still have the column
                $success = "Project-manager column added successfully! (Foreign key constraint may require database privileges)";
            }
        } else {
            $error = "Error adding project_manager_id column: " . mysqli_error($con);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Project-Manager Relationship</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
<?php include("admin_menu.php"); ?>

<div class="container-fluid mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-database me-2"></i>Setup Project-Manager Relationship</h4>
                </div>
                <div class="card-body">
                    <?php if(isset($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <?php if(isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <div class="alert alert-info">
                        <h5><i class="fas fa-info-circle me-2"></i>What this will do:</h5>
                        <ul class="mb-0">
                            <li>Add a <code>project_manager_id</code> column to the <code>projects</code> table</li>
                            <li>Establish a foreign key relationship with <code>project_managers</code> table</li>
                            <li>Enable project assignment functionality</li>
                        </ul>
                    </div>
                    
                    <form method="POST">
                        <div class="text-center">
                            <button type="submit" name="setup_relationship" class="btn btn-success btn-lg">
                                <i class="fas fa-cog me-2"></i>Setup Project-Manager Relationship
                            </button>
                        </div>
                    </form>
                    
                    <hr>
                    
                    <h5>Current Database Structure:</h5>
                    <?php
                    // Show current projects table structure
                    $result = mysqli_query($con, "DESCRIBE projects");
                    if($result) {
                        echo '<div class="table-responsive"><table class="table table-bordered table-sm">';
                        echo '<thead><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr></thead>';
                        echo '<tbody>';
                        while($row = mysqli_fetch_assoc($result)) {
                            echo "<tr>";
                            echo "<td><strong>{$row['Field']}</strong></td>";
                            echo "<td>{$row['Type']}</td>";
                            echo "<td>{$row['Null']}</td>";
                            echo "<td>{$row['Key']}</td>";
                            echo "<td>{$row['Default']}</td>";
                            echo "<td>{$row['Extra']}</td>";
                            echo "</tr>";
                        }
                        echo '</tbody></table></div>';
                    }
                    ?>
                </div>
            </div>
            
            <div class="text-center mt-3">
                <a href="all_managers.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Managers
                </a>
            </div>
        </div>
    </div>
</div>

<?php include("footer.php"); ?>
</body>
</html>