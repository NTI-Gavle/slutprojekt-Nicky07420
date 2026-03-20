<?php

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../database/post_queries.php';

// Must be logged in to delete anything
requireLogin();

// Only accept POST requests — never delete on a plain GET request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('index.php');
}

$postId = (int) ($_POST['post_id'] ?? 0);

if ($postId <= 0) {
    redirectTo('index.php');
}

// deleteOwnPost checks ownership inside the query, so another user
// can't delete someone else's post even by crafting a POST request.
deleteOwnPost($dbconn, $postId, (int) $_SESSION['user_id']);

redirectTo('index.php');