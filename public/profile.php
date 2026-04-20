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
?>

<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-7">
        <div class="feed-card mb-4">
            <h5 class="compose-title mb-3">Search profiles</h5>

            <form method="get" action="profile.php" class="profile-search-form">
                <input
                    type="text"
                    name="q"
                    class="form-control compose-textarea"
                    value="<?= htmlspecialchars($searchQuery) ?>"
                    placeholder="Search by username..."
                >
                <button type="submit" class="btn btn-primary btn-sm px-4">Search</button>
            </form>

            <?php if ($searchQuery !== ''): ?>
                <div class="mt-3">
                    <h6 class="compose-title mb-2">Results</h6>

                    <?php if (empty($searchResults)): ?>
                        <p class="text-muted mb-0">No profiles found.</p>
                    <?php else: ?>
                        <?php foreach ($searchResults as $result): ?>
                            <?php $resultProfilePicture = resolvePublicAssetPath($result['profile_picture'] ?? null); ?>
                            <a class="profile-search-card" href="profile.php?user=<?= urlencode($result['username']) ?>">
                                <?php if (!empty($resultProfilePicture)): ?>
                                    <img
                                        src="<?= htmlspecialchars($resultProfilePicture) ?>"
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
                                    <div class="profile-search-bio">
                                        <?= htmlspecialchars(trim((string) ($result['bio'] ?? '')) ?: 'No bio yet.') ?>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="feed-card mb-4 profile-card">
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
                    <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                        <div>
                            <h5 class="mb-1">@<?= htmlspecialchars($profile['username']) ?></h5>
                            <p class="text-muted mb-0">Member since <?= htmlspecialchars($profile['created_at']) ?></p>
                        </div>
                        <?php if ($isOwnProfile): ?>
                            <span class="badge bg-secondary">Your profile</span>
                        <?php endif; ?>
                    </div>

                    <p class="profile-bio mt-3 mb-0">
                        <?= htmlspecialchars($profileBio !== '' ? $profileBio : 'No bio yet.') ?>
                    </p>
                </div>
            </div>
        </div>

        <?php if ($isOwnProfile): ?>
            <div class="feed-card mb-3">
                <h6 class="compose-title mb-3">Edit profile</h6>

                <?php if ($profileError): ?>
                    <div class="alert alert-danger py-2"><?= htmlspecialchars($profileError) ?></div>
                <?php endif; ?>

                <form method="post" action="profile.php?user=<?= urlencode($profile['username']) ?>" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="bio" class="form-label">Bio</label>
                        <textarea
                            class="form-control compose-textarea"
                            id="bio"
                            name="bio"
                            rows="4"
                            maxlength="160"
                            placeholder="Tell people a little about yourself..."
                        ><?= htmlspecialchars($_POST['bio'] ?? $profileBio) ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="profile_picture" class="form-label">Profile picture</label>
                        <input
                            class="form-control form-control-sm compose-file-input"
                            type="file"
                            id="profile_picture"
                            name="profile_picture"
                            accept="image/*"
                        >
                    </div>

                    <div class="profile-edit-actions text-end">
                        <button type="submit" class="btn btn-primary btn-sm px-4">Save profile</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
