<?php
use \Slim\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

require_once 'ProductoDAO.php';

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
        $nombre = $data['nombre'] ?? "";
        $sector = $data['sector'] ?? "";
        $sectoresPermitidos = ['A', 'B', 'C', 'D']; 

        if (empty($nombre) || empty($sector)) {
            return $response->withStatus(400)->withJson(['error' => 'Completar datos obligatorios: nombre y sector.']);
        }
        $sector = strtoupper($sector);
        if (!in_array($sector, $sectoresPermitidos)) {
            return $response->withStatus(400)->withJson(['error' => 'Sector incorrecto. Debe ser de tipo: A (barra de tragos y vinos), B (barra de choperas de cerveza artesanal), C (cocina) y D (candy bar/postres artesanales).']);
        }

        $idProducto = $this->productoDAO->crearProducto($nombre, $sector);
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