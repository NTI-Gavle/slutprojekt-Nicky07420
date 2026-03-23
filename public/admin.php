<?php

$pageTitle = 'Admin Panel';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../database/user_queries.php';
require_once __DIR__ . '/../database/post_queries.php';

// Only admins can access this page
if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    redirectTo('index.php');
}

// Fetch data for the tables
$users = $dbconn->query(
    'SELECT id, username, email, role, created_at,
            (SELECT COUNT(*) FROM posts WHERE posts.user_id = users.id) AS post_count
     FROM users
     ORDER BY created_at DESC'
)->fetchAll();

$posts = $dbconn->query(
    'SELECT posts.*, users.username
     FROM posts
     JOIN users ON posts.user_id = users.id
     ORDER BY posts.created_at DESC'
)->fetchAll();
?>

<!-- Page header -->
<div class="d-flex align-items-center gap-2 mb-4">
    <h2 class="admin-page-title mb-0">Admin Panel rahhh</h2>
</div>

<!-- Users table -->
<div class="admin-section mb-4">
    <h5 class="admin-section-title">Users</h5>
    <div class="table-responsive">
        <table class="table admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Posts</th>
                    <th>Registered</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td class="text-muted"><?= (int) $user['id'] ?></td>
                    <td class="fw-semibold">@<?= htmlspecialchars($user['username']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td>
                        <?php if ($user['role'] === 'admin'): ?>
                            <span class="badge bg-danger">admin</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">user</span>
                        <?php endif; ?>
                    </td>
                    <td><?= (int) $user['post_count'] ?></td>
                    <td class="text-muted small"><?= htmlspecialchars($user['created_at']) ?></td>
                    <td>
                        <?php if ((int) $user['id'] !== (int) $_SESSION['user_id']): ?>
                            <button class="btn btn-delete btn-sm">
                                Delete
                            </button>
                        <?php else: ?>
                            <span class="text-muted small">You</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Posts table -->
<div class="admin-section">
    <h5 class="admin-section-title">Posts</h5>
    <div class="table-responsive">
        <table class="table admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Content</th>
                    <th>Posted</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($posts as $post): ?>
                <tr>
                    <td class="text-muted"><?= (int) $post['id'] ?></td>
                    <td class="fw-semibold">@<?= htmlspecialchars($post['username']) ?></td>
                    <td class="post-preview"><?= htmlspecialchars($post['content']) ?></td>
                    <td class="text-muted small"><?= htmlspecialchars($post['created_at']) ?></td>
                    <td>
                        <button class="btn btn-delete btn-sm">
                            Delete
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>