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
            // Use mime_content_type on the temp file rather than trusting the
            // client-supplied MIME type, which can be trivially spoofed
            $mimeType = mime_content_type($picture['tmp_name']) ?: '';

            if (!isset($allowedMimeTypes[$mimeType])) {
                $profileError = 'Only JPG, PNG, GIF, and WebP images are allowed.';
            } else {
                $uploadDirectory = __DIR__ . '/uploads/profiles';

                // The middle condition handles a race where another process creates
                // the directory between the is_dir check and the mkdir call
                if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0777, true) && !is_dir($uploadDirectory)) {
                    $profileError = 'Could not save the uploaded profile picture.';
                } else {
                    // uniqid with more_entropy=true reduces collision risk on busy servers
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
?>