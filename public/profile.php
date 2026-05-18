<?php

$pageTitle = 'Profile';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../database/user_queries.php';
require_once __DIR__ . '/../database/post_queries.php';

requireLogin();

if (!function_exists('getUserInitial')) {
    function getUserInitial(string $username): string
    {
        $username = trim($username);
        return $username === '' ? '?' : mb_strtoupper(mb_substr($username, 0, 1));
    }
}

$currentUser = getUserById($dbconn, (int) $_SESSION['user_id']);

if (!$currentUser) {
    redirectTo('logout.php');
}

$profileError = '';
$searchQuery = trim($_GET['q'] ?? '');
$requestedUsername = trim($_GET['user'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bio = trim(str_replace("\r\n", "\n", $_POST['bio'] ?? ''));
    $profilePicturePath = null;
    // Treat any error code other than UPLOAD_ERR_NO_FILE as an attempted upload
    $hasUpload = isset($_FILES['profile_picture']) && (int) ($_FILES['profile_picture']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

    if (mb_strlen($bio) > 160) {
        $profileError = 'Bio cannot be longer than 160 characters.';
    }

    if (!$profileError && $hasUpload) {
        $picture = $_FILES['profile_picture'];
        $allowedMimeTypes = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
            'image/webp' => 'webp',
        ];

        if ((int) $picture['error'] !== UPLOAD_ERR_OK) {
            $profileError = 'Profile picture upload failed. Please try again.';
        } else {
            $mimeType = mime_content_type($picture['tmp_name']) ?: '';

            if (!isset($allowedMimeTypes[$mimeType])) {
                $profileError = 'Only JPG, PNG, GIF, and WebP images are allowed.';
            } else {
                $uploadDirectory = __DIR__ . '/uploads/profiles';
                // Creates the uploads/profiles directory if it doesn't exist, with proper error handling
                if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0777, true) && !is_dir($uploadDirectory)) {
                    $profileError = 'Could not save the uploaded profile picture.';
                } else {
                    $filename = uniqid('profile_', true) . '.' . $allowedMimeTypes[$mimeType];
                    $destination = $uploadDirectory . '/' . $filename;

                    if (!move_uploaded_file($picture['tmp_name'], $destination)) {
                        $profileError = 'Could not save the uploaded profile picture.';
                    } else {
                        $profilePicturePath = 'uploads/profiles/' . $filename;
                    }
                }
            }
        }
    }

    if (!$profileError) {
        if (updateUserProfile($dbconn, (int) $currentUser['id'], $bio, $profilePicturePath)) {
            redirectTo('profile.php?user=' . urlencode($currentUser['username']));
        }

        $profileError = 'Something went wrong. Please try again.';
    }
}

$searchResults = $searchQuery !== '' ? searchUsersByUsername($dbconn, $searchQuery) : [];
// Default to showing the logged-in user's own profile; overridden below if ?user= is set
$profile = $currentUser;

if ($requestedUsername !== '') {
    $requestedProfile = getUserByUsername($dbconn, $requestedUsername);

    if ($requestedProfile !== false) {
        $profile = $requestedProfile;
    } else {
        $profileError = 'Profile not found.';
    }
}

$isOwnProfile = (int) $profile['id'] === (int) $currentUser['id'];
$profilePicture = resolvePublicAssetPath($profile['profile_picture'] ?? null);
$profileBio = trim((string) ($profile['bio'] ?? ''));
$profilePosts = getPostsByUserId($dbconn, (int) $profile['id'], (int) $currentUser['id']);
?>

<div class="row justify-content-center">
    <div class="col-12 col-md-9 col-lg-8">

        <div class="feed-card mb-4">
            <form method="get" action="profile.php" class="profile-search-form">
                <input
                    type="search"
                    class="form-control"
                    name="q"
                    value="<?= htmlspecialchars($searchQuery) ?>"
                    placeholder="Search users..."
                >
                <button type="submit" class="btn btn-primary btn-sm px-4">Search</button>
            </form>
        </div>

        <?php if ($profileError): ?>
            <div class="alert alert-danger py-2"><?= htmlspecialchars($profileError) ?></div>
        <?php endif; ?>

        <?php if ($searchQuery !== ''): ?>
            <div class="feed-card mb-4">
                <h5 class="compose-title">Search results</h5>

                <?php if (empty($searchResults)): ?>
                    <p class="text-muted mb-0">No users found.</p>
                <?php endif; ?>

                <?php foreach ($searchResults as $result): ?>
                    <?php $resultPicture = resolvePublicAssetPath($result['profile_picture'] ?? null); ?>
                    <a class="profile-search-card" href="profile.php?user=<?= urlencode($result['username']) ?>">
                        <?php if (!empty($resultPicture)): ?>
                            <img
                                src="<?= htmlspecialchars($resultPicture) ?>"
                                alt="<?= htmlspecialchars($result['username']) ?> profile picture"
                                class="profile-avatar"
                            >
                        <?php else: ?>
                            <div class="profile-avatar profile-avatar-fallback">
                                <?= htmlspecialchars(getUserInitial($result['username'])) ?>
                            </div>
                        <?php endif; ?>

                        <div class="flex-grow-1">
                            <div class="profile-search-username">@<?= htmlspecialchars($result['username']) ?></div>
                            <?php if (!empty($result['bio'])): ?>
                                <div class="profile-search-bio"><?= htmlspecialchars($result['bio']) ?></div>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="feed-card profile-card mb-4">
            <div class="profile-summary d-flex align-items-start gap-3">
                <?php if (!empty($profilePicture)): ?>
                    <img
                        src="<?= htmlspecialchars($profilePicture) ?>"
                        alt="<?= htmlspecialchars($profile['username']) ?> profile picture"
                        class="profile-avatar profile-avatar-lg"
                    >
                <?php else: ?>
                    <div class="profile-avatar profile-avatar-lg profile-avatar-fallback">
                        <?= htmlspecialchars(getUserInitial($profile['username'])) ?>
                    </div>
                <?php endif; ?>

                <div class="flex-grow-1">
                    <div class="post-header mb-2">
                        <h1 class="page-heading mb-0">@<?= htmlspecialchars($profile['username']) ?></h1>
                        <span class="post-meta"><?= count($profilePosts) ?> post<?= count($profilePosts) === 1 ? '' : 's' ?></span>
                    </div>

                    <?php if ($profileBio !== ''): ?>
                        <p class="profile-bio mb-0"><?= htmlspecialchars($profileBio) ?></p>
                    <?php else: ?>
                        <p class="text-muted mb-0">No bio yet.</p>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($isOwnProfile): ?>
                <form method="post" action="profile.php" enctype="multipart/form-data" class="post-edit-form">
                    <div class="mb-2">
                        <textarea
                            class="form-control compose-textarea"
                            name="bio"
                            rows="3"
                            maxlength="160"
                            placeholder="Write a short bio..."
                        ><?= htmlspecialchars($profileBio) ?></textarea>
                    </div>
                    <div class="mb-3">
                        <input class="form-control form-control-sm compose-file-input" type="file" name="profile_picture" accept="image/*">
                    </div>
                    <div class="profile-edit-actions text-end">
                        <button type="submit" class="btn btn-primary btn-sm px-4">Save profile</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>

        <h5 class="compose-title"><?= $isOwnProfile ? 'Your posts' : '@' . htmlspecialchars($profile['username']) . '\'s posts' ?></h5>

        <?php if (empty($profilePosts)): ?>
            <p class="text-center text-muted mt-4">No posts yet.</p>
        <?php endif; ?>

        <?php foreach ($profilePosts as $post): ?>
            <?php $postImagePath = resolvePublicAssetPath($post['image_path'] ?? null); ?>
            <div class="feed-card mb-3" id="post-<?= (int) $post['id'] ?>">
                <a href="post.php?id=<?= (int) $post['id'] ?>" class="post-card-link">
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
                        <img src="<?= htmlspecialchars($postImagePath) ?>" alt="Post image" class="post-image mb-2">
                    <?php endif; ?>
                </a>

                <div class="post-engagement d-flex align-items-center gap-3">
                    <span class="like-counter"><?= (int) $post['like_count'] ?> like<?= (int) $post['like_count'] === 1 ? '' : 's' ?></span>
                    <a href="post.php?id=<?= (int) $post['id'] ?>#comments" class="comment-counter text-decoration-none">
                        <?= renderCommentBubbleIcon() ?>
                        <span class="comment-counter-text">
                            <?= (int) $post['comment_count'] ?> comment<?= (int) $post['comment_count'] === 1 ? '' : 's' ?>
                        </span>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>

    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
