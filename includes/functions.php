<?php

// Redirect the user to a given URL and stop execution.
function redirectTo(string $url): never
{
    header('Location: ' . $url);
    die;
}

// Check if the current visitor is logged in.
function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Require the user to be logged in.
function requireLogin(): void
{
    if (!isLoggedIn()) {
        redirectTo('login.php');
    }
}