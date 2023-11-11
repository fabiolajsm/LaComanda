<?php
use \Slim\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

require_once 'UsuarioDAO.php';

class UsuarioController
{
    private $usuarioDAO;

    public function __construct($usuarioDAO)
    {
        $this->usuarioDAO = $usuarioDAO;
    }

    public function crearUsuario(ServerRequest $request, ResponseInterface $response)
    {
        $data = $request->getParsedBody();
        $nombre = $data['nombre'] ?? "";
        $tipo = $data['tipo'] ?? "";
        $cantidadOperaciones = $data['cantidadOperaciones'] ?? null;
        $pendientes = $data['pendientes'] ?? null;
        $tiposPermitidos = ['bartender', 'socio', 'cervecero', 'cocinero', 'mozo'];

        if (empty($nombre) || empty($tipo) || $cantidadOperaciones === null) {
            return $response->withStatus(400)->withJson(['error' => 'Completar datos obligatorios: nombre, tipo y cantidadOperaciones.']);
        }
        $tipo = strtolower($tipo);
        if (!in_array($tipo, $tiposPermitidos)) {
            return $response->withStatus(400)->withJson(['error' => 'Tipo de usuario incorrecto. Debe ser de tipo: bartender, socio, cervecero, cocinero o mozo.']);
        }
        if (!is_numeric($cantidadOperaciones)) {
            return $response->withStatus(400)->withJson(['error' => 'La cantidad de operaciones debe ser un número válido.']);
        }

        $listaPendientes = null;
        if ($pendientes !== null) {
            $listaPendientes = json_decode($pendientes);
            if (!is_array($listaPendientes)) {
                return $response->withStatus(400)->withJson(['error' => 'Pendientes no es un array de objetos válido.']);
            }
            if (is_array($listaPendientes) && count($listaPendientes) > 0) {
                foreach ($listaPendientes as $item) {
                    if (!is_object($item)) {
                        return $response->withStatus(400)->withJson(['error' => 'Pendientes no es un array de objetos válido.']);
                    }
                }
                $formattedPendientes = [];
                foreach ($listaPendientes as $item) {
                    $formattedPendientes[] = (object) $item;
                }
                $listaPendientes = json_encode($formattedPendientes);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return $response->withStatus(400)->withJson(['error' => 'Pendientes no es un JSON válido.']);
                }
            } else {
                $listaPendientes = null;
            }
        }
        $idUsuario = $this->usuarioDAO->crearUsuario($nombre, $tipo, $listaPendientes, $cantidadOperaciones);
        if ($idUsuario) {
            return $response->withStatus(201)->withJson(['message' => 'Usuario creado', 'id' => $idUsuario]);
        } else {
            return $response->withStatus(500)->withJson(['error' => 'No se pudo crear el usuario']);
        }
    }
    public function listarUsuarios(ResponseInterface $response)
    {
        try {
            $usuarios = $this->usuarioDAO->obtenerUsuarios();
            if ($usuarios) {
                return $response->withStatus(200)->withJson($usuarios);
            } else {
                return $response->withStatus(404)->withJson(['error' => 'No se encontraron usuarios']);
            }
        } catch (PDOException $e) {
            return $response->withStatus(500)->withJson(['error' => 'Error en la base de datos']);
        }
    }
}