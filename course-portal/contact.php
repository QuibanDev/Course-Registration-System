<?php error_reporting(E_ALL ^ E_NOTICE); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Contact Us</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
<?php require 'master.php'; ?>

<div class="page-content">
<div class="container" style="max-width:620px; margin:0 auto; padding:0 20px;">
<div class="ep-card">

    <div class="auth-icon-wrap">&#9742;</div>
    <h2 class="text-center" style="font-size:26px; margin-bottom:6px;">Contact the Registrar</h2>
    <p class="text-center text-muted" style="margin-bottom:28px;">
        Questions about registration, holds, or your schedule? Send us a message below.
    </p>

    <div id="contactAlert" style="display:none;" class="ep-alert"></div>

    <form id="contactForm">
        <div class="ep-form-group">
            <label class="ep-label" for="name">Full Name</label>
            <div class="ep-input-wrap">
                <input class="ep-input" type="text" id="name" name="name" placeholder="Enter your full name" required>
                <span class="ep-input-icon">&#128100;</span>
            </div>
        </div>

        <div class="ep-form-group">
            <label class="ep-label" for="email">Email Address</label>
            <div class="ep-input-wrap">
                <input class="ep-input" type="email" id="email" name="email" placeholder="Enter your email" required>
                <span class="ep-input-icon">&#9993;</span>
            </div>
        </div>

        <div class="ep-form-group">
            <label class="ep-label" for="subject">Subject</label>
            <div class="ep-input-wrap">
                <select class="ep-input no-icon" id="subject" name="subject">
                    <option>Registration Help</option>
                    <option>Course Availability</option>
                    <option>Account / Login Issue</option>
                    <option>Other</option>
                </select>
            </div>
        </div>

        <div class="ep-form-group">
            <label class="ep-label" for="message">Message</label>
            <div class="ep-input-wrap" style="align-items:flex-start;">
                <textarea class="ep-input" id="message" name="message" rows="5" placeholder="Enter your message" required></textarea>
                <span class="ep-input-icon" style="top:14px; position:absolute;">&#128172;</span>
            </div>
        </div>

        <button type="submit" class="ep-btn ep-btn-primary ep-btn-block" style="margin-top:8px;" id="sendBtn">
            &#9993;&nbsp; Send Message
        </button>
    </form>

</div>
</div>
</div>

<script>
document.getElementById('contactForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const form = e.target;
    const btn = document.getElementById('sendBtn');
    const alertEl = document.getElementById('contactAlert');
    btn.disabled = true;
    btn.innerHTML = 'Sending...';

    // Simulated send (no backend mail server configured)
    setTimeout(() => {
        alertEl.className = 'ep-alert ep-alert-success';
        alertEl.innerHTML = '<span>&#10003;</span><span>Thanks! Your message has been received and the registrar will reply by email shortly.</span>';
        alertEl.style.display = 'flex';
        form.reset();
        btn.disabled = false;
        btn.innerHTML = '&#9993;&nbsp; Send Message';
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }, 500);
});
</script>

<?php require 'footer.php'; ?>
</body>
</html>
