<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class AlmacenImagenesTest extends TestCase
{
    public function testFirmaSigV4CoincideConVectorDeReferenciaDeAwsS3(): void
    {
        $hashCuerpo = hash('sha256', '');
        $resultado = AlmacenImagenes::calcularFirmaV4(
            'GET',
            '/test.txt',
            '',
            [
                'host' => 'examplebucket.s3.amazonaws.com',
                'range' => 'bytes=0-9',
                'x-amz-content-sha256' => $hashCuerpo,
                'x-amz-date' => '20130524T000000Z',
            ],
            $hashCuerpo,
            '20130524T000000Z',
            'us-east-1',
            's3',
            'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY'
        );

        self::assertSame(
            'f0e8bdb87c964420e857bd35b5d6ed310bd44f0170aba48dd91039c6036bdb41',
            $resultado['firma']
        );
        self::assertSame(
            'host;range;x-amz-content-sha256;x-amz-date',
            $resultado['encabezados_firmados']
        );
    }

    public function testFallaDeConfiguracionS3UsaRespaldoLocal(): void
    {
        $variables = [
            'ALMACEN_IMAGENES' => 's3',
            'S3_ENDPOINT' => 'https://s3.us-west-2.idrivee2.com',
            'S3_REGION' => 'us-west-2',
            'S3_BUCKET' => 'itvomarket-imagenes',
            'S3_KEY_ID' => '',
            'S3_SECRET' => '',
            'S3_URL_PUBLICA' => 'https://itvomarket-imagenes.s3.us-west-2.idrivee2.com',
        ];
        $valoresAnteriores = [];

        foreach ($variables as $nombre => $valor) {
            $anterior = getenv($nombre);
            $valoresAnteriores[$nombre] = $anterior === false ? null : $anterior;
            putenv($nombre . '=' . $valor);
        }

        $archivoTemporal = tempnam(sys_get_temp_dir(), 'itvo-imagen-');
        self::assertNotFalse($archivoTemporal);
        $imagenPrueba = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/l9sAAAAASUVORK5CYII=',
            true
        );
        self::assertNotFalse($imagenPrueba);
        file_put_contents($archivoTemporal, $imagenPrueba);

        try {
            $rutaPublica = AlmacenImagenes::guardar($archivoTemporal, 'respaldo.png');
            $rutaLocal = dirname(__DIR__) . '/public/' . $rutaPublica;

            self::assertStringStartsWith('assets/uploads/producto_', $rutaPublica);
            self::assertFileExists($rutaLocal);
            self::assertSame($imagenPrueba, file_get_contents($rutaLocal));
        } finally {
            if (isset($rutaLocal) && is_file($rutaLocal)) {
                unlink($rutaLocal);
            }
            if (is_file($archivoTemporal)) {
                unlink($archivoTemporal);
            }
            foreach ($valoresAnteriores as $nombre => $valorAnterior) {
                if ($valorAnterior === null) {
                    putenv($nombre);
                } else {
                    putenv($nombre . '=' . $valorAnterior);
                }
            }
        }
    }
}
