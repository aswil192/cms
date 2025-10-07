<?php
session_start();
include('connection.php');

// Check if user is logged in as client
if(!isset($_SESSION['uid']) || $_SESSION['type'] != 'client') {
    header("Location: login.php");
    exit();
}

include('header.php');
?>

<!-- Header Start -->
<div class="container-fluid bg-breadcrumb">
    <div class="container text-center py-5" style="max-width: 900px;">
        <h3 class="text-white display-5 mb-4">Help & Support</h3>
        <ol class="breadcrumb d-flex justify-content-center mb-0">
            <li class="breadcrumb-item"><a href="index1.php">Home</a></li>
            <li class="breadcrumb-item active text-primary">Help Center</li>
        </ol>    
    </div>
</div>
<!-- Header End -->

<!-- Help & Support Start -->
<div class="container-fluid py-5">
    <div class="container py-5">
        <div class="row g-5">
            <!-- Left Side - FAQ & Resources -->
            <div class="col-lg-8">
                <!-- Quick Help Cards -->
                <div class="row g-4 mb-5">
                    <div class="col-md-6">
                        <div class="card help-card border-0 h-100">
                            <div class="card-body text-center p-4">
                                <div class="help-icon bg-primary rounded-circle mx-auto mb-3">
                                    <i class="fas fa-question-circle fa-2x text-white"></i>
                                </div>
                                <h5>FAQs</h5>
                                <p class="text-muted">Find answers to common questions about project management</p>
                                <a href="#faq-section" class="btn btn-outline-primary btn-sm">View FAQs</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card help-card border-0 h-100">
                            <div class="card-body text-center p-4">
                                <div class="help-icon bg-success rounded-circle mx-auto mb-3">
                                    <i class="fas fa-book fa-2x text-white"></i>
                                </div>
                                <h5>User Guide</h5>
                                <p class="text-muted">Learn how to use our platform effectively</p>
                                <a href="#guide-section" class="btn btn-outline-success btn-sm">View Guide</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- FAQ Section -->
                <div id="faq-section" class="mb-5">
                    <h3 class="mb-4">Frequently Asked Questions</h3>
                    <div class="accordion" id="faqAccordion">
                        <!-- FAQ Item 1 -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                    How do I submit a new construction project?
                                </button>
                            </h2>
                            <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <p>To submit a new project:</p>
                                    <ol>
                                        <li>Go to "My Projects" page</li>
                                        <li>Click "Add New Project" button</li>
                                        <li>Fill in all required project details</li>
                                        <li>Upload any reference images (optional)</li>
                                        <li>Click "Submit Project"</li>
                                    </ol>
                                    <p>Your project will be reviewed by our team and assigned a project manager.</p>
                                </div>
                            </div>
                        </div>

                        <!-- FAQ Item 2 -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                    How long does project approval take?
                                </button>
                            </h2>
                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Project approval typically takes 1-2 business days. You'll receive notifications about your project status updates.
                                </div>
                            </div>
                        </div>

                        <!-- FAQ Item 3 -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                    Can I edit my project after submission?
                                </button>
                            </h2>
                            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    You can only edit projects that are in "Pending" status. Once a project manager is assigned, please contact them directly for any changes.
                                </div>
                            </div>
                        </div>

                        <!-- FAQ Item 4 -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                    How do I track my project progress?
                                </button>
                            </h2>
                            <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    You can track your project progress by:
                                    <ul>
                                        <li>Viewing project status on "My Projects" page</li>
                                        <li>Checking regular updates from your project manager</li>
                                        <li>Reviewing milestone completions</li>
                                        <li>Contacting your assigned project manager directly</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- FAQ Item 5 -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                                    What file types can I upload for project images?
                                </button>
                            </h2>
                            <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Supported image formats: JPG, JPEG, PNG, GIF. Maximum file size: 2MB. For better quality, we recommend using JPG or PNG formats.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- User Guide Section -->
                <div id="guide-section" class="mb-5">
                    <h3 class="mb-4">User Guide</h3>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="card guide-card border-0 h-100">
                                <div class="card-body">
                                    <h5 class="card-title"><i class="fas fa-plus-circle text-primary me-2"></i>Creating Projects</h5>
                                    <p class="card-text">Learn how to create and submit new construction projects with all necessary details.</p>
                                    <ul class="list-unstyled">
                                        <li><i class="fas fa-check text-success me-2"></i>Fill project requirements</li>
                                        <li><i class="fas fa-check text-success me-2"></i>Set budget and timeline</li>
                                        <li><i class="fas fa-check text-success me-2"></i>Upload reference materials</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card guide-card border-0 h-100">
                                <div class="card-body">
                                    <h5 class="card-title"><i class="fas fa-eye text-info me-2"></i>Tracking Progress</h5>
                                    <p class="card-text">Monitor your project's development and communicate with your project manager.</p>
                                    <ul class="list-unstyled">
                                        <li><i class="fas fa-check text-success me-2"></i>View project status</li>
                                        <li><i class="fas fa-check text-success me-2"></i>Check milestone updates</li>
                                        <li><i class="fas fa-check text-success me-2"></i>Receive notifications</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side - Contact Support -->
            <div class="col-lg-4">
                <div class="card support-card border-0 shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-headset me-2"></i>Contact Support</h4>
                    </div>
                    <div class="card-body">
                        <div class="support-contact mb-4">
                            <h6 class="text-primary mb-3">Quick Support Options</h6>
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-phone-alt text-success me-3"></i>
                                <div>
                                    <small class="text-muted">Phone Support</small>
                                    <div>+91 9746461020</div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-envelope text-warning me-3"></i>
                                <div>
                                    <small class="text-muted">Email Support</small>
                                    <div>support@constructionmgmt.com</div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-clock text-info me-3"></i>
                                <div>
                                    <small class="text-muted">Support Hours</small>
                                    <div>Mon-Fri: 9AM-6PM</div>
                                </div>
                            </div>
                        </div>

                        <!-- Support Form -->
                        <h6 class="text-primary mb-3">Send us a Message</h6>
                        <form action="submit_support.php" method="POST">
                            <div class="mb-3">
                                <label for="subject" class="form-label">Subject</label>
                                <input type="text" class="form-control" id="subject" name="subject" required>
                            </div>
                            <div class="mb-3">
                                <label for="message" class="form-label">Message</label>
                                <textarea class="form-control" id="message" name="message" rows="4" required placeholder="Describe your issue or question..."></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="priority" class="form-label">Priority</label>
                                <select class="form-select" id="priority" name="priority">
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-paper-plane me-2"></i>Send Message
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Emergency Contact -->
                
            </div>
        </div>
    </div>
</div>
<!-- Help & Support End -->

<style>
.help-icon {
    width: 60px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.help-card, .guide-card {
    transition: transform 0.3s ease;
}

.help-card:hover, .guide-card:hover {
    transform: translateY(-5px);
}

.support-card {
    position: sticky;
    top: 20px;
}

.accordion-button {
    font-weight: 500;
}

.accordion-button:not(.collapsed) {
    background-color: #e7f1ff;
    color: #0d6efd;
}

.emergency-card {
    background-color: #f8f9fa;
}
</style>

<?php
include('footer.php');
?>