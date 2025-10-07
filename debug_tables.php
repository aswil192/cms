<?php
session_start();
include("connection.php");

echo "<h3>Database Structure Check</h3>";

// Check if tables exist
$tables = ['projects', 'clients', 'project_manager_assignments', 'progress_updates', 'project_managers'];
foreach ($tables as $table) {
    $result = mysqli_query($con, "SHOW TABLES LIKE '$table'");
    if(mysqli_num_rows($result) > 0) {
        echo "<p style='color: green;'>✓ Table '$table' exists</p>";
        
        // Show table structure
        $columns = mysqli_query($con, "DESCRIBE $table");
        echo "<details><summary>Columns in $table:</summary><ul>";
        while($col = mysqli_fetch_assoc($columns)) {
            echo "<li>{$col['Field']} ({$col['Type']})</li>";
        }
        echo "</ul></details>";
    } else {
        echo "<p style='color: red;'>✗ Table '$table' does NOT exist</p>";
    }
}
?>