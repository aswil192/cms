<?php
session_start();
include('connection.php');

$myusername=$_POST['email']; 
$mypassword=$_POST['password']; 

if(isset($_POST['submit']))
{
    if($myusername=="admin@yandex.com" and $mypassword=="admin@123")
    {
        $_SESSION['user'] = "admin";
        $_SESSION['user_type'] = "admin";
        $_SESSION['type'] = "admin"; // Add this for compatibility
        $_SESSION['user_id'] = 1;
        $_SESSION['uid'] = 1;
        $_SESSION['name'] = "admin";
        header("location:admin_dashboard.php");
    }
    else{
        if($_POST['type']=='client')
        {
            $sel="SELECT * FROM users WHERE email='$myusername' and password='$mypassword'";
            $result = mysqli_query($con,$sel) or die(mysqli_error($con));
            $rows = mysqli_num_rows($result);
            $row=mysqli_fetch_array($result);
            
            if($rows>0)
            {	
                $_SESSION['user'] = 'client';
                $_SESSION['user_type'] = 'client';
                $_SESSION['type'] = 'client'; // Add this for compatibility
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['uid'] = $row['id'];
                $_SESSION['name'] = $row['name'];
                header("location:index1.php");
            }
            else{
                header("location:login.php?st=fail");
            }
        }
        elseif($_POST['type']=='project_manager')
        {
            $sel="SELECT * FROM project_managers WHERE email='$myusername' and password='$mypassword'";
            $result = mysqli_query($con,$sel) or die(mysqli_error($con));
            $rows = mysqli_num_rows($result);
            $row=mysqli_fetch_array($result);
            
            if($rows>0)
            {	
                $_SESSION['user'] = 'project_manager';
                $_SESSION['user_type'] = 'project_manager';
                $_SESSION['type'] = 'project_manager'; // Add this for compatibility
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['uid'] = $row['id'];
                $_SESSION['email'] = $row['email'];
                $_SESSION['name'] = $row['name'];
                header("location:pm_dashboard.php");
            }
            else{
                header("location:login.php?st=fail");
            }
        }
        else
        {
            header("location:login.php?st=fail");
        }
    }
}
?>