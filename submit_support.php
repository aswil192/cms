<?php
session_start();
include('connection.php');

// Check if user is logged in as client
if(!isset($_SESSION['uid']) || $_SESSION['type'] != 'client') {
    header("Location: login.php");
    exit();
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $client_id = $_SESSION['uid'];
    $subject = mysqli_real_escape_string($con, $_POST['subject']);
    $message = mysqli_real_escape_string($con, $_POST['message']);
    $priority = mysqli_real_escape_string($con, $_POST['priority']);
    
    // Validate priority
    $allowed_priorities = ['low', 'medium', 'high', 'urgent'];
    if (!in_array($priority, $allowed_priorities)) {
        $priority = 'medium';
    }
    
    // Insert support ticket into database
    $query = "INSERT INTO support_tickets (client_id, subject, message, priority, status, created_at) 
              VALUES ('$client_id', '$subject', '$message', '$priority', 'open', NOW())";
    
    if(mysqli_query($con, $query)) {
        $_SESSION['success'] = "Your support ticket has been submitted successfully! We'll get back to you within 24 hours.";
    } else {
        $_SESSION['error'] = "Error submitting support ticket: " . mysqli_error($con);
    }
    
    header("Location: help.php");
    exit();
} else {
    header("Location: help.php");
    exit();
}
?>