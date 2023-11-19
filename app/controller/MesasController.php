<?php
use \Slim\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

require_once './dao/MesasDAO.php';

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
        if($this->mesasDAO->obtenerMesaPorIdCliente($idCliente)) {
            return $response->withStatus(400)->withJson(['error'=> 'Ya existe un cliente vinculado a la mesa']);
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
    public function modificarEstadoMesa(ServerRequest $request, ResponseInterface $response)
    {
        $body = $request->getBody()->getContents();
        $data = json_decode($body, true);

        $idMesa = $data['id'] ?? null;
        $estado = $data['estado'] ?? "";
        $estadosPermitidos = ['esperando', 'comiendo', 'pagando', 'cerrada'];

        if ($idMesa === null || empty($estado)) {
            return $response->withStatus(400)->withJson(['error' => 'Completar datos obligatorios: id y estado.']);
        }

        if (!is_numeric($idMesa)) {
            return $response->withStatus(400)->withJson(['error' => 'El Id debe ser un número válido']);
        }

        $estado = strtolower($estado);

        if (!in_array($estado, $estadosPermitidos)) {
            return $response->withStatus(400)->withJson(['error' => 'Estado incorrecto. Debe ser de tipo: esperando, comiendo, pagando o cerrada.']);
        }

        $resultado = $this->mesasDAO->modificarEstadoMesa($idMesa, $estado);
        if ($resultado) {
            return $response->withStatus(200)->withJson(['exito' => 'Estado de la mesa modificado correctamente']);
        } else {
            return $response->withStatus(404)->withJson(['error' => 'No se encontró la mesa con el ID proporcionado']);
        }
    }
    public function borrarMesa(ServerRequest $request, ResponseInterface $response)
    {
        $parametros = $request->getQueryParams();
        $codigo = $parametros['codigo'] ?? null;
        if ($codigo == null) {
            return $response->withStatus(404)->withJson(['error' => 'Debe ingresar el codigo de la mesa que desea borrar.']);
        }
        $mesaExistente = $this->mesasDAO->obtenerMesaPorCodigo($codigo);
        if (!$mesaExistente) {
            return $response->withStatus(404)->withJson(['error' => 'Mesa no encontrada']);
        }
        $borrado = $this->mesasDAO->borrarMesaPorCodigo($codigo);
        if ($borrado) {
            return $response->withStatus(200)->withJson(['mensaje' => 'Mesa borrada']);
        } else {
            return $response->withStatus(500)->withJson(['error' => 'No se pudo borrar la Mesa']);
        }
    }
}