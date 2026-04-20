<?php

$pageTitle = 'Post';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../database/post_queries.php';

requireLogin();

if (!function_exists('getUserInitial')) {
    function getUserInitial(string $username): string
    {
        $username = trim($username);
        return $username === '' ? '?' : mb_strtoupper(mb_substr($username, 0, 1));
    }
}

$postId = (int) ($_GET['id'] ?? 0);

if ($postId <= 0) {
    redirectTo('index.php');
}

$currentUserId = (int) $_SESSION['user_id'];
$post = getPostById($dbconn, $postId, $currentUserId);

if (!$post) {
    redirectTo('index.php');
}

$commentError = '';
$commentDraft = '';
$replyParentId = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $commentDraft = trim(str_replace("\r\n", "\n", $_POST['comment'] ?? ''));
    $submittedParentCommentId = (int) ($_POST['parent_comment_id'] ?? 0);
    $replyParentId = $submittedParentCommentId > 0 ? $submittedParentCommentId : null;

    if ($commentDraft === '') {
        $commentError = 'Comment cannot be empty.';
    } elseif (mb_strlen($commentDraft) > 500) {
        $commentError = 'Comment cannot be longer than 500 characters.';
    } else {
        if ($replyParentId !== null) {
            $parentComment = getCommentById($dbconn, $replyParentId);

            if (!$parentComment || (int) $parentComment['post_id'] !== $postId) {
                $commentError = 'That comment cannot be replied to.';
                $replyParentId = null;
            } elseif (createComment($dbconn, $postId, $currentUserId, $commentDraft, $replyParentId)) {
                redirectTo('post.php?id=' . $postId . '#comments');
            } else {
                $commentError = 'Something went wrong. Please try again.';
            }
        } elseif (createComment($dbconn, $postId, $currentUserId, $commentDraft)) {
            redirectTo('post.php?id=' . $postId . '#comments');
        } else {
            $commentError = 'Something went wrong. Please try again.';
        }
    }
}

$comments = getCommentsByPostId($dbconn, $postId);
$commentsByParent = [];

foreach ($comments as $comment) {
    $parentId = (int) ($comment['parent_comment_id'] ?? 0);
    $commentsByParent[$parentId][] = $comment;
}

$renderCommentThreads = function (
    array $commentsByParent,
    int $parentId,
    int $postId,
    ?int $replyParentId,
    string $commentDraft
 ) use (&$renderCommentThreads): void {
    if (empty($commentsByParent[$parentId])) {
        return;
    }

    foreach ($commentsByParent[$parentId] as $comment) {
        $commentId = (int) $comment['id'];
        $commentProfilePicture = resolvePublicAssetPath($comment['profile_picture'] ?? null);
        $replyFormId = 'reply-form-' . $commentId;
        $isReplyOpen = $replyParentId === $commentId;
        ?>
        <div class="comment-item">
            <div class="comment-row d-flex align-items-start gap-3">
                <?php if (!empty($commentProfilePicture)): ?>
                    <img
                        src="<?= htmlspecialchars($commentProfilePicture) ?>"
                        alt="<?= htmlspecialchars($comment['username']) ?> profile picture"
                        class="comment-avatar"
                    >
                <?php else: ?>
                    <div class="comment-avatar profile-avatar-fallback">
                        <?= htmlspecialchars(getUserInitial($comment['username'])) ?>
                    </div>
                <?php endif; ?>

                <div class="flex-grow-1">
                    <div class="post-header mb-1">
                        <a class="post-username profile-link" href="profile.php?user=<?= urlencode($comment['username']) ?>">
                            @<?= htmlspecialchars($comment['username']) ?>
                        </a>
                        <span class="post-time"><?= htmlspecialchars($comment['created_at']) ?></span>
                    </div>
                    <p class="post-content mb-2"><?= htmlspecialchars($comment['content']) ?></p>

                    <div class="comment-actions mb-2">
                        <button
                            type="button"
                            class="btn btn-link btn-sm p-0 text-decoration-none comment-reply-toggle"
                            data-bs-toggle="collapse"
                            data-bs-target="#<?= htmlspecialchars($replyFormId) ?>"
                            aria-expanded="<?= $isReplyOpen ? 'true' : 'false' ?>"
                            aria-controls="<?= htmlspecialchars($replyFormId) ?>"
                        >
                            Reply
                        </button>
                    </div>

                    <div class="collapse <?= $isReplyOpen ? 'show' : '' ?>" id="<?= htmlspecialchars($replyFormId) ?>">
                        <form method="post" action="post.php?id=<?= (int) $postId ?>" class="reply-form">
                            <input type="hidden" name="parent_comment_id" value="<?= $commentId ?>">
                            <div class="mb-2">
                                <textarea
                                    class="form-control compose-textarea"
                                    name="comment"
                                    rows="2"
                                    maxlength="500"
                                    placeholder="Reply to @<?= htmlspecialchars($comment['username']) ?>..."
                                ><?= $isReplyOpen ? htmlspecialchars($commentDraft) : '' ?></textarea>
                            </div>
                            <div class="text-end">
                                <button type="submit" class="btn btn-primary btn-sm px-4">Reply</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <?php if (!empty($commentsByParent[$commentId])): ?>
                <div class="comment-thread">
                    <?php $renderCommentThreads($commentsByParent, $commentId, $postId, $replyParentId, $commentDraft); ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
};

$postProfilePicture = resolvePublicAssetPath($post['profile_picture'] ?? null);
$postImagePath = resolvePublicAssetPath($post['image_path'] ?? null);
?>

<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-7">
        <a href="index.php" class="small text-decoration-none">&larr; Back to feed</a>

        <div class="feed-card mt-2 mb-4">
            <div class="post-summary d-flex align-items-center gap-3 mb-3">
                <?php if (!empty($postProfilePicture)): ?>
                    <img
                        src="<?= htmlspecialchars($postProfilePicture) ?>"
                        alt="<?= htmlspecialchars($post['username']) ?> profile picture"
                        class="profile-avatar"
                    >
                <?php else: ?>
                    <div class="profile-avatar profile-avatar-fallback">
                        <?= htmlspecialchars(getUserInitial($post['username'])) ?>
                    </div>
                <?php endif; ?>

                <div class="flex-grow-1">
                    <div class="post-header mb-1">
                        <a class="post-username profile-link" href="profile.php?user=<?= urlencode($post['username']) ?>">
                            @<?= htmlspecialchars($post['username']) ?>
                        </a>
                        <span class="post-time"><?= htmlspecialchars($post['created_at']) ?></span>
                    </div>
                </div>
            </div>

            <?php if ($post['content'] !== ''): ?>
                <p class="post-content mb-2"><?= htmlspecialchars($post['content']) ?></p>
            <?php endif; ?>

            <?php if (!empty($postImagePath)): ?>
                <img
                    src="<?= htmlspecialchars($postImagePath) ?>"
                    alt="Post image"
                    class="post-image"
                >
            <?php endif; ?>

            <div class="post-engagement d-flex flex-wrap align-items-center gap-3 mt-3">
                <form method="post" action="like_post.php" class="js-like-form">
                    <input type="hidden" name="post_id" value="<?= (int) $post['id'] ?>">
                    <input type="hidden" name="return_to" value="post-view-<?= (int) $post['id'] ?>">
                    <input
                        type="hidden"
                        class="js-like-action"
                        name="action"
                        value="<?= (int) $post['is_liked_by_current_user'] === 1 ? 'unlike' : 'like' ?>"
                    >
                    <button
                        type="submit"
                        class="btn btn-sm btn-heart <?= (int) $post['is_liked_by_current_user'] === 1 ? 'is-liked' : '' ?>"
                        aria-label="<?= (int) $post['is_liked_by_current_user'] === 1 ? 'Unlike post' : 'Like post' ?>"
                    >
                        <?= (int) $post['is_liked_by_current_user'] === 1 ? '&#10084;' : '&#9825;' ?>
                    </button>
                </form>
                <span class="like-counter" aria-label="Like count">
                    <?= (int) $post['like_count'] ?> like<?= (int) $post['like_count'] === 1 ? '' : 's' ?>
                </span>
                <a href="#comments" class="comment-counter text-decoration-none" aria-label="Comment count">
                    <?= (int) $post['comment_count'] ?> comment<?= (int) $post['comment_count'] === 1 ? '' : 's' ?>
                </a>
            </div>
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
                    ><?= $replyParentId === null ? htmlspecialchars($commentDraft) : '' ?></textarea>
                </div>
                <div class="text-end">
                    <button type="submit" class="btn btn-primary btn-sm px-4">Comment</button>
                </div>
            </form>
        </div>

        <div class="feed-card mb-3" id="comments">
            <h6 class="compose-title mb-3">Comments (<?= count($comments) ?>)</h6>

            <?php if (empty($comments)): ?>
                <p class="text-muted mb-0">No comments yet.</p>
            <?php else: ?>
                <?php $renderCommentThreads($commentsByParent, 0, $postId, $replyParentId, $commentDraft); ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
