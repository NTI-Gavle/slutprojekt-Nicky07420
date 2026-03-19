<?php

$pageTitle = 'Log in';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../database/user_queries.php';

$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Basic presence check
    if (empty($username) || empty($password)) {
        $error = 'Please fill in both fields.';
    } else {
        // Look up the user in the database
        $user = getUserByUsername($dbconn, $username);

        // Verify the password against the stored bcrypt hash
        if ($user && password_verify($password, $user['password_hash'])) {

            // Regenerate session ID to prevent session fixation attacks
            session_regenerate_id(true);

            // Store the user info we need in session
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];

            redirectTo('index.php');

        } else {
            // Generic message – don't reveal whether username or password was wrong
            $error = 'Incorrect username or password.';
        }
    }
}
?>

<!-- Login card -->
<div class="row justify-content-center">
    <div class="col-12 col-sm-10 col-md-6 col-lg-5">

        <div class="auth-card">
            <h2 class="auth-title">Welcome back</h2>
            <p class="auth-sub">Log in to see your feed</p>

            <!-- Error alert -->
            <?php if ($error): ?>
                <div class="alert alert-danger py-2" role="alert">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <!-- Login form -->
            <form method="post" action="login.php" novalidate>

                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input
                        type="text"
                        class="form-control"
                        id="username"
                        name="username"
                        value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                        autocomplete="username"
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
                        autocomplete="current-password"
                        required
                    >
                </div>

                <button type="submit" class="btn btn-primary w-100 mt-1">Log in</button>

            </form>

            <p class="auth-switch">
                No account? <a href="register.php">Register here!</a>
            </p>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>