<?php
// Error Handling
error_reporting(-1);
ini_set('display_errors', 1);

require '../clases/UsuarioDAO.php';
require_once '../clases/UsuarioController.php';
require '../clases/ProductoDAO.php';
require_once '../clases/ProductoController.php';
require '../clases/MesasDAO.php';
require_once '../clases/MesasController.php';
require '../clases/PedidosDAO.php';
require_once '../clases/PedidosController.php';

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

$app->post('/usuarios', function (Request $request, Response $response, $args) use ($usuarioController) {
    $request = $request->withParsedBody($request->getParsedBody());
    return $usuarioController->crearUsuario($request, $response);
});

$app->get('/usuarios', function (Request $request, Response $response, $args) use ($usuarioController) {
    return $usuarioController->listarUsuarios($response);
});

$productoDAO = new ProductoDAO($pdo); 
$productoController = new ProductoController($productoDAO); 
$app->post('/productos', function (Request $request, Response $response) use ($productoController) {
    $request = $request->withParsedBody($request->getParsedBody());
    return $productoController->crearProducto($request, $response);
});

$app->get('/productos', function (Request $request, Response $response) use ($productoController) {
    return $productoController->listarProductos($response);
});

$mesasDAO = new MesasDAO($pdo); 
$mesasController = new MesasController($mesasDAO); 
$app->post('/mesas', function (Request $request, Response $response) use ($mesasController) {
    $request = $request->withParsedBody($request->getParsedBody());
    return $mesasController->crearMesa($request, $response);
});

$app->get('/mesas', function (Request $request, Response $response) use ($mesasController) {
    return $mesasController->listarMesas($response);
});

$pedidosDAO = new PedidosDAO($pdo); 
$pedidosController = new PedidosController($pedidosDAO); 
$app->post('/pedidos', function (Request $request, Response $response) use ($pedidosController) {
    $request = $request->withParsedBody($request->getParsedBody());
    return $pedidosController->crearPedido($request, $response);
});

$app->get('/pedidos', function (Request $request, Response $response) use ($pedidosController) {
    return $pedidosController->listarPedidos($response);
});

$app->run();
?>