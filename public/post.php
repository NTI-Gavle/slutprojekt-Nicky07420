<?php

$pageTitle = 'Post';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../database/post_queries.php';

requireLogin();

$postId = (int) ($_GET['id'] ?? 0);

if ($postId <= 0) {
    redirectTo('index.php');
}

$post = getPostById($dbconn, $postId);

if (!$post) {
    redirectTo('index.php');
}

$commentError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $comment = trim(str_replace("\r\n", "\n", $_POST['comment'] ?? ''));

    if ($comment === '') {
        $commentError = 'Comment cannot be empty.';
    } elseif (mb_strlen($comment) > 500) {
        $commentError = 'Comment cannot be longer than 500 characters.';
    } else {
        if (createComment($dbconn, $postId, (int) $_SESSION['user_id'], $comment)) {
            redirectTo('post.php?id=' . $postId);
        } else {
            $commentError = 'Something went wrong. Please try again.';
        }
    }
}

$comments = getCommentsByPostId($dbconn, $postId);
?>

<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-7">
        <a href="index.php" class="small text-decoration-none">&larr; Back to feed</a>

        <div class="feed-card mt-2 mb-4">
            <div class="post-header mb-2">
                <span class="post-username">@<?= htmlspecialchars($post['username']) ?></span>
                <span class="post-time"><?= htmlspecialchars($post['created_at']) ?></span>
            </div>

            <?php if ($post['content'] !== ''): ?>
                <p class="post-content mb-2"><?= htmlspecialchars($post['content']) ?></p>
            <?php endif; ?>

            <?php if (!empty($post['image_path'])): ?>
                <img
                    src="<?= htmlspecialchars($post['image_path']) ?>"
                    alt="Post image"
                    class="post-image"
                >
            <?php endif; ?>
        </div>

        <div class="feed-card mb-4">
            <h6 class="compose-title mb-3">Leave a comment</h6>

            <?php if ($commentError): ?>
                <div class="alert alert-danger py-2 mb-2"><?= htmlspecialchars($commentError) ?></div>
            <?php endif; ?>

            <form method="post" action="post.php?id=<?= (int) $postId ?>">
                <div class="mb-2">
                    <textarea
                        class="form-control compose-textarea"
                        name="comment"
                        rows="3"
                        maxlength="500"
                        placeholder="Write a comment..."
                    ><?= htmlspecialchars($_POST['comment'] ?? '') ?></textarea>
                </div>
                <div class="text-end">
                    <button type="submit" class="btn btn-primary btn-sm px-4">Comment</button>
                </div>
            </form>
        </div>

        <div class="feed-card mb-3">
            <h6 class="compose-title mb-3">Comments (<?= count($comments) ?>)</h6>

            <?php if (empty($comments)): ?>
                <p class="text-muted mb-0">No comments yet.</p>
            <?php else: ?>
                <?php foreach ($comments as $comment): ?>
                    <div class="comment-item">
                        <div class="post-header mb-1">
                            <span class="post-username">@<?= htmlspecialchars($comment['username']) ?></span>
                            <span class="post-time"><?= htmlspecialchars($comment['created_at']) ?></span>
                        </div>
                        <p class="post-content mb-0"><?= htmlspecialchars($comment['content']) ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
