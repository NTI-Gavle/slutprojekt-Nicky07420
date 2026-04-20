<?php

$pageTitle = 'Register';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../database/user_queries.php';

$errors = [];
$formData = ['username' => '', 'email' => ''];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username  = trim($_POST['username']  ?? '');
    $email     = normalizeEmail($_POST['email'] ?? '');
    $password  = $_POST['password']       ?? '';
    $password2 = $_POST['password2']      ?? '';

    // Keep fields filled in on error
    $formData = ['username' => $username, 'email' => $email];

    if (empty($username)) {
        $errors[] = 'Username is required.';
    } elseif (strlen($username) < 3 || strlen($username) > 50) {
        $errors[] = 'Username must be between 3 and 50 characters.';
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email address is required.';
    }

    if (strlen($password) < 7) {
        $errors[] = 'Password must be at least 7 characters.';
    }

    if ($password !== $password2) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        $taken = checkUserExists($dbconn, $username, $email);

        if ($taken['username']) $errors[] = 'That username is already taken.';
        if ($taken['email'])    $errors[] = 'That email address is already registered.';
    }

    if (empty($errors)) {
        if (createUser($dbconn, $username, $email, $password)) {
            $user = getUserByUsername($dbconn, $username);
            session_regenerate_id(true);
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];
            redirectTo('index.php');
        } else {
            $errors[] = 'Something went wrong. Please try again.';
        }
    }
}
?>

<!-- Register card -->
<div class="row justify-content-center">
    <div class="col-12 col-sm-10 col-md-7 col-lg-6">

        <div class="auth-card">
            <h2 class="auth-title">Create an account</h2>
            <p class="auth-sub">Join for free today!</p>

            <?php if ($errors): ?>
                <div class="alert alert-danger py-2" role="alert">
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $err): ?>
                            <li><?= htmlspecialchars($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" action="register.php" novalidate>

                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input
                        type="text"
                        class="form-control"
                        id="username"
                        name="username"
                        value="<?= htmlspecialchars($formData['username']) ?>"
                        autocomplete="username"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email address</label>
                    <input
                        type="email"
                        class="form-control"
                        id="email"
                        name="email"
                        value="<?= htmlspecialchars($formData['email']) ?>"
                        autocomplete="email"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input
                        type="password"
                        class="form-control"
                        id="password"
                        name="password"
                        autocomplete="new-password"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label for="password2" class="form-label">Confirm password</label>
                    <input
                        type="password"
                        class="form-control"
                        id="password2"
                        name="password2"
                        autocomplete="new-password"
                        required
                    >
                </div>

                <button type="submit" class="btn btn-primary w-100 mt-1">Create account</button>

                <p class="gdpr-notice">
                    By registering you agree to our
                    <a href="privacy.php">privacy policy</a> (GDPR).
                </p>

            </form>

            <p class="auth-switch">
                Already have an account? <a href="login.php">Log in!</a>
            </p>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
