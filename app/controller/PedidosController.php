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
    // ABM
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
        if (!$this->pedidosDAO->codigoDeMesaExisteEnBD($codigoMesa)) {
            return $response->withStatus(400)->withJson(['error' => 'El código de mesa no existe']);
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
            return $response->withStatus(400)->withJson(['error' => 'El parámetro productos debe ser una lista de productos, con su idProducto, cantidad y tiempo de entrega(opcional) Ejemplo: [{id: 1, cantidad: 2, tiempoDeEntrega: 20}].']);
        }
        $tiempoEstimado = 0; // cambiar por el ue vien en el prodcuto
        $maxTiempoEstimado = 0;
        $productos = json_decode($data['productos'], true);
        foreach ($productos as $productoData) {
            $esProductoValido = $this->esProductoValido($productoData);
            if (is_string($esProductoValido)) {
                return $response->withStatus(400)->withJson(['error' => 'Elemento en la lista de productos inválida: ' . $esProductoValido]);
            } else {
                $producto = $this->pedidosDAO->obtenerProductoPorId($productoData['idProducto']);
                if (!$producto) {
                    return "El producto con id: " . $productoData['idProducto'] . " no existe";
                }
                $tiempoEstimado += $producto['tiempoEstimado'];
                $maxTiempoEstimado = max($maxTiempoEstimado, $producto['tiempoEstimado']);
            }
        }
        // Usa el tiempo estimado más grande como tiempo estimado final
        $tiempoEstimado = $maxTiempoEstimado;

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
        // Ejemplo: [{idProducto: 1, cantidad: 2, tiempoDeEntrega: 20}]
        $estadosPermitidos = ['PENDIENTE', 'EN PREPARACION', 'FINALIZADO', 'LISTO PARA SERVIR'];
        if (
            !isset($productoData['idProducto'], $productoData['cantidad'], $productoData['estado'])
        ) {
            return "Tiene que ingresar todos los datos del producto";
        }
        $idProducto = $productoData['idProducto'];
        $cantidad = $productoData['cantidad'];
        if (!is_numeric($idProducto) || !is_numeric($cantidad) || !is_string($productoData['estado'])) {
            return "Tipo de valor ingresado inválido";
        }
        if (!$this->pedidosDAO->obtenerProductoPorId($productoData['idProducto'])) {
            return "El producto con id: " . $idProducto . " no existe";
        }
        if (isset($productoData['tiempoDeEntrega']) && (!is_numeric($productoData['tiempoDeEntrega']) && $productoData['tiempoDeEntrega'] < 1)) {
            return "El tiempo de entrega tiene que ser un número válido mayor a 0";
        }
        $estado = strtoupper($productoData['estado']);
        if (!in_array($estado, $estadosPermitidos)) {
            return 'Estado incorrecto. Debe ser PENDIENTE, EN PREPARACION, LISTO PARA SERVIR ó FINALIZADO.';
        }
        $stock = $this->pedidosDAO->obtenerStockPorIdProducto($idProducto);
        if ($stock < $cantidad) {
            return "No hay stock suficiente del producto con id: " . $idProducto . ". Sólo quedan disponibles: " . $stock . " unidades del producto";
        }
        return true;
    }

    public function borrarPedidoPorId(ServerRequest $request, ResponseInterface $response)
    {
        $parametros = $request->getQueryParams();
        $id = $parametros['id'] ?? null;
        if ($id == null || !is_string($id)) {
            return $response->withStatus(404)->withJson(['error' => 'Debe ingresar el ID del pedido que desea borrar.']);
        }
        $pedidoExistente = $this->pedidosDAO->obtenerPedidoPorId($id);
        if (!$pedidoExistente) {
            return $response->withStatus(404)->withJson(['error' => 'Pedido no encontrado']);
        }
        $borrado = $this->pedidosDAO->borrarPedidoPorId($id);
        if ($borrado) {
            return $response->withStatus(200)->withJson(['mensaje' => 'Pedido borrado']);
        } else {
            return $response->withStatus(500)->withJson(['error' => 'No se pudo borrar el Pedido']);
        }
    }
    public function modificarPedidoPorId(ServerRequest $request, ResponseInterface $response)
    {
        $parametros = $request->getParsedBody();
        $id = $parametros['id'] ?? null;
        $fotoDeLaMesa = $_FILES['fotoDeLaMesa']['full_path'] ?? null;
        $tiempoEntrega = $parametros['tiempoEntrega'] ?? null;
        $estado = $parametros['estado'] ?? null;
        $estadosPermitidos = ['PENDIENTE', 'EN PREPARACION', 'FINALIZADO', 'LISTO PARA SERVIR'];

        if ($id == null) {
            return $response->withStatus(404)->withJson(['error' => 'Debe ingresar el ID del pedido que desea modificar.']);
        }
        $pedidoExistente = $this->pedidosDAO->obtenerPedidoPorId($id);
        if (!$pedidoExistente) {
            return $response->withStatus(404)->withJson(['error' => 'Pedido no encontrado']);
        }
        if ($fotoDeLaMesa == null && $tiempoEntrega == null && $estado == null) {
            return $response->withStatus(404)->withJson(['error' => 'Debe ingresar algun campo que desee modificar. Los campos permitidos para modificar son: fotoDeLaMesa, tiempoEntrega y estado.']);
        }
        if ($fotoDeLaMesa !== null) {
            $imageType = $_FILES['fotoDeLaMesa']['type'];
            if (stripos($imageType, 'jpg') === false && stripos($imageType, 'jpeg') === false) {
                return $response->withStatus(400)->withJson(['error' => 'La foto de la mesa debe ser un archivo JPG o JPEG válido.']);
            }
        }
        if ($estado !== null) {
            $estado = strtoupper($estado);
            if (!in_array($estado, $estadosPermitidos)) {
                return $response->withStatus(400)->withJson(['error' => 'Estado incorrecto. Debe ser de tipo: PENDIENTE, PROCESO ó FINALIZADO']);
            }
        }
        if ($tiempoEntrega !== null && (!is_numeric($tiempoEntrega) || $tiempoEntrega < 1)) {
            return $response->withStatus(400)->withJson(['error' => 'El tiempo de entrega debe estar expresado en minutos y ser un número válido mayor a 0.']);
        }

        $nuevosDatos = [
            'ID' => $pedidoExistente['ID'],
            'idCliente' => $pedidoExistente['idCliente'],
            'codigoMesa' => $pedidoExistente['codigoMesa'],
            'estado' => $estado ?? $pedidoExistente['estado'],
            'fotoDeLaMesa' => $fotoDeLaMesa ?? $pedidoExistente['fotoDeLaMesa'],
            'tiempoDeEntrega' => $tiempoEntrega ?? $pedidoExistente['tiempoDeEntrega'],
            'tiempoEstimado' => $pedidoExistente['tiempoEstimado'],
            'activo' => $pedidoExistente['activo'],
        ];
        $modificado = $this->pedidosDAO->modificarProductoPorId($id, $nuevosDatos);
        if ($modificado) {
            return $response->withStatus(200)->withJson(['mensaje' => 'Producto modificado']);
        } else {
            return $response->withStatus(500)->withJson(['error' => 'No se pudo modificar el producto']);
        }
    }

    public function verTiempoEspera(ServerRequest $request, ResponseInterface $response)
    {
        $parametros = $request->getQueryParams();
        $numeroDePedido = $parametros['numeroDePedido'] ?? null;
        $codigoMesa = $parametros['codigoMesa'] ?? null;

        if ($numeroDePedido == null || $codigoMesa == null) {
            return $response->withStatus(404)->withJson(['error' => 'Debe ingresar el número de pedido y el código de mesa a consultar.']);
        }

        $tiempoEstimado = $this->pedidosDAO->obtenerTiempoEstimadoPorPedidoYMesa($numeroDePedido, $codigoMesa);

        if ($tiempoEstimado !== null) {
            return $response->withStatus(200)->withJson(['tiempoEstimado' => $tiempoEstimado]);
        } else {
            return $response->withStatus(404)->withJson(['error' => 'No se encontró el tiempo estimado para el pedido y la mesa proporcionados.']);
        }
    }

    // Listados
    public function listarPedidos(ServerRequest $request, ResponseInterface $response)
    {
        $pedidos = $this->pedidosDAO->listarPedidos();

        if ($pedidos) {
            return $response->withStatus(200)->withJson($pedidos);
        } else {
            return $response->withStatus(404)->withJson(['error' => 'No se encontraron pedidos']);
        }
    }
    public function listarProductosEnPedidos(ServerRequest $request, ResponseInterface $response)
    {
        $pedidos = $this->pedidosDAO->listarProductosEnPedidos();

        if ($pedidos) {
            return $response->withStatus(200)->withJson($pedidos);
        } else {
            return $response->withStatus(404)->withJson(['error' => 'No se encontraron productos']);
        }
    }
    public function consultarPedidosListosYServir(ServerRequest $request, ResponseInterface $response)
    {
        try {
            $productosListos = $this->pedidosDAO->obtenerProductosListos();

            if ($productosListos) {
                foreach ($productosListos as $producto) {
                    $idPedido = $producto['idPedido'];
                    $this->pedidosDAO->cambiarEstadoPedidoyMesa($idPedido, 'cliente comiendo');
                }
                return $response->withStatus(200)->withJson(['mensaje' => 'Pedidos verificados y mesas servidas']);
            } else {
                return $response->withStatus(404)->withJson(['error' => 'No se encontraron productos listos para servir']);
            }
        } catch (Exception $e) {
            return $response->withStatus(500)->withJson(['error' => 'Error al verificar pedidos y servir mesas']);
        }
    }
    public function pagarPedido(ServerRequest $request, ResponseInterface $response){
        $parametros = $request->getQueryParams();
        $idPedido = $parametros['idPedido'] ?? null;
        if ($idPedido == null || !is_string($idPedido)) {
            return $response->withStatus(404)->withJson(['error' => 'Debe ingresar el idPedido del pedido que desea pagar.']);
        }
        $pedidoExistente = $this->pedidosDAO->obtenerPedidoPorId($idPedido);
        if (!$pedidoExistente) {
            return $response->withStatus(404)->withJson(['error' => 'Pedido no encontrado']);
        }
        $seModifico = $this->pedidosDAO->cambiarEstadoPedidoyMesa($idPedido, 'cliente pagando');
        if ($seModifico) {
            return $response->withStatus(200)->withJson(['mensaje' => 'Pedido con cliente pagando']);
        } else {
            return $response->withStatus(500)->withJson(['error' => 'No se pudo actualizar el estado del Pedido']);
        }
    }
}
