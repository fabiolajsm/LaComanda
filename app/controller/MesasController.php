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
        if ($this->mesasDAO->obtenerMesaPorIdCliente($idCliente)) {
            return $response->withStatus(400)->withJson(['error' => 'Ya existe un cliente vinculado a la mesa']);
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
    public function listarMesas(ServerRequest $request, ResponseInterface $response)
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
    public function cerrarMesa(ServerRequest $request, ResponseInterface $response)
    {
        $parametros = $request->getQueryParams();
        $idPedido = $parametros['idPedido'] ?? null;
        if ($idPedido == null || !is_string($idPedido)) {
            return $response->withStatus(404)->withJson(['error' => 'Debe ingresar el idPedido de la mesa que desea cerrar.']);
        }
        $pedidoExistente = $this->mesasDAO->obtenerPedidoPorId($idPedido);
        if (!$pedidoExistente) {
            return $response->withStatus(404)->withJson(['error' => 'Pedido no encontrado']);
        }
        $seModifico = $this->mesasDAO->cerrarMesa($idPedido, 'cerrado');
        if ($seModifico) {
            return $response->withStatus(200)->withJson(['mensaje' => 'Mesa y pedido cerrado']);
        } else {
            return $response->withStatus(500)->withJson(['error' => 'No se pudo actualizar el estado de la mesa y el pedido']);
        }
    }
    public function completarEncuesta(ServerRequest $request, ResponseInterface $response)
    {
        $data = $request->getParsedBody();
        $codigoMesa = $data['codigoMesa'] ?? null;
        $idPedido = $data['idPedido'] ?? null;
        $pMesa = $data['pMesa'] ?? null;
        $pRestaurante = $data['pRestaurante'] ?? null;
        $pMozo = $data['pMozo'] ?? null;
        $pCocinero = $data['pCocinero'] ?? null;
        $comentario = $data['comentario'] ?? null;

        if ($codigoMesa === null || $idPedido === null || $pMesa === null || $pRestaurante === null || $pMozo === null || $pCocinero === null || $comentario === null) {
            return $response->withStatus(400)->withJson(['error' => 'Completar datos obligatorios: codigoMesa, idCliente, pMesa, pRestaurante, pMozo, pCocinero y comentario.']);
        }
        if (!is_numeric($codigoMesa)) {
            return $response->withStatus(400)->withJson(['error' => 'El codigoMesa debe ser una numero valido']);
        }
        if (!$idPedido || !is_string($idPedido) && !empty($idPedido)) {
            return $response->withStatus(400)->withJson(['error' => 'El idPedido debe ser una palabra']);
        }
        if (!$comentario || !is_string($comentario) && !empty($comentario) || strlen($comentario) > 66) {
            return $response->withStatus(400)->withJson(['error' => 'El comentario debe ser una texto valido de hasta 66 caracteres']);
        }
        if (!is_numeric($pMesa) || !is_numeric($pMozo) || !is_numeric($pRestaurante) || !is_numeric($pCocinero)) {
            return $response->withStatus(400)->withJson(['error' => 'Las puntuaciones deben ser numeros validos']);
        }
        if ($pMesa < 1 || $pMesa > 10 || $pMozo < 1 || $pMozo > 10 || $pRestaurante < 1 || $pRestaurante > 10 || $pCocinero < 1 || $pCocinero > 10) {
            return $response->withStatus(400)->withJson(['error' => 'Las puntuaciones deben estar en el rango de 1 al 10']);
        }
        $mesaExistente = $this->mesasDAO->obtenerMesaPorCodigo($codigoMesa);
        if (!$mesaExistente) {
            return $response->withStatus(404)->withJson(['error' => 'Mesa no encontrada']);
        }
        $pedidoExistente = $this->mesasDAO->obtenerPedidoPorId($idPedido);
        if (!$pedidoExistente) {
            return $response->withStatus(404)->withJson(['error' => 'Pedido no encontrado']);
        }
        if ($mesaExistente['estado'] !== 'cerrado' || $pedidoExistente['estado'] !== 'cerrado') {
            return $response->withStatus(404)->withJson(['error' => 'No puede completar la encuesta ya que ']);
        }

        $encuestaCompleta = $this->mesasDAO->completarEncuesta($codigoMesa, $idPedido, $pMesa, $pRestaurante, $pMozo, $pCocinero, $comentario);
        if ($encuestaCompleta) {
            return $response->withStatus(201)->withJson(['mensaje' => 'Encuesta guardada']);
        } else {
            return $response->withStatus(500)->withJson(['error' => 'No se pudo guardar la encuesta']);
        }
    }
    public function obtenerMejoresComentarios(ServerRequest $request, ResponseInterface $response)
    {
        try {
            $mejoresComentarios = $this->mesasDAO->obtenerMejoresComentarios();
            if ($mejoresComentarios) {
                return $response->withStatus(200)->withJson($mejoresComentarios);
            } else {
                return $response->withStatus(404)->withJson(['error' => 'No se encontraron comentarios']);
            }
        } catch (PDOException $e) {
            return $response->withStatus(500)->withJson(['error' => 'Error en la base de datos']);
        }
    }
}