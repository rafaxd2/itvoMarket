<?php

declare(strict_types=1);

final class ProductoTest extends ModeloTestCase
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
                puntuacion INTEGER NOT NULL,
                comentario TEXT NULL,
                fecha TEXT NULL
            )'
        );
        $this->conexion->exec(
            'CREATE TABLE usuario (
                id_usuario INTEGER PRIMARY KEY,
                nombre TEXT NOT NULL
            )'
        );
        $this->conexion->exec(
            'CREATE TABLE vendedores (
                id_vendedor INTEGER PRIMARY KEY,
                id_usuario INTEGER NOT NULL
            )'
        );
        $this->conexion->exec(
            'CREATE TABLE estado_producto (
                id_estado INTEGER PRIMARY KEY,
                nombre_estado TEXT NOT NULL
            )'
        );
        $this->conexion->exec(
            'CREATE TABLE condicion_producto (
                id_condicion INTEGER PRIMARY KEY,
                nombre_condicion TEXT NOT NULL
            )'
        );
        $this->conexion->exec("INSERT INTO categorias (tipo_categoria) VALUES ('Comida'), ('Bebida')");
        $this->conexion->exec("INSERT INTO usuario (id_usuario, nombre) VALUES (1, 'Ana')");
        $this->conexion->exec("INSERT INTO vendedores (id_vendedor, id_usuario) VALUES (1, 1)");
        $this->conexion->exec("INSERT INTO estado_producto (id_estado, nombre_estado) VALUES (1, 'Disponible')");
        $this->conexion->exec("INSERT INTO condicion_producto (id_condicion, nombre_condicion) VALUES (1, 'Nuevo')");
    }

    public function testCrearProductoValido(): void
    {
        $idProducto = Producto::crear([
            'id_vendedor' => 3,
            'id_categoria' => 1,
            'nombre' => 'Torta',
            'descripcion_producto' => 'Torta de frijol',
            'precio' => 32.5,
            'imagen_producto' => 'assets/uploads/torta.jpg',
        ]);

        self::assertGreaterThan(0, $idProducto);
        self::assertSame('Torta', $this->conexion->query('SELECT nombre FROM productos')->fetchColumn());
    }

    public function testCrearProductoRechazaPrecioInvalido(): void
    {
        $datos = $this->datosProducto();
        $datos['precio'] = -1;

        $this->expectException(InvalidArgumentException::class);
        Producto::crear($datos);
    }

    public function testCrearProductoRechazaPrecioNoNumerico(): void
    {
        $datos = $this->datosProducto();
        $datos['precio'] = 'no es un precio';

        $this->expectException(InvalidArgumentException::class);
        Producto::crear($datos);
    }

    public function testCrearProductoRechazaNombreVacio(): void
    {
        $datos = $this->datosProducto();
        $datos['nombre'] = ' ';

        $this->expectException(InvalidArgumentException::class);
        Producto::crear($datos);
    }

    public function testCrearProductoPermiteContinuarSinImagenSiElAlmacenamientoNoEstaDisponible(): void
    {
        $datos = $this->datosProducto();
        $datos['imagen_producto'] = '';

        $idProducto = Producto::crear($datos);

        self::assertGreaterThan(0, $idProducto);
        self::assertSame('', $this->conexion->query('SELECT imagen_producto FROM productos')->fetchColumn());
    }

    public function testValidarFormatoDeImagen(): void
    {
        self::assertTrue(Producto::validarFormatoImagen('producto.JPG'));
        self::assertTrue(Producto::validarFormatoImagen('producto.png'));
        self::assertFalse(Producto::validarFormatoImagen('producto.gif'));
        self::assertFalse(Producto::validarFormatoImagen('producto'));
    }

    public function testCatalogoBuscaTextoEnNombreYDescripcion(): void
    {
        $this->insertarProductoCatalogo(1, 'Torta', 'Frijol y queso', 1);
        $this->insertarProductoCatalogo(2, 'Agua fresca', 'Torta de jamaica', 2);
        $this->insertarProductoCatalogo(3, 'Galleta', 'Avena y miel', 1);

        $productos = Producto::listarCatalogo('Torta', null);

        self::assertSame(['Agua fresca', 'Torta'], array_column($productos, 'nombre'));
    }

    public function testCatalogoFiltraPorCategoria(): void
    {
        $this->insertarProductoCatalogo(1, 'Torta', 'Frijol y queso', 1);
        $this->insertarProductoCatalogo(2, 'Agua fresca', 'Horchata', 2);

        $productos = Producto::listarCatalogo(null, 2);

        self::assertSame(['Agua fresca'], array_column($productos, 'nombre'));
    }

    public function testCatalogoCombinaTextoYCategoriaConAnd(): void
    {
        $this->insertarProductoCatalogo(1, 'Torta', 'Frijol', 1);
        $this->insertarProductoCatalogo(2, 'Torta de agua', 'Bebida', 2);
        $this->insertarProductoCatalogo(3, 'Agua fresca', 'Torta de jamaica', 2);

        $productos = Producto::listarCatalogo('Torta', 2);

        self::assertSame(['Agua fresca', 'Torta de agua'], array_column($productos, 'nombre'));
    }

    public function testCatalogoTrataPorcentajeComoTextoLiteral(): void
    {
        $this->insertarProductoCatalogo(1, 'Dulce 100%', 'Azúcar', 1);
        $this->insertarProductoCatalogo(2, 'Dulce 100 chocolate', 'Azúcar', 1);

        $productos = Producto::listarCatalogo('%', null);

        self::assertSame(['Dulce 100%'], array_column($productos, 'nombre'));
    }

    public function testCatalogoTrataGuionBajoComoTextoLiteral(): void
    {
        $this->insertarProductoCatalogo(1, 'Pan_cito', 'Pan dulce', 1);
        $this->insertarProductoCatalogo(2, 'Pancito', 'Pan dulce', 1);

        $productos = Producto::listarCatalogo('_', null);

        self::assertSame(['Pan_cito'], array_column($productos, 'nombre'));
    }

    public function testCatalogoTrataBarraInvertidaComoTextoLiteral(): void
    {
        $this->insertarProductoCatalogo(1, 'Botana\\Chile', 'Picante', 1);
        $this->insertarProductoCatalogo(2, 'Botana Chile', 'Picante', 1);

        $productos = Producto::listarCatalogo('\\', null);

        self::assertSame(['Botana\\Chile'], array_column($productos, 'nombre'));
    }

    public function testCatalogoDevuelveArregloVacioCuandoNoHayCoincidencias(): void
    {
        $this->insertarProductoCatalogo(1, 'Torta', 'Frijol y queso', 1);

        self::assertSame([], Producto::listarCatalogo('No existe', null));
    }

    public function testCatalogoSinFiltrosDevuelveTodasLasPublicaciones(): void
    {
        $this->insertarProductoCatalogo(1, 'Torta', 'Frijol y queso', 1);
        $this->insertarProductoCatalogo(2, 'Agua fresca', 'Horchata', 2);

        $productos = Producto::listarCatalogo('', null);

        self::assertSame(['Agua fresca', 'Torta'], array_column($productos, 'nombre'));
    }

    public function testDetalleCatalogoDevuelveProductoVendedorEstadoYCondicion(): void
    {
        $this->insertarProductoCatalogo(1, 'Torta', 'Frijol y queso', 1);

        $producto = Producto::buscarDetalleCatalogo('1');

        self::assertNotNull($producto);
        self::assertSame('Torta', $producto['nombre']);
        self::assertSame('Comida', $producto['categoria']);
        self::assertSame('Ana', $producto['nombre_vendedor']);
        self::assertSame('Disponible', $producto['estado']);
        self::assertSame('Nuevo', $producto['condicion']);
    }

    public function testDetalleCatalogoDevuelveNullCuandoElProductoNoExiste(): void
    {
        self::assertNull(Producto::buscarDetalleCatalogo(999));
    }

    public function testDetalleCatalogoRechazaIdNoNumerico(): void
    {
        self::assertNull(Producto::buscarDetalleCatalogo('no-es-un-id'));
    }

    public function testListarPorVendedorIncluyeCategoriaYNoDevuelveProductosAjeno(): void
    {
        $this->insertarProductoCatalogo(1, 'Torta', 'Frijol', 1);
        $this->conexion->exec(
            "INSERT INTO productos (
                id_producto, id_vendedor, id_categoria, id_estado, id_condicion,
                id_estado_ubicacion, nombre, descripcion_producto, precio, imagen_producto
            ) VALUES (2, 2, 2, 1, 1, 1, 'Agua', 'Horchata', 15, '')"
        );

        $productos = Producto::listarPorVendedor(1);

        self::assertCount(1, $productos);
        self::assertSame('Comida', $productos[0]['categoria']);
    }

    public function testObtenerPorIdYVendedorRequiereCoincidenciaDePropietario(): void
    {
        $this->insertarProductoCatalogo(1, 'Torta', 'Frijol', 1);

        self::assertSame('Torta', Producto::obtenerPorIdYVendedor(1, 1)['nombre']);
        self::assertNull(Producto::obtenerPorIdYVendedor(1, 2));
        self::assertNull(Producto::obtenerPorIdYVendedor(0, 1));
    }

    public function testActualizarProductoSoloModificaLosCamposPermitidosDelPropietario(): void
    {
        $this->insertarProductoCatalogo(1, 'Torta', 'Frijol', 1);

        self::assertTrue(Producto::actualizarProducto(1, 1, [
            'nombre' => 'Torta nueva',
            'precio' => '42.50',
            'descripcion_producto' => 'Frijol y queso',
            'id_categoria' => 2,
            'imagen_producto' => 'assets/uploads/nueva.jpg',
        ]));

        $producto = $this->conexion->query('SELECT * FROM productos WHERE id_producto = 1')->fetch();
        self::assertSame('Torta nueva', $producto['nombre']);
        self::assertSame(42.5, (float) $producto['precio']);
        self::assertSame(2, (int) $producto['id_categoria']);
        self::assertSame('assets/uploads/nueva.jpg', $producto['imagen_producto']);
        self::assertSame(1, (int) $producto['id_estado']);
        self::assertSame(1, (int) $producto['id_condicion']);
        self::assertSame(1, (int) $producto['id_estado_ubicacion']);
    }

    public function testActualizarProductoRechazaCategoriaInexistenteYOtroPropietario(): void
    {
        $this->insertarProductoCatalogo(1, 'Torta', 'Frijol', 1);
        $datos = [
            'nombre' => 'Torta nueva',
            'precio' => '42.50',
            'descripcion_producto' => 'Nueva',
            'id_categoria' => 1,
            'imagen_producto' => '',
        ];

        self::assertFalse(Producto::actualizarProducto(1, 2, $datos));
        $datos['id_categoria'] = 999;
        self::assertFalse(Producto::actualizarProducto(1, 1, $datos));
        self::assertSame('Torta', $this->conexion->query('SELECT nombre FROM productos')->fetchColumn());
    }

    public function testEliminarProductoBorraCalificacionesYProductoEnLaMismaOperacion(): void
    {
        $this->insertarProductoCatalogo(1, 'Torta', 'Frijol', 1);
        $this->conexion->exec(
            "INSERT INTO calificacion_producto (id_usuario, id_producto, puntuacion)
             VALUES (1, 1, 5)"
        );

        self::assertTrue(Producto::eliminarProducto(1, 1));
        self::assertSame(0, (int) $this->conexion->query('SELECT COUNT(*) FROM productos')->fetchColumn());
        self::assertSame(0, (int) $this->conexion->query('SELECT COUNT(*) FROM calificacion_producto')->fetchColumn());
    }

    public function testEliminarProductoNoBorraPublicacionDeOtroVendedor(): void
    {
        $this->insertarProductoCatalogo(1, 'Torta', 'Frijol', 1);

        self::assertFalse(Producto::eliminarProducto(1, 2));
        self::assertSame(1, (int) $this->conexion->query('SELECT COUNT(*) FROM productos')->fetchColumn());
    }

    public function testEliminarProductoRevierteCalificacionesSiFallaElBorradoDelProducto(): void
    {
        $this->insertarProductoCatalogo(1, 'Torta', 'Frijol', 1);
        $this->conexion->exec(
            "INSERT INTO calificacion_producto (id_usuario, id_producto, puntuacion)
             VALUES (1, 1, 5)"
        );
        $this->conexion->exec(
            "CREATE TRIGGER rechazar_eliminacion_producto
             BEFORE DELETE ON productos
             BEGIN
                 SELECT RAISE(FAIL, 'error de prueba');
             END"
        );

        self::assertFalse(Producto::eliminarProducto(1, 1));
        self::assertSame(1, (int) $this->conexion->query('SELECT COUNT(*) FROM productos')->fetchColumn());
        self::assertSame(1, (int) $this->conexion->query('SELECT COUNT(*) FROM calificacion_producto')->fetchColumn());
    }

    private function insertarProductoCatalogo(int $id, string $nombre, string $descripcion, int $idCategoria): void
    {
        $consulta = $this->conexion->prepare(
            'INSERT INTO productos (
                id_producto, id_vendedor, id_categoria, id_estado, id_condicion,
                id_estado_ubicacion, nombre, descripcion_producto, precio, imagen_producto
            ) VALUES (
                :id_producto, 1, :id_categoria, 1, 1, 1, :nombre, :descripcion, 25.00, ""
            )'
        );
        $consulta->execute([
            ':id_producto' => $id,
            ':id_categoria' => $idCategoria,
            ':nombre' => $nombre,
            ':descripcion' => $descripcion,
        ]);
    }

    private function datosProducto(): array
    {
        return [
            'id_vendedor' => 3,
            'id_categoria' => 1,
            'nombre' => 'Torta',
            'descripcion_producto' => 'Torta de frijol',
            'precio' => 32.5,
            'imagen_producto' => 'assets/uploads/torta.jpg',
        ];
    }
}
