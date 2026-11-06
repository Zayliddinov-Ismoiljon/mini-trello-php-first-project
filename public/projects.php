<?php
session_start();

require "../config/auth.php";
require "../config/csrfToken.php";
require "../config/database.php";
require "../vendor/autoload.php";
require "../components/pagination.php";

$pageTitle = "Projects";

global $pdo;
global $currentUserId;

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    verifyCsrf();
    $action = $_POST['action'];
    $project_name = $_POST['project_name'];
    $user_id = $_POST['user_id'];
    $status = $_POST['status'];

    if($action === 'create'){
       if(checkPermissions('project_create')){
           $createError = validateProject($_POST, isUpdate: false);

           if($createError){
               flash('danger', implode('.', $createError), 'creatModal');
               backToPage();
           }

           try {
               $statement = $pdo->prepare("INSERT INTO projects (project_name, user_id, status) VALUES (:project_name, :user_id, :status)");
               $statement->execute([
                   'project_name' => $project_name,
                   'user_id' => $user_id,
                   'status' => $status,
               ]);
               flash('success', "Muvaffaqiyatli yaratildi", '');
           } catch (PDOException $error){
               flash('danger', implode('.', $error), 'createOpen');
           }
       } else {
           flash('danger', 'Loyiha yaratish uchun ruxsatingiz yoq' );
       }

    }

    if($action === 'update'){
        if(checkPermissions('project_update')){
            $project_id = $_POST['project_id'];
            $updateError = validateProject($_POST, true);

            if($updateError){
                flash('danger', implode('.', $updateError), 'updateModal');
                backToPage();
            }

            try {

                if(isAdmin()){
                    $statement = $pdo->prepare("UPDATE projects SET project_name = ?, user_id = ?, status = ? WHERE id = ?");
                    $statement->execute([$project_name, $user_id, $status, $project_id]);
                } else {
                    $statement = $pdo->prepare("UPDATE projects SET project_name = ?, user_id = ?, status = ? WHERE id = ? AND user_id = ?");
                    $statement->execute([$project_name, $user_id, $status, $project_id, $currentUserId]);
                }

                if($statement->rowCount() === 0){
                    flash('danger', "Bu vazifa sizga tegishli emas", '');
                } else {
                    flash('success', "Muvaffaqiyatli tahrirlandi", '');
                }
                backToPage();

            } catch (PDOException $error){
                error_log($error->getMessage());
            }
        } else {
            flash('danger', 'Loyihani tahrirlash uchun ruxsatingiz yoq');
        }
    }

    if($action === 'delete') {
        if(checkPermissions('project_delete')){
            $project_id = (int)($_POST['project_id']) ?? 0;

            if(isAdmin()){
                $statement = $pdo->prepare("DELETE FROM projects WHERE id = ?");
                $statement->execute([$project_id]);
            } else {
                $statement = $pdo->prepare("DELETE FROM projects WHERE id = ? AND user_id = ?");
                $statement->execute([$project_id, $currentUserId]);
            }

            if($statement->rowCount() === 0){
                flash('danger', "Bu vazifa sizga tegishli emas", '');
            } else {
                flash('success', "Muvaffaqiyatli ochirildi", '');
            }

            backToPage();
        } else {
            flash('danger', "O'chirish uchun ruxsatingiz yo'q");
        }
    }

    backToPage();
}

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

$userList = [];

if(checkPermissions('users_all')){
    $userSql = "SELECT users.user_name, users.id as user_id, roles.role_name FROM users LEFT JOIN roles ON users.role_id = roles.id";
    $userStatement = $pdo->prepare($userSql);
    $userStatement->execute();
    $userList = $userStatement->fetchAll();
}


//project list

$project_name = $_GET['project_name'] ?? '';
$user_id = $_GET['user_id'] ?? '';
$projectsList = [];
$pager = paginate(0, 0);


if(checkPermissions('project_all')){
    $sql = "SELECT projects.*, users.user_name, roles.role_name FROM projects LEFT JOIN users ON projects.user_id = users.id
        LEFT JOIN roles ON users.role_id = roles.id";

    $where = [];
    $params = [];

    if(!isAdmin()){
        $where[] = "projects.user_id = :current_user";
        $params['current_user'] = $currentUserId;
    }

    if($project_name !== ''){
        $where[] = "projects.project_name LIKE :project_name";
        $params['project_name'] = "%{$project_name}%";
    }

    if($user_id !== ''){
        $where[] = "projects.user_id = :user_id";
        $params['user_id'] = (int)$user_id;
    }

    $whereSql = $where ? " WHERE " . implode(".", $where) : "";

    $countStatement = $pdo->prepare("SELECT COUNT(*) FROM projects" . $whereSql);
    $countStatement->execute($params);
    $total = (int)$countStatement->fetchColumn();

    $pager = paginate($total, 10);

    $sql .= $whereSql . " ORDER BY projects.id DESC LIMIT {$pager['perPage']} OFFSET {$pager['offset']}";

    $statement = $pdo->prepare($sql);
    $statement->execute($params);
    $projectsList = $statement->fetchAll();
}


?>


<!doctype html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Projects</title>

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

        .page-layout-head h2 {
            margin: 0;
            color: #1e293b;
            font-size: 16px;
            font-weight: 700;
        }

        .search-bar {
            margin-bottom: 20px;
        }

        .search-form {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
        }

        .search-field {
            position: relative;
            flex: 1;
            min-width: 220px;
            max-width: 420px;
        }

        .search-field i {
            position: absolute;
            top: 50%;
            left: 14px;
            color: var(--s-muted, #94a3b8);
            font-size: 14px;
            transform: translateY(-50%);
            pointer-events: none;
        }

        .search-field .form-control {
            height: 42px;
            padding-left: 38px;
            border: 1px solid var(--s-border, #eef0f4);
            border-radius: 12px;
            background: var(--s-input, #f4f6fb);
            color: var(--s-text, #475569);
            font-family: "Montserrat", system-ui, sans-serif;
            font-size: 13.5px;
            transition: border-color 0.15s, background 0.15s, box-shadow 0.15s;
        }

        .search-field .form-control:focus {
            border-color: var(--s-blue, #2563eb);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
        }

        .search-form .btn,
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

        .search-form .btn i,
        .btn-create i {
            font-size: 14px;
            line-height: 1;
        }

        /* Umumiy modal stili */
        .app-modal {
            text-align: left;
            white-space: normal;
        }

        .app-modal .modal-content {
            border: none;
            border-radius: 20px;
            font-family: "Montserrat", system-ui, sans-serif;
            box-shadow: 0 20px 50px rgba(15, 23, 42, 0.18);
        }

        .app-modal .modal-header,
        .app-modal .modal-footer {
            padding: 18px 24px;
            border-color: var(--s-border, #eef0f4);
        }

        .app-modal .modal-body {
            padding: 24px;
        }

        .app-modal .modal-title {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #1e293b;
            font-size: 17px;
            font-weight: 700;
        }

        .app-modal .modal-title i {
            display: grid;
            place-items: center;
            width: 34px;
            height: 34px;
            border-radius: 10px;
            font-size: 16px;
        }

        .app-modal .form-label {
            margin-bottom: 6px;
            color: #475569;
            font-size: 13px;
            font-weight: 600;
        }

        .app-modal .form-control,
        .app-modal .form-select {
            height: 42px;
            border: 1px solid var(--s-border, #eef0f4);
            border-radius: 12px;
            background-color: var(--s-input, #f4f6fb);
            color: #1e293b;
            font-size: 13.5px;
        }

        .app-modal .form-control:focus,
        .app-modal .form-select:focus {
            border-color: var(--s-blue, #2563eb);
            background-color: #fff;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
        }

        .app-modal .form-control:disabled,
        .app-modal .form-select:disabled {
            background-color: var(--s-input, #f4f6fb);
            color: #475569;
            opacity: 1;
        }

        .app-modal .modal-footer .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            height: 42px;
            padding: 0 18px;
            border-radius: 12px;
            font-size: 13.5px;
            font-weight: 600;
            line-height: 1;
        }

        /* Sarlavha ikonkasi ranglari */
        .app-modal .title-create i {
            background: #ecfdf5;
            color: #059669;
        }

        .app-modal .title-view i {
            background: #fff4e0;
            color: #f59e0b;
        }

        .app-modal .title-edit i {
            background: #eaf1ff;
            color: #2563eb;
        }

        /* O'chirish modali */
        .app-modal .delete-body {
            padding: 32px 24px 8px;
            text-align: center;
        }

        .app-modal .delete-icon {
            display: grid;
            place-items: center;
            width: 64px;
            height: 64px;
            margin: 0 auto 16px;
            border-radius: 50%;
            background: #fef2f2;
            color: #dc2626;
            font-size: 28px;
        }

        .app-modal .delete-body h5 {
            margin-bottom: 6px;
            color: #1e293b;
            font-size: 17px;
            font-weight: 700;
        }

        .app-modal .delete-body p {
            margin: 0;
            color: #64748b;
            font-size: 13.5px;
        }

        .app-modal .delete-footer {
            justify-content: center;
            border-top: 0;
            padding-bottom: 28px;
        }

        /* Amallar tugmalari */
        .tbl-btn.view {
            background: #fff4e0;
            color: #f59e0b;
        }

        .tbl-btn.view:hover {
            background: #f59e0b;
            color: #fff;
        }

        .tbl-btn.edit {
            background: #eaf1ff;
            color: #2563eb;
        }

        .tbl-btn.edit:hover {
            background: #2563eb;
            color: #fff;
        }

        .tbl-btn.danger {
            background: #fef2f2;
            color: #dc2626;
        }

        .tbl-btn.danger:hover {
            background: #dc2626;
            color: #fff;
        }

        /* Xabar (o'ng yuqori burchak) */
        .flash-alert {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 2000;
            min-width: 300px;
            max-width: 380px;
            border: none;
            border-radius: 14px;
            font-size: 14px;
        }


        .search-form .search-select {
            width: auto;
            min-width: 200px;
            height: 42px;
            padding: 0 40px 0 14px;
            border: 1px solid var(--s-border, #eef0f4);
            border-radius: 12px;
            background-color: var(--s-input, #f4f6fb);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%2394a3b8' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 14px center;
            background-size: 12px;
            color: var(--s-text, #475569);
            font-family: "Montserrat", system-ui, sans-serif;
            font-size: 13.5px;
            cursor: pointer;
            transition: border-color 0.15s, background-color 0.15s, box-shadow 0.15s;
        }

        .search-form .search-select:hover {
            border-color: #dbe1ea;
        }

        .search-form .search-select:focus {
            border-color: var(--s-blue, #2563eb);
            background-color: #fff;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
            outline: none;
        }

        /* "Barcha rollar" tanlanganda placeholder kabi xira ko'rinsin */
        .search-form .search-select:has(option[value=""]:checked) {
            color: var(--s-muted, #94a3b8);
        }

        .search-form .search-select option {
            color: #1e293b;
        }
    </style>
</head>
<body>

<?php if ($flash) : ?>
    <div class="flash-alert alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show shadow"
         role="alert" id="flashAlert">
        <i class="bi <?= $flash['type'] === 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle' ?> me-2"></i>
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Yopish"></button>
    </div>
<?php endif; ?>

<?php if (!checkPermissions('users_all')) : ?>
    <div class="flash-alert alert alert-warning alert-dismissible fade show shadow"
         role="alert" id="flashAlert">
        Foydalanuvchilar royxatini korish uchun ruxsat yoq
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Yopish"></button>
    </div>
<?php endif; ?>

<?php if (!checkPermissions('project_all')) : ?>
    <div class="flash-alert alert alert-warning alert-dismissible fade show shadow"
         role="alert" id="flashAlert">
        Loyihalar royxatini korish uchun ruxsat yoq
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Yopish"></button>
    </div>
<?php endif; ?>



<div class="layout">

    <?php require "../components/sidebar.php"; ?>

    <main class="main-content children">
        <?php require "../components/header.php"; ?>

        <div class="page-layout">
            <div class="page-layout-head">
                <h2>Loyihalar ro'yxati</h2>
                <?php if(checkPermissions('project_create')) : ?>
                    <button type="button" class="btn btn-primary btn-create"
                            data-bs-toggle="modal" data-bs-target="#createModal">
                        <i class="bi bi-plus-lg"></i>Yaratish
                    </button>
                <?php endif;?>
            </div>

            <div class="search-bar">
                <form method="get" class="search-form">
                    <div class="search-field">
                        <i class="bi bi-search"></i>
                        <input type="text"
                               name="project_name"
                               class="form-control"
                               placeholder="Ism familiya bo'yicha qidirish..."
                               value="<?= htmlspecialchars($project_name) ?>">
                    </div>

                    <?php if (isAdmin()) : ?>
                        <select name="user_id" class="form-select search-select">
                            <option value="">Barcha foydalanuvchilar</option>
                            <?php foreach ($userList as $user) : ?>
                                <option value="<?= $user['user_id'] ?>"
                                    <?= (string)$user_id === (string)$user['user_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($user['user_name'] ?? '-') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i>Qidirish
                    </button>

                    <?php if ($project_name !== '' || $user_id !== '') : ?>
                        <a href="?" class="btn btn-danger">
                            <i class="bi bi-x-lg"></i>Tozalash
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="tbl-wrap">
                <table class="tbl">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Loyiha nomi</th>
                        <th>Masul</th>
                        <th>Masul roli</th>
                        <th>Holati</th>
                        <th class="tbl-actions">Amallar</th>
                    </tr>
                    </thead>
                    <tbody>

                    <?php foreach ($projectsList as $project) : ?>
                        <tr>
                            <td><?= htmlspecialchars($project['id']) ?></td>
                            <td><?= htmlspecialchars($project['project_name']) ?></td>
                            <td><?= htmlspecialchars($project['user_name']) ?></td>
                            <td><?= htmlspecialchars($project['role_name']) ?></td>
                            <td><?= htmlspecialchars($project['status'] == 0 ? "Jarayonda" : "Yakunlangan") ?></td>
                            <td class="tbl-actions">
                                <?php if (checkPermissions('project_view')) : ?>
                                    <a href="#" class="tbl-btn view" title="Ko'rish"
                                       data-bs-toggle="modal" data-bs-target="#viewModal<?= $project['id'] ?>">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if (checkPermissions('project_update')) : ?>
                                    <a href="#" class="tbl-btn edit" title="Tahrirlash"
                                       data-bs-toggle="modal" data-bs-target="#editModal<?= $project['id'] ?>">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if (checkPermissions('project_delete')) : ?>
                                    <a href="#" class="tbl-btn danger" title="O'chirish"
                                       data-bs-toggle="modal" data-bs-target="#deleteModal<?= $project['id'] ?>">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    </tbody>
                </table>
            </div>
            <?php renderPagination($pager); ?>
        </div>
    </main>

</div>



<!-- Create modal -->
<div class="modal fade app-modal" id="createModal" tabindex="-1" aria-labelledby="createModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="post" class="modal-content">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="create"/>

            <div class="modal-header">
                <h5 class="modal-title title-create" id="createModalLabel">
                    <i class="bi bi-plus-lg"></i>Yangi Loyiha
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Yopish"></button>
            </div>

            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Loyiha nomi</label>
                    <input class="form-control" name="project_name"
                              placeholder="Masalan: Mening uyim"/>
                </div>
                <div>
                    <label class="form-label">Masul</label>
                    <select class="form-select" name="user_id">
                        <option name="">Foydalanuvchi tanlang</option>
                        <?php foreach ($userList as $user) : ?>
                            <option value="<?= $user['user_id'] ?>"><?= htmlspecialchars($user['user_name']) ?>
                                (<?= htmlspecialchars($user['role_name']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label">Holat</label>
                    <select class="form-select" name="status">
                        <option name="">Holat tanlang</option>
                        <option value="0">Jarayonda</option>
                        <option value="1">Yakunlangan</option>
                    </select>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Bekor qilish</button>
                <button type="submit" class="btn btn-success"><i class="bi bi-check-lg"></i>Saqlash</button>
            </div>
        </form>
    </div>
</div>


<?php foreach ($projectsList as $project) : ?>

    <!-- View modal -->
    <div class="modal fade app-modal" id="viewModal<?= $project['id'] ?>" tabindex="-1"
         aria-labelledby="viewModalLabel<?= $project['id'] ?>" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title title-view" id="viewModalLabel<?= $project['id'] ?>">
                        <i class="bi bi-eye"></i>Loyiha ko'rish
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Yopish"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Loyiha nomi</label>
                        <input type="text" class="form-control" disabled
                               value="<?= htmlspecialchars($project['project_name']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Masul</label>
                        <input type="text" class="form-control" disabled
                               value="<?= htmlspecialchars($project['user_name']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Holat</label>
                        <input type="text" class="form-control" disabled
                               value="<?= htmlspecialchars($project['status'] == 0 ? "Jarayonda" : "Yakunlangan") ?>">
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Yopish</button>
                </div>
            </div>
        </div>
    </div>


    <!-- Edit modal -->
    <div class="modal fade app-modal" id="editModal<?= $project['id'] ?>" tabindex="-1"
         aria-labelledby="editModalLabel<?= $project['id'] ?>" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="post" class="modal-content">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="update"/>
                <input type="hidden" name="project_id" value="<?= $project['id'] ?>"/>

                <div class="modal-header">
                    <h5 class="modal-title title-edit" id="editModalLabel<?= $project['id'] ?>">
                        <i class="bi bi-pencil"></i>Loyiha tahrirlash
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Yopish"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Loyiha nomi</label>
                        <input type="text" class="form-control" name="project_name"
                               value="<?= htmlspecialchars($project['project_name']) ?>">
                    </div>
                    <div>
                        <label class="form-label">Masul</label>
                        <select class="form-select" name="user_id">
                            <?php foreach ($userList as $user) : ?>
                                <option value="<?= $user['user_id'] ?>" <?= (int)$user['user_id'] === $project['user_id'] ? 'selected' : '' ?>><?= htmlspecialchars($user['user_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Holat</label>
                        <select class="form-select" name="status">
                            <option name="">Holat tanlang</option>
                            <option value="0" <?= (string)$project['status'] === "0" ? 'selected' : '' ?>>Jarayonda</option>
                            <option value="1" <?= (string)$project['status'] === "1" ? 'selected' : '' ?>>Yakunlangan</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Bekor qilish</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i>Yangilash</button>
                </div>
            </form>
        </div>
    </div>


    <!-- Delete modal -->
    <div class="modal fade app-modal" id="deleteModal<?= $project['id'] ?>" tabindex="-1"
         aria-labelledby="deleteModalLabel<?= $project['id'] ?>" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <form method="post" class="modal-content">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="delete"/>
                <input type="hidden" name="project_id" value="<?= $project['id'] ?>"/>

                <div class="delete-body">
                    <div class="delete-icon"><i class="bi bi-trash3"></i></div>
                    <h5 id="deleteModalLabel<?= $project['id'] ?>">Rostdan ham o'chirasizmi?</h5>
                    <p><strong><?= htmlspecialchars($project['project_name']) ?></strong> o'chiriladi.</p>
                </div>

                <div class="modal-footer delete-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Bekor qilish</button>
                    <button type="submit" class="btn btn-danger"><i class="bi bi-trash"></i>Ha, o'chirish</button>
                </div>
            </form>
        </div>
    </div>

<?php endforeach; ?>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>