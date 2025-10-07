<?php
// menu.php - Project Manager Navigation Menu
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>BuildMaster - Project Manager</title>
    
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
            background: var(--primary);
            color: white;
            transition: all 0.3s;
        }
        
        .sidebar .logo {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid #34495e;
        }
        
        .sidebar .nav-link {
            color: #bdc3c7;
            padding: 12px 20px;
            border-left: 4px solid transparent;
            transition: all 0.3s;
        }
        
        .sidebar .nav-link:hover, 
        .sidebar .nav-link.active {
            color: white;
            background: #34495e;
            border-left: 4px solid var(--secondary);
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
        
        .card-dashboard {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .card-dashboard .card-header {
            background: white;
            border-bottom: 1px solid #eee;
            font-weight: 600;
        }
        
        .stat-card {
            text-align: center;
            padding: 20px;
        }
        
        .stat-card h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .stat-card.primary { border-left: 4px solid var(--primary); }
        .stat-card.success { border-left: 4px solid var(--success); }
        .stat-card.warning { border-left: 4px solid var(--warning); }
        .stat-card.danger { border-left: 4px solid var(--danger); }
        
        .progress {
            height: 10px;
            margin-bottom: 5px;
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
                <h4><i class="fas fa-hard-hat"></i> BuildMaster</h4>
                <small class="text-muted">DashBoard</small>
            </div>
            
            <nav class="nav flex-column py-3">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'pm_dashboard.php' ? 'active' : ''; ?>" href="pm_dashboard.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'assigned_projects.php' ? 'active' : ''; ?>" href="assigned_projects.php">
                    <i class="fas fa-project-diagram"></i> My Projects
                </a>
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'project_progress.php' ? 'active' : ''; ?>" href="project_progress.php">
                    <i class="fas fa-chart-line"></i> Progress Tracking
                </a>
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'clients.php' ? 'active' : ''; ?>" href="clients.php">
                    <i class="fas fa-handshake"></i> Clients
                </a>
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'resources.php' ? 'active' : ''; ?>" href="resources.php">
                    <i class="fas fa-tools"></i> Resources
                </a>
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'payments.php' ? 'active' : ''; ?>" href="payments.php">
                    <i class="fas fa-credit-card"></i> Payments
                </a>
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>" href="profile.php">
                    <i class="fas fa-user-cog"></i> My Profile
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
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-user-circle me-1"></i> 
                                    <?php echo isset($_SESSION['name']) ? $_SESSION['name'] : 'Project Manager'; ?>
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
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