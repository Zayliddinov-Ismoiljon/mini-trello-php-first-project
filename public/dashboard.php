<?php
session_start();

require "../config/auth.php";
require "../config/database.php";
$pageTitle = "Dashboard";

global $pdo;

//userStatistics

$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$activeUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 1")->fetchColumn();
$inactiveUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 0")->fetchColumn();

//projectStatistics

$totalProjects = $pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn();
$progressProjects = $pdo->query("SELECT COUNT(*) FROM projects WHERE status = 0")->fetchColumn();
$finishedProjects = $pdo->query("SELECT COUNT(*) FROM projects WHERE status = 1")->fetchColumn();

//projectStatisticsByUser

$projectStatByUser = $pdo->query("
    SELECT projects.id,
           projects.project_name,
           users.user_name,
           COUNT(tasks.id) AS task_count,
           COUNT(CASE WHEN tasks.status = 1 THEN 1 END) AS new,
           COUNT(CASE WHEN tasks.status = 2 THEN 1 END) AS completed,
           COUNT(CASE WHEN tasks.status = 3 THEN 1 END) AS posponed
    FROM projects
    LEFT JOIN users ON users.id = projects.user_id
    LEFT JOIN tasks ON tasks.project_id = projects.id
    GROUP BY projects.id, projects.project_name, users.user_name
    ORDER BY projects.id DESC
    LIMIT 5
")->fetchAll();


//taskStatistics

$totalTasks = $pdo->query("SELECT COUNT(*) FROM tasks")->fetchColumn();
$newTasks = $pdo->query("SELECT COUNT(*) FROM tasks WHERE status = 1")->fetchColumn();
$completedTasks = $pdo->query("SELECT COUNT(*) FROM tasks WHERE status = 2")->fetchColumn();
$postponedTasks = $pdo->query("SELECT COUNT(*) FROM tasks WHERE status = 3")->fetchColumn();

//roleStatistic

$totalRoles = $pdo->query("SELECT COUNT(*) FROM roles")->fetchColumn();
$mostRoles = $pdo->query("SELECT roles.role_name, COUNT(role_id) FROM users LEFT JOIN roles ON roles.id = users.role_id 
    GROUP BY roles.role_name ORDER BY COUNT(role_id) DESC LIMIT 1")->fetchColumn();

$userCountByRole = $pdo->query("SELECT roles.role_name, COUNT(users.id) AS user_count FROM users LEFT JOIN roles ON roles.id = users.role_id
    GROUP BY users.role_id, roles.role_name ORDER BY user_count DESC
")->fetchAll();


$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

?>
<!doctype html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap"
          rel="stylesheet">

    <style>
        :root {
            --s-border: #eef0f4;
            --s-input: #f4f6fb;
            --s-text: #475569;
            --s-muted: #94a3b8;
            --s-dark: #1e293b;
            --s-blue: #2563eb;

            --c-green: #059669;
            --c-green-bg: #ecfdf5;
            --c-orange: #f59e0b;
            --c-orange-bg: #fff4e0;
            --c-blue: #2563eb;
            --c-blue-bg: #eaf1ff;
            --c-purple: #7c3aed;
            --c-purple-bg: #f3eeff;
            --c-red: #dc2626;
            --c-red-bg: #fef2f2;
        }

        body {
            margin: 0;
            background: #EFF2F7;
            font-family: "Montserrat", system-ui, sans-serif;
        }

        .layout {
            display: flex;
            min-height: 100vh;
        }

        .children {
            flex: 1;
            min-width: 0;
        }

        /* ===== Dashboard ===== */
        .dash {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .dash-head {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }

        .dash-head h2 {
            margin: 0 0 4px;
            color: var(--s-dark);
            font-size: 20px;
            font-weight: 700;
        }

        .dash-head p {
            margin: 0;
            color: var(--s-muted);
            font-size: 13.5px;
        }

        .dash-date {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            height: 40px;
            padding: 0 16px;
            border-radius: 12px;
            background: #fff;
            color: var(--s-text);
            font-size: 13px;
            font-weight: 600;
        }

        .dash-date i {
            color: var(--s-blue);
        }

        /* ===== Stat kartalar ===== */
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 20px;
        }

        .stat-card {
            position: relative;
            overflow: hidden;
            padding: 20px;
            border-radius: 20px;
            background: #fff;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 14px 30px rgba(15, 23, 42, 0.08);
        }

        /* Burchakdagi bezak doira */
        .stat-card::after {
            content: "";
            position: absolute;
            top: -40px;
            right: -40px;
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: var(--accent-bg);
            opacity: 0.7;
        }

        .stat-top {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 18px;
        }

        .stat-icon {
            display: grid;
            place-items: center;
            width: 46px;
            height: 46px;
            border-radius: 14px;
            background: var(--accent-bg);
            color: var(--accent);
            font-size: 20px;
        }

        .stat-trend {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            border-radius: 999px;
            background: var(--c-green-bg);
            color: var(--c-green);
            font-size: 12px;
            font-weight: 700;
        }

        .stat-trend.down {
            background: var(--c-red-bg);
            color: var(--c-red);
        }

        .stat-label {
            position: relative;
            z-index: 1;
            margin-bottom: 4px;
            color: var(--s-muted);
            font-size: 13px;
            font-weight: 600;
        }

        .stat-value {
            position: relative;
            z-index: 1;
            margin-bottom: 14px;
            color: var(--s-dark);
            font-size: 30px;
            font-weight: 800;
            line-height: 1.1;
        }

        .stat-foot {
            position: relative;
            z-index: 1;
            display: flex;
            gap: 14px;
            padding-top: 14px;
            border-top: 1px dashed var(--s-border);
            color: var(--s-text);
            font-size: 12.5px;
        }

        .stat-foot span {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .accent-green {
            --accent: var(--c-green);
            --accent-bg: var(--c-green-bg);
        }

        .accent-orange {
            --accent: var(--c-orange);
            --accent-bg: var(--c-orange-bg);
        }

        .accent-blue {
            --accent: var(--c-blue);
            --accent-bg: var(--c-blue-bg);
        }

        .accent-purple {
            --accent: var(--c-purple);
            --accent-bg: var(--c-purple-bg);
        }

        /* ===== Panel (oq blok) ===== */
        .dash-row {
            display: grid;
            grid-template-columns: minmax(0, 2fr) minmax(0, 1fr);
            gap: 20px;
        }

        .panel {
            padding: 20px;
            border-radius: 20px;
            background: #fff;
        }

        .panel-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--s-border);
        }

        .panel-head h3 {
            margin: 0;
            color: var(--s-dark);
            font-size: 16px;
            font-weight: 700;
        }

        .panel-head small {
            color: var(--s-muted);
            font-size: 12.5px;
            font-weight: 600;
        }

        /* ===== Tasklar holati ===== */
        .task-total {
            display: flex;
            align-items: baseline;
            gap: 8px;
            margin-bottom: 14px;
        }

        .task-total strong {
            color: var(--s-dark);
            font-size: 28px;
            font-weight: 800;
        }

        .task-total span {
            color: var(--s-muted);
            font-size: 13px;
            font-weight: 600;
        }

        .segment-bar {
            display: flex;
            gap: 4px;
            height: 14px;
            margin-bottom: 22px;
        }

        .segment-bar div {
            border-radius: 6px;
        }

        .task-status-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
        }

        .task-status {
            padding: 14px;
            border-radius: 14px;
            background: var(--s-input);
        }

        .task-status-name {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
            color: var(--s-text);
            font-size: 12.5px;
            font-weight: 600;
        }

        .task-status-value {
            color: var(--s-dark);
            font-size: 20px;
            font-weight: 800;
        }

        .task-status-value small {
            margin-left: 4px;
            color: var(--s-muted);
            font-size: 12px;
            font-weight: 600;
        }

        /* ===== Rollar ro'yxati ===== */
        .role-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .role-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 13px;
        }

        .role-name {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--s-dark);
            font-weight: 600;
        }

        .role-avatar {
            display: grid;
            place-items: center;
            width: 32px;
            height: 32px;
            border-radius: 10px;
            background: var(--accent-bg);
            color: var(--accent);
            font-size: 14px;
        }

        .role-count {
            color: var(--s-text);
            font-weight: 700;
        }

        .mini-bar {
            height: 6px;
            border-radius: 999px;
            background: var(--s-input);
            overflow: hidden;
        }

        .mini-bar div {
            height: 100%;
            border-radius: 999px;
            background: var(--accent);
        }

        /* ===== Loyihalar jadvali ===== */
        .dash-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13.5px;
        }

        .dash-table th {
            padding: 0 12px 12px;
            color: var(--s-muted);
            font-size: 12px;
            font-weight: 700;
            text-align: left;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            white-space: nowrap;
        }

        .dash-table td {
            padding: 14px 12px;
            border-top: 1px solid var(--s-border);
            color: var(--s-text);
            vertical-align: middle;
        }

        .dash-table .project-name {
            color: var(--s-dark);
            font-weight: 700;
        }

        .user-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }

        .user-chip .avatar {
            display: grid;
            place-items: center;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: var(--c-blue-bg);
            color: var(--c-blue);
            font-size: 12px;
            font-weight: 700;
        }

        .progress-cell {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 160px;
        }

        .progress-cell .mini-bar {
            flex: 1;
        }

        .progress-cell b {
            width: 38px;
            color: var(--s-dark);
            font-size: 12.5px;
            text-align: right;
        }

        .table-scroll {
            overflow-x: auto;
        }

        /* ===== Responsive ===== */
        @media (max-width: 1200px) {
            .stat-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .dash-row {
                grid-template-columns: minmax(0, 1fr);
            }
        }

        @media (max-width: 640px) {
            .stat-grid,
            .task-status-grid {
                grid-template-columns: minmax(0, 1fr);
            }
        }


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
         role="alert">
        <i class="bi <?= $flash['type'] === 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle' ?> me-2"></i>
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Yopish"></button>
    </div>
<?php endif; ?>

<div class="layout">

    <?php require "../components/sidebar.php"; ?>

    <main class="main-content children">
        <?php require "../components/header.php"; ?>

        <div class="dash">

            <!-- Stat kartalar -->
            <div class="stat-grid">

                <!-- Foydalanuvchilar -->
                <div class="stat-card accent-blue">
                    <div class="stat-top">
                        <div class="stat-icon"><i class="bi bi-people"></i></div>
                    </div>
                    <div class="stat-label">Foydalanuvchilar</div>
                    <div class="stat-value"><?= $totalUsers ?></div>
                    <div class="stat-foot">
                        <span><i class="dot" style="background: var(--c-green)"></i>Faol: <?= $activeUsers ?></span>
                        <span><i class="dot"
                                 style="background: var(--c-red)"></i>Faol emas: <?= $inactiveUsers ?></span>
                    </div>
                </div>

                <!-- Loyihalar -->
                <div class="stat-card accent-green">
                    <div class="stat-top">
                        <div class="stat-icon"><i class="bi bi-kanban"></i></div>
                    </div>
                    <div class="stat-label">Loyihalar</div>
                    <div class="stat-value"><?= $totalProjects ?></div>
                    <div class="stat-foot">
                        <span><i class="dot"
                                 style="background: var(--c-blue)"></i>Jarayonda: <?= $progressProjects ?></span>
                        <span><i class="dot"
                                 style="background: var(--c-green)"></i>Yakunlangan: <?= $finishedProjects ?></span>
                    </div>
                </div>

                <!-- Tasklar -->
                <div class="stat-card accent-orange">
                    <div class="stat-top">
                        <div class="stat-icon"><i class="bi bi-list-check"></i></div>
                    </div>
                    <div class="stat-label">Tasklar</div>
                    <div class="stat-value"><?= $totalTasks ?></div>
                    <div class="stat-foot">
                        <span><i class="dot" style="background: var(--c-blue)"></i>Yangi:<?= $newTasks ?></span>
                        <span><i class="dot"
                                 style="background: var(--c-green)"></i>Bajarilgan: <?= $completedTasks ?></span>
                        <span><i class="dot"
                                 style="background: var(--c-orange)"></i>Kechiktirilgan: <?= $postponedTasks ?></span>
                    </div>
                </div>

                <!-- Rollar -->
                <div class="stat-card accent-purple">
                    <div class="stat-top">
                        <div class="stat-icon"><i class="bi bi-shield-check"></i></div>
                    </div>
                    <div class="stat-label">Rollar</div>
                    <div class="stat-value"><?= $totalRoles ?></div>
                    <div class="stat-foot">
                        <span><i class="dot" style="background: var(--c-purple)"></i>Eng ko'p: <?= $mostRoles ?></span>
                    </div>
                </div>

            </div>

            <!-- Tasklar holati + Rollar -->
            <div class="dash-row">

                <!-- Tasklar holati -->
                <div class="panel">
                    <div class="panel-head">
                        <h3>Tasklar holati</h3>
                        <small>Barcha loyihalar bo'yicha</small>
                    </div>

                    <div class="task-total">
                        <strong><?= $totalTasks ?></strong>
                        <span>ta task</span>
                    </div>

                    <!-- Kenglik = foiz (width: X%) -->
                    <div class="segment-bar">
                        <div style="width: 14%; background: var(--c-purple)"></div>
                        <div style="width: 18%; background: var(--c-blue)"></div>
                        <div style="width: 8%;  background: var(--c-orange)"></div>
                        <div style="width: 60%; background: var(--c-green)"></div>
                    </div>

                    <div class="task-status-grid">
                        <div class="task-status">
                            <div class="task-status-name"><i class="dot" style="background: var(--c-purple)"></i>Yangi
                            </div>
                            <div class="task-status-value"><?= $newTasks ?><small>  <?= $totalTasks > 0 ? round($newTasks * 100 / $totalTasks) : 0 ?>%</small></div>
                        </div>
                        <div class="task-status">
                            <div class="task-status-name"><i class="dot" style="background: var(--c-blue)"></i>Bajarilgan
                            </div>
                            <div class="task-status-value"><?= $completedTasks ?>
                                <small>  <?= $totalTasks > 0 ? round($completedTasks * 100 / $totalTasks) : 0 ?>%</small></div>
                        </div>
                        <div class="task-status">
                            <div class="task-status-name"><i class="dot" style="background: var(--c-orange)"></i>Kechiktirilgan
                            </div>
                            <div class="task-status-value"><?= $postponedTasks ?>
                                <small>  <?= $totalTasks > 0 ? round($postponedTasks * 100 / $totalTasks) : 0 ?>%</small></div>
                        </div>
                    </div>
                </div>

                <!-- Rollar bo'yicha foydalanuvchilar -->
                <div class="panel">
                    <div class="panel-head">
                        <h3>Rollar bo'yicha</h3>
                        <small><?= $totalUsers ?> foydalanuvchi</small>
                    </div>

                    <ul class="role-list">
                        <?php foreach ($userCountByRole as $role) : ?>
                            <?php $percent = $totalUsers > 0 ? round($role['user_count'] * 100 / $totalUsers) : 0; ?>
                            <li class="accent-blue">
                                <div class="role-row">
                                    <span class="role-name">
                                        <span class="role-avatar"><i class="bi bi-person"></i></span>
                                        <?= htmlspecialchars($role['role_name'] ?? 'Rolsiz') ?>
                                    </span>
                                    <span class="role-count"><?= $role['user_count'] ?></span>
                                </div>
                                <div class="mini-bar"><div style="width: <?= $percent ?>%"></div></div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

            </div>

            <!-- Loyihalar bo'yicha tasklar -->
            <div class="panel">
                <div class="panel-head">
                    <h3>Loyihalar bo'yicha tasklar</h3>
                    <small>Oxirgi 5 ta loyiha</small>
                </div>

                <div class="table-scroll">
                    <table class="dash-table">
                        <thead>
                        <tr>
                            <th>Loyiha nomi</th>
                            <th>Mas'ul</th>
                            <th>Jami tasklar</th>
                            <th>Yangi</th>
                            <th>Bajarilgan</th>
                            <th>Kechiktirilgan</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($projectStatByUser as $projectStat) : ?>
                            <tr>
                                <td class="project-name"><?= htmlspecialchars($projectStat['project_name']) ?></td>
                                <td><span class="user-chip"><span class="avatar"><?= htmlspecialchars(mb_strtoupper(mb_substr($projectStat['user_name'] ?? '-', 0, 1))) ?></span><?= htmlspecialchars($projectStat['user_name']) ?></span></td>
                                <td><?= htmlspecialchars($projectStat['task_count']) ?></td>
                                <td><?= htmlspecialchars($projectStat['new']) ?></td>
                                <td><?= htmlspecialchars($projectStat['completed']) ?></td>
                                <td><?= htmlspecialchars($projectStat['posponed']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>