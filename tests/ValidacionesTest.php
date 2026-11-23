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

    public function testValidaNombreDeProductoSinCambiarLaReglaDeObligatoriedad(): void
    {
        self::assertSame('', validarNombreProducto('Producto válido'));
        self::assertSame('El nombre del producto es obligatorio.', validarNombreProducto('   '));
    }

    public function testValidaPrecioPositivoFinitoDentroDelMaximoYConDosDecimales(): void
    {
        self::assertSame('', validarPrecioProducto('12.50'));
        self::assertSame('', validarPrecioProducto('0.01'));
        self::assertSame('', validarPrecioProducto('9999.99'));
        self::assertSame('El precio debe ser un número positivo.', validarPrecioProducto('0'));
        self::assertSame('El precio debe ser un número positivo.', validarPrecioProducto('no es número'));
        self::assertSame('El precio debe ser un número positivo.', validarPrecioProducto('1e9999'));
        self::assertSame('El precio debe ser un número positivo.', validarPrecioProducto('10000'));
        self::assertSame('El precio debe ser un número positivo.', validarPrecioProducto('12.345'));
    }

    public function testValidaFormatoYEstadoDeLaImagenDeProducto(): void
    {
        self::assertSame('', validarImagenProducto([
            'error' => UPLOAD_ERR_OK,
            'name' => 'producto.JPG',
        ]));
        self::assertSame(
            'Debes seleccionar una imagen en formato jpg o png.',
            validarImagenProducto(null)
        );
        self::assertSame(
            'Debes seleccionar una imagen en formato jpg o png.',
            validarImagenProducto(['error' => UPLOAD_ERR_NO_FILE, 'name' => ''])
        );
        self::assertSame(
            'La imagen debe tener formato jpg o png.',
            validarImagenProducto(['error' => UPLOAD_ERR_OK, 'name' => 'producto.gif'])
        );
    }
}
