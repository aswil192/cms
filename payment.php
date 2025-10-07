<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('header.php');
include('connection.php');

// Check if user is logged in
if(!isset($_SESSION['uid'])) {
    echo '<script>alert("Please login first!"); window.location="login.php";</script>';
    exit();
}

// Handle project_id - try GET, POST, or set a default
$project_id = isset($_GET['project_id']) ? $_GET['project_id'] : (isset($_POST['project_id']) ? $_POST['project_id'] : '1');
$amount = isset($_GET['a']) ? $_GET['a'] : (isset($_POST['a']) ? $_POST['a'] : '50000');

// If no project_id provided, try to get the user's first project from database
if(empty($project_id) || $project_id == '1') {
    $user_id = $_SESSION['uid'];
    $project_query = mysqli_query($con, "SELECT id FROM projects WHERE client_id = '$user_id' LIMIT 1");
    if(mysqli_num_rows($project_query) > 0) {
        $project_data = mysqli_fetch_assoc($project_query);
        $project_id = $project_data['id'];
    } else {
        // If no projects found, use a default project_id
        $project_id = 1;
    }
}
?>

        <!-- Header Start -->
        <div class="container-fluid bg-breadcrumb">
            <div class="container text-center py-5" style="max-width: 900px;">
                <h4 class="text-white display-4 mb-4 wow fadeInDown" data-wow-delay="0.1s">Payment</h4>
                <ol class="breadcrumb d-flex justify-content-center mb-0 wow fadeInDown" data-wow-delay="0.3s">
                    <li class="breadcrumb-item"><a href="index1.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Payment</a></li>
                </ol>    
            </div>
        </div>
        <!-- Header End -->

        <!-- Contact Start -->
        <div class="container-fluid contact bg-light py-5">
            <div class="container py-5">
                <div class="row g-5">
                    <div class="col-lg-12 wow fadeInRight" data-wow-delay="0.4s">
                        <div class="alert alert-info">
                            <strong>Payment Details</strong>
                        </div>
                        <div>
                            <form method="POST" onsubmit="return validatePaymentForm()">
                                <div class="row g-4">
                                    <!-- Hidden fields -->
                                    <input type="hidden" name="user_id" value="<?php echo $_SESSION['uid']; ?>">
                                    <input type="hidden" name="project_id" value="<?php echo $project_id; ?>">
                                    
                                    <div class="col-lg-12 col-xl-12">
                                        <div class="form-floating">
                                            <input type="number" step="0.01" class="form-control border-0" id="amount" value="<?php echo $amount; ?>" name="amount" placeholder="Amount" required min="50000">
                                            <label for="amount">Amount (₹)</label>
                                            <small id="amountError" class="text-danger" style="display:none;">Amount must be at least ₹50,000</small>
                                        </div>
                                    </div>
                                    <div class="col-lg-12 col-xl-6">
                                        <div class="form-floating">
                                            <select class="form-control border-0" id="card_type" name="card_type" required>
                                                <option value="">Select Card Type</option>
                                                <option value="Credit">Credit Card</option>
                                                <option value="Debit">Debit Card</option>
                                            </select>
                                            <label for="card_type">Card Type</label>
                                        </div>
                                    </div>
                                    <div class="col-lg-12 col-xl-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control border-0" id="card_name" name="card_name" placeholder="Cardholder Name" required>
                                            <label for="card_name">Cardholder Name</label>
                                            <small id="cardNameError" class="text-danger" style="display:none;">Cardholder name should contain only letters and spaces</small>
                                        </div>
                                    </div>
                                    <div class="col-lg-12 col-xl-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control border-0" id="card_no" name="card_no" placeholder="Card Number" maxlength="16" required>
                                            <label for="card_no">Card Number</label>
                                            <small id="cardNoError" class="text-danger" style="display:none;">Card number must be exactly 16 digits</small>
                                        </div>
                                    </div>
                                    <div class="col-lg-12 col-xl-6">
                                        <div class="form-floating">
                                            <input type="password" class="form-control border-0" id="cvv" name="cvv" placeholder="Card CVV" maxlength="3" required>
                                            <label for="cvv">CVV</label>
                                            <small id="cvvError" class="text-danger" style="display:none;">CVV must be exactly 3 digits</small>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <button class="btn btn-primary w-100 py-3" type="submit" name="submit">Submit Payment</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Contact End -->
        
        <?php
        if(isset($_POST['submit']))
        {
            // Get form data
            $client_id = $_POST['user_id'];
            $project_id = $_POST['project_id'];
            $amount = $_POST['amount'];
            $card_type = $_POST['card_type'];
            $card_name = $_POST['card_name'];
            $card_no = $_POST['card_no'];
            $cvv = $_POST['cvv'];
            $payment_date = date("Y-m-d H:i:s"); 
            $status = "completed"; 
            
            // Server-side validation
            $errors = array();
            
            if ($amount < 50000) {
                $errors[] = "Amount must be at least ₹50,000.";
            }
            
            if (!preg_match("/^[a-zA-Z ]+$/", $card_name)) {
                $errors[] = "Cardholder name should contain only letters and spaces.";
            }
            
            if (!preg_match("/^[0-9]{16}$/", $card_no)) {
                $errors[] = "Card number must be exactly 16 digits.";
            }
            
            if (!preg_match("/^[0-9]{3}$/", $cvv)) {
                $errors[] = "CVV must be exactly 3 digits.";
            }
            
            if (empty($errors)) {
                // Escape variables for security
                $client_id = mysqli_real_escape_string($con, $client_id);
                $project_id = mysqli_real_escape_string($con, $project_id);
                $amount = mysqli_real_escape_string($con, $amount);
                $card_type = mysqli_real_escape_string($con, $card_type);
                $card_name = mysqli_real_escape_string($con, $card_name);
                $card_no = mysqli_real_escape_string($con, $card_no);
                $cvv = mysqli_real_escape_string($con, $cvv);
                
                // First, let's check if the payment table exists
                $table_check = mysqli_query($con, "SHOW TABLES LIKE 'payment'");
                if(mysqli_num_rows($table_check) == 0) {
                    // Create the payment table if it doesn't exist
                    $create_table = "CREATE TABLE payment (
                        id INT(11) PRIMARY KEY AUTO_INCREMENT,
                        project_id INT(11) NOT NULL,
                        client_id INT(11) NOT NULL,
                        amount DECIMAL(10,2) NOT NULL,
                        card_type VARCHAR(20) NOT NULL,
                        card_name VARCHAR(100) NOT NULL,
                        card_no VARCHAR(16) NOT NULL,
                        cvv VARCHAR(3) NOT NULL,
                        payment_date DATETIME NOT NULL,
                        status VARCHAR(20) DEFAULT 'completed',
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )";
                    
                    if(mysqli_query($con, $create_table)) {
                        echo '<script>alert("Payment table created successfully!");</script>';
                    } else {
                        $error_msg = "Failed to create payment table: " . mysqli_error($con);
                        echo '<script>alert("' . $error_msg . '")</script>';
                    }
                }
                
                $ins = "INSERT INTO payment(project_id, client_id, amount, card_type, card_name, card_no, cvv, payment_date, status) 
                        VALUES('$project_id', '$client_id', '$amount', '$card_type', '$card_name', '$card_no', '$cvv', '$payment_date', '$status')";
                
                $res = mysqli_query($con, $ins);
                
                if($res) {
                    echo '<script>alert("Payment Successful!");
                          window.location="vpayment.php";
                          </script>';
                } else {
                    $error_msg = "Payment failed. Database error: " . mysqli_error($con);
                    echo '<script>alert("' . $error_msg . '")</script>';
                }
            } else {
                echo '<script>alert("' . implode("\\n", $errors) . '")</script>';
            }
        }
        ?>

<script>
function validatePaymentForm() {
    var cardName = document.getElementById('card_name').value;
    var cardNo = document.getElementById('card_no').value;
    var cvv = document.getElementById('cvv').value;
    var amount = document.getElementById('amount').value;
    
    document.getElementById('cardNameError').style.display = 'none';
    document.getElementById('cardNoError').style.display = 'none';
    document.getElementById('cvvError').style.display = 'none';
    document.getElementById('amountError').style.display = 'none';
    
    var isValid = true;
    
    if (amount < 10000) {
        document.getElementById('amountError').style.display = 'block';
        isValid = false;
    }
    
    var nameRegex = /^[a-zA-Z ]+$/;
    if (!nameRegex.test(cardName)) {
        document.getElementById('cardNameError').style.display = 'block';
        isValid = false;
    }
    
    var cardRegex = /^[0-9]{16}$/;
    if (!cardRegex.test(cardNo)) {
        document.getElementById('cardNoError').style.display = 'block';
        isValid = false;
    }
    
    var cvvRegex = /^[0-9]{3}$/;
    if (!cvvRegex.test(cvv)) {
        document.getElementById('cvvError').style.display = 'block';
        isValid = false;
    }
    
    return isValid;
}

// Real-time validation
document.getElementById('amount').addEventListener('input', function() {
    if (this.value < 50000 && this.value.length > 0) {
        document.getElementById('amountError').style.display = 'block';
    } else {
        document.getElementById('amountError').style.display = 'none';
    }
});

document.getElementById('card_name').addEventListener('input', function() {
    var nameRegex = /^[a-zA-Z ]*$/;
    if (!nameRegex.test(this.value)) {
        document.getElementById('cardNameError').style.display = 'block';
    } else {
        document.getElementById('cardNameError').style.display = 'none';
    }
});

document.getElementById('card_no').addEventListener('input', function() {
    this.value = this.value.replace(/\D/g, '');
    if (this.value.length > 16) {
        this.value = this.value.substring(0, 16);
    }
});

document.getElementById('cvv').addEventListener('input', function() {
    this.value = this.value.replace(/\D/g, '');
    if (this.value.length > 3) {
        this.value = this.value.substring(0, 3);
    }
});
</script>

<?php
include('footer.php');
?>