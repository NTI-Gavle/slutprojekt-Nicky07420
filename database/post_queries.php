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

// Fetch all posts ordered newest first, including username + like metadata.
function getAllPosts(PDO $dbconn, int $currentUserId): array
{
    $stmt = $dbconn->prepare(
        'SELECT posts.*, users.username,
                (SELECT COUNT(*) FROM post_likes WHERE post_id = posts.id) AS like_count,
                EXISTS(
                    SELECT 1
                    FROM post_likes
                    WHERE post_id = posts.id AND user_id = :current_user_id
                ) AS is_liked_by_current_user
         FROM posts
         JOIN users ON posts.user_id = users.id
         ORDER BY posts.created_at DESC'
    );
    $stmt->execute([':current_user_id' => $currentUserId]);

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

    // rowCount() tells whether a row was actually deleted
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

function likePost(PDO $dbconn, int $postId, int $userId): bool
{
    $stmt = $dbconn->prepare(
        'INSERT IGNORE INTO post_likes (post_id, user_id) VALUES (:post_id, :user_id)'
    );

    return $stmt->execute([
        ':post_id' => $postId,
        ':user_id' => $userId,
    ]);
}

function unlikePost(PDO $dbconn, int $postId, int $userId): bool
{
    $stmt = $dbconn->prepare(
        'DELETE FROM post_likes WHERE post_id = :post_id AND user_id = :user_id'
    );

    return $stmt->execute([
        ':post_id' => $postId,
        ':user_id' => $userId,
    ]);
}

function getPostLikeCount(PDO $dbconn, int $postId): int
{
    $stmt = $dbconn->prepare('SELECT COUNT(*) FROM post_likes WHERE post_id = :post_id');
    $stmt->execute([':post_id' => $postId]);

    return (int) $stmt->fetchColumn();
}

function getPostById(PDO $dbconn, int $postId): ?array
{
    $stmt = $dbconn->prepare(
        'SELECT posts.*, users.username
         FROM posts
         JOIN users ON posts.user_id = users.id
         WHERE posts.id = :post_id
         LIMIT 1'
    );

    $stmt->execute([':post_id' => $postId]);
    $post = $stmt->fetch();

    return $post !== false ? $post : null;
}

function createComment(PDO $dbconn, int $postId, int $userId, string $content): bool
{
    $stmt = $dbconn->prepare(
        'INSERT INTO comments (post_id, user_id, content) VALUES (:post_id, :user_id, :content)'
    );

    return $stmt->execute([
        ':post_id' => $postId,
        ':user_id' => $userId,
        ':content' => $content,
    ]);
}

function getCommentsByPostId(PDO $dbconn, int $postId): array
{
    $stmt = $dbconn->prepare(
        'SELECT comments.*, users.username
         FROM comments
         JOIN users ON comments.user_id = users.id
         WHERE comments.post_id = :post_id
         ORDER BY comments.created_at ASC'
    );

    $stmt->execute([':post_id' => $postId]);

    return $stmt->fetchAll();
}
