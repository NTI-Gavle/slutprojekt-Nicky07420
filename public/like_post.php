<?php

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../database/post_queries.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('index.php');
}

$postId = (int) ($_POST['post_id'] ?? 0);
$action = $_POST['action'] ?? '';
$userId = (int) $_SESSION['user_id'];

if ($postId <= 0) {
    redirectTo('index.php');
}

if ($action === 'unlike') {
    unlikePost($dbconn, $postId, $userId);
} else {
    likePost($dbconn, $postId, $userId);
}

redirectTo('index.php');
