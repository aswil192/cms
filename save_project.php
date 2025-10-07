<?php
session_start();
include('connection.php');

// Check if user is logged in as client
if(!isset($_SESSION['uid']) || $_SESSION['type'] != 'client') {
    header("Location: login.php");
    exit();
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $client_id = $_SESSION['uid'];
    $project_name = mysqli_real_escape_string($con, $_POST['project_name']);
    $location = mysqli_real_escape_string($con, $_POST['location']);
    $budget = mysqli_real_escape_string($con, $_POST['budget']);
    $duration = mysqli_real_escape_string($con, $_POST['duration']);
    $start_date = mysqli_real_escape_string($con, $_POST['start_date']);
    $project_type = mysqli_real_escape_string($con, $_POST['project_type']);
    $description = mysqli_real_escape_string($con, $_POST['description']);
    $requirements = mysqli_real_escape_string($con, $_POST['requirements']);
    
    // Set default status
    $status = "Pending";
    
    // Calculate end date based on duration
    $end_date = date('Y-m-d', strtotime($start_date . ' + ' . $duration . ' days'));
    
    // Handle file upload
    $image_name = '';
    if(isset($_FILES['project_image']) && $_FILES['project_image']['error'] == 0) {
        $target_dir = "admin/projects/uploads/";
        
        // Create directory if it doesn't exist
        if(!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $image_name = time() . '_' . basename($_FILES['project_image']['name']);
        $target_file = $target_dir . $image_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        
        // Check if image file is actual image
        $check = getimagesize($_FILES['project_image']['tmp_name']);
        if($check !== false) {
            // Check file size (2MB limit)
            if($_FILES['project_image']['size'] > 2000000) {
                $_SESSION['error'] = "Sorry, your file is too large. Maximum size is 2MB.";
                header("Location: add_project.php");
                exit();
            }
            
            // Allow certain file formats
            $allowed_types = array('jpg', 'jpeg', 'png', 'gif');
            if(in_array($imageFileType, $allowed_types)) {
                if(move_uploaded_file($_FILES['project_image']['tmp_name'], $target_file)) {
                    // File uploaded successfully
                } else {
                    $_SESSION['error'] = "Sorry, there was an error uploading your file.";
                    header("Location: add_project.php");
                    exit();
                }
            } else {
                $_SESSION['error'] = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
                header("Location: add_project.php");
                exit();
            }
        } else {
            $_SESSION['error'] = "File is not an image.";
            header("Location: add_project.php");
            exit();
        }
    }
    
    // Debug: Check what's being inserted
    error_log("Client ID: " . $client_id);
    
    // Insert project into database - FIXED: added project_manager_id field
    $query = "INSERT INTO projects (
                client_id, 
                project_manager_id,
                name, 
                location, 
                description, 
                requirements, 
                start_date, 
                end_date, 
                project_type, 
                budget, 
                duration, 
                status, 
                image, 
                created_at
              ) VALUES (
                '$client_id', 
                NULL,
                '$project_name', 
                '$location', 
                '$description', 
                '$requirements', 
                '$start_date', 
                '$end_date', 
                '$project_type', 
                '$budget', 
                '$duration', 
                '$status', 
                '$image_name', 
                NOW()
              )";
    
    error_log("SQL Query: " . $query);
    
    if(mysqli_query($con, $query)) {
        $project_id = mysqli_insert_id($con);
        $_SESSION['success'] = "Project submitted successfully! It will be reviewed by our team.";
        error_log("Project inserted successfully with ID: " . $project_id);
    } else {
        $error_msg = "Error submitting project: " . mysqli_error($con);
        $_SESSION['error'] = $error_msg;
        error_log("Database error: " . $error_msg);
    }
    
    header("Location: projects.php");
    exit();
} else {
    // If someone tries to access this page directly
    header("Location: add_project.php");
    exit();
}
?>