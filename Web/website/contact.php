<?php
$pageTitle = 'Contact Us';
require_once 'header.php';

// Configuration
require_once '../config/credentials.php';

$messageSent = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $message = trim($_POST['message'] ?? '');
    $cfResponse = $_POST['cf-turnstile-response'] ?? '';

    // Basic Validation
    if (empty($message)) {
        $error = 'Message is required.';
    }
    // Turnstile Validation
    elseif ($turnstileSecretKey) {
        $data = [
            'secret' => $turnstileSecretKey,
            'response' => $cfResponse,
            'remoteip' => $_SERVER['REMOTE_ADDR']
        ];
        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data)
            ]
        ];
        $context = stream_context_create($options);
        $result = file_get_contents('https://challenges.cloudflare.com/turnstile/v0/siteverify', false, $context);
        $json = json_decode($result, true);

        if (!$json['success']) {
            $error = 'Captcha verification failed.';
        }
    }

    if (!$error) {
        $subject = "ShadowPulse Contact";
        $headers = "From: noreply@vod.fan\r\n";
        if ($email) {
            $headers .= "Reply-To: $email\r\n";
        }
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

        $body = "New message from ShadowPulse Contact Form:\n\n";
        if ($email) {
            $body .= "From: $email\n";
        } else {
            $body .= "From: Anonymous\n";
        }
        $body .= "IP: {$_SERVER['REMOTE_ADDR']}\n\n";
        $body .= "Message:\n$message\n";

        if (mail($toEmail, $subject, $body, $headers)) {
            $messageSent = true;
        } else {
            $error = 'Failed to send message. Please try again later.';
        }
    }
}
?>

<style>
    /* Override Default Grid Layout for this page only */
    .site-main {
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        justify-content: flex-start !important;
        padding-top: 40px;
        grid-template-columns: none !important;
    }
</style>

<div class="content" style="max-width: 600px; width: 100%;">
    <div class="content-header" style="justify-content: center;">
        <div class="content-title">Contact Us</div>
    </div>

    <div class="content-body">
        <?php if ($messageSent): ?>
            <div
                style="background: rgba(34, 197, 94, 0.2); color: #86efac; padding: 15px; border-radius: 8px; border: 1px solid rgba(34, 197, 94, 0.4); text-align: center;">
                <strong>Message Sent!</strong><br>
                Thank you for contacting us.<br>
                <a href="index.php"
                    style="color: inherit; text-decoration: underline; margin-top: 10px; display: inline-block;">Back to
                    Dashboard</a>
            </div>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="error" style="text-align: center;"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="contact.php">
                <div style="margin-bottom: 15px;">
                    <label for="email"
                        style="display: block; margin-bottom: 6px; font-weight: 500; font-size: 0.9rem;">Email
                        (Optional)</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>"
                        style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-soft); background: rgba(15, 23, 42, 0.6); color: var(--text-main); font-family: inherit;">
                </div>

                <div style="margin-bottom: 20px;">
                    <label for="message"
                        style="display: block; margin-bottom: 6px; font-weight: 500; font-size: 0.9rem;">Message</label>
                    <textarea id="message" name="message" rows="6" required
                        style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border-soft); background: rgba(15, 23, 42, 0.6); color: var(--text-main); font-family: inherit; resize: vertical;"><?php echo htmlspecialchars($message ?? ''); ?></textarea>
                </div>

                <!-- Cloudflare Turnstile -->
                <div style="display: flex; justify-content: center; margin-bottom: 20px;">
                    <div class="cf-turnstile" data-sitekey="0x4AAAAAACICJe8nCHxTcKoq" data-theme="dark"></div>
                </div>

                <div style="text-align: center;">
                    <button type="submit"
                        style="padding: 10px 40px; border-radius: 999px; border: 1px solid var(--accent); background: linear-gradient(135deg, #1E90FF, #1b4f9f); color: #fff; font-weight: 600; cursor: pointer; transition: all 0.2s;">
                        Send Message
                    </button>
                </div>
            </form>
            <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
        <?php endif; ?>
    </div>
</div>

<div style="text-align: center; margin-top: 15px; font-size: 0.75em; color: var(--text-muted); opacity: 0.6;">
    Contact Form v1
</div>

<?php require_once 'footer.php'; ?>