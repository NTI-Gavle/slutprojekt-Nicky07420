<?php

require_once __DIR__ . '/db.php';

// Insert a new post into the database.
function createPost(PDO $dbconn, int $userId, string $content, ?string $imagePath = null): bool
{
    $stmt = $dbconn->prepare(
        'INSERT INTO posts (user_id, content, image_path) VALUES (:user_id, :content, :image_path)'
    );

    return $stmt->execute([
        ':user_id'    => $userId,
        ':content'    => $content,
        ':image_path' => $imagePath,
    ]);
}

// Fetch all posts ordered newest first, including the username of each author.
function getAllPosts(PDO $dbconn): array
{
    $stmt = $dbconn->query(
        'SELECT posts.*, users.username
         FROM posts
         JOIN users ON posts.user_id = users.id
         ORDER BY posts.created_at DESC'
    );

    return $stmt->fetchAll();
}

function deleteOwnPost(PDO $dbconn, int $postId, int $userId): bool
{
    $stmt = $dbconn->prepare(
        'DELETE FROM posts WHERE id = :id AND user_id = :user_id'
    );

    $stmt->execute([
        ':id'      => $postId,
        ':user_id' => $userId,
    ]);

    // rowCount() tells us whether a row was actually deleted
    return $stmt->rowCount() > 0;
}

function getPostImagePath(PDO $dbconn, int $postId): ?string
{
    $stmt = $dbconn->prepare('SELECT image_path FROM posts WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $postId]);
    $imagePath = $stmt->fetchColumn();

    return $imagePath !== false ? $imagePath : null;
}

function deletePostById(PDO $dbconn, int $postId): bool
{
    $stmt = $dbconn->prepare('DELETE FROM posts WHERE id = :id');
    $stmt->execute([':id' => $postId]);

    return $stmt->rowCount() > 0;
}
