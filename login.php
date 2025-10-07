<?php
include('header.php');
?>

        <!-- Header Start -->
        <div class="container-fluid bg-breadcrumb">
            <div class="container text-center py-5" style="max-width: 900px;">
                <h4 class="text-white display-4 mb-4 wow fadeInDown" data-wow-delay="0.1s">BuildMaster Construction Management</h4>
                <ol class="breadcrumb d-flex justify-content-center mb-0 wow fadeInDown" data-wow-delay="0.3s">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item active text-primary">Login</li>
                </ol>    
            </div>
        </div>
        <!-- Header End -->


        <!-- Contact Start -->
        <div class="container-fluid contact bg-light py-5">
            <div class="container py-5">
                <div class="row g-5">
                    <div class="col-lg-6 wow fadeInLeft" data-wow-delay="0.2s">
					<div>
						<h4 class="text-primary">Project Monitoring & Management</h4>
						<h1 class="display-4 mb-4">Real-Time Insights for Smarter Construction Management</h1>
						<p class="mb-5">
							Stay updated with real-time project progress, resource allocation, and budget tracking. 
							Receive instant alerts on delays, budget overruns, and critical milestones. 
							Ensure smooth coordination between clients, project managers, and teams to deliver projects on time and within budget.
						</p>
					</div>
				</div>
                    <div class="col-lg-6 wow fadeInRight" data-wow-delay="0.4s">
                        <div>
                            <p class="mb-4">
                                Login to continue
                            </p>
							<?php
								error_reporting(0);
								if($_REQUEST['st']=="fail")
								{
									echo"<div class='alert alert-danger alert-dismissible fade show' role='alert'>
									<center><b>Incorrect Username or Password!</b></center>
								</div>";
								}
								?>
                            <form method="POST" action="check.php">
								<div class="row g-4">
									<div class="col-lg-12 col-xl-12">
										<div class="form-floating">
											<input type="email" class="form-control border-0" name="email" placeholder="Your Email">
											<label for="email">Your Email</label>
										</div>
									</div>
									<div class="col-lg-12 col-xl-12">
										<div class="form-floating">
											<input type="password" class="form-control border-0" name="password" placeholder="Password">
											<label for="password">Password</label>
										</div>
									</div>
									<div class="col-lg-12 col-xl-12">
										<div class="form-floating">
											<select class="form-select border-0" name="type">
												<option value="admin">Admin</option>
												<option value="project_manager">Project Manager</option>
												<option value="client">Client</option>
											</select>
											<label for="phase">User Type</label>
										</div>
									</div>
									<!-- Submit Button -->
									<div class="col-12">
										<button type="submit" name="submit" class="btn btn-primary w-100 py-3">Login Now</button>
									</div>
								</div>
							</form>
							<br>
							<div class="text-center">
								<p class="mb-2">Don't have an account?</p>
								<div class="d-flex justify-content-center gap-3">
									<a href="register.php" class="btn btn-outline-primary">Register as Client</a>
									<a href="pm_register.php" class="btn btn-outline-secondary">Register as Project Manager</a>
								</div>
							</div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Contact End -->


        
<?php
include('footer.php');
?>