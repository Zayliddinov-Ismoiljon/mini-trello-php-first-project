<?php

$pageTitle = $pageTitle ?? 'Bosh sahifa';

?>
<style>
    .hdr {
        position: sticky
        z-index: 900;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        margin-bottom: 24px;
        padding: 12px 20px;
        background: #fff;
        border-radius: 20px;
        box-shadow: 0 6px 24px rgba(15, 23, 42, 0.08);
        font-family: "Montserrat", system-ui, sans-serif;
    }

    .hdr-title h1 {
        margin: 0;
        color: #1e293b;
        font-size: 18px;
        font-weight: 700;
        line-height: 1.3;
    }

    .hdr-title span {
        color: var(--s-muted);
        font-size: 12px;
    }

    .hdr-right {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .hdr-icon {
        position: relative;
        display: grid;
        place-items: center;
        width: 40px;
        height: 40px;
        border: 1px solid var(--s-border);
        border-radius: 12px;
        background: var(--s-input);
        color: var(--s-text);
        font-size: 18px;
        cursor: pointer;
        transition: background 0.15s, color 0.15s;
    }

    .hdr-icon:hover {
        background: var(--s-blue-soft);
        color: var(--s-blue);
    }

    .hdr-dot {
        position: absolute;
        top: 9px;
        right: 10px;
        width: 8px;
        height: 8px;
        border: 2px solid #fff;
        border-radius: 50%;
        background: #ef4444;
    }

    .hdr-user {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 5px 10px 5px 5px;
        border: 1px solid var(--s-border);
        border-radius: 14px;
        background: #fff;
        cursor: pointer;
        transition: background 0.15s;
    }

    .hdr-user:hover {
        background: var(--s-input);
    }

    .hdr-avatar {
        display: grid;
        place-items: center;
        flex-shrink: 0;
        width: 34px;
        height: 34px;
        border-radius: 10px;
        background: var(--s-blue);
        color: #fff;
        font-size: 14px;
        font-weight: 700;
    }

    .hdr-user-info {
        display: flex;
        flex-direction: column;
        text-align: left;
        line-height: 1.25;
    }

    .hdr-user-info strong {
        color: #1e293b;
        font-size: 13px;
        font-weight: 600;
    }

    .hdr-user-info span {
        color: var(--s-muted);
        font-size: 11px;
    }

    .hdr-user > i {
        color: var(--s-muted);
        font-size: 12px;
    }

    .hdr-menu {
        min-width: 200px;
        margin-top: 10px !important;
        padding: 8px;
        border: 1px solid var(--s-border);
        border-radius: 14px;
        box-shadow: 0 10px 30px rgba(15, 23, 42, 0.12);
        font-family: "Montserrat", system-ui, sans-serif;
    }

    .hdr-menu .dropdown-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 9px 12px;
        border-radius: 10px;
        color: var(--s-text);
        font-size: 13px;
        font-weight: 500;
    }

    .hdr-menu .dropdown-item:hover {
        background: var(--s-input);
        color: #1e293b;
    }

    .hdr-menu .dropdown-item.danger:hover {
        background: #fef2f2;
        color: #dc2626;
    }

    .hdr-menu .dropdown-divider {
        margin: 6px 0;
        border-color: var(--s-border);
    }

    @media (max-width: 576px) {
        .hdr-user-info,
        .hdr-user > i {
            display: none;
        }
    }
</style>

<header class="hdr">

    <div class="hdr-title">
        <h1><?= htmlspecialchars($pageTitle) ?></h1>
        <span>Xush kelibsiz, <?= htmlspecialchars($_SESSION['user_name']) ?></span>


    </div>

    <div class="hdr-right">

        <button type="button" class="hdr-icon" aria-label="Bildirishnomalar">
            <i class="bi bi-bell"></i>
            <span class="hdr-dot"></span>
        </button>

        <div class="dropdown">
            <div class="hdr-user" data-bs-toggle="dropdown" aria-expanded="false" role="button">
                <div class="hdr-avatar"><i class="bi bi-person-fill"></i></div>
                <div class="hdr-user-info">
                    <strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong>
                    <span><?= htmlspecialchars($_SESSION['role_name']) ?></span>
                </div>
<!--                <i class="bi bi-chevron-down"></i>-->
            </div>

<!--            <ul class="dropdown-menu dropdown-menu-end hdr-menu">-->
<!--                <li>-->
<!--                    <a class="dropdown-item" href="profile.php">-->
<!--                        <i class="bi bi-person"></i>Profil-->
<!--                    </a>-->
<!--                </li>-->
<!--                <li>-->
<!--                    <a class="dropdown-item" href="settings.php">-->
<!--                        <i class="bi bi-gear"></i>Sozlamalar-->
<!--                    </a>-->
<!--                </li>-->
<!--                <li><hr class="dropdown-divider"></li>-->
<!--                <li>-->
<!--                    <a class="dropdown-item danger" href="logout.php">-->
<!--                        <i class="bi bi-box-arrow-right"></i>Tizimdan chiqish-->
<!--                    </a>-->
<!--                </li>-->
<!--            </ul>-->
        </div>

    </div>

</header>