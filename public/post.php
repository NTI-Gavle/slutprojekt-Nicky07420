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
$editCommentId = null;
$postError = '';
$postDraft = (string) ($post['content'] ?? '');
$isPostEditOpen = isset($_GET['edit']) && (int) $post['user_id'] === $currentUserId;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formType = $_POST['form_type'] ?? 'comment';

    if ($formType === 'edit_post') {
        $postDraft = trim(str_replace("\r\n", "\n", $_POST['content'] ?? ''));
        $isPostEditOpen = true;

        if ((int) $post['user_id'] !== $currentUserId) {
            $postError = 'You can only edit your own posts.';
        } elseif ($postDraft === '' && empty($post['image_path'])) {
            $postError = 'Post cannot be empty.';
        } elseif (mb_strlen($postDraft) > 500) {
            $postError = 'Post cannot be longer than 500 characters.';
        } elseif (updateOwnPost($dbconn, $postId, $currentUserId, $postDraft)) {
            redirectTo('post.php?id=' . $postId);
        } else {
            $postError = 'Something went wrong. Please try again.';
        }
    } else {
        $commentDraft = trim(str_replace("\r\n", "\n", $_POST['comment'] ?? ''));
        $submittedParentCommentId = (int) ($_POST['parent_comment_id'] ?? 0);
        $submittedEditCommentId = (int) ($_POST['edit_comment_id'] ?? 0);
        $submittedDeleteCommentId = (int) ($_POST['delete_comment_id'] ?? 0);
        $replyParentId = $submittedParentCommentId > 0 ? $submittedParentCommentId : null;
        $editCommentId = $submittedEditCommentId > 0 ? $submittedEditCommentId : null;

        if ($submittedDeleteCommentId > 0) {
            if (deleteCommentThread($dbconn, $submittedDeleteCommentId, $currentUserId)) {
                redirectTo('post.php?id=' . $postId . '#comments');
            }

            $commentError = 'You can only delete your own comments.';
        } elseif ($editCommentId !== null) {
            $existingComment = getCommentById($dbconn, $editCommentId);

            if (!$existingComment || (int) $existingComment['user_id'] !== $currentUserId || (int) $existingComment['post_id'] !== $postId) {
                $commentError = 'You can only edit your own comments.';
                $editCommentId = null;
            } elseif ($commentDraft === '') {
                $commentError = 'Comment cannot be empty.';
            } elseif (mb_strlen($commentDraft) > 500) {
                $commentError = 'Comment cannot be longer than 500 characters.';
            } elseif (updateCommentContent($dbconn, $editCommentId, $currentUserId, $commentDraft)) {
                redirectTo('post.php?id=' . $postId . '#comments');
            } else {
                $commentError = 'Something went wrong. Please try again.';
            }
        } elseif ($commentDraft === '') {
            $commentError = 'Comment cannot be empty.';
        } elseif (mb_strlen($commentDraft) > 500) {
            $commentError = 'Comment cannot be longer than 500 characters.';
        } elseif ($replyParentId !== null) {
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
    int $currentUserId,
    ?int $replyParentId,
    ?int $editCommentId,
    string $commentDraft,
    int $depth = 0
 ) use (&$renderCommentThreads): void {
    if (empty($commentsByParent[$parentId])) {
        return;
    }

    foreach ($commentsByParent[$parentId] as $comment) {
        $commentId = (int) $comment['id'];
        $commentProfilePicture = resolvePublicAssetPath($comment['profile_picture'] ?? null);
        $replyFormId = 'reply-form-' . $commentId;
        $editFormId = 'edit-form-' . $commentId;
        $isOwnComment = (int) $comment['user_id'] === $currentUserId;
        $isReplyOpen = $replyParentId === $commentId;
        $isEditOpen = $editCommentId === $commentId;
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
                        <span class="post-meta">
                            <span class="post-time"><?= htmlspecialchars($comment['created_at']) ?></span>
                            <?php if (!empty($comment['edited_at'])): ?>
                                <span class="edited-indicator">edited</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <p class="post-content mb-2"><?= htmlspecialchars($comment['content']) ?></p>

                    <div class="comment-actions d-flex flex-wrap align-items-center gap-2 mb-2">
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

                        <?php if ($isOwnComment): ?>
                            <button
                                type="button"
                                class="btn btn-link btn-sm p-0 text-decoration-none comment-reply-toggle"
                                data-bs-toggle="collapse"
                                data-bs-target="#<?= htmlspecialchars($editFormId) ?>"
                                aria-expanded="<?= $isEditOpen ? 'true' : 'false' ?>"
                                aria-controls="<?= htmlspecialchars($editFormId) ?>"
                            >
                                Edit
                            </button>

                            <form
                                method="post"
                                action="post.php?id=<?= (int) $postId ?>"
                                class="d-inline"
                                onsubmit="return confirm('Delete this comment and all of its replies?')"
                            >
                                <input type="hidden" name="delete_comment_id" value="<?= $commentId ?>">
                                <button type="submit" class="btn btn-link btn-sm p-0 text-decoration-none comment-delete-toggle">
                                    Delete
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>

                    <div class="collapse <?= $isEditOpen ? 'show' : '' ?>" id="<?= htmlspecialchars($editFormId) ?>">
                        <form method="post" action="post.php?id=<?= (int) $postId ?>" class="reply-form">
                            <input type="hidden" name="edit_comment_id" value="<?= $commentId ?>">
                            <div class="mb-2">
                                <textarea
                                    class="form-control compose-textarea"
                                    name="comment"
                                    rows="2"
                                    maxlength="500"
                                    placeholder="Edit your comment..."
                                ><?= $isEditOpen ? htmlspecialchars($commentDraft) : htmlspecialchars($comment['content']) ?></textarea>
                            </div>
                            <div class="text-end">
                                <button type="submit" class="btn btn-primary btn-sm px-4">Save</button>
                            </div>
                        </form>
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
                <div class="comment-thread <?= $depth >= 7 ? 'comment-thread-capped' : '' ?>">
                    <?php $renderCommentThreads($commentsByParent, $commentId, $postId, $currentUserId, $replyParentId, $editCommentId, $commentDraft, $depth + 1); ?>
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
                        <span class="post-meta">
                            <span class="post-time"><?= htmlspecialchars($post['created_at']) ?></span>
                            <?php if (!empty($post['edited_at'])): ?>
                                <span class="edited-indicator">edited</span>
                            <?php endif; ?>
                        </span>
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

            <?php if ($postError): ?>
                <div class="alert alert-danger py-2 mt-3 mb-0"><?= htmlspecialchars($postError) ?></div>
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
                    <?= renderCommentBubbleIcon() ?>
                    <span class="comment-counter-text">
                        <?= (int) $post['comment_count'] ?> comment<?= (int) $post['comment_count'] === 1 ? '' : 's' ?>
                    </span>
                </a>

                <?php if ((int) $post['user_id'] === $currentUserId): ?>
                    <button
                        type="button"
                        class="btn btn-link btn-sm p-0 text-decoration-none post-edit-toggle"
                        data-bs-toggle="collapse"
                        data-bs-target="#edit-post"
                        aria-expanded="<?= $isPostEditOpen ? 'true' : 'false' ?>"
                        aria-controls="edit-post"
                    >
                        Edit
                    </button>
                <?php endif; ?>
            </div>

            <?php if ((int) $post['user_id'] === $currentUserId): ?>
                <div class="collapse <?= $isPostEditOpen ? 'show' : '' ?>" id="edit-post">
                    <form method="post" action="post.php?id=<?= (int) $postId ?>#edit-post" class="post-edit-form">
                        <input type="hidden" name="form_type" value="edit_post">
                        <div class="mb-2">
                            <textarea
                                class="form-control compose-textarea"
                                name="content"
                                rows="3"
                                maxlength="500"
                                placeholder="Edit your post..."
                            ><?= htmlspecialchars($postDraft) ?></textarea>
                        </div>
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary btn-sm px-4">Save post</button>
                        </div>
                    </form>
                </div>
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
                    ><?= $replyParentId === null && $editCommentId === null ? htmlspecialchars($commentDraft) : '' ?></textarea>
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
                <?php $renderCommentThreads($commentsByParent, 0, $postId, $currentUserId, $replyParentId, $editCommentId, $commentDraft); ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
