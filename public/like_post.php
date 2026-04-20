<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../database/post_queries.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('index.php');
}

$postId = (int) ($_POST['post_id'] ?? 0);
$action = $_POST['action'] ?? '';
$userId = (int) $_SESSION['user_id'];
$returnTo = $_POST['return_to'] ?? '';

if (preg_match('/^post-(\d+)$/', $returnTo, $matches)) {
    $redirectUrl = 'index.php#post-' . (int) $matches[1];
} elseif (preg_match('/^post-view-(\d+)$/', $returnTo, $matches)) {
    $redirectUrl = 'post.php?id=' . (int) $matches[1];
} else {
    $redirectUrl = 'index.php';
}

if ($postId <= 0) {
    redirectTo($redirectUrl);
}

if ($action === 'unlike') {
    unlikePost($dbconn, $postId, $userId);
} else {
    likePost($dbconn, $postId, $userId);
}

redirectTo($redirectUrl);
