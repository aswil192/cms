<?php
include('header.php');
?>

        <!-- Header Start -->
        <div class="container-fluid bg-breadcrumb">
            <div class="container text-center py-5" style="max-width: 900px;">
                <h4 class="text-white display-4 mb-4 wow fadeInDown" data-wow-delay="0.1s">BuildMaster Construction Management</h4>
                <ol class="breadcrumb d-flex justify-content-center mb-0 wow fadeInDown" data-wow-delay="0.3s">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item active text-primary">Project Manager Registration</li>
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
                            <h4 class="text-primary">Project Manager Portal</h4>
                            <h1 class="display-4 mb-4">Advanced Project Management Tools for Professionals</h1>
                            <p class="mb-5">
                                Access powerful project management features including team coordination, 
                                resource allocation, budget tracking, and real-time progress monitoring. 
                                Manage multiple construction projects efficiently with our comprehensive toolkit.
                            </p>
                            <div class="mb-4">
                                <h5 class="text-primary">Benefits for Project Managers:</h5>
                                <ul class="list-unstyled">
                                    <li>✓ Manage multiple projects simultaneously</li>
                                    <li>✓ Track team performance and allocation</li>
                                    <li>✓ Monitor budgets and expenses in real-time</li>
                                    <li>✓ Generate detailed project reports</li>
                                    <li>✓ Coordinate with clients and contractors</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 wow fadeInRight" data-wow-delay="0.4s">
                        <div>
                            <p class="mb-4">
                                Register as a Project Manager to access advanced project management features. 
                                Fill in the details below to create your professional account.
                            </p>
                            <form method="POST" onsubmit="return validatePMForm()">
								<div class="row g-4">
									<!-- Name -->
									<div class="col-lg-12 col-xl-6">
										<div class="form-floating">
											<input type="text" class="form-control border-0" name="name" id="name" placeholder="Full Name" required>
											<label for="name">Full Name</label>
											<small id="nameError" class="text-danger" style="display:none;">Name should contain only letters and spaces</small>
										</div>
									</div>
									<!-- Email -->
									<div class="col-lg-12 col-xl-6">
										<div class="form-floating">
											<input type="email" class="form-control border-0" name="email" id="email" placeholder="Your Email" required>
											<label for="email">Your Email</label>
											<small id="emailError" class="text-danger" style="display:none;">Please enter a valid email format</small>
										</div>
									</div>
									<!-- Phone -->
									<div class="col-lg-12 col-xl-6">
										<div class="form-floating">
											<input type="text" class="form-control border-0" name="phone" id="phone" placeholder="Your Phone" maxlength="10" required>
											<label for="phone">Your Phone (10 digits)</label>
											<small id="phoneError" class="text-danger" style="display:none;">Phone number must be exactly 10 digits</small>
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
									<!-- Address -->
									<div class="col-12">
										<div class="form-floating">
											<textarea class="form-control border-0" name="address" id="address" placeholder="Your Address" style="height: 100px"></textarea>
											<label for="address">Your Address (Optional)</label>
										</div>
									</div>
									<!-- Submit Button -->
									<div class="col-12">
										<button type="submit" name="submit_pm" class="btn btn-primary w-100 py-3">Register as Project Manager</button>
									</div>
								</div>
							</form>
							<br>
							<p style="text-align: center; margin-bottom:0px;">Already have an account? <a href="login.php">Login</a></p>

                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Contact End -->

<?php
include("connection.php");
if(isset($_POST['submit_pm']))
{
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = $_POST['password'];
    $address = $_POST['address'];
    
    // Server-side validation
    $errors = array();
    
    // Name validation - letters and spaces only (no numbers)
    if (!preg_match("/^[a-zA-Z ]+$/", $name)) {
        $errors[] = "Name should contain only letters and spaces.";
    }
    
    // Email validation - must contain exactly one @ symbol
    $atCount = substr_count($email, '@');
    if ($atCount !== 1) {
        $errors[] = "Email must contain exactly one @ symbol.";
    }
    
    // Phone validation - exactly 10 digits
    if (!preg_match("/^[0-9]{10}$/", $phone)) {
        $errors[] = "Phone number must be exactly 10 digits.";
    }
    
    // Password validation - at least 8 characters
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    }
    
    // Check if email already exists
    $check_email = "SELECT id FROM project_managers WHERE email = '$email'";
    $result = mysqli_query($con, $check_email);
    if (mysqli_num_rows($result) > 0) {
        $errors[] = "Email already exists. Please use a different email.";
    }
    
    // If no errors, proceed with registration
    if (empty($errors)) {
        // Hash the password
       $ins = "INSERT INTO `project_managers`(`name`, `email`, `phone`, `password`, `address`) 
        VALUES ('$name','$email','$phone','$password','$address')";
$res = mysqli_query($con, $ins);

if($res) {
    echo '<script>alert("Project Manager Registered Successfully!")
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
function validatePMForm() {
    // Get form values
    var name = document.getElementById('name').value;
    var email = document.getElementById('email').value;
    var phone = document.getElementById('phone').value;
    var password = document.getElementById('password').value;
    
    // Reset error messages
    document.getElementById('nameError').style.display = 'none';
    document.getElementById('emailError').style.display = 'none';
    document.getElementById('phoneError').style.display = 'none';
    document.getElementById('passwordError').style.display = 'none';
    
    var isValid = true;
    
    // Name validation - letters and spaces only (no numbers)
    var nameRegex = /^[a-zA-Z ]+$/;
    if (!nameRegex.test(name)) {
        document.getElementById('nameError').style.display = 'block';
        isValid = false;
    }
    
    // Email validation - must contain exactly one @ symbol
    var atCount = (email.match(/@/g) || []).length;
    if (atCount !== 1) {
        document.getElementById('emailError').style.display = 'block';
        isValid = false;
    }
    
    // Phone validation - exactly 10 digits
    var phoneRegex = /^[0-9]{10}$/;
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

document.getElementById('email').addEventListener('input', function() {
    var atCount = (this.value.match(/@/g) || []).length;
    if (atCount !== 1 && this.value.length > 0) {
        document.getElementById('emailError').style.display = 'block';
    } else {
        document.getElementById('emailError').style.display = 'none';
    }
});

document.getElementById('phone').addEventListener('input', function() {
    // Remove non-digit characters
    this.value = this.value.replace(/\D/g, '');
    
    var phoneRegex = /^[0-9]{0,10}$/;
    if (!phoneRegex.test(this.value)) {
        document.getElementById('phoneError').style.display = 'block';
    } else {
        document.getElementById('phoneError').style.display = 'none';
    }
    
    // Limit to 10 digits
    if (this.value.length > 10) {
        this.value = this.value.substring(0, 10);
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