<?php

declare(strict_types=1);

require_once __DIR__ . '/database.php';

return [
    'APP_URL' => rtrim(obtenerValorEntorno('APP_URL', 'http://localhost:8000'), '/'),
    'MAIL_MODO' => strtolower(obtenerValorEntorno('MAIL_MODO', 'log')),
    'MAIL_HOST' => obtenerValorEntorno('MAIL_HOST'),
    'MAIL_PORT' => (int) obtenerValorEntorno('MAIL_PORT', '587'),
    'MAIL_USERNAME' => obtenerValorEntorno('MAIL_USERNAME'),
    'MAIL_PASSWORD' => obtenerValorEntorno('MAIL_PASSWORD'),
    'MAIL_FROM_NAME' => obtenerValorEntorno('MAIL_FROM_NAME', 'ITVOmarket'),
];
