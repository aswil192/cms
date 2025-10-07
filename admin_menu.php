<?php
// admin_menu.php - Admin Navigation Menu
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>BuildMaster - Admin Panel</title>
    
    <!-- Bootstrap CSS -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Your Custom CSS -->
    <link href="css/style.css" rel="stylesheet">
    
    <!-- Dashboard Custom CSS -->
    <style>
        :root {
            --primary: #2c3e50;
            --secondary: #3498db;
            --success: #27ae60;
            --warning: #f39c12;
            --danger: #e74c3c;
            --light: #ecf0f1;
            --dark: #2c3e50;
        }
        
        .dashboard-wrapper {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 250px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            transition: all 0.3s;
        }
        
        .sidebar .logo {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            border-left: 4px solid transparent;
            transition: all 0.3s;
        }
        
        .sidebar .nav-link:hover, 
        .sidebar .nav-link.active {
            color: white;
            background: rgba(255,255,255,0.1);
            border-left: 4px solid #ffd700;
        }
        
        .sidebar .nav-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        .main-content {
            flex: 1;
            background: #f8f9fa;
            padding: 20px;
        }
        
        .navbar-top {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .admin-badge {
            background: linear-gradient(45deg, #ff6b6b, #ee5a24);
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: bold;
        }
        
        @media (max-width: 768px) {
            .dashboard-wrapper {
                flex-direction: column;
            }
            .sidebar {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="logo">
                <h4><i class="fas fa-crown"></i> BuildMaster</h4>
                <small>Admin Panel</small>
            </div>
            
            <nav class="nav flex-column py-3">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'active' : ''; ?>" href="admin_dashboard.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'all_projects.php' ? 'active' : ''; ?>" href="all_projects.php">
                    <i class="fas fa-project-diagram"></i> Manage Projects
                </a>
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'all_clients.php' ? 'active' : ''; ?>" href="all_clients.php">
                    <i class="fas fa-users"></i> Manage Clients
                </a>
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'all_managers.php' ? 'active' : ''; ?>" href="all_managers.php">
                    <i class="fas fa-user-plus"></i> Project Managers
                </a>
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_payments.php' ? 'active' : ''; ?>" href="admin_payments.php">
                    <i class="fas fa-credit-card"></i> Payments
                </a>
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'view_complaints.php' ? 'active' : ''; ?>" href="view_complaints.php">
                    <i class="fas fa-exclamation-circle"></i> Complaints
                </a>
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_resources.php' ? 'active' : ''; ?>" href="admin_resources.php">
                    <i class="fas fa-tools"></i> Resources
                </a>
                
                
                <div class="mt-3"></div>
                <a class="nav-link text-warning" href="logout.php">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Navigation -->
            <nav class="navbar navbar-expand-lg navbar-top">
                <div class="container-fluid">
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarTop">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    
                    <div class="collapse navbar-collapse" id="navbarTop">
                        <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                            <li class="nav-item">
                                <span class="nav-link admin-badge">
                                    <i class="fas fa-shield-alt me-1"></i>ADMIN
                                </span>
                            </li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-user-shield me-1"></i> 
                                    <?php echo isset($_SESSION['username']) ? $_SESSION['username'] : 'Admin'; ?>
                                </a>
                                <ul class="dropdown-menu">
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>
            
            <!-- Content Area -->
            <div class="content-area">
                <!-- Page content will be inserted here -->