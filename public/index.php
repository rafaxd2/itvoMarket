<?php

declare(strict_types=1);

require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/ProductoController.php';
require_once __DIR__ . '/../controllers/UbicacionController.php';
require_once __DIR__ . '/../controllers/CatalogoController.php';
require_once __DIR__ . '/../controllers/DisponibilidadController.php';
require_once __DIR__ . '/../controllers/CatalogoVendedorController.php';

$controlador = new AuthController();
$accion = $_GET['accion'] ?? 'login';

$accionesPublicas = [
    'login',
    'registro',
    'procesarLogin',
    'procesarRegistro',
    'logout',
    'olvide',
    'restablecer',
    'verificar_codigo',
    'reenviar_codigo',
    'corregir_correo',
    'nueva_contrasena',
    'guardar_contrasena',
];
if (!in_array($accion, $accionesPublicas, true)) {
    AuthController::requerirSesion();
}

switch ($accion) {
    case 'registro':
        $controlador->mostrarRegistro();
        break;
    case 'procesarRegistro':
        $controlador->registrar();
        break;
    case 'login':
        $controlador->mostrarLogin();
        break;
    case 'procesarLogin':
        $controlador->login();
        break;
    case 'olvide':
        $controlador->solicitarRestablecimiento();
        break;
    case 'restablecer':
        $controlador->mostrarRestablecer();
        break;
    case 'verificar_codigo':
        $controlador->verificarCodigoRecuperacion();
        break;
    case 'reenviar_codigo':
        $controlador->reenviarCodigoRecuperacion();
        break;
    case 'corregir_correo':
        $controlador->corregirCorreoRecuperacion();
        break;
    case 'nueva_contrasena':
        $controlador->mostrarNuevaContrasena();
        break;
    case 'guardar_contrasena':
        $controlador->guardarNuevaContrasena();
        break;
    case 'logout':
        $controlador->cerrarSesion();
        break;
    case 'publicar':
        AuthController::requerirRol('vendedor', 'panel.php?rol=comprador&mensaje=solo_vendedores');
        (new ProductoController())->mostrarFormulario();
        break;
    case 'procesarPublicacion':
        AuthController::requerirRol('vendedor', 'panel.php?rol=comprador&mensaje=solo_vendedores');
        (new ProductoController())->publicar();
        break;
    case 'ubicacion':
        AuthController::requerirRol('vendedor', 'panel.php?rol=comprador&mensaje=solo_vendedores');
        (new UbicacionController())->mostrarFormulario();
        break;
    case 'guardarUbicacion':
        AuthController::requerirRol('vendedor', 'panel.php?rol=comprador&mensaje=solo_vendedores');
        (new UbicacionController())->guardar();
        break;
    case 'disponibilidad':
        AuthController::requerirRol('vendedor', 'panel.php?rol=comprador&mensaje=solo_vendedores');
        (new DisponibilidadController())->mostrar();
        break;
    case 'actualizar_disponibilidad':
        AuthController::requerirRol('vendedor', 'panel.php?rol=comprador&mensaje=solo_vendedores');
        (new DisponibilidadController())->actualizar();
        break;
    case 'mis_productos':
        AuthController::requerirRol('vendedor', 'panel.php?rol=comprador&mensaje=solo_vendedores');
        (new CatalogoVendedorController())->misProductos();
        break;
    case 'editar_producto':
        AuthController::requerirRol('vendedor', 'panel.php?rol=comprador&mensaje=solo_vendedores');
        $metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $controladorCatalogoVendedor = new CatalogoVendedorController();
        if ($metodo === 'GET') {
            $controladorCatalogoVendedor->mostrarEdicion();
        } elseif ($metodo === 'POST') {
            $controladorCatalogoVendedor->guardarEdicion();
        } else {
            http_response_code(405);
            header('Allow: GET, POST');
        }
        break;
    case 'confirmar_eliminar':
        AuthController::requerirRol('vendedor', 'panel.php?rol=comprador&mensaje=solo_vendedores');
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
            http_response_code(405);
            header('Allow: GET');
            break;
        }
        (new CatalogoVendedorController())->confirmarEliminar();
        break;
    case 'eliminar_producto':
        AuthController::requerirRol('vendedor', 'panel.php?rol=comprador&mensaje=solo_vendedores');
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            break;
        }
        (new CatalogoVendedorController())->eliminar();
        break;
    case 'mapa':
        header('Location: panel.php?rol=comprador');
        exit;
        break;
    case 'catalogo':
        AuthController::requerirRol('comprador', 'panel.php?rol=vendedor');
        (new CatalogoController())->mostrar();
        break;
    case 'producto':
        AuthController::requerirRol('comprador', 'panel.php?rol=vendedor');
        (new CatalogoController())->mostrarDetalle();
        break;
    case 'imagen':
        (new CatalogoController())->mostrarImagen();
        break;
    case 'cambiarRol':
        $controlador->cambiarRol();
        break;
    default:
        $controlador->mostrarLogin();
        break;
}
