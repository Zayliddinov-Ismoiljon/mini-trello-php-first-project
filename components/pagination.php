<?php

if (!function_exists('paginate')) {

    function paginate(int $total, int $perPage = 10, string $param = 'page'): array
    {
        $perPage = max(1, $perPage);
        $totalPages = max(1, (int)ceil($total / $perPage));

        $page = (int)($_GET[$param] ?? 1);
        $page = min(max(1, $page), $totalPages);

        $offset = ($page - 1) * $perPage;

        return [
            'total'      => $total,
            'perPage'    => $perPage,
            'page'       => $page,
            'totalPages' => $totalPages,
            'offset'     => $offset,
            'from'       => $total > 0 ? $offset + 1 : 0,
            'to'         => min($offset + $perPage, $total),
            'param'      => $param,
        ];
    }

    function paginationUrl(array $pager, int $page): string
    {
        $query = $_GET;
        $query[$pager['param']] = $page;

        return '?' . http_build_query($query);
    }

    /**
     * Ko'rsatiladigan sahifa raqamlari: 1 ... 4 5 [6] 7 8 ... 50
     */
    function paginationPages(int $current, int $total, int $around = 2): array
    {
        $pages = [];

        for ($i = 1; $i <= $total; $i++) {
            if ($i === 1 || $i === $total || abs($i - $current) <= $around) {
                $pages[] = $i;
            } elseif (end($pages) !== '...') {
                $pages[] = '...';
            }
        }

        return $pages;
    }

    function renderPagination(array $pager): void
    {
        static $styled = false;

        if (!$styled) {
            $styled = true;
            ?>
            <style>
                .pager {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    flex-wrap: wrap;
                    gap: 12px;
                    margin-top: 20px;
                    padding-top: 16px;
                    border-top: 1px solid var(--s-border, #eef0f4);
                    font-family: "Montserrat", system-ui, sans-serif;
                }

                .pager-info {
                    color: var(--s-muted, #94a3b8);
                    font-size: 13px;
                    font-weight: 500;
                }

                .pager-info b {
                    color: #1e293b;
                    font-weight: 700;
                }

                .pager-list {
                    display: flex;
                    align-items: center;
                    gap: 6px;
                    margin: 0;
                    padding: 0;
                    list-style: none;
                }

                .pager-link {
                    display: grid;
                    place-items: center;
                    min-width: 38px;
                    height: 38px;
                    padding: 0 10px;
                    border: 1px solid var(--s-border, #eef0f4);
                    border-radius: 10px;
                    background: #fff;
                    color: var(--s-text, #475569);
                    font-size: 13px;
                    font-weight: 600;
                    text-decoration: none;
                    transition: background 0.15s, border-color 0.15s, color 0.15s;
                }

                a.pager-link:hover {
                    border-color: var(--s-blue, #2563eb);
                    color: var(--s-blue, #2563eb);
                }

                .pager-link.active {
                    border-color: var(--s-blue, #2563eb);
                    background: var(--s-blue, #2563eb);
                    color: #fff;
                }

                .pager-link.disabled {
                    opacity: 0.45;
                    pointer-events: none;
                }

                .pager-dots {
                    min-width: 24px;
                    color: var(--s-muted, #94a3b8);
                    text-align: center;
                }
            </style>
            <?php
        }

        if ($pager['total'] === 0) {
            return;
        }

        $page = $pager['page'];
        $last = $pager['totalPages'];
        ?>
        <nav class="pager" aria-label="Sahifalar">
            <div class="pager-info">
                <b><?= $pager['from'] ?>–<?= $pager['to'] ?></b> / <?= $pager['total'] ?> ta yozuv
            </div>

            <?php if ($last > 1) : ?>
                <ul class="pager-list">
                    <li>
                        <a class="pager-link<?= $page <= 1 ? ' disabled' : '' ?>"
                           href="<?= htmlspecialchars(paginationUrl($pager, $page - 1)) ?>"
                           aria-label="Oldingi">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>

                    <?php foreach (paginationPages($page, $last) as $item) : ?>
                        <li>
                            <?php if ($item === '...') : ?>
                                <span class="pager-dots">…</span>
                            <?php elseif ($item === $page) : ?>
                                <span class="pager-link active" aria-current="page"><?= $item ?></span>
                            <?php else : ?>
                                <a class="pager-link" href="<?= htmlspecialchars(paginationUrl($pager, $item)) ?>"><?= $item ?></a>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>

                    <li>
                        <a class="pager-link<?= $page >= $last ? ' disabled' : '' ?>"
                           href="<?= htmlspecialchars(paginationUrl($pager, $page + 1)) ?>"
                           aria-label="Keyingi">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            <?php endif; ?>
        </nav>
        <?php
    }
}