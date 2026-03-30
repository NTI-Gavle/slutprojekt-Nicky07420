<?php

session_start();

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../database/user_queries.php';

// Redirect if not logged in or not admin
if (!isLoggedIn() || ($_SESSION['role'] ?? '') !== 'admin') {
    redirectTo('index.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('admin.php');
}

$userId = (int) ($_POST['user_id'] ?? 0);

if ($userId <= 0 || $userId === (int) $_SESSION['user_id']) {
    redirectTo('admin.php');
}

$imagePaths = getUserPostImagePaths($dbconn, $userId);

// Delete the user and their posts, then delete any associated images
if (deleteUserById($dbconn, $userId)) {
    foreach ($imagePaths as $imagePath) {
        $absoluteImagePath = __DIR__ . '/' . ltrim($imagePath, '/');

        if (is_file($absoluteImagePath)) {
            unlink($absoluteImagePath);
        }
    }
}

redirectTo('admin.php');
