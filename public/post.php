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
// Open the edit form immediately if ?edit is in the URL and the viewer owns the post
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
        // Treat 0 (absent/unset) as null so downstream checks are unambiguous
        $replyParentId = $submittedParentCommentId > 0 ? $submittedParentCommentId : null;
        $editCommentId = $submittedEditCommentId > 0 ? $submittedEditCommentId : null;

        if ($submittedDeleteCommentId > 0) {
            if (deleteCommentThread($dbconn, $submittedDeleteCommentId, $currentUserId)) {
                redirectTo('post.php?id=' . $postId . '#comments');
            }

            $commentError = 'You can only delete your own comments.';
        } elseif ($editCommentId !== null) {
            $existingComment = getCommentById($dbconn, $editCommentId);

            // Verify ownership and that the comment belongs to this post before allowing edits
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

            // Guard against replying to a comment that belongs to a different post
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

// Group comments by their parent_comment_id for easier threaded rendering later
$commentsByParent = [];

foreach ($comments as $comment) {
    $parentId = (int) ($comment['parent_comment_id'] ?? 0);
    $commentsByParent[$parentId][] = $comment;
}

// This controls the comment tree and $depth controls the indentation level (max 7 levels deep, then it caps with CSS)
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
        // These flags drive the Bootstrap collapse state on initial render,
        // so the correct inline form is pre-expanded after a failed submission.
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
                <!-- Once depth hits 7, switch to a flat CSS class that stops adding indentation -->
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