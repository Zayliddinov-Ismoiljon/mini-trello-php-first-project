<?php

require "../config/database.php";

global $pdo;

if(session_status() === PHP_SESSION_NONE){
    session_start();
}


if(empty($_SESSION['user_id'])){
    header("Location: index.php");
    exit();
}

$currentUserId = (int)$_SESSION['user_id'];
$currentUserName = $_SESSION['user_name'] ?? '';
$currentRoleId   = (int)($_SESSION['role_id'] ?? 0);

$permStatement = $pdo->prepare("
    SELECT permissions.code
    FROM role_permissions
    JOIN permissions ON permissions.id = role_permissions.permission_id
    WHERE role_permissions.role_id = ?
");
$permStatement->execute([$currentRoleId]);
$userPermissions = $permStatement->fetchAll(PDO::FETCH_COLUMN);

function isAdmin(): bool {
    return (int)($_SESSION['role_id'] ?? 0) === 1;
}

function checkPermissions(string $permission_code) : bool {
    global $userPermissions;
  return in_array($permission_code, $userPermissions ?? [], true);
}


