<?php
require "../config/auth.php";
require_once "../config/database.php";
$pageTitle = "Mening dashboardim";

global $pdo;
global $currentUserId;

// requirePermission('dashboard_manager');   // ruxsat tizimingizdagi kod bilan oching

$uid = (int)$currentUserId;

// ===== 1. Mening loyihalarim =====
$statement = $pdo->prepare("
    SELECT COUNT(*)                     AS total,
           COALESCE(SUM(status = 0), 0) AS progress,
           COALESCE(SUM(status = 1), 0) AS finished
    FROM projects
    WHERE user_id = ?
");
$statement->execute([$uid]);
$projectStats = $statement->fetch();

// ===== 2. Loyihalarimdagi tasklar =====
$statement = $pdo->prepare("
    SELECT COUNT(tasks.id)                                                  AS total,
           COALESCE(SUM(tasks.status = 1), 0)                               AS new_count,
           COALESCE(SUM(tasks.status = 2), 0)                               AS completed,
           COALESCE(SUM(tasks.status = 3), 0)                               AS postponed,
           COALESCE(SUM(tasks.deadline < CURDATE() AND tasks.status <> 2), 0) AS overdue,
           COUNT(DISTINCT tasks.user_id)                                    AS team
    FROM tasks
    JOIN projects ON projects.id = tasks.project_id
    WHERE projects.user_id = ?
");
$statement->execute([$uid]);
$taskStats = $statement->fetch();

$totalTasks = (int)$taskStats['total'];

function percent(int $part, int $total): int
{
    return $total > 0 ? (int)round($part * 100 / $total) : 0;
}

$newPercent       = percent((int)$taskStats['new_count'], $totalTasks);
$completedPercent = percent((int)$taskStats['completed'], $totalTasks);
$postponedPercent = percent((int)$taskStats['postponed'], $totalTasks);

// ===== 3. Loyihalarim bo'yicha tasklar =====
$statement = $pdo->prepare("
    SELECT projects.id,
           projects.project_name,
           projects.status,
           COUNT(tasks.id)                             AS task_count,
           COUNT(CASE WHEN tasks.status = 1 THEN 1 END) AS new_count,
           COUNT(CASE WHEN tasks.status = 2 THEN 1 END) AS completed,
           COUNT(CASE WHEN tasks.status = 3 THEN 1 END) AS postponed
    FROM projects
    LEFT JOIN tasks ON tasks.project_id = projects.id
    WHERE projects.user_id = ?
    GROUP BY projects.id, projects.project_name, projects.status
    ORDER BY projects.id DESC
    LIMIT 8
");
$statement->execute([$uid]);
$myProjects = $statement->fetchAll();

// ===== 4. Yaqin 7 kundagi muddatlar =====
$statement = $pdo->prepare("
    SELECT tasks.id, tasks.task_name, tasks.deadline, tasks.status,
           projects.project_name, users.user_name
    FROM tasks
    JOIN projects ON projects.id = tasks.project_id
    LEFT JOIN users ON users.id = tasks.user_id
    WHERE projects.user_id = ?
      AND tasks.status <> 2
      AND tasks.deadline BETWEEN CURDATE() AND CURDATE() + INTERVAL 7 DAY
    ORDER BY tasks.deadline
    LIMIT 6
");
$statement->execute([$uid]);
$upcomingTasks = $statement->fetchAll();

// ===== 5. Jamoa: eng faol xodimlar =====
$statement = $pdo->prepare("
    SELECT users.user_name,
           COUNT(tasks.id)                             AS total,
           COUNT(CASE WHEN tasks.status = 2 THEN 1 END) AS completed
    FROM tasks
    JOIN projects ON projects.id = tasks.project_id
    JOIN users ON users.id = tasks.user_id
    WHERE projects.user_id = ?
    GROUP BY users.id, users.user_name
    ORDER BY completed DESC, total DESC
    LIMIT 5
");
$statement->execute([$uid]);
$topMembers = $statement->fetchAll();

function initial(?string $name): string
{
    return mb_strtoupper(mb_substr($name ?? '-', 0, 1));
}

function daysLeftLabel(string $deadline): string
{
    $days = (int)(new DateTime('today'))->diff(new DateTime($deadline))->format('%r%a');

    return match (true) {
        $days === 0 => 'Bugun',
        $days === 1 => 'Ertaga',
        default     => $days . ' kun qoldi',
    };
}

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

?>
<!doctype html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mening dashboardim</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">

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

        .dash {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        /* ===== Salomlashish ===== */
        .dash-hello {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            padding: 22px 24px;
            border-radius: 20px;
            background: linear-gradient(120deg, #2563eb, #4f46e5);
            color: #fff;
        }

        .dash-hello h2 {
            margin: 0 0 4px;
            font-size: 20px;
            font-weight: 700;
        }

        .dash-hello p {
            margin: 0;
            opacity: 0.85;
            font-size: 13.5px;
        }

        .hello-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.16);
            font-size: 13px;
            font-weight: 600;
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
            flex-wrap: wrap;
            gap: 6px 14px;
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

        .accent-green  { --accent: var(--c-green);  --accent-bg: var(--c-green-bg); }
        .accent-orange { --accent: var(--c-orange); --accent-bg: var(--c-orange-bg); }
        .accent-blue   { --accent: var(--c-blue);   --accent-bg: var(--c-blue-bg); }
        .accent-purple { --accent: var(--c-purple); --accent-bg: var(--c-purple-bg); }
        .accent-red    { --accent: var(--c-red);    --accent-bg: var(--c-red-bg); }

        .stat-card.accent-red .stat-value {
            color: var(--c-red);
        }

        /* ===== Panel ===== */
        .dash-row {
            display: grid;
            grid-template-columns: minmax(0, 3fr) minmax(0, 2fr);
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
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0;
            color: var(--s-dark);
            font-size: 16px;
            font-weight: 700;
        }

        .panel-head h3 i {
            color: var(--s-blue);
        }

        .panel-head small {
            color: var(--s-muted);
            font-size: 12.5px;
            font-weight: 600;
        }

        .panel-head a {
            color: var(--s-blue);
            font-size: 12.5px;
            font-weight: 600;
            text-decoration: none;
        }

        .empty {
            padding: 28px 12px;
            color: var(--s-muted);
            font-size: 13px;
            text-align: center;
        }

        .empty i {
            display: block;
            margin-bottom: 8px;
            font-size: 26px;
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
            border-radius: 6px;
            background: var(--s-input);
            overflow: hidden;
        }

        .segment-bar div {
            border-radius: 6px;
        }

        .task-status-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
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

        /* ===== Yaqin muddatlar ===== */
        .deadline-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .deadline-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            border-radius: 14px;
            background: var(--s-input);
        }

        .deadline-date {
            display: grid;
            place-items: center;
            flex-shrink: 0;
            width: 46px;
            height: 46px;
            border-radius: 12px;
            background: #fff;
            color: var(--s-dark);
            line-height: 1.1;
            text-align: center;
        }

        .deadline-date b {
            font-size: 16px;
            font-weight: 800;
        }

        .deadline-date small {
            color: var(--s-muted);
            font-size: 10.5px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .deadline-info {
            flex: 1;
            min-width: 0;
        }

        .deadline-info strong {
            display: block;
            overflow: hidden;
            color: var(--s-dark);
            font-size: 13px;
            font-weight: 700;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .deadline-info span {
            display: block;
            overflow: hidden;
            color: var(--s-muted);
            font-size: 12px;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .badge-soft {
            flex-shrink: 0;
            padding: 4px 10px;
            border-radius: 999px;
            background: var(--c-orange-bg);
            color: var(--c-orange);
            font-size: 11.5px;
            font-weight: 700;
            white-space: nowrap;
        }

        .badge-soft.urgent {
            background: var(--c-red-bg);
            color: var(--c-red);
        }

        /* ===== Jadval ===== */
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

        .status-pill {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }

        .status-pill.progress {
            background: var(--c-blue-bg);
            color: var(--c-blue);
        }

        .status-pill.finished {
            background: var(--c-green-bg);
            color: var(--c-green);
        }

        .progress-cell {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 150px;
        }

        .mini-bar {
            flex: 1;
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

        .progress-cell b {
            width: 38px;
            color: var(--s-dark);
            font-size: 12.5px;
            text-align: right;
        }

        .table-scroll {
            overflow-x: auto;
        }

        /* ===== Jamoa ===== */
        .member-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .member-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
            font-size: 13px;
        }

        .avatar {
            display: grid;
            place-items: center;
            flex-shrink: 0;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--c-blue-bg);
            color: var(--c-blue);
            font-size: 12px;
            font-weight: 700;
        }

        .member-name {
            flex: 1;
            min-width: 0;
            overflow: hidden;
            color: var(--s-dark);
            font-weight: 600;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .member-count {
            color: var(--s-text);
            font-size: 12.5px;
            font-weight: 700;
            white-space: nowrap;
        }

        .member-count small {
            color: var(--s-muted);
            font-weight: 600;
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
    </style>
</head>
<body>

<?php if ($flash) : ?>
    <div class="flash-alert alert alert-<?= htmlspecialchars($flash['type']) ?> alert-dismissible fade show shadow" role="alert">
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

                <div class="stat-card accent-green">
                    <div class="stat-top">
                        <div class="stat-icon"><i class="bi bi-kanban"></i></div>
                    </div>
                    <div class="stat-label">Mening loyihalarim</div>
                    <div class="stat-value"><?= (int)$projectStats['total'] ?></div>
                    <div class="stat-foot">
                        <span><i class="dot" style="background: var(--c-blue)"></i>Jarayonda: <?= (int)$projectStats['progress'] ?></span>
                        <span><i class="dot" style="background: var(--c-green)"></i>Yakunlangan: <?= (int)$projectStats['finished'] ?></span>
                    </div>
                </div>

                <div class="stat-card accent-orange">
                    <div class="stat-top">
                        <div class="stat-icon"><i class="bi bi-list-check"></i></div>
                    </div>
                    <div class="stat-label">Vazifalar</div>
                    <div class="stat-value"><?= $totalTasks ?></div>
                    <div class="stat-foot">
                        <span><i class="dot" style="background: var(--c-purple)"></i>Yangi: <?= (int)$taskStats['new_count'] ?></span>
                        <span><i class="dot" style="background: var(--c-green)"></i>Bajarilgan: <?= (int)$taskStats['completed'] ?></span>
                    </div>
                </div>

                <div class="stat-card accent-red">
                    <div class="stat-top">
                        <div class="stat-icon"><i class="bi bi-alarm"></i></div>
                    </div>
                    <div class="stat-label">Muddati o'tgan</div>
                    <div class="stat-value"><?= (int)$taskStats['overdue'] ?></div>
                    <div class="stat-foot">
                        <span><i class="dot" style="background: var(--c-orange)"></i>Kechiktirilgan: <?= (int)$taskStats['postponed'] ?></span>
                    </div>
                </div>

                <div class="stat-card accent-purple">
                    <div class="stat-top">
                        <div class="stat-icon"><i class="bi bi-people"></i></div>
                    </div>
                    <div class="stat-label">Jamoam</div>
                    <div class="stat-value"><?= (int)$taskStats['team'] ?></div>
                    <div class="stat-foot">
                        <span><i class="dot" style="background: var(--c-purple)"></i>Loyihalarimda ishlayotgan xodimlar</span>
                    </div>
                </div>

            </div>

            <!-- Tasklar holati + Yaqin muddatlar -->
            <div class="dash-row">

                <div class="panel">
                    <div class="panel-head">
                        <h3><i class="bi bi-pie-chart"></i>Vazifalar holati</h3>
                        <small>Mening loyihalarim bo'yicha</small>
                    </div>

                    <div class="task-total">
                        <strong><?= $totalTasks ?></strong>
                        <span>ta vazifa</span>
                    </div>

                    <div class="segment-bar">
                        <div style="width: <?= $newPercent ?>%; background: var(--c-purple)"></div>
                        <div style="width: <?= $completedPercent ?>%; background: var(--c-green)"></div>
                        <div style="width: <?= $postponedPercent ?>%; background: var(--c-orange)"></div>
                    </div>

                    <div class="task-status-grid">
                        <div class="task-status">
                            <div class="task-status-name"><i class="dot" style="background: var(--c-purple)"></i>Yangi</div>
                            <div class="task-status-value"><?= (int)$taskStats['new_count'] ?><small><?= $newPercent ?>%</small></div>
                        </div>
                        <div class="task-status">
                            <div class="task-status-name"><i class="dot" style="background: var(--c-green)"></i>Bajarilgan</div>
                            <div class="task-status-value"><?= (int)$taskStats['completed'] ?><small><?= $completedPercent ?>%</small></div>
                        </div>
                        <div class="task-status">
                            <div class="task-status-name"><i class="dot" style="background: var(--c-orange)"></i>Kechiktirilgan</div>
                            <div class="task-status-value"><?= (int)$taskStats['postponed'] ?><small><?= $postponedPercent ?>%</small></div>
                        </div>
                    </div>
                </div>

                <div class="panel">
                    <div class="panel-head">
                        <h3><i class="bi bi-calendar-event"></i>Yaqin muddatlar</h3>
                        <small>Keyingi 7 kun</small>
                    </div>

                    <?php if ($upcomingTasks) : ?>
                        <ul class="deadline-list">
                            <?php foreach ($upcomingTasks as $task) : ?>
                                <?php $date = new DateTime($task['deadline']); ?>
                                <?php $label = daysLeftLabel($task['deadline']); ?>
                                <li class="deadline-item">
                                    <div class="deadline-date">
                                        <div>
                                            <b><?= $date->format('d') ?></b><br>
                                            <small><?= $date->format('m.Y') ?></small>
                                        </div>
                                    </div>
                                    <div class="deadline-info">
                                        <strong><?= htmlspecialchars($task['task_name']) ?></strong>
                                        <span><?= htmlspecialchars($task['project_name']) ?> · <?= htmlspecialchars($task['user_name'] ?? '-') ?></span>
                                    </div>
                                    <span class="badge-soft<?= in_array($label, ['Bugun', 'Ertaga'], true) ? ' urgent' : '' ?>">
                                        <?= $label ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else : ?>
                        <div class="empty">
                            <i class="bi bi-cup-hot"></i>
                            Keyingi 7 kunda muddati tugaydigan vazifa yo'q
                        </div>
                    <?php endif; ?>
                </div>

            </div>

            <!-- Loyihalarim + Jamoa -->
            <div class="dash-row">

                <div class="panel">
                    <div class="panel-head">
                        <h3><i class="bi bi-folder2-open"></i>Mening loyihalarim</h3>
                        <a href="projects.php">Barchasi <i class="bi bi-arrow-right"></i></a>
                    </div>

                    <?php if ($myProjects) : ?>
                        <div class="table-scroll">
                            <table class="dash-table">
                                <thead>
                                <tr>
                                    <th>Loyiha</th>
                                    <th>Holati</th>
                                    <th>Vazifalar</th>
                                    <th>Bajarilish</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($myProjects as $project) : ?>
                                    <?php
                                    $projectPercent = percent((int)$project['completed'], (int)$project['task_count']);
                                    $barClass = $projectPercent >= 70 ? 'accent-green' : ($projectPercent >= 40 ? 'accent-blue' : 'accent-orange');
                                    ?>
                                    <tr>
                                        <td class="project-name"><?= htmlspecialchars($project['project_name']) ?></td>
                                        <td>
                                            <?php if ((int)$project['status'] === 1) : ?>
                                                <span class="status-pill finished">Yakunlangan</span>
                                            <?php else : ?>
                                                <span class="status-pill progress">Jarayonda</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= (int)$project['completed'] ?> / <?= (int)$project['task_count'] ?></td>
                                        <td class="<?= $barClass ?>">
                                            <div class="progress-cell">
                                                <div class="mini-bar"><div style="width: <?= $projectPercent ?>%"></div></div>
                                                <b><?= $projectPercent ?>%</b>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else : ?>
                        <div class="empty">
                            <i class="bi bi-folder-x"></i>
                            Sizga hali loyiha biriktirilmagan
                        </div>
                    <?php endif; ?>
                </div>

                <div class="panel">
                    <div class="panel-head">
                        <h3><i class="bi bi-trophy"></i>Eng faol xodimlar</h3>
                        <small>Bajarilgan vazifalar</small>
                    </div>

                    <?php if ($topMembers) : ?>
                        <ul class="member-list">
                            <?php foreach ($topMembers as $member) : ?>
                                <?php $memberPercent = percent((int)$member['completed'], (int)$member['total']); ?>
                                <li class="accent-green">
                                    <div class="member-row">
                                        <span class="avatar"><?= htmlspecialchars(initial($member['user_name'])) ?></span>
                                        <span class="member-name"><?= htmlspecialchars($member['user_name']) ?></span>
                                        <span class="member-count">
                                            <?= (int)$member['completed'] ?><small> / <?= (int)$member['total'] ?></small>
                                        </span>
                                    </div>
                                    <div class="mini-bar"><div style="width: <?= $memberPercent ?>%"></div></div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else : ?>
                        <div class="empty">
                            <i class="bi bi-people"></i>
                            Loyihalaringizda hali vazifa yo'q
                        </div>
                    <?php endif; ?>
                </div>

            </div>

        </div>
    </main>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>