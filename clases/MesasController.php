<?php
use \Slim\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

require_once 'MesasDAO.php';

class MesasController
{
    private $mesasDAO;

    public function __construct($mesasDAO)
    {
        $this->mesasDAO = $mesasDAO;
    }
    public function crearMesa(ServerRequest $request, ResponseInterface $response)
    {
        $data = $request->getParsedBody();
        $idCliente = $data['idCliente'] ?? null;
        $estado = $data['estado'] ?? "";
        $estadosPermitidos = ['esperando', 'comiendo', 'pagando', 'cerrada']; 

        if ($idCliente === null || empty($estado)) {
            return $response->withStatus(400)->withJson(['error' => 'Completar datos obligatorios: idCliente y estado.']);
        }
        $estado = strtolower($estado);
        if (!in_array($estado, $estadosPermitidos)) {
            return $response->withStatus(400)->withJson(['error' => 'Estado incorrecto. Debe ser de tipo: esperando, comiendo, pagando o cerrada.']);
        }

        $idMesa = $this->mesasDAO->crearMesa($idCliente, $estado);
        if ($idMesa) {
            return $response->withStatus(201)->withJson(['message' => 'Mesa creada', 'id' => $idMesa]);
        } else {
            return $response->withStatus(500)->withJson(['error' => 'No se pudo crear la mesa']);
        }
    }
    public function listarMesas(ResponseInterface $response)
    {
        try {
            $mesas = $this->mesasDAO->obtenerMesas();
            if ($mesas) {
                return $response->withStatus(200)->withJson($mesas);
            } else {
                return $response->withStatus(404)->withJson(['error' => 'No se encontraron mesas']);
            }
        } catch (PDOException $e) {
            return $response->withStatus(500)->withJson(['error' => 'Error en la base de datos']);
        }
    }
}