<?php

use \Slim\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

require_once './dao/PedidosDAO.php';

class PedidosController
{
    private $pedidosDAO;

    public function __construct($pedidosDAO)
    {
        $this->pedidosDAO = $pedidosDAO;
    }

    public function crearPedido(ServerRequest $request, ResponseInterface $response)
    {
        $data = $request->getParsedBody();
        $idCliente = $data['idCliente'] ?? "";
        $codigoMesa = $data['codigoMesa'] ?? 0;
        $estado = $data['estado'] ?? "";
        $tiempoDeEntrega = $data['tiempoDeEntrega'] ?? null;
        $fotoDeLaMesa = $_FILES['fotoDeLaMesa']['full_path'] ?? null;
        $estadosPermitidos = ['PENDIENTE', 'PROCESO', 'FINALIZADO'];
        $productos = json_decode($data['productos'], true) ?? null;

        if (empty($idCliente) || $codigoMesa === 0 || empty($estado) || $productos === null) {
            return $response->withStatus(400)->withJson(['error' => 'Completar datos obligatorios: idCliente, codigoMesa, estado y productos.']);
        }
        if (!is_numeric($idCliente)) {
            return $response->withStatus(400)->withJson(['error' => 'El ID del cliente debe ser un número válido.']);
        }
        if (!is_numeric($codigoMesa)) {
            return $response->withStatus(400)->withJson(['error' => 'El código de la mesa debe ser un número válido.']);
        }
        $estado = strtoupper($estado);
        if (!in_array($estado, $estadosPermitidos)) {
            return $response->withStatus(400)->withJson(['error' => 'Estado incorrecto. Debe ser de tipo: PENDIENTE, PROCESO ó FINALIZADO']);
        }
        if ($fotoDeLaMesa !== null) {
            $imageType = $_FILES['fotoDeLaMesa']['type'];
            if (stripos($imageType, 'jpg') === false && stripos($imageType, 'jpeg') === false) {
                return $response->withStatus(400)->withJson(['error' => 'La foto de la mesa debe ser un archivo JPG o JPEG válido.']);
            }
        }
        if (!is_array($productos)) {
            return $response->withStatus(400)->withJson(['error' => 'El parámetro productos debe ser una lista de productos, con su idProducto, cantidad, tiempo estimado y de entrega(opcional) Ejemplo: [{id: 1, cantidad: 2, tiempoEstimado: 15, tiempoDeEntrega: 20}].']);
        }
        $tiempoEstimado = 0;
        $productos = json_decode($data['productos'], true);
        foreach ($productos as $productoData) {
            $esProductoValido = $this->esProductoValido($productoData);
            if (is_string($esProductoValido)) {
                return $response->withStatus(400)->withJson(['error' => 'Elemento en la lista de productos inválida: ' . $esProductoValido]);
            } else {
                $tiempoEstimado += $productoData['tiempoEstimado'];
            }
        }
        if ($tiempoDeEntrega != null && !is_numeric($tiempoDeEntrega)) {
            return $response->withStatus(400)->withJson(['error' => 'El tiempo de entrega debe tener el formato correcto, ej. 10 (10 minutos).']);
        }

        $idPedido = $this->pedidosDAO->crearPedido($idCliente, $codigoMesa, $estado, json_encode($fotoDeLaMesa), json_encode($productos), $tiempoDeEntrega, $tiempoEstimado);
        if ($idPedido) {
            return $response->withStatus(201)->withJson(['message' => 'Pedido creado', 'id' => $idPedido]);
        } else {
            return $response->withStatus(500)->withJson(['error' => 'No se pudo crear el pedido']);
        }
    }

    private function esProductoValido($productoData)
    {
        // Se espera una lista con el id del producto, cantidad, tiempo estimado y tiempo de entrega(este puede tener valor null) (los tiempos expresados en minutos)
        // Ejemplo: [{id: 1, cantidad: 2, tiempoEstimado: 15, tiempoDeEntrega: 20}]
        $estadosPermitidos = ['PENDIENTE', 'EN PREPARACION', 'FINALIZADO', 'LISTO PARA SERVIR'];
        if (
            !isset($productoData['idProducto'], $productoData['cantidad'], $productoData['tiempoEstimado'], $productoData['tiempoDeEntrega'], $productoData['estado'])
        ) {
            return "Tiene que ingresar todos los datos del producto";
        }
        $idProducto = $productoData['idProducto'];
        $cantidad = $productoData['cantidad'];
        if (!is_numeric($idProducto) || !is_numeric($cantidad) || !is_numeric($productoData['tiempoEstimado']) || !is_numeric($productoData['tiempoDeEntrega']) || !is_string($productoData['estado'])) {
            return "Tipo de valor ingresado inválido";
        }
        $estado = strtoupper($productoData['estado']);
        if (!in_array($estado, $estadosPermitidos)) {
            return 'Estado incorrecto. Debe ser PENDIENTE, EN PREPARACION, LISTO PARA SERVIR ó FINALIZADO.';
        }
        if ($this->pedidosDAO->productoExisteEnBD($productoData['idProducto']) == false) {
            return "El producto con id: " . $idProducto . " no existe";
        }
        $stock = $this->pedidosDAO->obtenerStockPorIdProducto($productoData['cantidad']);
        if ($stock < $cantidad) {
            return "No hay stock suficiente del producto con id: " . $idProducto . ". Sólo quedan disponibles: " . $stock . " unidades del producto";
        }
        return true;
    }

    public function listarPedidos(ResponseInterface $response)
    {
        $pedidos = $this->pedidosDAO->listarPedidos();

        if ($pedidos) {
            return $response->withStatus(200)->withJson($pedidos);
        } else {
            return $response->withStatus(404)->withJson(['error' => 'No se encontraron pedidos']);
        }
    }
}
