<?php
session_start();
include('connection.php');

// Check if user is logged in as client
if(!isset($_SESSION['uid']) || $_SESSION['type'] != 'client') {
    header("Location: login.php");
    exit();
}

$client_id = $_SESSION['uid'];
$ticket_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if($ticket_id == 0) {
    header("Location: my_tickets.php");
    exit();
}

// Get ticket details
$query = "SELECT st.*, u.name as client_name, u.email as client_email 
          FROM support_tickets st 
          LEFT JOIN users u ON st.client_id = u.id 
          WHERE st.id = '$ticket_id' AND st.client_id = '$client_id'";
$result = mysqli_query($con, $query);

if(!$result || mysqli_num_rows($result) == 0) {
    $_SESSION['error'] = "Ticket not found or you don't have permission to view it.";
    header("Location: my_tickets.php");
    exit();
}

$ticket = mysqli_fetch_assoc($result);

// Handle reply submission
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reply_message'])) {
    $reply_message = mysqli_real_escape_string($con, $_POST['reply_message']);
    
    if(!empty($reply_message)) {
        // Update the ticket with client's reply
        $update_query = "UPDATE support_tickets 
                        SET message = CONCAT(message, '\n\n--- Client Reply ---\n', '$reply_message'),
                            status = 'open',
                            updated_at = NOW()
                        WHERE id = '$ticket_id' AND client_id = '$client_id'";
        
        if(mysqli_query($con, $update_query)) {
            $_SESSION['success'] = "Your reply has been sent successfully!";
            header("Location: view_ticket.php?id=" . $ticket_id);
            exit();
        } else {
            $_SESSION['error'] = "Error sending reply: " . mysqli_error($con);
        }
    } else {
        $_SESSION['error'] = "Please enter a message.";
    }
}

include('header.php');
?>

<!-- Header Start -->
<div class="container-fluid bg-breadcrumb">
    <div class="container text-center py-5" style="max-width: 900px;">
        <h3 class="text-white display-5 mb-4">Support Ticket Details</h3>
        <ol class="breadcrumb d-flex justify-content-center mb-0">
            <li class="breadcrumb-item"><a href="index1.php">Home</a></li>
            <li class="breadcrumb-item"><a href="my_tickets.php">My Tickets</a></li>
            <li class="breadcrumb-item active text-primary">Ticket #<?php echo $ticket['id']; ?></li>
        </ol>    
    </div>
</div>
<!-- Header End -->

<!-- Ticket Details Start -->
<div class="container-fluid py-5">
    <div class="container py-5">
        <div class="row g-4 justify-content-center">
            <div class="col-lg-10">
                <?php
                if(isset($_SESSION['success'])) {
                    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>'.$_SESSION['success'].'
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                          </div>';
                    unset($_SESSION['success']);
                }
                
                if(isset($_SESSION['error'])) {
                    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>'.$_SESSION['error'].'
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                          </div>';
                    unset($_SESSION['error']);
                }
                ?>

                <!-- Ticket Header Card -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-primary text-white py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="mb-0">Ticket #<?php echo $ticket['id']; ?></h4>
                            <div class="d-flex gap-2">
                                <?php
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
                                ?>
                                <span class="badge <?php echo $priority_badge; ?>"><?php echo ucfirst($ticket['priority']); ?> Priority</span>
                                <span class="badge <?php echo $status_badge; ?>"><?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="ticket-info">
                                    <h6 class="text-primary mb-2">Subject</h6>
                                    <p class="mb-0 fs-5"><?php echo htmlspecialchars($ticket['subject']); ?></p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="ticket-info">
                                    <h6 class="text-primary mb-2">Created</h6>
                                    <p class="mb-0"><?php echo date('F j, Y g:i A', strtotime($ticket['created_at'])); ?></p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="ticket-info">
                                    <h6 class="text-primary mb-2">Last Updated</h6>
                                    <p class="mb-0"><?php echo date('F j, Y g:i A', strtotime($ticket['updated_at'])); ?></p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="ticket-info">
                                    <h6 class="text-primary mb-2">Client</h6>
                                    <p class="mb-0"><?php echo htmlspecialchars($ticket['client_name']); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Message Thread -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-light py-3">
                        <h5 class="mb-0">Message Thread</h5>
                    </div>
                    <div class="card-body">
                        <!-- Original Message -->
                        <div class="message-thread">
                            <div class="message-item client-message mb-4">
                                <div class="d-flex align-items-start">
                                    <div class="message-avatar bg-primary rounded-circle d-flex align-items-center justify-content-center me-3">
                                        <i class="fas fa-user text-white"></i>
                                    </div>
                                    <div class="message-content flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h6 class="mb-0 text-primary">You</h6>
                                            <small class="text-muted"><?php echo date('M j, Y g:i A', strtotime($ticket['created_at'])); ?></small>
                                        </div>
                                        <div class="message-bubble bg-light p-3 rounded">
                                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($ticket['message'])); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Admin Response (if exists) -->
                            <?php if(!empty($ticket['admin_response'])): ?>
                            <div class="message-item admin-message mb-4">
                                <div class="d-flex align-items-start">
                                    <div class="message-avatar bg-success rounded-circle d-flex align-items-center justify-content-center me-3">
                                        <i class="fas fa-headset text-white"></i>
                                    </div>
                                    <div class="message-content flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h6 class="mb-0 text-success">Support Team</h6>
                                            <small class="text-muted"><?php echo date('M j, Y g:i A', strtotime($ticket['updated_at'])); ?></small>
                                        </div>
                                        <div class="message-bubble bg-primary text-white p-3 rounded">
                                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($ticket['admin_response'])); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Additional replies parsed from message field -->
                            <?php
                            $message_content = $ticket['message'];
                            if(strpos($message_content, '--- Client Reply ---') !== false) {
                                $parts = explode('--- Client Reply ---', $message_content);
                                $original_message = $parts[0];
                                
                                for($i = 1; $i < count($parts); $i++) {
                                    $reply_content = trim($parts[$i]);
                                    if(!empty($reply_content)) {
                                        echo '
                                        <div class="message-item client-message mb-4">
                                            <div class="d-flex align-items-start">
                                                <div class="message-avatar bg-primary rounded-circle d-flex align-items-center justify-content-center me-3">
                                                    <i class="fas fa-user text-white"></i>
                                                </div>
                                                <div class="message-content flex-grow-1">
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <h6 class="mb-0 text-primary">You (Follow-up)</h6>
                                                        <small class="text-muted">' . date('M j, Y g:i A') . '</small>
                                                    </div>
                                                    <div class="message-bubble bg-light p-3 rounded">
                                                        <p class="mb-0">' . nl2br(htmlspecialchars($reply_content)) . '</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>';
                                    }
                                }
                            }
                            ?>
                        </div>
                    </div>
                </div>

                <!-- Reply Form (only show if ticket is not resolved/closed) -->
                <?php if($ticket['status'] != 'resolved' && $ticket['status'] != 'closed'): ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light py-3">
                        <h5 class="mb-0">Add Reply</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="reply_message" class="form-label">Your Message</label>
                                <textarea class="form-control" id="reply_message" name="reply_message" rows="5" 
                                          placeholder="Type your reply here..." required></textarea>
                                <div class="form-text">
                                    Your reply will be added to this ticket and our support team will be notified.
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-2"></i>Send Reply
                                </button>
                                <a href="my_tickets.php" class="btn btn-outline-secondary">Back to Tickets</a>
                            </div>
                        </form>
                    </div>
                </div>
                <?php else: ?>
                <div class="alert alert-info">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-info-circle me-3 fa-2x"></i>
                        <div>
                            <h6 class="mb-1">Ticket <?php echo ucfirst($ticket['status']); ?></h6>
                            <p class="mb-0">This ticket has been <?php echo $ticket['status']; ?>. If you need further assistance, please create a new support ticket.</p>
                        </div>
                    </div>
                </div>
                <div class="text-center mt-4">
                    <a href="my_tickets.php" class="btn btn-outline-primary me-2">Back to Tickets</a>
                    <a href="help.php" class="btn btn-primary">Create New Ticket</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<!-- Ticket Details End -->

<style>
.message-avatar {
    width: 40px;
    height: 40px;
    flex-shrink: 0;
}

.message-bubble {
    position: relative;
}

.client-message .message-bubble {
    border-left: 4px solid #0d6efd;
}

.admin-message .message-bubble {
    border-left: 4px solid #198754;
}

.ticket-info {
    padding: 10px 0;
}

.ticket-info h6 {
    font-size: 0.875rem;
    font-weight: 600;
}

.card-header h4, .card-header h5 {
    font-weight: 600;
}

.alert {
    border: none;
    border-radius: 10px;
}
</style>

<?php
include('footer.php');
?>