<?php
include('header.php');
include('connection.php');

$query = "SELECT * FROM projects where id='$_REQUEST[id]'";
$result = mysqli_query($con, $query);
$project = mysqli_fetch_assoc($result);
?>


        <!-- Header Start -->
        <div class="container-fluid bg-breadcrumb">
            <div class="container text-center py-5" style="max-width: 900px;">
                <h4 class="text-white display-4 mb-4 wow fadeInDown" data-wow-delay="0.1s">Project</h4>   
            </div>
        </div>
        <!-- Header End -->


        <!-- About Start -->
       <div class="container-fluid about py-5">
			<div class="container py-5">
				<div class="row g-5">
					<div class="col-lg-7 wow fadeInLeft" data-wow-delay="0.2s">
						<div class="h-100">
							<h1 class="display-4 mb-4"><?php echo $project['name']; ?></h1>
							<p class="mb-4">
								<?php echo $project['description']; ?>
							</p>
							<div class="text-dark mb-4">
								<p class="fs-5"><span class="fa fa-calendar-alt text-primary me-2"></span> Start Date: <?php echo $project['start_date']; ?></p>
								<p class="fs-5"><span class="fa fa-calendar-check text-primary me-2"></span> End Date: <?php echo $project['end_date']; ?></p>
								<p class="fs-5"><span class="fa fa-rupee-sign text-primary me-2"></span> Budget: ₹<?php echo number_format($project['budget'], 2); ?></p>
								<p class="fs-5"><span class="fa fa-info-circle text-primary me-2"></span> Status: <?php echo ucfirst($project['status']); ?></p>
								<?php
								if($project['status']=='ongoing')
								{
									$sel=mysqli_query($con,"select * from project_manager_assignments where project_id='$project[id]'");
									$row=mysqli_fetch_array($sel);
									
									$sel1=mysqli_query($con,"select * from project_managers where id='$row[project_manager_id]'");
									$row1=mysqli_fetch_array($sel1);
								?>
								<p class="fs-5"><span class="fa fa-info-circle text-primary me-2"></span> Assigned Project Manager: <?php echo $row1['name']; ?></p>
								<?php
								}
								?>
							</div>
							
						</div>
					</div>

					<div class="col-lg-5 wow fadeInRight" data-wow-delay="0.2s">
						<div class="position-relative h-100">
							<img src="admin/projects/uploads/<?php echo $project['image']; ?>" class="img-fluid w-100 h-100" style="object-fit: cover;" alt="<?php echo $project['name']; ?>">
						</div>
					</div>
				</div>
			</div>
		</div>
<!-- About End -->


<!-- Resources Table Section Start -->
<div class="container-fluid resources py-5 bg-light">
    <div class="container py-5">
        <div class="text-center mb-5 wow fadeInUp">
            <h4 class="text-primary">Allocated Resources</h4>
            <h1 class="display-4">Resources Assigned to <?php echo $project['name']; ?></h1>
        </div>

        <div class="table-responsive wow fadeInUp">
            <table class="table table-bordered table-striped">
                <thead class="table-primary">
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
                    $res_query = mysqli_query($con, "SELECT * FROM resources WHERE project_id='$project[id]'");
                    $total_expense = 0;
                    if(mysqli_num_rows($res_query) > 0){
                        $i = 1;
                        while($res = mysqli_fetch_array($res_query)){
                            $resource_total = $res['quantity'] * $res['cost'];
                            $total_expense += $resource_total;
                    ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td><?php echo $res['name']; ?></td>
                        <td><?php echo $res['type']; ?></td>
                        <td><?php echo $res['quantity']; ?></td>
                        <td><?php echo number_format($res['cost'], 2); ?></td>
                        <td><?php echo number_format($resource_total, 2); ?></td>
                    </tr>
                    <?php
                        }
                    } else {
                        echo '<tr><td colspan="6" class="text-center">No resources allocated yet.</td></tr>';
                    }

                    $balance = $project['budget'] - $total_expense;
                    ?>
                </tbody>
                <tfoot>
                    <tr class="table-secondary">
                        <th colspan="5" class="text-end">Total Expense:</th>
                        <th>₹<?php echo number_format($total_expense, 2); ?></th>
                    </tr>
                    <tr class="table-success">
                        <th colspan="5" class="text-end">Remaining Budget:</th>
                        <th>₹<?php echo number_format($balance, 2); ?></th>
                    </tr>
                </tfoot>
            </table>
			
			<a href="bill_view.php?id=<?php echo $_REQUEST['id']?>" class="btn btn-primary">View Bill</a>
        </div>
    </div>
</div>
<!-- Resources Table Section End -->


<!-- Project Progress Section Start -->
<div class="container-fluid progress-updates py-5 bg-white">
    <div class="container py-5">
        <div class="text-center mb-5 wow fadeInUp">
            <h4 class="text-primary">Project Progress Updates</h4>
            <h1 class="display-4">Track Progress of <?php echo $project['name']; ?></h1>
        </div>

        <?php
        $progress_query = mysqli_query($con, "SELECT * FROM progress_updates WHERE project_id='$project[id]' ORDER BY update_date ASC");

        if(mysqli_num_rows($progress_query) > 0){
            $step = 1;
            while($update = mysqli_fetch_array($progress_query)){
        ?>
        <div class="card shadow-sm mb-4 wow fadeInUp">
            <div class="card-body">
                <h5 class="card-title">Update #<?php echo $step++; ?> - <?php echo date('d M Y', strtotime($update['update_date'])); ?></h5>
                <p class="text-muted"><strong>Progress:</strong> <?php echo number_format($update['progress_percentage']); ?>%</p>
                <div class="progress mb-3" style="height: 20px;">
                    <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo $update['progress_percentage']; ?>%;" aria-valuenow="<?php echo $update['progress_percentage']; ?>" aria-valuemin="0" aria-valuemax="100">
                        <?php echo $update['progress_percentage']; ?>%
                    </div>
                </div>
                <p class="card-text"><?php echo nl2br($update['description']); ?></p>
            </div>
        </div>
        <?php
            }
        } else {
            echo '<div class="alert alert-warning text-center">No progress updates available yet.</div>';
        }
        ?>
    </div>
</div>
<!-- Project Progress Section End -->





<?php
include('footer.php');
?>