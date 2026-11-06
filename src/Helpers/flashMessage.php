<?php
function flash(string $type, string $message, string $reopen = ''): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message, 'reopen' => $reopen];
}