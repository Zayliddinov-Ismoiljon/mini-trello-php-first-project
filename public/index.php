<?php
session_start();
require __DIR__ . '/../config/database.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

global $pdo;
$errorMessage = "";

if($_SERVER['REQUEST_METHOD'] == "POST"){
    $login = $_POST['login'];

    $statement = $pdo->prepare("SELECT users.*, roles.role_name FROM users LEFT JOIN roles ON users.role_id = roles.id WHERE login = ?");
    $statement->execute([$login]);
    $user = $statement->fetch();

    if($user){
        $passwordHash = $user['password'];
        $userInputHash = hash('sha512', $_POST['password']);

        if($userInputHash === $passwordHash){
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role_id'] = $user['role_id'];
            $_SESSION['role_name'] = $user['role_name'];
            $_SESSION['user_name'] = $user['user_name'];
            $_SESSION['user_status'] = $user['status'];

            if($_SESSION['role_id'] == 54){
                header("Location: dashboard-pm.php");
            } else {
                header("Location: dashboard.php");
            }

            flash('success', "Muvaffaqiyatli kirildi", '');
            exit();
        } else {
            $errorMessage = "Login yoki parol xato";
        }

    } else  {
        $errorMessage = "Login yoki parol xato";
    }


}



?>

<!doctype html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="./assets/css/sidebar.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="min-vh-100 d-flex align-items-center justify-content-center p-3">

    <div class="card shadow-sm border-0" style="width: 100%; max-width: 400px; border-radius: 16px;">
        <div class="card-body p-4 p-sm-5">

            <h1 class="h4 fw-bold text-center mb-1">Tizimga kirish</h1>
            <p class="text-secondary text-center mb-4">Hisobingizga kiring</p>

            <p><?= htmlspecialchars($errorMessage) ?></p>

            <form method="post">
                <div class="mb-3">
                    <label for="login" class="form-label">Login</label>
                    <input type="text" class="form-control" id="login" name="login"
                           placeholder="Login kiriting" required>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label">Parol</label>
                    <input type="password" class="form-control" id="password" name="password"
                           placeholder="Parol kiriting" required>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2">Kirish</button>
            </form>

        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>