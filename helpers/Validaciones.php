<?php

declare(strict_types=1);

// Normaliza el correo y acepta solo direcciones válidas cuyo dominio sea exactamente gmail.com.
function normalizarCorreoGmail(string $correo): ?string
{
    $correoNormalizado = strtolower(trim($correo));

    if (
        strlen($correoNormalizado) > 254
        || filter_var($correoNormalizado, FILTER_VALIDATE_EMAIL) === false
        || preg_match('/\A[a-z0-9_%+-]+(?:\.[a-z0-9_%+-]+)*@gmail\.com\z/', $correoNormalizado) !== 1
    ) {
        return null;
    }

    return $correoNormalizado;
}

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
