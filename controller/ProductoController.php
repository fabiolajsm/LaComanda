<?php
use \Slim\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

require_once '../dao/ProductoDAO.php';

class ProductoController
{
    private $productoDAO;

    public function __construct($productoDAO)
    {
        $this->productoDAO = $productoDAO;
    }

    public function crearProducto(ServerRequest $request, ResponseInterface $response)
    {
        $data = $request->getParsedBody();
        $idPedido = $data['idPedido'] ?? "";
        $nombre = $data['nombre'] ?? "";
        $tiempoEstimado = $data['tiempoEstimado'] ?? "";
        $tiempoDeEntrega = $data['tiempoDeEntrega'] ?? null;
        $precio = $data['precio'] ?? 0;
        $estado = $data['estado'] ?? "";
        $sector = $data['sector'] ?? "";
        $estadosPermitidos = ['PENDIENTE', 'PROCESO', 'FINALIZADO']; 
        $sectoresPermitidos = ['A', 'B', 'C', 'D']; 

        if (empty($idPedido) || empty($nombre) || empty($tiempoEstimado) || $precio === 0 || empty($estado) || empty($sector)) {
            return $response->withStatus(400)->withJson(['error' => 'Completar datos obligatorios: idPedido, nombre, tiempoEstimado, precio, estado y sector.']);
        }
        if (!is_string($idPedido)) {
            return $response->withStatus(400)->withJson(['error' => 'El idPedido debe ser un código válido alfanumérico.']);
        }
        if (!is_string($nombre)) {
            return $response->withStatus(400)->withJson(['error' => 'El nombre debe ser un texto válido.']);
        }
        if (!is_numeric($tiempoEstimado)) {
            return $response->withStatus(400)->withJson(['error' => 'El tiempo estimado debe tener el formato correcto expresado en minutos, ej. 10 (10 minutos).']);
        }
        if ($tiempoDeEntrega != null && !is_numeric($tiempoEstimado)) {
            return $response->withStatus(400)->withJson(['error' => 'El tiempo de entrega debe tener el formato correcto, ej. 10 (10 minutos).']);
        }
        if (!is_numeric($precio)) {
            return $response->withStatus(400)->withJson(['error' => 'El precio debe ser un número válido.']);
        }
        $precio = $precio + 0.0;
        $estado = strtoupper($estado);
        if (!in_array($estado, $estadosPermitidos)) {
            return $response->withStatus(400)->withJson(['error' => 'Estado incorrecto. Debe ser de tipo: PENDIENTE, PROCESO ó FINALIZADO']);
        }
        $sector = strtoupper($sector);
        if (!in_array($sector, $sectoresPermitidos)) {
            return $response->withStatus(400)->withJson(['error' => 'Sector incorrecto. Debe ser de tipo: A (barra de tragos y vinos), B (barra de choperas de cerveza artesanal), C (cocina) y D (candy bar/postres artesanales).']);
        }

        $idProducto = $this->productoDAO->crearProducto($idPedido, $nombre, $tiempoEstimado, $tiempoDeEntrega, $precio, $estado, $sector);
        if ($idProducto) {
            return $response->withStatus(201)->withJson(['message' => 'Producto creado', 'id' => $idProducto]);
        } else {
            return $response->withStatus(500)->withJson(['error' => 'No se pudo crear el producto']);
        }
    }

    public function listarProductos(ResponseInterface $response)
    {
        try {
            $productos = $this->productoDAO->obtenerProductos();
            if ($productos) {
                return $response->withStatus(200)->withJson($productos);
            } else {
                return $response->withStatus(404)->withJson(['error' => 'No se encontraron productos']);
            }
        } catch (PDOException $e) {
            return $response->withStatus(500)->withJson(['error' => 'Error en la base de datos']);
        }
    }
}