<?php
use Slim\Routing\RouteCollectorProxy;

// Error Handling
error_reporting(-1);
ini_set('display_errors', 1);

require './dao/UsuarioDAO.php';
require_once './controller/UsuarioController.php';
require './dao/ProductoDAO.php';
require_once './controller/ProductoController.php';
require './dao/MesasDAO.php';
require_once './controller/MesasController.php';
require './dao/PedidosDAO.php';
require_once './controller/PedidosController.php';
require './middlewares/AuthTokenMiddleware.php';

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

// Instantiate App
$app = AppFactory::create();

// Add error middleware
$app->addErrorMiddleware(true, true, true);

// Add parse body
$app->addBodyParsingMiddleware();

// Routes
$app->get('[/]', function (Request $request, Response $response) {
    $payload = json_encode(array('method' => 'GET', 'msg' => "Bienvenido a mi primera chambaa"));
    $response->getBody()->write($payload);
    return $response->withHeader('Content-Type', 'application/json');
});

$pdo = new PDO('mysql:host=localhost;dbname=segundoparcial;charset=utf8', 'root', '', array(PDO::ATTR_EMULATE_PREPARES => false, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));


$usuarioDAO = new UsuarioDAO($pdo);
$usuarioController = new UsuarioController($usuarioDAO);
// Grupo de rutas para usuarios
$app->group('/usuarios', function (RouteCollectorProxy $group) use ($usuarioController) {
    $group->get('[/]', [$usuarioController, 'listarUsuarios'])->add(\AuthTokenMiddleware::class . ":validarSocio");
    $group->get('/traerUno', [$usuarioController, 'listarUsuarioPorId'])->add(\AuthTokenMiddleware::class . ":validarSocio");
    $group->post('[/]', [$usuarioController, 'altaUsuario'])->add(\AuthTokenMiddleware::class . ":validarSocio");
    $group->put('[/]', [$usuarioController, 'modificarUsuarioPorId'])->add(\AuthTokenMiddleware::class . ":validarSocio");
    $group->get('/borrar', [$usuarioController, 'borrarUsuarioPorId'])->add(\AuthTokenMiddleware::class . ":validarSocio");
    $group->get('/login', [$usuarioController, 'login']);
});

$productoDAO = new ProductoDAO($pdo);
$productoController = new ProductoController($productoDAO);
// Grupo de rutas para productos
$app->group('/productos', function (RouteCollectorProxy $group) use ($productoController) {
    $group->post('[/]', [$productoController, 'crearProducto'])->add(\AuthTokenMiddleware::class . ":validarSocio");
    $group->get('[/]', [$productoController, 'listarProductos']);
    $group->put('[/]', [$productoController, 'modificarProductoPorId'])->add(\AuthTokenMiddleware::class . ":validarSocio");
    $group->get('/borrar', [$productoController, 'borrarProductoPorId'])->add(\AuthTokenMiddleware::class . ":validarSocio");
    $group->post('/cargarCsv', [$productoController, 'cargarProductosDesdeCSV']);
    $group->get('/descargarCsv', [$productoController, 'descargarProductosComoCSV']);
    $group->get('/segunEmpleado', [$productoController, 'listarProductosSegunEmpleado']);
    $group->put('/productoDelPedido', [$productoController, 'modificarProductoSegunEmpleado'])->add(\AuthTokenMiddleware::class . ":validarEmpleado");
});

$mesasDAO = new MesasDAO($pdo);
$mesasController = new MesasController($mesasDAO);
// Grupo de rutas para mesas
$app->group('/mesas', function (RouteCollectorProxy $group) use ($mesasController) {
    $group->post('[/]', [$mesasController, 'crearMesa'])->add(\AuthTokenMiddleware::class . ":validarSocio");
    $group->get('[/]', [$mesasController, 'listarMesas'])->add(\AuthTokenMiddleware::class . ":validarSocio");
    $group->put('[/]', [$mesasController, 'modificarEstadoMesa'])->add(\AuthTokenMiddleware::class . ":validarMozoOSocio");
    $group->get('/borrar', [$mesasController, 'borrarMesa'])->add(\AuthTokenMiddleware::class . ":validarSocio");
    $group->get('/cerrar', [$mesasController, 'cerrarMesa'])->add(\AuthTokenMiddleware::class . ":validarSocio");
    $group->post('/encuesta', [$mesasController, 'completarEncuesta']);
    $group->get('/mejoresComentarios', [$mesasController, 'obtenerMejoresComentarios'])->add(\AuthTokenMiddleware::class . ":validarSocio");
    $group->get('/mesaMasUsada', [$mesasController, 'obtenerMesaMasUsada'])->add(\AuthTokenMiddleware::class . ":validarSocio");

});

$pedidosDAO = new PedidosDAO($pdo);
$pedidosController = new PedidosController($pedidosDAO);
// Grupo de rutas para pedidos
$app->group('/pedidos', function (RouteCollectorProxy $group) use ($pedidosController) {
    $group->post('[/]', [$pedidosController, 'crearPedido'])->add(\AuthTokenMiddleware::class . ":validarMozo");
    $group->get('[/]', [$pedidosController, 'listarPedidos']);
    $group->get('/productos', [$pedidosController, 'listarProductosEnPedidos']);
    $group->get('/borrar', [$pedidosController, 'borrarPedidoPorId'])->add(\AuthTokenMiddleware::class . ":validarMozo");
    $group->post('/modificar', [$pedidosController, 'modificarPedidoPorId'])->add(\AuthTokenMiddleware::class . ":validarMozo");
    $group->get('/verTiempoEspera', [$pedidosController, 'verTiempoEspera']);
    $group->get('/consultarPedidosListosYServir', [$pedidosController, 'consultarPedidosListosYServir'])->add(\AuthTokenMiddleware::class . ":validarMozo");
    $group->get('/pagarPedido', [$pedidosController, 'pagarPedido'])->add(\AuthTokenMiddleware::class . ":validarMozo");
});

$app->run();
?>