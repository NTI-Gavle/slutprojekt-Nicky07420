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

function normalizeStoredAssetPath(?string $storedPath): ?string
{
    if ($storedPath === null) {
        return null;
    }

    $storedPath = trim($storedPath);

    if ($storedPath === '') {
        return null;
    }

    if (preg_match('~^(?:[a-z][a-z0-9+.-]*:)?//~i', $storedPath) || str_starts_with($storedPath, 'data:')) {
        return $storedPath;
    }

    $normalizedPath = str_replace('\\', '/', $storedPath);

    if (preg_match('~(?:^|/)public/(.+)$~', $normalizedPath, $matches)) {
        $normalizedPath = $matches[1];
    }

    $normalizedPath = preg_replace('~^[./]+~', '', $normalizedPath);
    $normalizedPath = ltrim($normalizedPath, '/');

    return $normalizedPath !== '' ? $normalizedPath : null;
}

function resolvePublicAssetPath(?string $storedPath): ?string
{
    $normalizedPath = normalizeStoredAssetPath($storedPath);

    if ($normalizedPath === null) {
        return null;
    }

    if (preg_match('~^[a-z]:/~i', $normalizedPath)) {
        return null;
    }

    $projectRoot = dirname(__DIR__);
    $candidateFiles = [
        $projectRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . $normalizedPath,
        $projectRoot . DIRECTORY_SEPARATOR . $normalizedPath,
    ];

    foreach ($candidateFiles as $candidateFile) {
        if (is_file($candidateFile)) {
            return $normalizedPath;
        }
    }

    return $normalizedPath;
}

function resolveStoredAssetFilesystemPath(?string $storedPath): ?string
{
    $normalizedPath = normalizeStoredAssetPath($storedPath);

    if ($normalizedPath === null) {
        return null;
    }

    if (is_file($storedPath ?? '')) {
        return $storedPath;
    }

    $projectRoot = dirname(__DIR__);
    $candidateFiles = [
        $projectRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . $normalizedPath,
        $projectRoot . DIRECTORY_SEPARATOR . $normalizedPath,
    ];

    foreach ($candidateFiles as $candidateFile) {
        if (is_file($candidateFile)) {
            return $candidateFile;
        }
    }

    return null;
}

function getUserInitial(string $username): string
{
    $username = trim($username);
    if ($username === '') {
        return '?';
    }

    return mb_strtoupper(mb_substr($username, 0, 1));
}
