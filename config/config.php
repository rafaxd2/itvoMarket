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
    'ALMACEN_IMAGENES' => strtolower(obtenerValorEntorno('ALMACEN_IMAGENES', 'local')),
    'S3_ENDPOINT' => rtrim(obtenerValorEntorno('S3_ENDPOINT'), '/'),
    'S3_REGION' => obtenerValorEntorno('S3_REGION'),
    'S3_BUCKET' => obtenerValorEntorno('S3_BUCKET'),
    'S3_KEY_ID' => obtenerValorEntorno('S3_KEY_ID'),
    'S3_SECRET' => obtenerValorEntorno('S3_SECRET'),
    'S3_URL_PUBLICA' => rtrim(obtenerValorEntorno('S3_URL_PUBLICA'), '/'),
];
