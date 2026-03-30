<?php

session_start();

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../database/post_queries.php';

if (!isLoggedIn() || ($_SESSION['role'] ?? '') !== 'admin') {
    redirectTo('index.php');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('admin.php');
}

$postId = (int) ($_POST['post_id'] ?? 0);

if ($postId <= 0) {
    redirectTo('admin.php');
}

$imagePath = getPostImagePath($dbconn, $postId);

if (deletePostById($dbconn, $postId) && $imagePath) {
    $absoluteImagePath = __DIR__ . '/' . ltrim($imagePath, '/');

    if (is_file($absoluteImagePath)) {
        unlink($absoluteImagePath);
    }
}

redirectTo('admin.php');
