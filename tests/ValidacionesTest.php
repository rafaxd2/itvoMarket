<?php

declare(strict_types=1);

require_once __DIR__ . '/../helpers/Validaciones.php';

final class ValidacionesTest extends ModeloTestCase
{
    public function testNormalizaYValidaCorreosGmailPermitidos(): void
    {
        self::assertSame('a@gmail.com', normalizarCorreoGmail('a@gmail.com'));
        self::assertSame('a@gmail.com', normalizarCorreoGmail('A@Gmail.com '));
    }

    public function testRechazaCorreosGmailConFormatoInvalido(): void
    {
        $correosInvalidos = [
            'a@gmail.com.xd',
            'a@gmail.com.',
            'a@gmail',
            '@gmail.com',
            'a b@gmail.com',
            'a@outlook.com',
            '',
            str_repeat('a', 245) . '@gmail.com',
        ];

        foreach ($correosInvalidos as $correo) {
            self::assertNull(normalizarCorreoGmail($correo), 'Se aceptó el correo inválido: ' . $correo);
        }
    }
}
