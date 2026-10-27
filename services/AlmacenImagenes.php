<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

// Administra el almacenamiento de imágenes local y compatible con S3 sin dependencias externas.
final class AlmacenImagenes
{
    // Guarda la imagen en S3 cuando está configurado y usa el almacenamiento local ante cualquier fallo.
    public static function guardar(string $rutaTemporal, string $nombreOriginal): string
    {
        $extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));

        if (!in_array($extension, ['jpg', 'jpeg', 'png'], true) || !is_file($rutaTemporal)) {
            error_log('No fue posible almacenar la imagen: archivo o extensión inválidos.');
            return '';
        }

        try {
            $configuracion = self::obtenerConfiguracion();
            $nombreUnico = 'producto_' . bin2hex(random_bytes(16)) . '.' . $extension;
            $tipoMimeValidado = $extension === 'png' ? 'image/png' : 'image/jpeg';

            if ($configuracion['ALMACEN_IMAGENES'] === 's3') {
                try {
                    self::subirObjetoS3(
                        $rutaTemporal,
                        'productos/' . $nombreUnico,
                        $tipoMimeValidado,
                        $configuracion
                    );
                    return $nombreUnico;
                } catch (Throwable $excepcion) {
                    error_log('Falló la carga de imagen a S3; se usará almacenamiento local.');
                }
            }

            return self::guardarLocal($rutaTemporal, $nombreUnico);
        } catch (Throwable $excepcion) {
            error_log('No fue posible almacenar la imagen del producto; se continuará sin imagen.');
            return '';
        }
    }

    // Guarda una imagen local con nombre seguro y devuelve la ruta relativa pública.
    public static function guardarLocal(
        string $rutaTemporal,
        string $nombreArchivo,
        ?string $directorioDestino = null
    ): string {
        $nombreSeguro = basename($nombreArchivo);
        $extension = strtolower(pathinfo($nombreSeguro, PATHINFO_EXTENSION));

        if (!in_array($extension, ['jpg', 'jpeg', 'png'], true) || !is_file($rutaTemporal)) {
            throw new RuntimeException('El archivo local no es válido.');
        }

        $directorio = $directorioDestino ?? __DIR__ . '/../public/assets/uploads';
        if (!is_dir($directorio) && !@mkdir($directorio, 0775, true) && !is_dir($directorio)) {
            throw new RuntimeException('No fue posible preparar el almacenamiento local.');
        }

        $rutaDestino = rtrim($directorio, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $nombreSeguro;
        if (!@move_uploaded_file($rutaTemporal, $rutaDestino) && !@copy($rutaTemporal, $rutaDestino)) {
            throw new RuntimeException('No fue posible guardar la imagen en almacenamiento local.');
        }

        return 'assets/uploads/' . $nombreSeguro;
    }

    // Sube un objeto privado con PutObject usando la firma AWS SigV4.
    public static function subirObjetoS3(
        string $rutaArchivo,
        string $clave,
        string $tipoMime,
        ?array $configuracion = null
    ): void {
        $configuracion = $configuracion ?? self::obtenerConfiguracion();
        self::validarConfiguracionS3($configuracion);

        if (!is_file($rutaArchivo)) {
            throw new RuntimeException('No se encontró el archivo para subir.');
        }

        if (!in_array($tipoMime, ['image/jpeg', 'image/png'], true)) {
            throw new RuntimeException('El tipo de imagen no está permitido para S3.');
        }

        $cuerpo = @file_get_contents($rutaArchivo);
        if ($cuerpo === false) {
            throw new RuntimeException('No fue posible leer el archivo para subir.');
        }

        $resultado = self::enviarSolicitudS3(
            'PUT',
            $clave,
            '',
            ['content-type' => $tipoMime],
            $cuerpo,
            $configuracion
        );

        if ($resultado['codigo'] < 200 || $resultado['codigo'] >= 300) {
            throw new RuntimeException(self::mensajeErrorHttp('PutObject', $resultado));
        }

    }

    // Recupera un objeto privado del directorio productos mediante GetObject firmado.
    public static function obtenerImagenS3(string $claveArchivo, ?array $configuracion = null): array
    {
        if (!self::esClaveProductoSegura($claveArchivo)) {
            throw new InvalidArgumentException('La clave de imagen no es válida.');
        }

        $configuracion = $configuracion ?? self::obtenerConfiguracion();
        $resultado = self::enviarSolicitudS3(
            'GET',
            'productos/' . $claveArchivo,
            '',
            [],
            '',
            $configuracion
        );

        if ($resultado['codigo'] !== 200) {
            throw new RuntimeException(self::mensajeErrorHttp('GetObject', $resultado));
        }

        $tipoContenido = $resultado['encabezados']['content-type'] ?? '';
        if (!in_array(strtolower(trim(explode(';', $tipoContenido)[0])), ['image/jpeg', 'image/png'], true)) {
            throw new RuntimeException('El objeto S3 no tiene un tipo de imagen permitido.');
        }

        $tipoContenidoCanonico = strtolower(trim(explode(';', $tipoContenido)[0]));

        return [
            'contenido' => $resultado['respuesta'],
            'tipo_contenido' => $tipoContenidoCanonico,
        ];
    }

    // Valida que la clave corresponda a un archivo de producto sin rutas ni segmentos relativos.
    public static function esClaveProductoSegura(string $claveArchivo): bool
    {
        return strlen($claveArchivo) <= 255
            && !str_contains($claveArchivo, '..')
            && preg_match('/\Aproducto_[A-Za-z0-9._-]+\.(?:jpg|jpeg|png)\z/i', $claveArchivo) === 1;
    }

    // Calcula la firma AWS SigV4 a partir de los datos canónicos de una solicitud.
    public static function calcularFirmaV4(
        string $metodo,
        string $rutaCanonica,
        string $consultaCanonica,
        array $encabezados,
        string $hashContenido,
        string $fechaHora,
        string $region,
        string $servicio,
        string $secreto
    ): array {
        $encabezadosNormalizados = [];
        foreach ($encabezados as $nombre => $valor) {
            $nombreNormalizado = strtolower(trim((string) $nombre));
            $valorNormalizado = preg_replace('/\s+/', ' ', trim((string) $valor));
            $encabezadosNormalizados[$nombreNormalizado] = $valorNormalizado ?? '';
        }
        ksort($encabezadosNormalizados, SORT_STRING);

        $lineasEncabezados = [];
        foreach ($encabezadosNormalizados as $nombre => $valor) {
            $lineasEncabezados[] = $nombre . ':' . $valor;
        }

        $nombresFirmados = implode(';', array_keys($encabezadosNormalizados));
        $encabezadosCanonicos = implode("\n", $lineasEncabezados) . "\n";
        $solicitudCanonica = strtoupper($metodo) . "\n"
            . $rutaCanonica . "\n"
            . $consultaCanonica . "\n"
            . $encabezadosCanonicos . "\n"
            . $nombresFirmados . "\n"
            . $hashContenido;
        $fecha = substr($fechaHora, 0, 8);
        $alcance = $fecha . '/' . $region . '/' . $servicio . '/aws4_request';
        $cadenaFirmar = "AWS4-HMAC-SHA256\n"
            . $fechaHora . "\n"
            . $alcance . "\n"
            . hash('sha256', $solicitudCanonica);

        $claveFecha = hash_hmac('sha256', $fecha, 'AWS4' . $secreto, true);
        $claveRegion = hash_hmac('sha256', $region, $claveFecha, true);
        $claveServicio = hash_hmac('sha256', $servicio, $claveRegion, true);
        $claveFirma = hash_hmac('sha256', 'aws4_request', $claveServicio, true);

        return [
            'firma' => hash_hmac('sha256', $cadenaFirmar, $claveFirma),
            'encabezados_firmados' => $nombresFirmados,
        ];
    }

    // Devuelve una configuración saneada sin registrar credenciales.
    private static function obtenerConfiguracion(): array
    {
        return [
            'ALMACEN_IMAGENES' => strtolower(obtenerValorEntorno('ALMACEN_IMAGENES', 'local')),
            'S3_ENDPOINT' => rtrim(obtenerValorEntorno('S3_ENDPOINT'), '/'),
            'S3_REGION' => obtenerValorEntorno('S3_REGION'),
            'S3_BUCKET' => obtenerValorEntorno('S3_BUCKET'),
            'S3_KEY_ID' => obtenerValorEntorno('S3_KEY_ID'),
            'S3_SECRET' => obtenerValorEntorno('S3_SECRET'),
            'S3_URL_PUBLICA' => rtrim(obtenerValorEntorno('S3_URL_PUBLICA'), '/'),
        ];
    }

    // Comprueba los parámetros S3 necesarios antes de crear cualquier solicitud firmada.
    private static function validarConfiguracionS3(array $configuracion): void
    {
        foreach (['S3_ENDPOINT', 'S3_REGION', 'S3_BUCKET', 'S3_KEY_ID', 'S3_SECRET'] as $clave) {
            if (!isset($configuracion[$clave]) || $configuracion[$clave] === '') {
                throw new RuntimeException('La configuración de S3 está incompleta.');
            }
        }

        if (!extension_loaded('curl')) {
            throw new RuntimeException('La extensión cURL de PHP no está habilitada.');
        }
    }

    // Envía una solicitud firmada al endpoint S3 y devuelve su estado y respuesta.
    private static function enviarSolicitudS3(
        string $metodo,
        string $clave,
        string $consulta,
        array $encabezadosAdicionales,
        string $cuerpo,
        array $configuracion
    ): array {
        self::validarConfiguracionS3($configuracion);
        $url = self::crearUrlEndpoint($configuracion, $clave, $consulta);
        $partesUrl = parse_url($url);
        if ($partesUrl === false || !isset($partesUrl['host'])) {
            throw new RuntimeException('El endpoint S3 no es válido.');
        }

        $host = $partesUrl['host'] . (isset($partesUrl['port']) ? ':' . $partesUrl['port'] : '');
        $fechaHora = gmdate('Ymd\THis\Z');
        $hashContenido = hash('sha256', $cuerpo);
        $encabezados = array_merge($encabezadosAdicionales, [
            'host' => $host,
            'x-amz-content-sha256' => $hashContenido,
            'x-amz-date' => $fechaHora,
        ]);
        $firma = self::calcularFirmaV4(
            $metodo,
            (string) ($partesUrl['path'] ?? '/'),
            $consulta,
            $encabezados,
            $hashContenido,
            $fechaHora,
            $configuracion['S3_REGION'],
            's3',
            $configuracion['S3_SECRET']
        );
        $fecha = substr($fechaHora, 0, 8);
        $alcance = $fecha . '/' . $configuracion['S3_REGION'] . '/s3/aws4_request';
        $encabezadosHttp = [];
        foreach ($encabezados as $nombre => $valor) {
            $encabezadosHttp[] = $nombre . ': ' . $valor;
        }
        $encabezadosHttp[] = 'Authorization: AWS4-HMAC-SHA256 Credential='
            . $configuracion['S3_KEY_ID'] . '/' . $alcance
            . ', SignedHeaders=' . $firma['encabezados_firmados']
            . ', Signature=' . $firma['firma'];

        $conexion = curl_init($url);
        if ($conexion === false) {
            throw new RuntimeException('No fue posible iniciar la solicitud S3.');
        }

        curl_setopt_array($conexion, [
            CURLOPT_CUSTOMREQUEST => strtoupper($metodo),
            CURLOPT_HTTPHEADER => $encabezadosHttp,
            CURLOPT_POSTFIELDS => $cuerpo,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $encabezadosRespuesta = [];
        curl_setopt(
            $conexion,
            CURLOPT_HEADERFUNCTION,
            static function ($conexion, string $linea) use (&$encabezadosRespuesta): int {
                $longitud = strlen($linea);
                $separador = strpos($linea, ':');
                if ($separador !== false) {
                    $nombre = strtolower(trim(substr($linea, 0, $separador)));
                    $encabezadosRespuesta[$nombre] = trim(substr($linea, $separador + 1));
                }

                return $longitud;
            }
        );

        $respuesta = curl_exec($conexion);
        if ($respuesta === false) {
            $mensajeCurl = curl_error($conexion);
            curl_close($conexion);
            throw new RuntimeException('La solicitud S3 falló por cURL: ' . $mensajeCurl);
        }

        $codigoHttp = (int) curl_getinfo($conexion, CURLINFO_HTTP_CODE);
        curl_close($conexion);

        return [
            'codigo' => $codigoHttp,
            'respuesta' => (string) $respuesta,
            'encabezados' => $encabezadosRespuesta,
        ];
    }

    // Construye la URL estilo path que acepta el endpoint regional de IDrive e2.
    private static function crearUrlEndpoint(array $configuracion, string $clave, string $consulta): string
    {
        $partes = parse_url($configuracion['S3_ENDPOINT']);
        if ($partes === false || !isset($partes['scheme'], $partes['host'])) {
            throw new RuntimeException('El endpoint S3 no es válido.');
        }

        $host = $partes['host'] . (isset($partes['port']) ? ':' . $partes['port'] : '');
        $rutaBase = rtrim((string) ($partes['path'] ?? ''), '/');
        $ruta = $rutaBase . '/' . rawurlencode($configuracion['S3_BUCKET']);
        if ($clave !== '') {
            $ruta .= '/' . self::codificarClave($clave);
        }

        return $partes['scheme'] . '://' . $host . $ruta . ($consulta !== '' ? '?' . $consulta : '');
    }

    // Codifica cada componente de la clave conservando las separaciones por barra.
    private static function codificarClave(string $clave): string
    {
        return implode('/', array_map('rawurlencode', explode('/', ltrim($clave, '/'))));
    }

    // Resume errores HTTP sin incluir credenciales en mensajes visibles ni registros de aplicación.
    private static function mensajeErrorHttp(string $operacion, array $resultado): string
    {
        $detalle = trim(strip_tags($resultado['respuesta']));

        return 'Error de ' . $operacion . ' en S3 (HTTP ' . $resultado['codigo'] . ')'
            . ($detalle !== '' ? ': ' . substr($detalle, 0, 2000) : '.');
    }
}
