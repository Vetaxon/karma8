<?php

function checkEmail(string $email): int
{
    sleep(rand(1, 60));
    echo 'E-mail has been checked for: ' . $email . PHP_EOL;
    return rand(0, 1);
}

function sendEmail(string $from, string $to, string $text): bool
{
    sleep(rand(1, 10));
    echo sprintf('E-mail has been send to %s with text: %s', $to, $text) . PHP_EOL;
    return true;
}
