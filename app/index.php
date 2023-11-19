<?php
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
require './middlewares/AuthMesaMiddleware.php';
require './middlewares/AuthPedidoMiddleware.php';
require './middlewares/AuthUsuarioMiddleware.php';

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

// usuarios
$usuarioDAO = new UsuarioDAO($pdo);
$usuarioController = new UsuarioController($usuarioDAO);

$app->get('/usuarios/login', function (Request $request, Response $response, $args) use ($usuarioController) {
    $request = $request->withParsedBody($request->getParsedBody());
    return $usuarioController->login($request, $response);
});

$app->post('/usuarios', function (Request $request, Response $response, $args) use ($usuarioController) {
    $request = $request->withParsedBody($request->getParsedBody());
    return $usuarioController->altaUsuario($request, $response);
})->add(\AuthUsuarioMiddleware::class . ":validarSocio");

$app->get('/usuarios/borrar/', function (Request $request, Response $response, $args) use ($usuarioController) {
    return $usuarioController->borrarUsuarioPorId($request, $response);
})->add(\AuthUsuarioMiddleware::class . ":validarSocioParametros");

$app->get('/usuarios', function (Request $request, Response $response, $args) use ($usuarioController) {
    return $usuarioController->listarUsuarios($response);
})->add(\AuthUsuarioMiddleware::class . ":validarSocioParametros");

$app->get('/usuarios/traerUno/', function (Request $request, Response $response, $args) use ($usuarioController) {
    return $usuarioController->listarUsuarioPorId($request, $response);
})->add(\AuthUsuarioMiddleware::class . ":validarSocioParametros");

$app->put('/usuarios', function (Request $request, Response $response, $args) use ($usuarioController) {
    return $usuarioController->modificarUsuarioPorId($request, $response);
})->add(\AuthUsuarioMiddleware::class . ":validarSocio");

// productos
$productoDAO = new ProductoDAO($pdo);
$productoController = new ProductoController($productoDAO);
$app->post('/productos', function (Request $request, Response $response) use ($productoController) {
    $request = $request->withParsedBody($request->getParsedBody());
    return $productoController->crearProducto($request, $response);
});
$app->get('/productos/borrar/', function (Request $request, Response $response) use ($productoController) {
    $request = $request->withParsedBody($request->getParsedBody());
    return $productoController->borrarProductoPorId($request, $response);
});
$app->get('/productos', function (Request $request, Response $response) use ($productoController) {
    return $productoController->listarProductos($response);
});
$app->put('/productos', function (Request $request, Response $response, $args) use ($productoController) {
    return $productoController->modificarProductoPorId($request, $response);
});

// mesas
$mesasDAO = new MesasDAO($pdo);
$mesasController = new MesasController($mesasDAO);

$app->post('/mesas', function (Request $request, Response $response) use ($mesasController) {
    return $mesasController->crearMesa($request, $response);
})->add(\AuthMesaMiddleware::class . ":validarAltaMesa");
$app->get('/mesas', function (Request $request, Response $response) use ($mesasController) {
    return $mesasController->listarMesas($response);
});
$app->put('/mesas', function (Request $request, Response $response) use ($mesasController) {
    return $mesasController->modificarEstadoMesa($request, $response);
})->add(\AuthMesaMiddleware::class . ":validarModificacionMesa");
$app->get('/mesas/borrar', function (Request $request, Response $response) use ($mesasController) {
    return $mesasController->borrarMesa($request, $response);
});

// pedidos
$pedidosDAO = new PedidosDAO($pdo);
$pedidosController = new PedidosController($pedidosDAO);
$app->post('/pedidos', function (Request $request, Response $response) use ($pedidosController) {
    $request = $request->withParsedBody($request->getParsedBody());
    return $pedidosController->crearPedido($request, $response);
})->add(\AuthPedidoMiddleware::class . ":validarAltaPedido");

$app->get('/pedidos', function (Request $request, Response $response) use ($pedidosController) {
    return $pedidosController->listarPedidos($response);
});
$app->get('/pedidos_productos', function (Request $request, Response $response) use ($pedidosController) {
    return $pedidosController->listarProductosEnPedidos($response);
});
$app->get('/pedidos/borrar', function (Request $request, Response $response) use ($pedidosController) {
    return $pedidosController->borrarPedidoPorId($request, $response);
});
$app->post('/pedido', function (Request $request, Response $response) use ($pedidosController) {
    return $pedidosController->modificarPedidoPorId($request, $response);
});

$app->run();
?>