<?php

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../database/post_queries.php';

requireLogin();

// Only accept POST requests — never delete on a plain GET request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('index.php');
}

$postId = (int) ($_POST['post_id'] ?? 0);

if ($postId <= 0) {
    redirectTo('index.php');
}

// Only allow deleting if the post belongs to the logged-in user
deleteOwnPost($dbconn, $postId, (int) $_SESSION['user_id']);

redirectTo('index.php');