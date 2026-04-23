<?php

// Redirect the user to a given URL and stop execution.
function redirectTo(string $url): never
{
    header('Location: ' . $url);
    die;
}

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

function isAbsoluteFilesystemPath(string $path): bool
{
    return (bool) preg_match('~^(?:[a-z]:[\\/]|/)~i', $path);
}

function getProjectRootPath(): string
{
    return str_replace('\\', '/', dirname(__DIR__));
}

function filesystemPathToPublicAssetPath(string $absolutePath): ?string
{
    $absolutePath = str_replace('\\', '/', $absolutePath);
    $projectRoot = getProjectRootPath();
    $publicRoot = $projectRoot . '/public';

    if (str_starts_with($absolutePath, $publicRoot . '/')) {
        return ltrim(substr($absolutePath, strlen($publicRoot)), '/');
    }

    if (str_starts_with($absolutePath, $projectRoot . '/')) {
        return '../' . ltrim(substr($absolutePath, strlen($projectRoot)), '/');
    }

    return null;
}

function resolvePublicAssetPath(?string $storedPath): ?string
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

    if (isAbsoluteFilesystemPath($storedPath) && is_file($storedPath)) {
        $webPath = filesystemPathToPublicAssetPath($storedPath);
        if ($webPath !== null) {
            return $webPath;
        }
    }

    $normalizedPath = normalizeStoredAssetPath($storedPath);

    if ($normalizedPath === null) {
        return null;
    }

    $projectRoot = getProjectRootPath();
    $candidateFiles = [
        $projectRoot . '/public/' . $normalizedPath,
        $projectRoot . '/' . $normalizedPath,
    ];

    foreach ($candidateFiles as $candidateFile) {
        if (is_file($candidateFile)) {
            $webPath = filesystemPathToPublicAssetPath($candidateFile);
            if ($webPath !== null) {
                return $webPath;
            }
        }
    }

    if (isAbsoluteFilesystemPath($storedPath)) {
        return null;
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

    $projectRoot = getProjectRootPath();
    $candidateFiles = [
        $projectRoot . '/public/' . $normalizedPath,
        $projectRoot . '/' . $normalizedPath,
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

function renderCommentBubbleIcon(string $className = 'comment-bubble-icon'): string
{

    // Goofy ahh name
    return sprintf(
        '<span class="%s" aria-hidden="true"><svg viewBox="0 0 24 24" focusable="false" role="img"><path d="M5.5 4.5h13a3 3 0 0 1 3 3v6a3 3 0 0 1-3 3H13l-4.5 3v-3H5.5a3 3 0 0 1-3-3v-6a3 3 0 0 1 3-3Z"/><circle cx="9" cy="11" r="1"/><circle cx="12" cy="11" r="1"/><circle cx="15" cy="11" r="1"/></svg></span>',
        htmlspecialchars($className, ENT_QUOTES, 'UTF-8')
    );
}
