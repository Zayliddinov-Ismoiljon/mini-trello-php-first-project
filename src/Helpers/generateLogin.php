<?php


function generatePassword(int $length = 10): string
{
    $chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789';
    $password = '';

    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }

    return $password;
}


function generateLogin(string $name): string
{
    $base = preg_replace('/[^a-z0-9]/', '', strtolower($name));
    $base = $base !== '' ? substr($base, 0, 10) : 'user';

    return $base . random_int(100, 9999);
}