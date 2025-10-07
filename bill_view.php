<?php
include('header.php');
include('connection.php');

$project_id = $_REQUEST['id'];

// Fetch project
$query = "SELECT * FROM projects WHERE id='" . $project_id . "'";
$result = mysqli_query($con, $query);
$project = mysqli_fetch_assoc($result);

// Fetch resources
$res_query = mysqli_query($con, "SELECT * FROM resources WHERE project_id='" . $project_id . "'");
$total_expense = 0;
$resources = array();
if(mysqli_num_rows($res_query) > 0){
    while($res = mysqli_fetch_array($res_query)){
        $res['total_cost'] = $res['quantity'] * $res['cost'];
        $total_expense += $res['total_cost'];
        $resources[] = $res;
    }
}
$balance = $project['budget'] - $total_expense;

// Calculate client payments from the new payment table
$payment_query = mysqli_query($con, "SELECT SUM(amount) AS paid_amount FROM payment WHERE project_id='" . $project_id . "' AND status='completed'");
$payment_row = mysqli_fetch_assoc($payment_query);
$paid_amount = isset($payment_row['paid_amount']) ? $payment_row['paid_amount'] : 0;
$remaining_payment = $total_expense - $paid_amount;
if($remaining_payment < 0) $remaining_payment = 0;
?>

<!-- Header Start -->
<div class="container-fluid bg-breadcrumb">
    <div class="container text-center py-5" style="max-width: 900px;">
        <h4 class="text-white display-4 mb-4 wow fadeInDown" data-wow-delay="0.1s">Project Bill</h4>   
        <p class="text-white fs-5">View and pay for allocated resources of your project.</p>
    </div>
</div>
<!-- Header End -->

<!-- Bill / Payment Table Section -->
<div class="container-fluid py-5 bg-light">
    <div class="container py-5">
        <div class="text-center mb-5 wow fadeInUp">
            <h4 class="text-primary">Client Bill</h4>
            <h1 class="display-4">Project: <?php echo $project['name']; ?></h1>
        </div>

        <!-- Resources Table -->
        <div class="table-responsive wow fadeInUp mb-4">
            <table class="table table-bordered table-striped">
                <thead class="table-primary text-center">
                    <tr>
                        <th>#</th>
                        <th>Resource Name</th>
                        <th>Type</th>
                        <th>Quantity</th>
                        <th>Cost per Unit (₹)</th>
                        <th>Total Cost (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if(count($resources) > 0){
                        $i = 1;
                        foreach($resources as $res){
                    ?>
                    <tr class="text-center">
                        <td><?php echo $i++; ?></td>
                        <td><?php echo $res['name']; ?></td>
                        <td><?php echo $res['type']; ?></td>
                        <td><?php echo $res['quantity']; ?></td>
                        <td><?php echo number_format($res['cost'], 2); ?></td>
                        <td><?php echo number_format($res['total_cost'], 2); ?></td>
                    </tr>
                    <?php
                        }
                    } else {
                        echo '<tr><td colspan="6" class="text-center">No resources allocated yet.</td></tr>';
                    }
                    ?>
                </tbody>
                <tfoot>
                    <tr class="table-secondary text-center">
                        <th colspan="5" class="text-end">Total Expense:</th>
                        <th>₹<?php echo number_format($total_expense, 2); ?></th>
                    </tr>
                    <tr class="table-info text-center">
                        <th colspan="5" class="text-end">Already Paid:</th>
                        <th>₹<?php echo number_format($paid_amount, 2); ?></th>
                    </tr>
                    <tr class="table-success text-center">
                        <th colspan="5" class="text-end">Remaining Payment:</th>
                        <th>₹<?php echo number_format($remaining_payment, 2); ?></th>
                    </tr>
                    <tr class="table-warning text-center">
                        <th colspan="6">
                            <?php if($remaining_payment > 0){ ?>
                            <a href="payment.php?project_id=<?php echo $project_id; ?>&a=<?php echo $remaining_payment;?>" class="btn btn-primary btn-lg">Pay Now</a>
                            <?php } else { ?>
                            <span class="text-success fw-bold">All payments completed!</span>
                            <?php } ?>
                        </th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Payment History Table -->
        <div class="table-responsive wow fadeInUp mt-4">
            <h4 class="text-primary mb-3">Payment History</h4>
            <table class="table table-bordered table-striped">
                <thead class="table-secondary text-center">
                    <tr>
                        <th>#</th>
                        <th>Amount (₹)</th>
                        <th>Card Type</th>
                        <th>Card Name</th>
                        <th>Card Number</th>
                        <th>Payment Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $pay_query = mysqli_query($con, "SELECT * FROM payment WHERE project_id='" . $project_id . "' ORDER BY payment_date DESC");
                    if(mysqli_num_rows($pay_query) > 0){
                        $j = 1;
                        while($pay = mysqli_fetch_array($pay_query)){
                    ?>
                    <tr class="text-center">
                        <td><?php echo $j++; ?></td>
                        <td>₹<?php echo number_format($pay['amount'],2); ?></td>
                        <td><?php echo $pay['card_type']; ?></td>
                        <td><?php echo $pay['card_name']; ?></td>
                        <td><?php echo '**** **** **** ' . substr($pay['card_no'],-4); ?></td>
                        <td><?php echo date('d-m-Y H:i', strtotime($pay['payment_date'])); ?></td>
                        <td><?php echo ucfirst($pay['status']); ?></td>
                    </tr>
                    <?php
                        }
                    } else {
                        echo '<tr><td colspan="7" class="text-center">No payments made yet.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<!-- Bill Section End -->

<?php
include('footer.php');
?>
