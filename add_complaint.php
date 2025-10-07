<?php
include('header.php');
include('connection.php');

// Handle form submission
if (isset($_POST['submit'])) {
    $project_id     = mysqli_real_escape_string($con, $_POST['project_id']);
    $client_id      = mysqli_real_escape_string($con, $_POST['client_id']);
    $complaint_text = mysqli_real_escape_string($con, $_POST['complaint_text']);
    $status         = 'pending'; // Default status
    $complaint_date = date('Y-m-d H:i:s');

    $insert_query = "INSERT INTO complaints (project_id, client_id, complaint_text, status, complaint_date) 
                     VALUES ('$project_id', '$client_id', '$complaint_text', '$status', '$complaint_date')";

    if (mysqli_query($con, $insert_query)) {
        echo "<script>alert('Complaint submitted successfully'); window.location='complaint.php';</script>";
    } else {
        echo "<script>alert('Failed to submit complaint');</script>";
    }
}
?>

<!-- Header Start -->
<div class="container-fluid bg-breadcrumb">
    <div class="container text-center py-5" style="max-width: 900px;">
        <h4 class="text-white display-4 mb-4 wow fadeInDown" data-wow-delay="0.1s">Submit a Complaint</h4>
    </div>
</div>
<!-- Header End -->

<!-- Complaint Form Start -->
<div class="container-fluid bg-light py-5">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">

                <form method="POST" class="bg-white p-4 rounded shadow-sm">
                    <div class="mb-3">
						<label for="project_id" class="form-label">Select Project</label>
						<select name="project_id" id="project_id" class="form-control" required>
							<option value="">-- Select Project --</option>
							<?php
							// Fetch projects from database
							$project_query = "SELECT id, name FROM projects where client_id='$_SESSION[uid]'";
							$result = mysqli_query($con, $project_query);

							while ($row = mysqli_fetch_assoc($result)) {
								echo "<option value='{$row['id']}'>{$row['name']}</option>";
							}
							?>
						</select>
					</div>

                    <div class="mb-3">
                        <input type="hidden" name="client_id" id="client_id" class="form-control" value="<?php echo  $_SESSION['uid']; ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="complaint_text" class="form-label">Complaint Text</label>
                        <textarea name="complaint_text" id="complaint_text" class="form-control" rows="5" required></textarea>
                    </div>

                    <button type="submit" name="submit" class="btn btn-primary">Submit Complaint</button>
                </form>

            </div>
        </div>
    </div>
</div>
<!-- Complaint Form End -->

<?php
include('footer.php');
?>
