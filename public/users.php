<?php
session_start();

require "../config/auth.php";
require "../config/csrfToken.php";
require "../config/database.php";
require "../vendor/autoload.php";
require "../components/pagination.php";

$pageTitle = "Users";

global $pdo;


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';
    $user_name = $_POST['user_name'];
    $role_id = $_POST['role_id'];
    $status = $_POST['status'];

    if ($action == 'create') {
        if(checkPermissions('users_create')){
            $error = validateUser($_POST, false);
            $login = generateLogin($user_name);
            $password = generatePassword();

            if ($error) {
                flash('danger', implode('.', $error), 'createModal');
                backToPage();
            }

            try {
                $statement = $pdo->prepare("INSERT INTO users (user_name, role_id, status, login, password) VALUES (:user_name, :role_id, :status, :login, :password)");
                $statement->execute([
                    'user_name' => $user_name,
                    'role_id' => $role_id,
                    'status' => $status,
                    'login' => $login,
                    'password' => $password,
                ]);
                flash('success', "Muvaffaqiyatli yaratildi", '');
            } catch (PDOException  $errors) {
                flash('danger', implode('.', $errors), 'createModal');
            }
        } else {
            flash('danger', "Foydalanuvchi yaratish uchun ruxsatingiz yo'q");
        }
    }

    if($action == 'update'){
        if(checkPermissions("users_update")){
            $user_id = $_POST['user_id'];
            $updateError = validateUser($_POST, true);

            if($updateError){
                flash('danger', implode('.', $updateError), 'updateModal');
                backToPage();
            }

            try {
                $statement = $pdo->prepare('UPDATE users SET user_name = ?, role_id = ?, status = ? WHERE id = ?');
                $statement->execute([$user_name, $role_id, $status, $user_id]);
                flash('success', "Muvaffaqiyatli tahrirlandi", '');
            } catch (PDOException $error){
                error_log($error->getMessage());
            }
        } else {
            flash('danger', "Foydalanuvchi taahrirlash uchun ruxsatingiz yo'q");
        }
    }

    if($action == 'delete'){
        if(checkPermissions('users_delete')){
            $user_id = $_POST['user_id'];

            $statement = $pdo->prepare('DELETE FROM users WHERE id = ? ');
            $statement->execute([$user_id]);
            flash('success', "Muvaffaqiyatli ochirildi", '');
        } else {
            flash('danger', "Foydalanuvchi o'chirish uchun ruxsatingiz yo'q");
        }
    }

    backToPage();

}

//role list
$roleList = [];

if(checkPermissions('roles_all')){
    $roleStatement = $pdo->prepare("SELECT * FROM roles ORDER BY id DESC ");
    $roleStatement->execute();
    $roleList = $roleStatement->fetchAll();
} else {
    flash('danger', "Rollar ro'yxati uchun ruxsatingiz yo'q");
}

$user_name = trim($_GET['user_name'] ?? '');
$role_id   = $_GET['role_id'] ?? '';

$usersList = [];
$pager = paginate(0, 0);

if (checkPermissions('users_all')){
    $where  = [];
    $params = [];

    if ($user_name !== '') {
        $where[] = "users.user_name LIKE :user_name";
        $params['user_name'] = "%{$user_name}%";
    }

    if ($role_id !== '') {
        $where[] = "users.role_id = :role_id";
        $params['role_id'] = (int)$role_id;
    }

    $whereSql = $where ? " WHERE " . implode(" AND ", $where) : "";


    $countStatement = $pdo->prepare("SELECT COUNT(*) FROM users" . $whereSql);
    $countStatement->execute($params);
    $total = (int)$countStatement->fetchColumn();


    $pager = paginate($total, 10);


    $sql = "SELECT users.*, roles.role_name FROM users LEFT JOIN roles ON users.role_id = roles.id" . $whereSql
        . " ORDER BY users.id DESC
        LIMIT {$pager['perPage']} OFFSET {$pager['offset']}";

    $statement = $pdo->prepare($sql);
    $statement->execute($params);
    $usersList = $statement->fetchAll();
} else {
    flash('danger', "Foydalanuvchilar ro'yxati uchun ruxsatingiz yo'q");
}

$flash = $_SESSION['flash'] ?? '';
$newUser = $_SESSION['new_user'] ?? '';
unset($_SESSION['flash'], $_SESSION['new_user']);

?>


<!doctype html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users</title>

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

<?php if ($newUser) : ?>
    <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
        Login: <strong><?= htmlspecialchars($newUser['login']) ?></strong> &nbsp;
        Parol: <strong><?= htmlspecialchars($newUser['password']) ?></strong><br>
        <small>Parol faqat bir marta ko'rsatiladi, nusxalab oling.</small>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Yopish"></button>
    </div>
<?php endif; ?>

<div class="layout">

    <?php require "../components/sidebar.php"; ?>

    <main class="main-content children">
        <?php require "../components/header.php"; ?>

        <div class="page-layout">
            <div class="page-layout-head">
                <h2>Foydalanuvchilar ro'yxati</h2>
                <?php if (checkPermissions('users_create')) : ?>
                    <button type="button" class="btn btn-primary btn-create"
                            data-bs-toggle="modal" data-bs-target="#createModal">
                        <i class="bi bi-plus-lg"></i>Yaratish
                    </button>
                <?php endif; ?>
            </div>

            <div class="search-bar">
                <form method="get" class="search-form">
                    <div class="search-field">
                        <i class="bi bi-search"></i>
                        <input type="text"
                               name="user_name"
                               class="form-control"
                               placeholder="Ism familiya bo'yicha qidirish..."
                               value="<?= htmlspecialchars($user_name) ?>">
                    </div>

                    <select name="role_id" class="form-select search-select">
                        <option value="">Barcha rollar</option>
                        <?php foreach ($roleList as $role) : ?>
                            <option value="<?= $role['id'] ?>"
                                <?= (string)$role_id === (string)$role['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($role['role_name'] ?? '-') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i>Qidirish
                    </button>

                    <?php if ($user_name !== '' || $role_id !== '') : ?>
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
                        <th>Ism familiya</th>
                        <th>Rol nomi</th>
                        <th>Holat</th>
                        <th class="tbl-actions">Amallar</th>
                    </tr>
                    </thead>
                    <tbody>

                    <?php foreach ($usersList as $user) : ?>
                        <tr>
                            <td><?= htmlspecialchars($user['id']) ?></td>
                            <td><?= htmlspecialchars($user['user_name']) ?></td>
                            <td><?= htmlspecialchars($user['role_name']) ?></td>
                            <td><?= htmlspecialchars($user['status'] == 1 ? 'Faol' : 'Faol emas') ?></td>
                            <td class="tbl-actions">
                                <?php if(checkPermissions('users_view')) : ?>
                                <a href="#" class="tbl-btn view" title="Ko'rish"
                                   data-bs-toggle="modal" data-bs-target="#viewModal<?= $user['id'] ?>">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php endif;?>
                                <?php if(checkPermissions('users_update')) : ?>
                                    <a href="#" class="tbl-btn edit" title="Tahrirlash"
                                       data-bs-toggle="modal" data-bs-target="#editModal<?= $user['id'] ?>">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                <?php endif;?>
                                <?php if(checkPermissions('users_delete')) : ?>
                                    <a href="#" class="tbl-btn danger" title="O'chirish"
                                       data-bs-toggle="modal" data-bs-target="#deleteModal<?= $user['id'] ?>">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                <?php endif;?>
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
                    <i class="bi bi-plus-lg"></i>Yangi foydalanuvchi
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Yopish"></button>
            </div>

            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Ism familiyasi</label>
                    <input type="text" class="form-control" name="user_name" placeholder="Masalan: Zayliddinov Ismoil"
                           value="">
                </div>
                <div>
                    <label class="form-label">Rol</label>
                    <select class="form-select" name="role_id">
                        <?php foreach ($roleList as $role) : ?>
                            <option value="<?= htmlspecialchars($role['id']) ?>"><?= htmlspecialchars($role['role_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label">Holat</label>
                    <select class="form-select" name="status">
                        <option value="1">Faol</option>
                        <option value="0">Faol emas</option>
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


<!--View modal-->

<?php foreach ($usersList as $user) : ?>

    <!-- View modal -->
    <div class="modal fade app-modal" id="viewModal<?= $user['id'] ?>" tabindex="-1"
         aria-labelledby="viewModalLabel<?= $user['id'] ?>" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title title-view" id="viewModalLabel<?= $user['id'] ?>">
                        <i class="bi bi-eye"></i>Foydalanuvchini ko'rish
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Yopish"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Ism familiyasi</label>
                        <input type="text" class="form-control" disabled
                               value="<?= htmlspecialchars($user['user_name']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Roli</label>
                        <input type="text" class="form-control" disabled
                               value="<?= htmlspecialchars($user['role_name']) ?>">
                    </div>
                    <div>
                        <label class="form-label">Holat</label>
                        <input type="text" class="form-control" disabled
                               value="<?= (int)$user['status'] === 1 ? 'Faol' : 'Faol emas' ?>">
                    </div>
                    <div>
                        <label class="form-label">Login</label>
                        <input type="text" class="form-control" disabled
                               value="<?= $user['login'] ?>">
                    </div>
                    <div>
                        <label class="form-label">Password</label>
                        <input type="text" class="form-control" disabled
                               value="<?= $user['password'] ?>">
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Yopish</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit modal -->
    <div class="modal fade app-modal" id="editModal<?= $user['id'] ?>" tabindex="-1"
         aria-labelledby="editModalLabel<?= $user['id'] ?>" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="post" class="modal-content">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="update"/>
                <input type="hidden" name="user_id" value="<?= $user['id'] ?>"/>

                <div class="modal-header">
                    <h5 class="modal-title title-edit" id="editModalLabel<?= $user['id'] ?>">
                        <i class="bi bi-pencil"></i>Foydalanuvchi tahrirlash
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Yopish"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Ism familiyasi</label>
                        <input type="text" class="form-control" name="user_name"
                               value="<?= htmlspecialchars($user['user_name']) ?>">
                    </div>
                    <div>
                        <label class="form-label">Roli</label>
                        <select class="form-select" name="role_id">
                            <?php foreach ($roleList as $role) : ?>
                                <option value="<?= $role['id'] ?>" <?= (int)$user['role_id'] === $role['id'] ? 'selected' : '' ?>><?= htmlspecialchars($role['role_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Holat</label>
                        <select class="form-select" name="status">
                            <option value="1" <?= (int)$user['status'] === 1 ? 'selected' : '' ?>>Faol</option>
                            <option value="0" <?= (int)$user['status'] === 0 ? 'selected' : '' ?>>Faol emas</option>
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


<!--Delete modal-->

<!-- Delete modal -->
    <div class="modal fade app-modal" id="deleteModal<?= $user['id'] ?>" tabindex="-1"
         aria-labelledby="deleteModalLabel<?= $user['id'] ?>" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <form method="post" class="modal-content">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="delete"/>
                <input type="hidden" name="user_id" value="<?= $user['id'] ?>"/>

                <div class="delete-body">
                    <div class="delete-icon"><i class="bi bi-trash3"></i></div>
                    <h5 id="deleteModalLabel<?= $user['id'] ?>">Rostdan ham o'chirasizmi?</h5>
                    <p><strong><?= htmlspecialchars($user['role_name']) ?></strong> roli o'chiriladi.</p>
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