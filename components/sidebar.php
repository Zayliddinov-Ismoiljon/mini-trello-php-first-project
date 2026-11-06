<?php

$currentPage = basename($_SERVER['SCRIPT_NAME']);

$menu = [
    [
        'title' => 'Bosh sahifa',
        'icon'  => 'bi-grid-1x2',
        'href'  => 'dashboard.php',
        'match' => ['dashboard.php'],
        'permission' => 'dashboard_admin'
    ],
    [
        'title' => 'Bosh sahifa',
        'icon'  => 'bi-grid-1x2',
        'href'  => 'dashboard-pm.php',
        'match' => ['dashboard-pm.php'],
        'permission' => 'dashboard_project_meneger'
    ],
    [
        'title' => 'Loyihalar',
        'icon'  => 'bi-folder2',
        'href'  => 'projects.php',
        'match' => ['projects.php'],
        'permission' => 'project_menu'
    ],
    [
        'title' => 'Vazifalar',
        'icon'  => 'bi-check2-square',
        'href'  => 'tasks.php',
        'match' => ['tasks.php'],
        'permission' => 'task_menu'
    ],
    [
        'title' => 'Foydalanuvchilar',
        'icon'  => 'bi-people',
        'href'  => 'users.php',
        'match' => ['users.php'],
        'permission' => 'users_menu'
    ],
    [
        'title' => 'Rollar',
        'icon'  => 'bi-shield-check',
        'href'  => 'roles.php',
        'match' => ['roles.php', 'role-view.php'],
        'permission' => 'roles_menu'
    ],
];

?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<style>
    :root {
        --s-w: 264px;
        --s-gap: 14px;
        --s-blue: #2563eb;
        --s-blue-soft: #eaf1ff;
        --s-text: #475569;
        --s-muted: #94a3b8;
        --s-border: #eef0f4;
        --s-input: #f4f6fb;
    }

    html.side-collapsed {
        --s-w: 76px;
    }

    .msb {
        position: fixed;
        top: var(--s-gap);
        left: var(--s-gap);
        bottom: var(--s-gap);
        width: var(--s-w);
        display: flex;
        flex-direction: column;
        background: #fff;
        border-radius: 20px;
        box-shadow: 0 6px 24px rgba(15, 23, 42, 0.08);
        font-family: "Montserrat", system-ui, sans-serif;
        overflow: hidden;
        z-index: 1000;
        transition: width 0.25s ease;
    }

    .main-content {
        margin-left: calc(var(--s-w) + var(--s-gap) * 2);
        padding: 15px 5px;
        transition: margin-left 0.25s ease;
    }

    .msb-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        padding: 18px 16px;
        border-bottom: 1px solid var(--s-border);
    }

    .msb-brand {
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 0;
        text-decoration: none;
    }

    .msb-logo {
        display: grid;
        place-items: center;
        flex-shrink: 0;
        width: 34px;
        height: 34px;
        border-radius: 10px;
        background: var(--s-blue);
        color: #fff;
        font-size: 18px;
    }

    .msb-name {
        color: #1e293b;
        font-size: 16px;
        font-weight: 700;
        white-space: nowrap;
    }

    .msb-toggle {
        display: grid;
        place-items: center;
        flex-shrink: 0;
        width: 32px;
        height: 32px;
        border: 0;
        border-radius: 8px;
        background: transparent;
        color: var(--s-muted);
        font-size: 18px;
        cursor: pointer;
        transition: background 0.15s, color 0.15s;
    }

    .msb-toggle:hover {
        background: var(--s-input);
        color: var(--s-text);
    }

    .msb-search {
        position: relative;
        margin: 14px 14px 8px;
    }

    .msb-search i {
        position: absolute;
        top: 50%;
        left: 12px;
        color: var(--s-muted);
        font-size: 14px;
        transform: translateY(-50%);
    }

    .msb-search input {
        width: 100%;
        height: 40px;
        padding: 0 12px 0 36px;
        border: 1px solid var(--s-border);
        border-radius: 12px;
        background: var(--s-input);
        color: var(--s-text);
        font-family: inherit;
        font-size: 13px;
        outline: none;
        transition: border-color 0.15s, background 0.15s;
    }

    .msb-search input:focus {
        border-color: var(--s-blue);
        background: #fff;
    }

    .msb-nav {
        display: flex;
        flex-direction: column;
        gap: 2px;
        flex: 1;
        padding: 6px 14px;
        overflow-y: auto;
        overflow-x: hidden;
    }

    .msb-link {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 11px 12px;
        border-radius: 12px;
        color: var(--s-text);
        font-size: 13.5px;
        font-weight: 500;
        text-decoration: none;
        white-space: nowrap;
        transition: background 0.15s, color 0.15s;
    }

    .msb-link i {
        flex-shrink: 0;
        font-size: 18px;
    }

    .msb-link:hover {
        background: var(--s-input);
        color: #1e293b;
    }

    .msb-link.active {
        background: var(--s-blue-soft);
        color: var(--s-blue);
        font-weight: 600;
    }

    .msb-empty {
        display: none;
        padding: 14px 12px;
        color: var(--s-muted);
        font-size: 13px;
    }

    .msb-foot {
        padding: 10px 14px 14px;
        border-top: 1px solid var(--s-border);
    }

    .msb-foot .msb-link:hover {
        background: #fef2f2;
        color: #dc2626;
    }

    html.side-collapsed .msb-hide {
        display: none;
    }

    html.side-collapsed .msb-head {
        flex-direction: column;
        padding: 14px 0;
    }

    html.side-collapsed .msb-link {
        justify-content: center;
        padding: 12px 0;
    }
</style>

<aside class="msb">

    <div class="msb-head">
        <a href="dashboard.php" class="msb-brand">
            <span class="msb-logo"><i class="bi bi-kanban-fill"></i></span>
            <span class="msb-name msb-hide">Mini Trello</span>
        </a>
        <button type="button" class="msb-toggle" id="msbToggle" aria-label="Menyuni yig'ish">
            <i class="bi bi-layout-sidebar-inset"></i>
        </button>
    </div>


    <nav class="msb-nav" id="msbNav">
        <?php foreach ($menu as $item) : ?>
            <?php if (!checkPermissions($item['permission'])) continue; ?>
            <?php $active = in_array($currentPage, $item['match'], true); ?>
            <a href="<?= htmlspecialchars($item['href']) ?>"
               class="msb-link<?= $active ? ' active' : '' ?>"
               title="<?= htmlspecialchars($item['title']) ?>"
                <?= $active ? 'aria-current="page"' : '' ?>>
                <i class="bi <?= htmlspecialchars($item['icon']) ?>"></i>
                <span class="msb-hide"><?= htmlspecialchars($item['title']) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="msb-foot">
        <a href="logout.php" class="msb-link" title="Tizimdan chiqish">
            <i class="bi bi-box-arrow-right"></i>
            <span class="msb-hide">Tizimdan chiqish</span>
        </a>
    </div>

</aside>

<script>
    (function () {
        var root = document.documentElement;
        var toggle = document.getElementById('msbToggle');

        try {
            if (localStorage.getItem('sideCollapsed') === '1') {
                root.classList.add('side-collapsed');
            }
        } catch (e) {}

        toggle.addEventListener('click', function () {
            var collapsed = root.classList.toggle('side-collapsed');
            try {
                localStorage.setItem('sideCollapsed', collapsed ? '1' : '0');
            } catch (e) {}
        });


    })();
</script>