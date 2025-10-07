<?php
include('header.php');
include('connection.php');

$sel1=mysqli_query($con,"select * from user where id='$_SESSION[uid]'");
$row1=mysqli_fetch_array($sel1);


$re = mysqli_query($con, "SELECT * FROM smart_meter ORDER BY id DESC LIMIT 10");
$total_current = 0;
    $total_voltage = 0;
    $total_power = 0;
    $total_cost = 0;
    $total_units = 0;
    $time_hours = 12; 

    while ($row = mysqli_fetch_array($re)) {
        $total_current += $row[1];
        $total_voltage += $row[2];
        $total_power += $row[3];

        $cost = ($row[3] * 0.7); 
        $total_cost += $cost;

        $units = ($row[3] * $time_hours) / 1000;
        $total_units += $units;
	}
	
	$rate=$total_units*6.50;
	$fixed_charges = 100.00;
	$meter_rent = 20.00;
	$taxes_duties = 50.00;
	
	$total_amount = round($rate + $fixed_charges + $meter_rent + $taxes_duties, 2);
	
	$bill_no = date("Ymd") . "/" . strtoupper(substr(uniqid(), -5));

?>

        <!-- Header Start -->
        <div class="container-fluid bg-breadcrumb">
            <div class="container text-center py-5" style="max-width: 900px;">
                <h4 class="text-white display-4 mb-4 wow fadeInDown" data-wow-delay="0.1s">Electricity Bill</h4>
                <ol class="breadcrumb d-flex justify-content-center mb-0 wow fadeInDown" data-wow-delay="0.3s">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item active text-primary">Electricity Bill</li>
                </ol>    
            </div>
        </div>
        <!-- Header End -->


        <!-- Blog Start -->
        <div class="container-fluid blog py-5">
            <div class="container py-5">
                <div class="row g-4">
				
				<div class="bill-container" style="max-width: 600px; margin: auto; padding: 20px; border: 1px solid #ccc; font-family: Arial, sans-serif;">
					<h2 style="text-align: center; color: #003399;">Kerala State Electricity Board (KSEB)</h2>
					<hr>
					<table width="100%" style="margin-bottom: 20px;">
						<tr>
							<td><strong>Consumer Number:</strong> 123456789</td>
							<td><strong>Bill No. :</strong> <?php echo $bill_no; ?></td>
							
						</tr>
						<tr>
							<td><strong>Consumer Name:</strong> <?php echo $row1['name']; ?></td>
							<td><strong>Bill Date:</strong> <?php echo date("d-M-Y"); ?></td>
						</tr>
						<tr>
							<td><strong>Connection Type:</strong> <?php echo $row['phase']; ?> phase</td>
							<td><strong>Due Date:</strong> 15-Mar-2024</td>
						</tr>
						<tr>
							<td><strong>Units Consumed:</strong> <?php echo round($total_units, 2); ?> kWh</td>
							<td><strong>Tariff:</strong> ₹6.50 per unit</td>
						</tr>
					</table>

					<h3 style="color: #cc0000; text-align: center;">Bill Summary</h3>
					<table width="100%" border="1" cellspacing="0" cellpadding="8" style="border-collapse: collapse; text-align: left;">
						<tr>
							<th>Description</th>
							<th>Amount (₹)</th>
						</tr>
						<tr>
							<td>Energy Charges</td>
							<td>₹<?php echo round($rate, 2); ?></td>
						</tr>
						<tr>
							<td>Fixed Charges</td>
							<td>₹<?php echo number_format($fixed_charges, 2); ?></td>
						</tr>
						<tr>
							<td>Meter Rent</td>
							<td>₹<?php echo number_format($meter_rent, 2); ?></td>
						</tr>
						<tr>
							<td>Taxes & Duties</td>
							<td>₹<?php echo number_format($taxes_duties, 2); ?></td>
						</tr>
						<tr>
							<td><strong>Total Payable Amount</strong></td>
							<td><strong>₹<?php echo number_format($total_amount, 2); ?></strong></td>
						</tr>
					</table>
					<?php
					$pay=mysqli_query($con,"select * from payment where user_id='$_SESSION[uid]'");
					$cc=mysqli_num_rows($pay);
					if($cc>0)
					{
					?>
						<p style="text-align: center; margin-top: 20px; font-size: 18px; color: green;">
						<strong>Payment Completed</strong>
					<?php
					}else{					
					?>
					<p style="text-align: center; margin-top: 20px; font-size: 16px;">
						<strong>Due Date for Payment:</strong><span style="color: red;"> <?php echo date("d-M-Y", strtotime("+10 days")); ?></span>
					</p>
					<p style="text-align: center; color: #006600;"><a href="payment.php?a=<?php echo $total_amount?>&b=<?php echo $bill_no?>" class="btn btn-danger">Pay Now</a></p>
					<?php
					}
					?>
				</div>

				
				</div>
            </div>
         </div>
        <!-- Blog End -->

        <!-- Footer Start -->
<?php
include('footer.php');
?>