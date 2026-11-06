<?php

require "../config/auth.php";
require "../config/csrfToken.php";
require "../config/database.php";
require "../vendor/autoload.php";

$pageTitle = "Ruxsatlar";
global $pdo;
global $userPermissions;
$role_id = $_GET['role_id'] ?? "";


$role_id = (int)($_GET['role_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    verifyCsrf();

    $selectedPermIds = array_map("intval", $_POST['permissions'] ?? []);

    $pdo->beginTransaction();

    try {
        $delete = $pdo->prepare("DELETE FROM role_permissions WHERE role_id = ?");
        $delete->execute([$role_id]);

        $insert = $pdo->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
        foreach ($selectedPermIds as $permId) {
            $insert->execute([$role_id, $permId]);
        }

        $pdo->commit();
        flash('success', "Ruxsatlar biriktirildi");
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log($e->getMessage());
        flash('danger', "Saqlashda xatolik yuz berdi");
    }

    header("Location: roles.php");
    exit;
}

$checkedPermStatement = $pdo->prepare("SELECT permission_id FROM role_permissions WHERE role_id = ?");
$checkedPermStatement->execute([$role_id]);
$initialCheckedPermList = $checkedPermStatement->fetchAll(PDO::FETCH_COLUMN);

$permStatement = $pdo->prepare("SELECT * FROM permissions ORDER BY content_type, id");
$permStatement->execute();
$permissionList = $permStatement->fetchAll();

$groupedPermissions = [];

foreach ($permissionList as $permission) {
    $groupedPermissions[$permission['content_type']][] = $permission;
}

?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Role view</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/table.css" rel="stylesheet"/>

    <style>
        body {
            margin: 0;
            background: #EFF2F7;
        }

        .layout {
            display: flex;
            min-height: 100vh;
        }

        .children {
            flex: 1;
            min-width: 0;
        }

        .page-layout {
            background: white;
            padding: 20px;
            border-radius: 20px;
        }

        .page-layout-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--s-border);
        }

        .page-layout-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--s-border, #eef0f4);
        }

        .head-title {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .head-title h2 {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
            color: #1e293b;
            font-size: 16px;
            font-weight: 700;
        }

        .head-title h2 i {
            display: grid;
            place-items: center;
            width: 34px;
            height: 34px;
            border-radius: 10px;
            background: #eaf1ff;
            color: #2563eb;
            font-size: 16px;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            height: 38px;
            padding: 0 14px;
            border: 1px solid var(--s-border, #eef0f4);
            border-radius: 12px;
            background: var(--s-input, #f4f6fb);
            color: #475569;
            font-family: "Montserrat", system-ui, sans-serif;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: background 0.15s, border-color 0.15s, color 0.15s;
        }

        .btn-back i {
            font-size: 15px;
            transition: transform 0.15s;
        }

        .btn-back:hover {
            border-color: #2563eb;
            background: #fff;
            color: #2563eb;
        }

        .btn-back:hover i {
            transform: translateX(-3px);
        }

        .perm-group-row td {
            background: var(--s-input, #f4f6fb);
            color: #1e293b;
            font-size: 13px;
            font-weight: 700;
            text-transform: capitalize;
        }

        .perm-count {
            margin-left: 4px;
            color: var(--s-muted, #94a3b8);
            font-weight: 600;
        }

        .tbl .form-check-input {
            width: 18px;
            height: 18px;
            margin: 0;
            cursor: pointer;
        }

        .perm-type {
            color: #1e293b;
            font-size: 13px;
            font-weight: 700;
            text-transform: capitalize;
            white-space: nowrap;
            vertical-align: middle;
        }

        .perm-count {
            margin-left: 4px;
            color: var(--s-muted, #94a3b8);
            font-weight: 600;
        }

        .perm-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .perm-item {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin: 0;
            padding: 8px 12px;
            border: 1px solid var(--s-border, #eef0f4);
            border-radius: 10px;
            background: var(--s-input, #f4f6fb);
            color: #475569;
            font-size: 13px;
            cursor: pointer;
            transition: border-color 0.15s, background 0.15s;
        }

        .perm-item:hover {
            border-color: #dbe1ea;
        }

        .perm-item:has(.form-check-input:checked) {
            border-color: var(--s-blue, #2563eb);
            background: #eaf1ff;
            color: #1e293b;
        }

        .tbl .form-check-input {
            width: 18px;
            height: 18px;
            margin: 0;
            cursor: pointer;
        }

        .btn-create {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            height: 42px;
            padding: 0 18px;
            border-radius: 12px;
            font-family: "Montserrat", system-ui, sans-serif;
            font-size: 13.5px;
            font-weight: 600;
            line-height: 1;
            white-space: nowrap;
        }
    </style>

</head>
<body>

<div class="layout">
    <?php require "../components/sidebar.php"?>

    <main class="main-content children">
        <?php require "../components/header.php"; ?>

        <form method="post" class="page-layout">
            <?= csrfField() ?>

            <div class="page-layout-head">
                <div class="head-title">
                    <a href="roles.php" class="btn-back" title="Rollarga qaytish">
                        <i class="bi bi-arrow-left"></i>
                        <span>Orqaga</span>
                    </a>
                    <h2>
                        Ruxsatlar ro'yxati
                    </h2>
                </div>

                <button type="submit" class="btn btn-primary btn-create">
                    <i class="bi bi-check-lg"></i>Biriktirish
                </button>
            </div>

            <div class="tbl-wrap">
                <table class="tbl">
                    <thead>
                    <tr>
                        <th style="width: 200px;">Content type</th>
                        <th>Ruxsatlar</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($groupedPermissions as $contentType => $permissions) : ?>
                        <tr>
                            <td class="perm-type">
                                <?= htmlspecialchars($contentType) ?>
                                <span class="perm-count">(<?= count($permissions) ?> ta)</span>
                            </td>
                            <td>
                                <div class="perm-list">
                                    <?php foreach ($permissions as $permission) : ?>
                                        <label class="perm-item" for="perm<?= $permission['id'] ?>">
                                            <input type="checkbox"
                                                   class="form-check-input"
                                                   name="permissions[]"
                                                   value="<?= $permission['id'] ?>"
                                                   id="perm<?= $permission['id'] ?>"
                                                <?= in_array((int)$permission['id'], $initialCheckedPermList, true) ? 'checked' : '' ?>
                                            >
                                            <?= htmlspecialchars($permission['description']) ?>
<!--                                            (--><?php //= htmlspecialchars($permission['code']) ?><!--)-->
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </form>
    </main>
</div>

</body>
</html>