<?php
use \Slim\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

require_once './dao/ProductoDAO.php';

class ProductoController
{
    private $productoDAO;

    public function __construct($productoDAO)
    {
        $this->productoDAO = $productoDAO;
    }
    // ABM
    public function crearProducto(ServerRequest $request, ResponseInterface $response)
    {
        $data = $request->getParsedBody();
        $nombre = $data['nombre'] ?? "";
        $precio = $data['precio'] ?? null;
        $sector = $data['sector'] ?? "";
        $stock = $data['stock'] ?? null;
        $tiempoEstimado = $data['tiempoEstimado'] ?? null;
        $sectoresPermitidos = ['A', 'B', 'C', 'D'];

        if (empty($nombre) || $precio === null || empty($sector) || $stock == null || $tiempoEstimado === null) {
            return $response->withStatus(400)->withJson(['error' => 'Completar datos obligatorios: stock, nombre, tiempoEstimado, precio y sector.']);
        }
        if (!is_numeric($stock) || $stock < 0) {
            return $response->withStatus(400)->withJson(['error' => 'El stock debe ser un número válido mayor a 0.']);
        }
        if (!is_string($nombre)) {
            return $response->withStatus(400)->withJson(['error' => 'El nombre debe ser un texto válido.']);
        }
        if (!is_numeric($precio) || $precio < 0) {
            return $response->withStatus(400)->withJson(['error' => 'El precio debe ser un número válido mayor a 0.']);
        }
        if (!is_numeric($tiempoEstimado) || $tiempoEstimado < 0) {
            return $response->withStatus(400)->withJson(['error' => 'El tiempo estimado debe estar expresado en minutos y ser un número válido mayor a 0.']);
        }
        $precio = $precio + 0.0;

        $sector = strtoupper($sector);
        if (!in_array($sector, $sectoresPermitidos)) {
            return $response->withStatus(400)->withJson(['error' => 'Sector incorrecto. Debe ser de tipo: A (barra de tragos y vinos), B (barra de choperas de cerveza artesanal), C (cocina) y D (candy bar/postres artesanales).']);
        }

        if ($this->productoDAO->obtenerProducto($nombre, $sector)) {
            return $response->withStatus(400)->withJson(['error' => 'Ya existe el producto: ' . $nombre . ' en el sector ' . strtoupper($sector)]);
        }

        $idProducto = $this->productoDAO->crearProducto($nombre, $precio, $sector, $stock, $tiempoEstimado);
        if ($idProducto) {
            return $response->withStatus(201)->withJson(['message' => 'Producto creado', 'id' => $idProducto]);
        } else {
            return $response->withStatus(500)->withJson(['error' => 'No se pudo crear el producto']);
        }
    }
    public function borrarProductoPorId(ServerRequest $request, ResponseInterface $response)
    {
        $parametros = $request->getQueryParams();
        $id = $parametros['id'] ?? null;
        if ($id == null) {
            return $response->withStatus(404)->withJson(['error' => 'Debe ingresar el ID del producto que desea borrar.']);
        }
        $productoExistente = $this->productoDAO->obtenerProductoPorId($id);
        if (!$productoExistente) {
            return $response->withStatus(404)->withJson(['error' => 'Producto no encontrado']);
        }
        $borrado = $this->productoDAO->borrarProductoPorId($id);
        if ($borrado) {
            return $response->withStatus(200)->withJson(['mensaje' => 'Producto borrado']);
        } else {
            return $response->withStatus(500)->withJson(['error' => 'No se pudo borrar el Producto']);
        }
    }
    public function modificarProductoPorId(ServerRequest $request, ResponseInterface $response)
    {
        $parametros = $request->getParsedBody();
        $id = $parametros['id'] ?? null;
        $nombre = $parametros['nombre'] ?? null;
        $precio = $parametros['precio'] ?? null;
        $stock = $parametros['stock'] ?? null;
        $tiempoEstimado = $parametros['tiempoEstimado'] ?? null;

        if ($id == null) {
            return $response->withStatus(404)->withJson(['error' => 'Debe ingresar el ID del producto que desea modificar.']);
        }
        $productoExistente = $this->productoDAO->obtenerProductoPorId($id);
        if (!$productoExistente) {
            return $response->withStatus(404)->withJson(['error' => 'Producto no encontrado']);
        }
        if ($nombre == null && $precio == null && $stock == null && $tiempoEstimado === null) {
            return $response->withStatus(404)->withJson(['error' => 'Debe ingresar algun campo que desee modificar. Los campos permitidos para modificar son: precio, nombre, stock y tiempoEstimado.']);
        }
        if (!is_numeric($stock) || $stock < 0) {
            return $response->withStatus(400)->withJson(['error' => 'El stock debe ser un número válido mayor a 0.']);
        }
        if (!is_string($nombre)) {
            return $response->withStatus(400)->withJson(['error' => 'El nombre debe ser un texto válido.']);
        }
        if (!is_numeric($precio) || $precio < 0) {
            return $response->withStatus(400)->withJson(['error' => 'El precio debe ser un número válido mayor a 0.']);
        }
        if (!is_numeric($tiempoEstimado) || $tiempoEstimado < 0) {
            return $response->withStatus(400)->withJson(['error' => 'El tiempo estimado debe estar expresado en minutos y ser un número válido mayor a 0.']);
        }
        $precio = $precio + 0.0;

        $nuevosDatos = [
            'ID' => $productoExistente['ID'],
            'nombre' => $nombre ?? $productoExistente['nombre'],
            'precio' => $precio ?? $productoExistente['precio'],
            'sector' => $productoExistente['sector'],
            'stock' => $stock ?? $productoExistente['stock'],
            'tiempoEstimado' => $tiempoEstimado ?? $productoExistente['tiempo$tiempoEstimado'],
            'activo' => $productoExistente['activo'],
        ];
        $modificado = $this->productoDAO->modificarProducto($id, $nuevosDatos);
        if ($modificado) {
            return $response->withStatus(200)->withJson(['mensaje' => 'Producto modificado']);
        } else {
            return $response->withStatus(500)->withJson(['error' => 'No se pudo modificar el producto']);
        }
    }
    // Listados
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