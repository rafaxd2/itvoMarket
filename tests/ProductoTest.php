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
        $this->conexion->exec("INSERT INTO categorias (tipo_categoria) VALUES ('Comida')");
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

    public function testValidarFormatoDeImagen(): void
    {
        self::assertTrue(Producto::validarFormatoImagen('producto.JPG'));
        self::assertTrue(Producto::validarFormatoImagen('producto.png'));
        self::assertFalse(Producto::validarFormatoImagen('producto.gif'));
        self::assertFalse(Producto::validarFormatoImagen('producto'));
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
