<?php
include('header.php');
include('connection.php');

// Check if user is logged in
if(!isset($_SESSION['uid'])) {
    echo '<script>alert("Please login first!"); window.location="login.php";</script>';
    exit();
}
?>

        <!-- Header Start -->
        <div class="container-fluid bg-breadcrumb">
            <div class="container text-center py-5" style="max-width: 900px;">
                <h4 class="text-white display-4 mb-4 wow fadeInDown" data-wow-delay="0.1s">View Payments</h4>
                <ol class="breadcrumb d-flex justify-content-center mb-0 wow fadeInDown" data-wow-delay="0.3s">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item active text-primary">View Payments</li>
                </ol>    
            </div>
        </div>
        <!-- Header End -->

        <!-- Blog Start -->
        <div class="container-fluid blog py-5">
            <div class="container py-5">
                <div class="row g-4">
                
            <?php
            // Check if payment table exists
            $table_check = mysqli_query($con, "SHOW TABLES LIKE 'payment'");
            if(mysqli_num_rows($table_check) == 0) {
                echo '<div class="alert alert-warning text-center">
                        <h4>No Payment Records Found</h4>
                        <p>The payment table does not exist or has no records.</p>
                        <a href="payment.php" class="btn btn-primary">Make a Payment</a>
                      </div>';
            } else {
                // Fixed: Use client_id instead of user_id
                $query = mysqli_query($con, "SELECT * FROM payment WHERE client_id='{$_SESSION['uid']}'");

                if (mysqli_num_rows($query) > 0) {
                ?>
                    <table class="table table-bordered text-center">
                        <thead class="bg-primary text-white">
                            <tr>
                                <th>ID</th>
                                <th>Project ID</th>
                                <th>Amount (₹)</th>
                                <th>Card Type</th>
                                <th>Cardholder Name</th>
                                <th>Payment Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $i=1;
                            while ($row = mysqli_fetch_assoc($query)) {
                            ?>
                                <tr>
                                    <td><?php echo $i; ?></td>
                                    <td><?php echo $row['project_id']; ?></td>
                                    <td>₹<?php echo number_format($row['amount'], 2); ?></td>
                                    <td><?php echo $row['card_type']; ?></td>
                                    <td><?php echo $row['card_name']; ?></td>
                                    <td><?php echo date("d-M-Y H:i", strtotime($row['payment_date'])); ?></td>
                                    <td>
                                        <?php
                                        if ($row['status'] == 'completed') {
                                            echo "<span class='badge bg-success'>Completed</span>";
                                        } else {
                                            echo "<span class='badge bg-warning'>Pending</span>";
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php
                            $i++;
                            }
                            ?>
                        </tbody>
                    </table>
                <?php
                } else {
                    echo '<div class="alert alert-info text-center">
                            <h4>No Payments Found</h4>
                            <p>You haven\'t made any payments yet.</p>
                            <a href="payment.php" class="btn btn-primary">Make Your First Payment</a>
                          </div>';
                }
            }
            ?>

                </div>
            </div>
         </div>
        <!-- Blog End -->

<?php
include('footer.php');
?>