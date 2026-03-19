<ul class="navbar-nav ms-auto align-items-md-center gap-1">
    <?php if (isLoggedIn()): ?>

        <!-- Logged-in links -->
        <li class="nav-item">
            <a class="nav-link" href="index.php">Home</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="profile.php">Profile</a>
        </li>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
        <li class="nav-item">
            <a class="nav-link text-danger fw-semibold" href="admin.php">
                <span class="badge bg-danger me-1">ADMIN</span>Panel
            </a>
        </li>
        <?php endif; ?>
        <li class="nav-item">
            <a class="nav-link" href="logout.php">Log out</a>
        </li>

    <?php else: ?>

        <!-- Guest links -->
        <li class="nav-item">
            <a class="nav-link" href="login.php">Log in</a>
        </li>
        <li class="nav-item">
            <a class="btn btn-outline-light btn-sm ms-1" href="register.php">Register</a>
        </li>

    <?php endif; ?>
</ul>