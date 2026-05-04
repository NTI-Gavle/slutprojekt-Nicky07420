<?php

$pageTitle = 'Home';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../database/post_queries.php';

// Only logged-in users can see the feed
requireLogin();

$postError = '';
$postSuccess = '';

// Handle new post submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = trim(str_replace("\r\n", "\n", $_POST['content'] ?? '')); // Normalise newlines and trim whitespace
    $imagePath = null;
    $hasUpload = isset($_FILES['image']) && (int) ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

    if ($hasUpload) {
        $image = $_FILES['image'];
        $allowedMimeTypes = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp',
        ];

        if ((int) $image['error'] !== UPLOAD_ERR_OK) {
            $postError = 'Image upload failed. Please try again.';
        } else {
            $mimeType = mime_content_type($image['tmp_name']) ?: '';

            if (!isset($allowedMimeTypes[$mimeType])) {
                $postError = 'Only JPG, PNG, GIF, and WebP images are allowed.';
            } else {
                $uploadDirectory = __DIR__ . '/uploads/posts';

                if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0777, true) && !is_dir($uploadDirectory)) {
                    $postError = 'Could not save the uploaded image.';
                } else {
                    $filename = uniqid('post_', true) . '.' . $allowedMimeTypes[$mimeType];
                    $destination = $uploadDirectory . '/' . $filename;

                    if (!move_uploaded_file($image['tmp_name'], $destination)) {
                        $postError = 'Could not save the uploaded image.';
                    } else {
                        $imagePath = 'uploads/posts/' . $filename;
                    }
                }
            }
        }
    }

    if (!$postError && $content === '' && $imagePath === null) {
        $postError = 'Post cannot be empty.';
    } elseif (!$postError && mb_strlen($content) > 500) {
        $postError = 'Post cannot be longer than 500 characters. (How did you get this message)?';
    } elseif (!$postError) {
        if (createPost($dbconn, (int) $_SESSION['user_id'], $content, $imagePath)) {
            redirectTo('index.php');
        } else {
            $postError = 'Something went wrong. Please try again.';
        }
    }
}

// Fetch all posts
$posts = getAllPosts($dbconn, (int) $_SESSION['user_id']);
?>  

<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-7">

        <div class="feed-card mb-4">
            <h5 class="compose-title">What's on your mind?</h5>

            <?php if ($postError): ?>
                <div class="alert alert-danger py-2"><?= htmlspecialchars($postError) ?></div>
            <?php endif; ?>

            <form method="post" action="index.php" enctype="multipart/form-data">
                <div class="mb-2">
                    <textarea
                        class="form-control compose-textarea"
                        name="content"
                        id="postContent"
                        rows="3"
                        maxlength="500"
                        placeholder="Write something..."
                    ><?= htmlspecialchars($_POST['content'] ?? '') ?></textarea>
                </div>
                <div class="mb-3">
                    <input class="form-control form-control-sm compose-file-input" type="file" name="image" accept="image/*">
                </div>
                <div class="compose-actions d-flex justify-content-between align-items-center gap-2">
                    <span class="char-counter" id="charCounter">500 characters left</span>
                    <button type="submit" class="btn btn-primary btn-sm px-4">Post</button>
                </div>
            </form>
        </div>

        <!-- Post feed -->
        <?php if (empty($posts)): ?>
            <p class="text-center text-muted mt-5">No posts yet. Be the first to post!</p>
        <?php endif; ?>

        <?php foreach ($posts as $post): ?>
            <?php
            $postProfilePicture = resolvePublicAssetPath($post['profile_picture'] ?? null);
            $postImagePath = resolvePublicAssetPath($post['image_path'] ?? null);
            ?>
            <div class="feed-card mb-3" id="post-<?= (int) $post['id'] ?>">
                <div class="post-card-body d-flex align-items-start gap-3 mb-2">
                    <?php if (!empty($postProfilePicture)): ?>
                        <img
                            src="<?= htmlspecialchars($postProfilePicture) ?>"
                            alt="<?= htmlspecialchars($post['username']) ?> profile picture"
                            class="profile-avatar post-avatar"
                        >
                    <?php else: ?>
                        <div class="profile-avatar post-avatar profile-avatar-fallback">
                            <?= htmlspecialchars(getUserInitial($post['username'])) ?>
                        </div>
                    <?php endif; ?>

                    <a href="post.php?id=<?= (int) $post['id'] ?>" class="post-card-link flex-grow-1">
                        <!-- Post header: username + timestamp -->
                        <div class="post-header mb-2">
                            <span class="post-username">@<?= htmlspecialchars($post['username']) ?></span>
                            <span class="post-meta">
                                <span class="post-time"><?= htmlspecialchars($post['created_at']) ?></span>
                                <?php if (!empty($post['edited_at'])): ?>
                                    <span class="edited-indicator">edited</span>
                                <?php endif; ?>
                            </span>
                        </div>

                        <?php if ($post['content'] !== ''): ?>
                            <p class="post-content mb-2"><?= htmlspecialchars($post['content']) ?></p>
                        <?php endif; ?>

                        <?php if (!empty($postImagePath)): ?>
                            <img
                                src="<?= htmlspecialchars($postImagePath) ?>"
                                alt="Post image"
                                class="post-image mb-2"
                            >
                        <?php endif; ?>
                    </a>
                </div>

                <div class="post-engagement d-flex align-items-center gap-2 mb-2">
                    <form method="post" action="like_post.php" class="js-like-form">
                        <input type="hidden" name="post_id" value="<?= (int) $post['id'] ?>">
                        <input type="hidden" name="return_to" value="post-<?= (int) $post['id'] ?>">
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
                    <a
                        href="post.php?id=<?= (int) $post['id'] ?>#comments"
                        class="comment-counter text-decoration-none"
                        aria-label="Comment count"
                    >
                        <?= renderCommentBubbleIcon() ?>
                        <span class="comment-counter-text">
                            <?= (int) $post['comment_count'] ?> comment<?= (int) $post['comment_count'] === 1 ? '' : 's' ?>
                        </span>
                    </a>
                </div>

                <!-- Delete button; only shown to the post's author -->
                <?php if ((int) $post['user_id'] === (int) $_SESSION['user_id']): ?>
                    <div class="post-owner-actions d-flex align-items-center gap-2">
                        <a href="post.php?id=<?= (int) $post['id'] ?>&edit=1#edit-post" class="btn btn-secondary btn-sm">Edit</a>
                        <form method="post" action="delete_post.php"
                              onsubmit="return confirm('Delete this post?')">
                            <input type="hidden" name="post_id" value="<?= (int) $post['id'] ?>">
                            <button type="submit" class="btn btn-delete btn-sm">Delete</button>
                        </form>
                    </div>
                <?php endif; ?>

            </div>
        <?php endforeach; ?>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
