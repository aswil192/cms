<?php
include('header.php');
include('connection.php');
?>

<!-- Header Start -->
<div class="container-fluid bg-breadcrumb">
    <div class="container text-center py-5" style="max-width: 900px;">
        <h4 class="text-white display-4 mb-4 wow fadeInDown" data-wow-delay="0.1s">Complaints</h4>
    </div>
</div>
<!-- Header End -->

<!-- Complaints Table Start -->
<div class="container-fluid bg-light py-5">
    <div class="container py-5">
        <h3 class="mb-4 text-center">List of Complaints</h3>

        <div class="table-responsive">
            <a href="add_complaint.php" class="btn btn-danger" style="float:right;">Add Complaint</a> <br><br>
			<table class="table table-bordered table-hover">
                <thead class="table-dark text-center">
                    <tr>
                        <th>#</th>
                        <th>Project ID</th>
                        <th>Client ID</th>
                        <th>Complaint Text</th>
                        <th>Status</th>
                        <th>Response</th>
                        <th>Complaint Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query = "SELECT * FROM complaints ORDER BY complaint_date DESC";
                    $result = mysqli_query($con, $query);

                    if(mysqli_num_rows($result) > 0){
                        $count = 1;
                        while($row = mysqli_fetch_assoc($result)){
							
							// Fetch project
							$query1 = "SELECT * FROM projects WHERE id='" . $row['project_id'] . "'";
							$result1 = mysqli_query($con, $query1);
							$project1 = mysqli_fetch_assoc($result1);

							// Fetch project
							$query2 = "SELECT * FROM users WHERE id='" . $row['client_id'] . "'";
							$result2 = mysqli_query($con, $query2);
							$project2 = mysqli_fetch_assoc($result2);
							
                            echo '<tr class="text-center">';
                            echo '<td>'. $count++ .'</td>';
                            echo '<td>'. $project1['name'] .'</td>';
                            echo '<td>'. $project2['name'] .'</td>';
                            echo '<td>'. nl2br(htmlspecialchars($row['complaint_text'])) .'</td>';
                            echo '<td>'. ucfirst($row['status']) .'</td>';
                            echo '<td>'. (!empty($row['response']) ? nl2br(htmlspecialchars($row['response'])) : '<span class="text-muted">Pending</span>') .'</td>';
                            echo '<td>'. date('d M Y, H:i', strtotime($row['complaint_date'])) .'</td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="7" class="text-center">No complaints found.</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<!-- Complaints Table End -->

<?php
include('footer.php');
?>
