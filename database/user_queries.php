<?php

require_once __DIR__ . '/db.php';

// Fetch a single user row by username.
function getUserByUsername(PDO $dbconn, string $username): array|false
{
    $stmt = $dbconn->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
    $stmt->execute([':username' => $username]);
    return $stmt->fetch();
}

/* Insert a new user into the database.
The password is hashed with bcrypt before being stored. */
function createUser(PDO $dbconn, string $username, string $email, string $password): bool
{
    $passwordHash = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $dbconn->prepare(
        'INSERT INTO users (username, email, password_hash) VALUES (:username, :email, :password_hash)'
    );

    return $stmt->execute([
        ':username'      => $username,
        ':email'         => $email,
        ':password_hash' => $passwordHash,
    ]);
}

// Check whether a username or email address is already taken.
function checkUserExists(PDO $dbconn, string $username, string $email): array
{
    $stmt = $dbconn->prepare(
        'SELECT username, email FROM users WHERE username = :username OR email = :email'
    );
    $stmt->execute([':username' => $username, ':email' => $email]);
    $rows = $stmt->fetchAll();

    $taken = ['username' => false, 'email' => false];
    foreach ($rows as $row) {
        if ($row['username'] === $username) $taken['username'] = true;
        if ($row['email']    === $email)    $taken['email']    = true;
    }

    return $taken;
}

function getUserPostImagePaths(PDO $dbconn, int $userId): array
{
    $stmt = $dbconn->prepare('SELECT image_path FROM posts WHERE user_id = :user_id AND image_path IS NOT NULL');
    $stmt->execute([':user_id' => $userId]);

    return array_column($stmt->fetchAll(), 'image_path');
}

function deleteUserById(PDO $dbconn, int $userId): bool
{
    $stmt = $dbconn->prepare('DELETE FROM users WHERE id = :id');
    $stmt->execute([':id' => $userId]);

    return $stmt->rowCount() > 0;
}
