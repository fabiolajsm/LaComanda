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
        $stock = $data['stock'] ?? null;
        $nombre = $data['nombre'] ?? "";
        $precio = $data['precio'] ?? 0;
        $sector = $data['sector'] ?? "";
        $sectoresPermitidos = ['A', 'B', 'C', 'D'];

        if (empty($nombre) || $precio === 0 || empty($sector) || $stock == null) {
            return $response->withStatus(400)->withJson(['error' => 'Completar datos obligatorios: stock, nombre, tiempoEstimado, precio y sector.']);
        }
        if (!is_numeric($stock)) {
            return $response->withStatus(400)->withJson(['error' => 'El stock debe ser un número válido mayor a 0.']);
        }
        if (!is_string($nombre)) {
            return $response->withStatus(400)->withJson(['error' => 'El nombre debe ser un texto válido.']);
        }
        if (!is_numeric($precio)) {
            return $response->withStatus(400)->withJson(['error' => 'El precio debe ser un número válido.']);
        }
        $precio = $precio + 0.0;

        $sector = strtoupper($sector);
        if (!in_array($sector, $sectoresPermitidos)) {
            return $response->withStatus(400)->withJson(['error' => 'Sector incorrecto. Debe ser de tipo: A (barra de tragos y vinos), B (barra de choperas de cerveza artesanal), C (cocina) y D (candy bar/postres artesanales).']);
        }

        $idProducto = $this->productoDAO->crearProducto($nombre, $precio, $sector, $stock);
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