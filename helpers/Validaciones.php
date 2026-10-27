<?php

declare(strict_types=1);

function validarContrasenaSegura(string $contrasena): string
{
    $longitud = function_exists('mb_strlen')
        ? mb_strlen($contrasena, 'UTF-8')
        : strlen($contrasena);
    $tieneLetra = preg_match('/\p{L}/u', $contrasena) === 1;
    $tieneNumero = preg_match('/[0-9]/', $contrasena) === 1;

    if ($longitud < 8 || !$tieneLetra || !$tieneNumero) {
        return 'La contraseña debe tener al menos 8 caracteres, una letra y un número.';
    }

    return '';
}
