<?php
include('header.php');
?>

        <!-- Header Start -->
        <div class="container-fluid bg-breadcrumb">
            <div class="container text-center py-5" style="max-width: 900px;">
                <h4 class="text-white display-4 mb-4 wow fadeInDown" data-wow-delay="0.1s">BuildMaster Construction Management</h4>
                <ol class="breadcrumb d-flex justify-content-center mb-0 wow fadeInDown" data-wow-delay="0.3s">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item active text-primary">Register</li>
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
                                Register your project today and take control of your construction site. 
                                Fill in the details below to get started.
                            </p>
                            <form method="POST" onsubmit="return validateForm()">
								<div class="row g-4">
									<!-- Name -->
									<div class="col-lg-12 col-xl-6">
										<div class="form-floating">
											<input type="text" class="form-control border-0" name="name" id="name" placeholder="Your Name" required>
											<label for="name">Your Name</label>
											<small id="nameError" class="text-danger" style="display:none;">Name should contain only letters and spaces</small>
										</div>
									</div>
									<!-- Email -->
									<div class="col-lg-12 col-xl-6">
										<div class="form-floating">
											<input type="email" class="form-control border-0" name="email" id="email" placeholder="Your Email" required>
											<label for="email">Your Email</label>
										</div>
									</div>
									<!-- Phone -->
									<div class="col-lg-12 col-xl-6">
										<div class="form-floating">
											<input type="text" class="form-control border-0" name="phone" id="phone" placeholder="Your Phone" required>
											<label for="phone">Your Phone</label>
											<small id="phoneError" class="text-danger" style="display:none;">Phone number should contain only digits</small>
										</div>
									</div>
									<!-- Password -->
									<div class="col-lg-12 col-xl-6">
										<div class="form-floating">
											<input type="password" class="form-control border-0" name="password" id="password" placeholder="Password" required>
											<label for="password">Password</label>
											<small id="passwordError" class="text-danger" style="display:none;">Password must be at least 8 characters long</small>
										</div>
									</div>
									<!-- Submit Button -->
									<div class="col-12">
										<button type="submit" name="submit" class="btn btn-primary w-100 py-3">Register Now</button>
									</div>
								</div>
							</form>
							<br>
							<p style="margin-left: 200px; margin-bottom:0px;">Already have an account? <a href="login.php">Login Now</a></p>

                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Contact End -->

<?php
include("connection.php");
if(isset($_POST['submit']))
{
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = $_POST['password'];
    
    // Server-side validation
    $errors = array();
    
    // Name validation - only letters and spaces
    if (!preg_match("/^[a-zA-Z ]+$/", $name)) {
        $errors[] = "Name should contain only letters and spaces.";
    }
    
    // Phone validation - only numbers
    if (!preg_match("/^[0-9]+$/", $phone)) {
        $errors[] = "Phone number should contain only digits.";
    }
    
    // Password validation - at least 8 characters
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    }
    
    // If no errors, proceed with registration
    if (empty($errors)) {
        $ins = "INSERT INTO `users`(`name`, `email`, `phone`, `password`) 
                VALUES ('$name','$email','$phone','$password')";
        $res = mysqli_query($con, $ins);
        
        if($res) {
            echo '<script>alert("Successfully Registered!")
                  window.location="login.php";
                  </script>';
        } else {
            echo '<script>alert("Registration failed. Please try again.")</script>';
        }
    } else {
        // Display errors
        echo '<script>alert("' . implode("\\n", $errors) . '")</script>';
    }
}
?>

<script>
function validateForm() {
    // Get form values
    var name = document.getElementById('name').value;
    var phone = document.getElementById('phone').value;
    var password = document.getElementById('password').value;
    
    // Reset error messages
    document.getElementById('nameError').style.display = 'none';
    document.getElementById('phoneError').style.display = 'none';
    document.getElementById('passwordError').style.display = 'none';
    
    var isValid = true;
    
    // Name validation - only letters and spaces
    var nameRegex = /^[a-zA-Z ]+$/;
    if (!nameRegex.test(name)) {
        document.getElementById('nameError').style.display = 'block';
        isValid = false;
    }
    
    // Phone validation - only numbers
    var phoneRegex = /^[0-9]+$/;
    if (!phoneRegex.test(phone)) {
        document.getElementById('phoneError').style.display = 'block';
        isValid = false;
    }
    
    // Password validation - at least 8 characters
    if (password.length < 8) {
        document.getElementById('passwordError').style.display = 'block';
        isValid = false;
    }
    
    return isValid;
}

// Real-time validation as user types
document.getElementById('name').addEventListener('input', function() {
    var nameRegex = /^[a-zA-Z ]*$/;
    if (!nameRegex.test(this.value)) {
        document.getElementById('nameError').style.display = 'block';
    } else {
        document.getElementById('nameError').style.display = 'none';
    }
});

document.getElementById('phone').addEventListener('input', function() {
    var phoneRegex = /^[0-9]*$/;
    if (!phoneRegex.test(this.value)) {
        document.getElementById('phoneError').style.display = 'block';
    } else {
        document.getElementById('phoneError').style.display = 'none';
    }
});

document.getElementById('password').addEventListener('input', function() {
    if (this.value.length < 8 && this.value.length > 0) {
        document.getElementById('passwordError').style.display = 'block';
    } else {
        document.getElementById('passwordError').style.display = 'none';
    }
});
</script>

<?php
include('footer.php');
?>