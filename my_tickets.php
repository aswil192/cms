<?php
session_start();
include('connection.php');

if(!isset($_SESSION['uid']) || $_SESSION['type'] != 'client') {
    header("Location: login.php");
    exit();
}

$client_id = $_SESSION['uid'];
include('header.php');
?>

<div class="container-fluid bg-breadcrumb">
    <div class="container text-center py-5">
        <h3 class="text-white display-5 mb-4">My Support Tickets</h3>
    </div>
</div>

<div class="container-fluid py-5">
    <div class="container py-5">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">My Support Requests</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Ticket ID</th>
                                        <th>Subject</th>
                                        <th>Priority</th>
                                        <th>Status</th>
                                        <th>Date Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $query = "SELECT * FROM support_tickets WHERE client_id = '$client_id' ORDER BY created_at DESC";
                                    $result = mysqli_query($con, $query);
                                    
                                    if(mysqli_num_rows($result) > 0) {
                                        while($ticket = mysqli_fetch_assoc($result)) {
                                            $status_badge = '';
                                            switch($ticket['status']) {
                                                case 'open': $status_badge = 'bg-warning'; break;
                                                case 'in_progress': $status_badge = 'bg-info'; break;
                                                case 'resolved': $status_badge = 'bg-success'; break;
                                                case 'closed': $status_badge = 'bg-secondary'; break;
                                                default: $status_badge = 'bg-secondary';
                                            }
                                            
                                            $priority_badge = '';
                                            switch($ticket['priority']) {
                                                case 'low': $priority_badge = 'bg-success'; break;
                                                case 'medium': $priority_badge = 'bg-primary'; break;
                                                case 'high': $priority_badge = 'bg-warning'; break;
                                                case 'urgent': $priority_badge = 'bg-danger'; break;
                                                default: $priority_badge = 'bg-secondary';
                                            }
                                            
                                            echo "<tr>
                                                <td>#{$ticket['id']}</td>
                                                <td>{$ticket['subject']}</td>
                                                <td><span class='badge $priority_badge'>{$ticket['priority']}</span></td>
                                                <td><span class='badge $status_badge'>{$ticket['status']}</span></td>
                                                <td>" . date('M d, Y', strtotime($ticket['created_at'])) . "</td>
                                                <td>
                                                    <a href='view_ticket.php?id={$ticket['id']}' class='btn btn-sm btn-primary'>View</a>
                                                </td>
                                            </tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='6' class='text-center'>No support tickets found.</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>