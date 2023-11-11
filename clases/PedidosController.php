<?php

use \Slim\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

require_once 'PedidosDAO.php';

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
        $codigoMesa = $data['codigoMesa'] ?? "";
        $estado = $data['estado'] ?? "";
        $tiempoEstimado = $data['tiempoEstimado'] ?? "";
        $tiempoDeEntrega = $data['tiempoDeEntrega'] ?? null;
        $fotoDeLaMesa = $_FILES['fotoDeLaMesa']['tmp_name'] ?? null;
        echo json_encode($_FILES['fotoDeLaMesa']) . ' file foto';
        if (empty($idCliente) || empty($codigoMesa) || empty($estado) || empty($tiempoEstimado)) {
            return $response->withStatus(400)->withJson(['error' => 'Completar datos obligatorios: idCliente, codigoMesa, estado y tiempoEstimado.']);
        }

        if (!is_numeric($idCliente)) {
            return $response->withStatus(400)->withJson(['error' => 'El ID del cliente debe ser un número válido.']);
        }

        if (!preg_match("/^\d{5}$/", $codigoMesa)) {
            return $response->withStatus(400)->withJson(['error' => 'El código de la mesa debe ser un digito de 5 caracteres numéricos.']);
        }

        if (empty($estado)) {
            return $response->withStatus(400)->withJson(['error' => 'El estado no puede estar vacío.']);
        }

        if (!preg_match("/^\d+(min|h)$/", $tiempoEstimado)) {
            return $response->withStatus(400)->withJson(['error' => 'El tiempo estimado debe tener el formato correcto, ej. 10min o 1h.']);
        }

        if ($tiempoDeEntrega && !preg_match("/^\d+(min|h)$/", $tiempoDeEntrega)) {
            return $response->withStatus(400)->withJson(['error' => 'El tiempo de entrega debe tener el formato correcto, ej. 10min o 1h.']);
        }
        if ($fotoDeLaMesa !== null) {
            $imageType = $_FILES['fotoDeLaMesa']['type'];
            if (stripos($imageType, 'jpg') === false && stripos($imageType, 'jpeg') === false) {
                return $response->withStatus(400)->withJson(['error' => 'La foto de la mesa debe ser un archivo JPG o JPEG válido.']);
            }
        }

        $idPedido = $this->pedidosDAO->crearPedido($idCliente, $codigoMesa, $estado, $tiempoEstimado, $tiempoDeEntrega, file_get_contents($fotoDeLaMesa));
        if ($idPedido) {
            return $response->withStatus(201)->withJson(['message' => 'Pedido creado', 'id' => $idPedido]);
        } else {
            return $response->withStatus(500)->withJson(['error' => 'No se pudo crear el pedido']);
        }
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
