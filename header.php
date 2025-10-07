<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="utf-8">
        <title>BuildMaster - Construction Management System</title>
        <meta content="width=device-width, initial-scale=1.0" name="viewport">
        <meta content="" name="keywords">
        <meta content="" name="description">

        <!-- Google Web Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&family=Edu+TAS+Beginner:wght@400..700&family=Jost:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

        <!-- Icon Font Stylesheet -->
        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css"/>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

        <!-- Libraries Stylesheet -->
        <link rel="stylesheet" href="lib/animate/animate.min.css"/>
        <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">


        <!-- Customized Bootstrap Stylesheet -->
        <link href="css/bootstrap.min.css" rel="stylesheet">

        <!-- Template Stylesheet -->
        <link href="css/style.css" rel="stylesheet">
    </head>

    <body>

        <!-- Spinner Start -->
        <div id="spinner" class="show bg-white position-fixed translate-middle w-100 vh-100 top-50 start-50 d-flex align-items-center justify-content-center">
            <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                <span class="sr-only">Loading...</span>
            </div>
        </div>
        <!-- Spinner End -->

        

        <!-- Navbar & Hero Start -->
        <div class="container-fluid header-top">
            <div class="container d-flex align-items-center">
                <div class="d-flex align-items-center h-100">
                    <a href="#" class="navbar-brand" style="height: 125px;">
                        <h1 class="text-primary mb-0"><i class="fas fa-hard-hat"></i> BuildMaster</h1>
                        <!-- <img src="img/logo.png" alt="Logo"> -->
                    </a>
                </div>
                <div class="w-100 h-100">
                    <div class="topbar px-0 py-2 d-none d-lg-block" style="height: 45px;">
                        <div class="row gx-0 align-items-center">
                            <div class="col-lg-4 text-center text-lg-end">
                                
                            </div>
                        </div>
                    </div>
                    <div class="nav-bar px-0 py-lg-0" style="height: 80px;">
                        <nav class="navbar navbar-expand-lg navbar-light d-flex justify-content-lg-end">
                            <a href="#" class="navbar-brand-2">
                                <h1 class="text-primary mb-0"><i class="fas fa-bolt"></i> Electra</h1>
                                <!-- <img src="img/logo.png" alt="Logo"> -->
                            </a>  
                            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                                <span class="fa fa-bars"></span>
                            </button>
                            <div class="collapse navbar-collapse" id="navbarCollapse">
                                <?php
                                error_reporting(0);
                                session_start();
                                
                                // Check if user is logged in using the correct session variable
                                if(isset($_SESSION['user']) && $_SESSION['user'] != '') {
                                    // User is logged in, show appropriate menu based on user type
                                    if($_SESSION['user'] == 'client') {
                                ?>
                                
                                <div class="navbar-nav mx-0 mx-lg-auto bg-white">
                                    <a href="index1.php" class="nav-item nav-link">Home</a>
                                    <a href="add_project.php" class="nav-item nav-link">Add Project</a>
                                    <a href="projects.php" class="nav-item nav-link">View Projects</a>
                                    <a href="complaint.php" class="nav-item nav-link">Complaints</a>
                                    <div class="nav-item dropdown">
                                        <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                                            <i class="fas fa-user me-1"></i><?php echo isset($_SESSION['name']) ? $_SESSION['name'] : 'User'; ?>
                                        </a>
                                        <div class="dropdown-menu">
                                            <a href="logout.php" class="dropdown-item"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
                                        </div>
                                    </div>
                                </div>
                                <?php
                                    } else if($_SESSION['user'] == 'project_manager') {
                                        // Project Manager menu
                                ?>
                                <div class="navbar-nav mx-0 mx-lg-auto bg-white">
                                    <a href="index1.php" class="nav-item nav-link">Dashboard</a>
                                    <a href="projects.php" class="nav-item nav-link">Projects</a>
                                    <a href="complaint.php" class="nav-item nav-link">Complaints</a>
                                    <div class="nav-item dropdown">
                                        <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                                            <i class="fas fa-user-tie me-1"></i><?php echo isset($_SESSION['name']) ? $_SESSION['name'] : 'Manager'; ?>
                                        </a>
                                        <div class="dropdown-menu">
                                            <a href="logout.php" class="dropdown-item"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
                                        </div>
                                    </div>
                                </div>
                                <?php
                                    } else if($_SESSION['user'] == 'admin') {
                                        // Admin menu
                                ?>
                                <div class="navbar-nav mx-0 mx-lg-auto bg-white">
                                    <a href="index1.php" class="nav-item nav-link">Dashboard</a>
                                    <a href="projects.php" class="nav-item nav-link">Projects</a>
                                    <a href="complaint.php" class="nav-item nav-link">Complaints</a>
                                    <div class="nav-item dropdown">
                                        <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                                            <i class="fas fa-user-cog me-1"></i><?php echo isset($_SESSION['name']) ? $_SESSION['name'] : 'Admin'; ?>
                                        </a>
                                        <div class="dropdown-menu">
                                            <a href="logout.php" class="dropdown-item"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
                                        </div>
                                    </div>
                                </div>
                                <?php
                                    }
                                } else {
                                    // User is not logged in, show public menu
                                ?>
                                <div class="navbar-nav mx-0 mx-lg-auto bg-white">
                                    <a href="index.php" class="nav-item nav-link">Home</a>
                                    <a href="about.php" class="nav-item nav-link">About</a>
                                    <div class="nav-btn ps-3">
                                        <a href="login.php" class="btn btn-primary py-2 px-4 ms-0 ms-lg-3">Login</a>
                                    </div>
                                </div>
                                <?php
                                }
                                ?>
                            </div>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
        <!-- Navbar & Hero End -->