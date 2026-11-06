<?php
session_start();

require "../config/auth.php";
require "../config/csrfToken.php";
require "../config/database.php";
require "../vendor/autoload.php";


$pageTitle = "Roles";

global $pdo;
global $currentUserId;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $action   = $_POST['action'] ?? '';
    $roleName = trim($_POST['role_name'] ?? '');
    $status   = (int)($_POST['status'] ?? 1) === 0 ? 0 : 1;
    $roleId   = (int)($_POST['role_id'] ?? 0);


    if ($action === 'create') {
        $errors = validateRole($_POST, false);

        if($errors){
            $_SESSION['old_role'] = ['role_name' => $roleName, 'status' => (string)$status];
            flash('danger', implode('.', $errors), 'createModal');
            backToPage();
        }


        try {
            $statement = $pdo->prepare(
                "INSERT INTO roles (role_name, status, created_at) VALUES (:role_name, :status, NOW())"
            );
            $statement->execute(['role_name' => $roleName, 'status' => $status]);

            unset($_SESSION['old_role']);
            flash('success', "Rol muvaffaqiyatli qo'shildi");
        } catch (PDOException $e) {
            error_log($e->getMessage());
            flash(
                'danger',
                ((int)($e->errorInfo[1] ?? 0) === 1062)
                    ? "Bunday rol allaqachon mavjud"
                    : "Rol yaratishda xatolik yuz berdi",
                'createModal'
            );
        }
        backToPage();
    }


    if ($action === 'update') {
        $error = validateRole($_POST);

        if($error){
            flash('danger', implode('.', $error), 'updateModal');
            backToPage();
        }

        try {
            $statement = $pdo->prepare("UPDATE roles SET role_name = ?, status = ? WHERE id = ?");
            $statement->execute([$roleName, $status, $roleId]);
            flash('success', "Rol muvaffaqiyatli tahrirlandi");
        } catch (PDOException $e) {
            error_log($e->getMessage());
        }
        backToPage();
    }


    if ($action === 'delete') {
        try {
            if(isAdmin()){
                $statement = $pdo->prepare("DELETE FROM roles WHERE id = ?");
                $statement->execute([$roleId]);
            } else {
                $statement = $pdo->prepare("DELETE FROM roles WHERE id = ? AND user_id = ?");
                $statement->execute([$roleId, $currentUserId]);
            }

            if($statement->rowCount() === 0){
                flash('danger', "Bu rolni ochira olmaysiz");
            } else {
                flash('success', "Rol o'chirildi");
            }
        } catch (PDOException $e) {
            error_log($e->getMessage());
            flash(
                'danger',
                ((int)($e->errorInfo[1] ?? 0) === 1451)
                    ? "Bu rol boshqa ma'lumotlarga bog'langan, o'chirib bo'lmaydi"
                    : "Rolni o'chirishda xatolik yuz berdi"
            );
        }
        backToPage();
    }
}

$flash = $_SESSION['flash'] ?? null;
$old   = $_SESSION['old_role'] ?? ['role_name' => '', 'status' => '1'];
unset($_SESSION['flash'], $_SESSION['old_role']);


$role_name = $_GET['role_name'] ?? "";

if ($role_name) {
    $statement = $pdo->prepare("SELECT * FROM roles WHERE role_name LIKE :role_name ORDER BY id DESC LIMIT 10 OFFSET 0");
    $statement->execute(["role_name" => "%" . $role_name . "%"]);
} else {
    $statement = $pdo->prepare("SELECT * FROM roles ORDER BY id DESC LIMIT 10 OFFSET 0");
    $statement->execute();
}

$rolesList = $statement->fetchAll();
?>

<!doctype html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Roles</title>

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
        .app-modal .title-create i { background: #ecfdf5; color: #059669; }
        .app-modal .title-view i   { background: #fff4e0; color: #f59e0b; }
        .app-modal .title-edit i   { background: #eaf1ff; color: #2563eb; }

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
                <h2>Rollar ro'yxati</h2>
                <button type="button" class="btn btn-primary btn-create"
                        data-bs-toggle="modal" data-bs-target="#createModal">
                    <i class="bi bi-plus-lg"></i>Yaratish
                </button>
            </div>

            <div class="search-bar">
                <form method="get" class="search-form">
                    <div class="search-field">
                        <i class="bi bi-search"></i>
                        <input type="text"
                               name="role_name"
                               class="form-control"
                               placeholder="Rol nomi bo'yicha qidirish..."
                               value="<?= htmlspecialchars($role_name) ?>">
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i>Qidirish
                    </button>

                    <?php if ($role_name !== '') : ?>
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
                        <th>Nomi</th>
                        <th>Holat</th>
                        <th class="tbl-actions">Amallar</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($rolesList as $role) : ?>
                        <tr>
                            <td><?= htmlspecialchars($role['id']) ?></td>
                            <td>
                                <a href="role-view.php?role_id=<?= $role['id'] ?>"><?= htmlspecialchars($role['role_name']) ?></a>
                            </td>
                            <td><?= (int)$role['status'] === 1 ? 'Faol' : 'Faol emas' ?></td>
                            <td class="tbl-actions">
                                <a href="#" class="tbl-btn view" title="Ko'rish"
                                   data-bs-toggle="modal" data-bs-target="#viewModal<?= $role['id'] ?>">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="#" class="tbl-btn edit" title="Tahrirlash"
                                   data-bs-toggle="modal" data-bs-target="#editModal<?= $role['id'] ?>">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <a href="#" class="tbl-btn danger" title="O'chirish"
                                   data-bs-toggle="modal" data-bs-target="#deleteModal<?= $role['id'] ?>">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (!$rolesList) : ?>
                        <tr>
                            <td colspan="4" class="text-center text-secondary py-4">Ma'lumot topilmadi</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
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
                    <i class="bi bi-plus-lg"></i>Yangi rol
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Yopish"></button>
            </div>

            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Rol nomi</label>
                    <input type="text" class="form-control" name="role_name" placeholder="Masalan: Admin"
                           value="<?= htmlspecialchars($old['role_name']) ?>">
                </div>
                <div>
                    <label class="form-label">Holat</label>
                    <select class="form-select" name="status">
                        <option value="1" <?= $old['status'] === '1' ? 'selected' : '' ?>>Faol</option>
                        <option value="0" <?= $old['status'] === '0' ? 'selected' : '' ?>>Faol emas</option>
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

<!-- Har bir qator uchun View / Edit / Delete modallari (jadvaldan tashqarida) -->
<?php foreach ($rolesList as $role) : ?>

    <!-- View modal -->
    <div class="modal fade app-modal" id="viewModal<?= $role['id'] ?>" tabindex="-1"
         aria-labelledby="viewModalLabel<?= $role['id'] ?>" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title title-view" id="viewModalLabel<?= $role['id'] ?>">
                        <i class="bi bi-eye"></i>Rolni ko'rish
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Yopish"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Rol nomi</label>
                        <input type="text" class="form-control" disabled
                               value="<?= htmlspecialchars($role['role_name']) ?>">
                    </div>
                    <div>
                        <label class="form-label">Holat</label>
                        <input type="text" class="form-control" disabled
                               value="<?= (int)$role['status'] === 1 ? 'Faol' : 'Faol emas' ?>">
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Yopish</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit modal -->
    <div class="modal fade app-modal" id="editModal<?= $role['id'] ?>" tabindex="-1"
         aria-labelledby="editModalLabel<?= $role['id'] ?>" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="post" class="modal-content">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="update"/>
                <input type="hidden" name="role_id" value="<?= $role['id'] ?>"/>

                <div class="modal-header">
                    <h5 class="modal-title title-edit" id="editModalLabel<?= $role['id'] ?>">
                        <i class="bi bi-pencil"></i>Rolni tahrirlash
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Yopish"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Rol nomi</label>
                        <input type="text" class="form-control" name="role_name"
                               value="<?= htmlspecialchars($role['role_name']) ?>">
                    </div>
                    <div>
                        <label class="form-label">Holat</label>
                        <select class="form-select" name="status">
                            <option value="1" <?= (int)$role['status'] === 1 ? 'selected' : '' ?>>Faol</option>
                            <option value="0" <?= (int)$role['status'] === 0 ? 'selected' : '' ?>>Faol emas</option>
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
    <div class="modal fade app-modal" id="deleteModal<?= $role['id'] ?>" tabindex="-1"
         aria-labelledby="deleteModalLabel<?= $role['id'] ?>" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <form method="post" class="modal-content">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="delete"/>
                <input type="hidden" name="role_id" value="<?= $role['id'] ?>"/>

                <div class="delete-body">
                    <div class="delete-icon"><i class="bi bi-trash3"></i></div>
                    <h5 id="deleteModalLabel<?= $role['id'] ?>">Rostdan ham o'chirasizmi?</h5>
                    <p><strong><?= htmlspecialchars($role['role_name']) ?></strong> roli o'chiriladi.</p>
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