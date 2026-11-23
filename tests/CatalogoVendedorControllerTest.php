<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;

require_once __DIR__ . '/../controllers/CatalogoVendedorController.php';

final class CatalogoVendedorControllerTest extends ModeloTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->conexion->exec(
            'CREATE TABLE categorias (
                id_categoria INTEGER PRIMARY KEY AUTOINCREMENT,
                tipo_categoria TEXT NOT NULL
            )'
        );
        $this->conexion->exec(
            'CREATE TABLE vendedores (
                id_vendedor INTEGER PRIMARY KEY,
                id_usuario INTEGER NOT NULL
            )'
        );
        $this->conexion->exec(
            'CREATE TABLE productos (
                id_producto INTEGER PRIMARY KEY AUTOINCREMENT,
                id_vendedor INTEGER NOT NULL,
                id_categoria INTEGER NOT NULL,
                id_estado INTEGER NOT NULL,
                id_condicion INTEGER NOT NULL,
                id_estado_ubicacion INTEGER NOT NULL,
                nombre TEXT NOT NULL,
                descripcion_producto TEXT NOT NULL,
                precio REAL NOT NULL,
                imagen_producto TEXT NOT NULL
            )'
        );
        $this->conexion->exec(
            'CREATE TABLE calificacion_producto (
                id_calificacion_producto INTEGER PRIMARY KEY AUTOINCREMENT,
                id_usuario INTEGER NOT NULL,
                id_producto INTEGER NOT NULL,
                puntuacion INTEGER NOT NULL
            )'
        );
        $this->conexion->exec("INSERT INTO categorias (tipo_categoria) VALUES ('Comida'), ('Bebida')");
        $this->conexion->exec('INSERT INTO vendedores (id_vendedor, id_usuario) VALUES (1, 10), (2, 20)');

        AuthController::iniciarSesion();
        $_SESSION = [
            'usuario_id' => 10,
            'usuario_rol' => 'vendedor',
            'token_csrf_catalogo' => 'token-valido',
        ];
        $_GET = ['accion' => 'mis_productos'];
        $_POST = [];
        $_FILES = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        $_GET = [];
        $_POST = [];
        $_FILES = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
        parent::tearDown();
    }

    public function testGuardarEdicionActualizaLosCamposPermitidosYConservaLaImagen(): void
    {
        $this->insertarProducto(1, 1, 'Torta', 'Frijol', 25.0, 'assets/uploads/imagen-actual.jpg');
        $this->prepararEdicion([
            'nombre' => 'Torta nueva',
            'precio' => '42.50',
            'descripcion_producto' => 'Frijol y queso',
            'id_categoria' => '2',
        ]);

        $this->capturarVista(static function (): void {
            (new CatalogoVendedorController())->guardarEdicion();
        });

        $producto = $this->conexion->query('SELECT * FROM productos WHERE id_producto = 1')->fetch();
        self::assertSame('Torta nueva', $producto['nombre']);
        self::assertSame(42.5, (float) $producto['precio']);
        self::assertSame('Frijol y queso', $producto['descripcion_producto']);
        self::assertSame(2, (int) $producto['id_categoria']);
        self::assertSame('assets/uploads/imagen-actual.jpg', $producto['imagen_producto']);
        self::assertSame(
            'Cambios guardados correctamente. Torta nueva ya muestra la información actualizada.',
            $_SESSION['mensaje_catalogo_vendedor']
        );
    }

    #[DataProvider('datosInvalidosDeEdicion')]
    public function testGuardarEdicionRechazaDatosInvalidos(
        array $cambios,
        string $campo,
        string $mensaje
    ): void {
        $this->insertarProducto(1, 1, 'Torta original', 'Frijol', 25.0);
        $this->prepararEdicion($cambios);

        $html = $this->capturarVista(static function (): void {
            (new CatalogoVendedorController())->guardarEdicion();
        });

        self::assertStringContainsString('id="' . $campo . '"', $html);
        self::assertStringContainsString('aria-invalid="true"', $html);
        self::assertStringContainsString($mensaje, $html);
        self::assertSame(
            'Torta original',
            $this->conexion->query('SELECT nombre FROM productos WHERE id_producto = 1')->fetchColumn()
        );
        self::assertSame(
            25.0,
            (float) $this->conexion->query('SELECT precio FROM productos WHERE id_producto = 1')->fetchColumn()
        );
    }

    public function testImagenNuevaSeGuardaYLaAnteriorSeEliminaTrasActualizar(): void
    {
        $directorioUploads = dirname(__DIR__) . '/public/assets/uploads';
        if (!is_dir($directorioUploads)) {
            self::assertTrue(mkdir($directorioUploads, 0775, true));
        }

        $nombreAnterior = 'producto_prueba_' . bin2hex(random_bytes(6)) . '.jpg';
        $rutaAnterior = $directorioUploads . DIRECTORY_SEPARATOR . $nombreAnterior;
        $rutaTemporal = tempnam(sys_get_temp_dir(), 'itvo-edicion-');
        self::assertNotFalse(file_put_contents($rutaAnterior, 'imagen anterior'));
        self::assertNotFalse(file_put_contents($rutaTemporal, 'imagen nueva'));
        $almacenAnterior = getenv('ALMACEN_IMAGENES');
        putenv('ALMACEN_IMAGENES=local');

        try {
            $this->insertarProducto(
                1,
                1,
                'Torta',
                'Frijol',
                25.0,
                'assets/uploads/' . $nombreAnterior
            );
            $this->prepararEdicion([
                'nombre' => 'Torta actualizada',
                'precio' => '30',
                'id_categoria' => '1',
            ]);
            $_FILES['imagen'] = [
                'name' => 'nueva.png',
                'tmp_name' => $rutaTemporal,
                'error' => UPLOAD_ERR_OK,
            ];

            $this->capturarVista(static function (): void {
                (new CatalogoVendedorController())->guardarEdicion();
            });

            $rutaGuardada = (string) $this->conexion
                ->query('SELECT imagen_producto FROM productos WHERE id_producto = 1')
                ->fetchColumn();
            self::assertStringStartsWith('assets/uploads/producto_', $rutaGuardada);
            self::assertFileExists(dirname(__DIR__) . '/public/' . $rutaGuardada);
            self::assertFileDoesNotExist($rutaAnterior);
        } finally {
            $rutaGuardada = $rutaGuardada ?? '';
            if (str_starts_with($rutaGuardada, 'assets/uploads/')) {
                $rutaNueva = dirname(__DIR__) . '/public/' . $rutaGuardada;
                if (is_file($rutaNueva)) {
                    unlink($rutaNueva);
                }
            }
            if (is_file($rutaAnterior)) {
                unlink($rutaAnterior);
            }
            if (is_file($rutaTemporal)) {
                unlink($rutaTemporal);
            }
            if ($almacenAnterior === false) {
                putenv('ALMACEN_IMAGENES');
            } else {
                putenv('ALMACEN_IMAGENES=' . $almacenAnterior);
            }
        }
    }

    public function testFallaAlActualizarConservaImagenAnteriorYEliminaLaNueva(): void
    {
        $directorioUploads = dirname(__DIR__) . '/public/assets/uploads';
        if (!is_dir($directorioUploads)) {
            self::assertTrue(mkdir($directorioUploads, 0775, true));
        }

        $nombreAnterior = 'producto_prueba_' . bin2hex(random_bytes(6)) . '.jpg';
        $rutaAnterior = $directorioUploads . DIRECTORY_SEPARATOR . $nombreAnterior;
        $rutaTemporal = tempnam(sys_get_temp_dir(), 'itvo-edicion-');
        self::assertNotFalse(file_put_contents($rutaAnterior, 'imagen anterior'));
        self::assertNotFalse(file_put_contents($rutaTemporal, 'imagen nueva'));
        $almacenAnterior = getenv('ALMACEN_IMAGENES');
        putenv('ALMACEN_IMAGENES=local');
        $imagenesPrevias = glob($directorioUploads . DIRECTORY_SEPARATOR . 'producto_*.png') ?: [];

        try {
            $this->insertarProducto(
                1,
                1,
                'Torta',
                'Frijol',
                25.0,
                'assets/uploads/' . $nombreAnterior
            );
            $this->conexion->exec(
                "CREATE TRIGGER rechazar_actualizacion_producto
                 BEFORE UPDATE ON productos
                 BEGIN
                     SELECT RAISE(FAIL, 'error de prueba');
                 END"
            );
            $this->prepararEdicion([
                'nombre' => 'Torta actualizada',
                'precio' => '30',
                'id_categoria' => '1',
            ]);
            $_FILES['imagen'] = [
                'name' => 'nueva.png',
                'tmp_name' => $rutaTemporal,
                'error' => UPLOAD_ERR_OK,
            ];

            $this->capturarVista(static function (): void {
                (new CatalogoVendedorController())->guardarEdicion();
            });

            self::assertFileExists($rutaAnterior);
            self::assertSame(
                'assets/uploads/' . $nombreAnterior,
                $this->conexion->query('SELECT imagen_producto FROM productos WHERE id_producto = 1')->fetchColumn()
            );
            self::assertSame(
                $imagenesPrevias,
                glob($directorioUploads . DIRECTORY_SEPARATOR . 'producto_*.png') ?: []
            );
        } finally {
            if (is_file($rutaAnterior)) {
                unlink($rutaAnterior);
            }
            if (is_file($rutaTemporal)) {
                unlink($rutaTemporal);
            }
            if ($almacenAnterior === false) {
                putenv('ALMACEN_IMAGENES');
            } else {
                putenv('ALMACEN_IMAGENES=' . $almacenAnterior);
            }
        }
    }

    public static function datosInvalidosDeEdicion(): array
    {
        return [
            'nombre vacío' => [
                ['nombre' => '', 'precio' => '25', 'id_categoria' => '1'],
                'nombre',
                'El nombre del producto es obligatorio.',
            ],
            'precio vacío' => [
                ['nombre' => 'Torta editada', 'precio' => '', 'id_categoria' => '1'],
                'precio',
                'El precio es obligatorio.',
            ],
            'precio cero' => [
                ['nombre' => 'Torta editada', 'precio' => '0', 'id_categoria' => '1'],
                'precio',
                'El precio debe ser un número positivo.',
            ],
            'precio negativo' => [
                ['nombre' => 'Torta editada', 'precio' => '-1', 'id_categoria' => '1'],
                'precio',
                'El precio debe ser un número positivo.',
            ],
            'precio mayor al máximo' => [
                ['nombre' => 'Torta editada', 'precio' => '10000', 'id_categoria' => '1'],
                'precio',
                'El precio debe ser un número positivo.',
            ],
            'precio con más de dos decimales' => [
                ['nombre' => 'Torta editada', 'precio' => '12.345', 'id_categoria' => '1'],
                'precio',
                'El precio debe ser un número positivo.',
            ],
            'categoría inexistente' => [
                ['nombre' => 'Torta editada', 'precio' => '25', 'id_categoria' => '999'],
                'id_categoria',
                'Selecciona una categoría válida.',
            ],
        ];
    }

    public function testVendedorNoPuedeEditarNiEliminarProductoAjeno(): void
    {
        $this->insertarProducto(1, 2, 'Producto privado', 'No modificar', 25.0);

        $_GET['id'] = '1';
        $htmlEdicion = $this->capturarVista(static function (): void {
            (new CatalogoVendedorController())->mostrarEdicion();
        });
        self::assertStringContainsString('Esta publicación no está disponible', $htmlEdicion);

        $_POST = ['id_producto' => '1', 'token_csrf' => 'token-valido'];
        $htmlEliminacion = $this->capturarVista(static function (): void {
            (new CatalogoVendedorController())->eliminar();
        });
        self::assertStringContainsString('Esta publicación no está disponible', $htmlEliminacion);
        self::assertSame(1, (int) $this->conexion->query('SELECT COUNT(*) FROM productos')->fetchColumn());
        self::assertSame('Producto privado', $this->conexion->query('SELECT nombre FROM productos')->fetchColumn());
    }

    public function testIdentificadorNoNumericoOInexistenteMuestraLaVistaNoDisponible(): void
    {
        foreach (['no-es-un-id', '999'] as $idProducto) {
            $_GET['id'] = $idProducto;
            $html = $this->capturarVista(static function (): void {
                (new CatalogoVendedorController())->mostrarEdicion();
            });

            self::assertStringContainsString('Esta publicación no está disponible', $html);
            self::assertStringNotContainsString('Producto privado', $html);

            $_POST = ['id_producto' => $idProducto, 'token_csrf' => 'token-valido'];
            $html = $this->capturarVista(static function (): void {
                (new CatalogoVendedorController())->eliminar();
            });
            self::assertStringContainsString('Esta publicación no está disponible', $html);
        }
    }

    public function testFormularioDeConfirmacionNoEliminaElProducto(): void
    {
        $this->insertarProducto(1, 1, 'Torta', 'Frijol', 25.0);
        $_GET['id'] = '1';

        $html = $this->capturarVista(static function (): void {
            (new CatalogoVendedorController())->confirmarEliminar();
        });

        self::assertStringContainsString('¿Eliminar Torta?', $html);
        self::assertSame(1, (int) $this->conexion->query('SELECT COUNT(*) FROM productos')->fetchColumn());
    }

    public function testTokenCsrfInvalidoImpideEditarYEliminar(): void
    {
        $this->insertarProducto(1, 1, 'Torta original', 'Frijol', 25.0);
        $this->prepararEdicion([
            'nombre' => 'Torta modificada',
            'precio' => '42.50',
            'id_categoria' => '1',
        ]);
        $_POST['token_csrf'] = 'token-incorrecto';

        $this->capturarVista(static function (): void {
            (new CatalogoVendedorController())->guardarEdicion();
        });
        self::assertSame(
            'Torta original',
            $this->conexion->query('SELECT nombre FROM productos WHERE id_producto = 1')->fetchColumn()
        );

        $_POST = ['id_producto' => '1', 'token_csrf' => 'token-incorrecto'];
        $this->capturarVista(static function (): void {
            (new CatalogoVendedorController())->eliminar();
        });
        self::assertSame(1, (int) $this->conexion->query('SELECT COUNT(*) FROM productos')->fetchColumn());
    }

    public function testImagenAnteriorSeConservaSiLaTransaccionDeEliminacionFalla(): void
    {
        $directorioUploads = dirname(__DIR__) . '/public/assets/uploads';
        if (!is_dir($directorioUploads)) {
            self::assertTrue(mkdir($directorioUploads, 0775, true));
        }
        $nombreArchivo = 'producto_prueba_' . bin2hex(random_bytes(6)) . '.jpg';
        $rutaArchivo = $directorioUploads . DIRECTORY_SEPARATOR . $nombreArchivo;
        self::assertNotFalse(file_put_contents($rutaArchivo, 'imagen de prueba'));

        try {
            $this->insertarProducto(1, 1, 'Torta', 'Frijol', 25.0, 'assets/uploads/' . $nombreArchivo);
            $this->conexion->exec(
                "CREATE TRIGGER rechazar_eliminacion_producto
                 BEFORE DELETE ON productos
                 BEGIN
                     SELECT RAISE(FAIL, 'error de prueba');
                 END"
            );
            $this->conexion->exec(
                'INSERT INTO calificacion_producto (id_usuario, id_producto, puntuacion) VALUES (10, 1, 5)'
            );
            $_POST = ['id_producto' => '1', 'token_csrf' => 'token-valido'];

            $this->capturarVista(static function (): void {
                (new CatalogoVendedorController())->eliminar();
            });

            self::assertFileExists($rutaArchivo);
            self::assertSame(1, (int) $this->conexion->query('SELECT COUNT(*) FROM productos')->fetchColumn());
            self::assertSame(1, (int) $this->conexion->query('SELECT COUNT(*) FROM calificacion_producto')->fetchColumn());
        } finally {
            if (is_file($rutaArchivo)) {
                unlink($rutaArchivo);
            }
        }
    }

    public function testFallaAlBorrarImagenNoImpideEliminarElProducto(): void
    {
        $this->insertarProducto(
            1,
            1,
            'Torta',
            'Frijol',
            25.0,
            'assets/uploads/subdirectorio/imagen-invalida.jpg'
        );
        $this->conexion->exec(
            'INSERT INTO calificacion_producto (id_usuario, id_producto, puntuacion) VALUES (10, 1, 5)'
        );
        $_POST = ['id_producto' => '1', 'token_csrf' => 'token-valido'];

        $this->capturarVista(static function (): void {
            (new CatalogoVendedorController())->eliminar();
        });

        self::assertSame(0, (int) $this->conexion->query('SELECT COUNT(*) FROM productos')->fetchColumn());
        self::assertSame(0, (int) $this->conexion->query('SELECT COUNT(*) FROM calificacion_producto')->fetchColumn());
        self::assertSame('Producto eliminado correctamente.', $_SESSION['mensaje_catalogo_vendedor']);
    }

    private function insertarProducto(
        int $idProducto,
        int $idVendedor,
        string $nombre,
        string $descripcion,
        float $precio,
        string $imagen = ''
    ): void {
        $consulta = $this->conexion->prepare(
            'INSERT INTO productos (
                id_producto, id_vendedor, id_categoria, id_estado, id_condicion,
                id_estado_ubicacion, nombre, descripcion_producto, precio, imagen_producto
            ) VALUES (
                :id_producto, :id_vendedor, 1, 1, 1, 1, :nombre, :descripcion, :precio, :imagen
            )'
        );
        $consulta->execute([
            ':id_producto' => $idProducto,
            ':id_vendedor' => $idVendedor,
            ':nombre' => $nombre,
            ':descripcion' => $descripcion,
            ':precio' => $precio,
            ':imagen' => $imagen,
        ]);
    }

    private function prepararEdicion(array $cambios): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = array_merge([
            'id_producto' => '1',
            'nombre' => 'Torta editada',
            'precio' => '30.00',
            'descripcion_producto' => 'Descripción editada',
            'id_categoria' => '1',
            'token_csrf' => 'token-valido',
        ], $cambios);
    }

    private function capturarVista(callable $accion): string
    {
        ob_start();
        $accion();

        return (string) ob_get_clean();
    }
}
