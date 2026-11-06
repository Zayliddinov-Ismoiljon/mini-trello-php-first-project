<?php
session_start();

require "../config/auth.php";
require "../config/csrfToken.php";
require "../config/database.php";
require "../vendor/autoload.php";
require "../components/pagination.php";

$pageTitle = "Tasks";

global $pdo;
global $currentUserId;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        if(checkPermissions('task_create')){
            $errors = validateTask($_POST);

            if ($errors) {
                flash('danger', implode('. ', $errors), 'createModal');
                backToPage();
            }

            try {
                $statement = $pdo->prepare(
                    'INSERT INTO tasks (project_id, task_name, user_id, deadline, status, created_at)
                 VALUES (:project_id, :task_name, :user_id, :deadline, :status, NOW())'
                );
                $statement->execute([
                    'project_id' => (int)$_POST['project_id'],
                    'task_name' => trim($_POST['task_name']),
                    'user_id'   => (int)$_POST['user_id'],
                    'deadline'  => $_POST['deadline'],
                    'status'    => 1,
                ]);
                flash('success', "Vazifa yaratildi");
            } catch (PDOException $e) {
                error_log($e->getMessage());
                flash('danger', "Vazifa yaratishda xatolik yuz berdi", 'createModal');
            }
            backToPage();
        } else {
            flash('danger', "Vazifa yaratish uchun ruxsatingiz yo'q");
        }
    }

    if ($action === 'update') {
        if(checkPermissions('task_update')){
            $taskId = (int)($_POST['task_id'] ?? 0);
            $errors = validateTask($_POST, true);

            if ($errors) {
                flash('danger', implode('. ', $errors), 'editModal' . $taskId);
                backToPage();
            }

            try {
                if(isAdmin()){
                    $statement = $pdo->prepare(
                        "UPDATE tasks SET project_id = ?, task_name = ?, user_id = ?, deadline = ? WHERE id = ?"
                    );
                    $statement->execute([
                        (int)$_POST['project_id'],
                        trim($_POST['task_name']),
                        (int)$_POST['user_id'],
                        $_POST['deadline'],
                        $taskId,
                    ]);
                } else {
                    $statement = $pdo->prepare(
                        "UPDATE tasks SET project_id = ?, task_name = ?, user_id = ?, deadline = ? WHERE id = ? AND user_id = ?"
                    );
                    $statement->execute([
                        (int)$_POST['project_id'],
                        trim($_POST['task_name']),
                        (int)$_POST['user_id'],
                        $_POST['deadline'],
                        $taskId,
                        $currentUserId
                    ]);
                }

                if($statement->rowCount() === 0){
                    flash('danger', "Vazifa sizga tegishli emas");
                } else {
                    flash('success', "Vazifa tahrirlandi");
                }

                backToPage();
            } catch (PDOException $e) {
                error_log($e->getMessage());
                flash('danger', "Vazifani tahrirlashda xatolik yuz berdi", 'editModal' . $taskId);
            }
            backToPage();
        } else {
            flash('danger', "Vazifa tahrirlash uchun ruxsatingiz yo'q");
        }
    }

    if($action === 'delete'){
        if(checkPermissions('task_delete')){
            $task_id = (int)($_POST['task_id'] ?? 0);

            if(isAdmin()){
                $statement = $pdo->prepare('DELETE FROM tasks WHERE id = ?');
                $statement->execute([$task_id]);
            } else {
                $statement = $pdo->prepare('DELETE FROM tasks WHERE id = ? AND user_id = ?');
                $statement->execute([$task_id, $currentUserId]);
            }

            if($statement->rowCount() === 0){
                flash('danger', "Bu vazifa sizga tegishli emas", '');
            } else {
                flash('success', "Muvaffaqiyatli ochirildi", '');
            }

            backToPage();
        } else {
            flash('danger', "Vazifa o'chirish uchun ruxsatingiz yo'q");
        }
    }
}


//project list
$projectList = [];
$pager = paginate(0,0);

if(checkPermissions('project_all')){
    $projectStatement = $pdo->prepare("SELECT projects.project_name, projects.id FROM projects ORDER BY id DESC");
    $projectStatement->execute();
    $projectList = $projectStatement->fetchAll();
} else {
    flash('danger', 'Loyihalar royxati uchun ruxsatingiz yoq');
}


//user list
$userList = [];

if(checkPermissions('users_all')){
    $userStatement = $pdo->prepare("SELECT users.id as user_id, users.user_name, roles.id as role_id, roles.role_name FROM users
    LEFT JOIN roles ON users.role_id = roles.id ORDER BY users.id DESC ");
    $userStatement->execute();
    $userList = $userStatement->fetchAll();
} else {
    flash('danger', 'Foydalanuvchilar royxati uchun ruxsatingiz yoq');
}

$project_id = $_GET['project_id'] ?? '';
$user_id = $_GET['user_id'] ?? '';

$tasksList = [];

if(checkPermissions('task_all')){
    $where = [];
    $params = [];

    $sql = "SELECT tasks.*, users.user_name, roles.role_name, projects.project_name FROM tasks LEFT JOIN users ON tasks.user_id = users.id 
        LEFT JOIN roles ON users.role_id = roles.id LEFT JOIN projects ON projects.id = tasks.project_id";

    if(!isAdmin()){
        $where[] = "tasks.user_id = :current_user";
        $params['current_user'] = $currentUserId;
    }

    if($project_id !== ""){
        $where[] = "tasks.project_id =:project_id";
        $params['project_id'] = (int)$project_id;
    }

    if($user_id !== ''){
        $where[] = "tasks.user_id =:user_id";
        $params['user_id'] = (int)$user_id;
    }

    $whereSql = $where ? " WHERE " . implode(" AND ", $where) : "";

    $countStatement = $pdo->prepare("SELECT COUNT(*) FROM tasks" . $whereSql);
    $countStatement->execute($params);
    $total = (int)$countStatement->fetchColumn();

    $pager = paginate($total, 10);


    $sql .= $whereSql . " ORDER BY tasks.id DESC LIMIT {$pager['perPage']} OFFSET {$pager['offset']}";

    $statement = $pdo->prepare($sql);
    $statement->execute($params);
    $tasksList = $statement->fetchAll();
} else {
    flash('danger', "Vaizfalar ro'yxati uchun ruxsatingiz yo'q");
}

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);


?>


<!doctype html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tasks</title>

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
            rows: 4,
            cols: 40,
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

        .search-field .form-select {
            height: 42px;
            padding-left: 38px;
            border: 1px solid var(--s-border, #eef0f4);
            border-radius: 12px;
            background-color: var(--s-input, #f4f6fb);
            color: var(--s-text, #475569);
            font-family: "Montserrat", system-ui, sans-serif;
            font-size: 13.5px;
            cursor: pointer;
            transition: border-color 0.15s, background-color 0.15s, box-shadow 0.15s;
        }

        .search-field .form-select:focus {
            border-color: var(--s-blue, #2563eb);
            background-color: #fff;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
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

<div class="layout">

    <?php require "../components/sidebar.php"; ?>

    <main class="main-content children">
        <?php require "../components/header.php"; ?>

        <div class="page-layout">
            <div class="page-layout-head">
                <h2>Vazifalar ro'yxati</h2>
                <button type="button" class="btn btn-primary btn-create"
                        data-bs-toggle="modal" data-bs-target="#createModal">
                    <i class="bi bi-plus-lg"></i>Yaratish
                </button>
            </div>

            <div class="search-bar">
                <form method="get" class="search-form">
                    <div class="search-field">
                        <i class="bi bi-person"></i>
                        <select name="project_id" class="form-select">
                            <option value="">Barcha loyihalar</option>
                            <?php foreach ($projectList as $project) : ?>
                                <option value="<?= $project['id'] ?>"
                                    <?= (string)($_GET['project_id'] ?? '') === (string)$project['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($project['project_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <?php if (isAdmin()) : ?>
                        <div class="search-field">
                            <i class="bi bi-person"></i>
                            <select name="user_id" class="form-select">
                                <option value="">Barcha bajaruvchilar</option>
                                <?php foreach ($userList as $user) : ?>
                                    <option value="<?= $user['user_id'] ?>"
                                        <?= (string)($_GET['user_id'] ?? '') === (string)$user['user_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($user['user_name']) ?> (<?= htmlspecialchars($user['role_name'] ?? '-') ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-funnel"></i>Filtrlash
                    </button>

                    <?php if (($_GET['user_id'] ?? '') || ($_GET['project_id'] ?? '') !== '') : ?>
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
                        <th>Loyiha</th>
                        <th>Vazifa mazmuni</th>
                        <th>Bajaruvchi</th>
                        <th>Bajaruvchi roli</th>
                        <th>Muddati</th>
                        <th>Holati</th>
                        <th class="tbl-actions">Amallar</th>
                    </tr>
                    </thead>
                    <tbody>

                    <?php foreach ($tasksList as $task) : ?>
                        <tr>
                            <td><?= htmlspecialchars($task['id']) ?></td>
                            <td><?= htmlspecialchars($task['project_name']) ?></td>
                            <td><?= htmlspecialchars($task['task_name']) ?></td>
                            <td><?= htmlspecialchars($task['user_name']) ?></td>
                            <td><?= htmlspecialchars($task['role_name']) ?></td>
                            <td><?= htmlspecialchars($task['deadline']) ?></td>
                            <td>
                                <?= match ((int)$task['status']) {
                                    1 => 'Yangi',
                                    2 => 'Bajarilgan',
                                    3 => 'Kechiktirilgan',
                                    default => '-',
                                } ?>
                            </td>
                            <td class="tbl-actions">
                                <?php if (checkPermissions('task_view')) : ?>
                                    <a href="#" class="tbl-btn view" title="Ko'rish"
                                       data-bs-toggle="modal" data-bs-target="#viewModal<?= $task['id'] ?>">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if (checkPermissions('task_update')) : ?>
                                    <a href="#" class="tbl-btn edit" title="Tahrirlash"
                                       data-bs-toggle="modal" data-bs-target="#editModal<?= $task['id'] ?>">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                <?php endif; ?>
                                <?php if (checkPermissions('task_delete')) : ?>
                                    <a href="#" class="tbl-btn danger" title="O'chirish"
                                       data-bs-toggle="modal" data-bs-target="#deleteModal<?= $task['id'] ?>">
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
            <input type="hidden" name="status" value="1"/>

            <div class="modal-header">
                <h5 class="modal-title title-create" id="createModalLabel">
                    <i class="bi bi-plus-lg"></i>Yangi Task
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Yopish"></button>
            </div>

            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Loyiha</label>
                    <select class="form-select" name="project_id">
                        <option value="">Loyiha tanlang</option>
                        <?php foreach ($projectList as $project) : ?>
                            <option value="<?= $project['id'] ?>"><?= htmlspecialchars($project['project_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Task mazmuni</label>
                    <textarea rows="6" cols="40" class="form-control" name="task_name"
                              placeholder="Masalan: Bu ish qilinsin"></textarea>
                </div>
                <div>
                    <label class="form-label">Bajaruvchi</label>
                    <select class="form-select" name="user_id">
                        <option value="">Bajaruvchi tanlang</option>
                        <?php foreach ($userList as $user) : ?>
                            <option value="<?= $user['user_id'] ?>"><?= htmlspecialchars($user['user_name']) ?>
                                (<?= htmlspecialchars($user['role_name']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Muddat</label>
                    <input type="date" class="form-control" name="deadline" placeholder="Muddat belgilang"
                           value="">
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Bekor qilish</button>
                <button type="submit" class="btn btn-success"><i class="bi bi-check-lg"></i>Saqlash</button>
            </div>
        </form>
    </div>
</div>


<?php foreach ($tasksList as $task) : ?>

    <!--View modal-->

    <div class="modal fade app-modal" id="viewModal<?= $task['id'] ?>" tabindex="-1"
         aria-labelledby="viewModalLabel<?= $task['id'] ?>" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title title-view" id="viewModalLabel<?= $task['id'] ?>">
                        <i class="bi bi-eye"></i>Vazifa ko'rish
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Yopish"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Loyiha</label>
                        <input type="text" class="form-control" disabled
                               value="<?= htmlspecialchars($task['project_name']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Task mazmuni</label>
                        <input type="text" class="form-control" disabled
                               value="<?= htmlspecialchars($task['task_name']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Bajaruvchi</label>
                        <input type="text" class="form-control" disabled
                               value="<?= htmlspecialchars($task['user_name']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Bajaruvchi roli</label>
                        <input type="text" class="form-control" disabled
                               value="<?= htmlspecialchars($task['role_name']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Muddati</label>
                        <input type="text" class="form-control" disabled
                               value="<?= htmlspecialchars($task['deadline']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Holati</label>
                        <input type="text" class="form-control" disabled
                               value="<?= match ($task['status']) {
                                   1 => "Yangi",
                                   2 => "Bajarilgan",
                                   3 => "Kechiktirilgan",
                               } ?>">
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Yopish</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit modal -->
    <div class="modal fade app-modal" id="editModal<?= $task['id'] ?>" tabindex="-1"
         aria-labelledby="editModalLabel<?= $task['id'] ?>" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="post" class="modal-content">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="update"/>
                <input type="hidden" name="task_id" value="<?= $task['id'] ?>"/>

                <div class="modal-header">
                    <h5 class="modal-title title-edit" id="editModalLabel<?= $task['id'] ?>">
                        <i class="bi bi-pencil"></i>Vazifani tahrirlash
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Yopish"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Loyiha</label>
                        <select class="form-select" name="project_id">
                            <option value="">Loyiha tanlang</option>
                            <?php foreach ($projectList as $project) : ?>
                                <option value="<?= $project['id'] ?>" <?= $project['id'] === $task['project_id'] ? "selected" : "" ?>><?= htmlspecialchars($project['project_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Vazifa mazmuni</label>
                        <input type="text" class="form-control" name="task_name"
                               value="<?= htmlspecialchars($task['task_name']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Bajaruvchi</label>
                        <select class="form-select" name="user_id">
                            <?php foreach ($userList as $user) : ?>
                                <option value="<?= $user['user_id'] ?>"
                                    <?= (int)$user['user_id'] === (int)$task['user_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($user['user_name']) ?> (<?= htmlspecialchars($user['role_name'] ?? '-') ?>)
                                </option>                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Muddati</label>
                        <input type="date" class="form-control" name="deadline"
                               value="<?= htmlspecialchars($task['deadline']) ?>" >
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
    <div class="modal fade app-modal" id="deleteModal<?= $task['id'] ?>" tabindex="-1"
         aria-labelledby="deleteModalLabel<?= $task['id'] ?>" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <form method="post" class="modal-content">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="delete"/>
                <input type="hidden" name="task_id" value="<?= $task['id'] ?>"/>

                <div class="delete-body">
                    <div class="delete-icon"><i class="bi bi-trash3"></i></div>
                    <h5 id="deleteModalLabel<?= $task['id'] ?>">Rostdan ham o'chirasizmi?</h5>
                    <p><strong><?= htmlspecialchars($task['task_name']) ?></strong>  o'chiriladi.</p>
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