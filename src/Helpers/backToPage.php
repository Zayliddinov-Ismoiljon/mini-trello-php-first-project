<?php
function backToPage(): void
{
    header("Location: " . basename($_SERVER['SCRIPT_NAME']));
    exit();
}