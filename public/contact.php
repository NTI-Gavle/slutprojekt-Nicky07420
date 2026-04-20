<?php
$pageTitle = "Contact";
require_once __DIR__ . '/../includes/header.php';

$name = $email = $message = '';
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($name)) {
        $errors[] = "Name is required.";
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "A valid email is required.";
    }
    if (empty($message)) {
        $errors[] = "Message cannot be empty.";
    }

    if (!$errors) {

        $success = true;
        $name = $email = $message = '';
    }
}
?>

<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-7">
        <div class="feed-card">
            <p class="page-eyebrow">Contact</p>
            <h1 class="page-heading mb-2">Contact Us</h1>
            <p class="page-copy mb-4">Send a message and I will get back to you.</p>

            <?php if ($success): ?>
                <div class="alert alert-success py-2" role="alert">
                    Thank you! Your message has been sent.
                </div>
            <?php endif; ?>

            <?php if ($errors): ?>
                <div class="alert alert-danger py-2" role="alert">
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="contact.php" method="post" class="contact-form">
                <div>
                    <label for="name" class="form-label">Name</label>
                    <input
                        type="text"
                        name="name"
                        id="name"
                        class="form-control"
                        value="<?= htmlspecialchars($name) ?>"
                    >
                </div>

                <div>
                    <label for="email" class="form-label">Email</label>
                    <input
                        type="email"
                        name="email"
                        id="email"
                        class="form-control"
                        value="<?= htmlspecialchars($email) ?>"
                    >
                </div>

                <div>
                    <label for="message" class="form-label">Message</label>
                    <textarea
                        name="message"
                        id="message"
                        class="form-control"
                        rows="5"
                    ><?= htmlspecialchars($message) ?></textarea>
                </div>

                <div class="d-grid d-sm-flex justify-content-sm-end">
                    <button type="submit" class="btn btn-primary px-4">Send</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
