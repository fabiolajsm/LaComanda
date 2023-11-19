<?php
use \Slim\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

require_once './dao/UsuarioDAO.php';
require './utils/AutentificadorJWT.php';

class UsuarioController
{
    private $usuarioDAO;

    public function __construct($usuarioDAO)
    {
        $this->usuarioDAO = $usuarioDAO;
    }
    // Login 
    public function login(ServerRequest $request, ResponseInterface $response)
    {
        try {
            $parametros = $request->getQueryParams();
            $usuario = $parametros['usuario'] ?? null;
            $contrasena = $parametros['contrasena'] ?? null;

            if ($usuario == null || $contrasena == null || empty($usuario) || empty($contrasena)) {
                return $response->withStatus(404)->withJson(['error' => 'Debe ingresar el  del usuario y contrasena.']);
            }
            $usuarioEncontrado = $this->usuarioDAO->login($usuario, $contrasena);
            if ($usuarioEncontrado) {
                $datos = array('usuario' => $usuario, 'cargoEmpleado' => $usuarioEncontrado['tipo']);
                $token = AutentificadorJWT::CrearToken($datos);
                $payload = array('jwt' => $token);
                return $response->withStatus(200)->withJson($payload);
            } else {
                return $response->withStatus(404)->withJson(['error' => 'No se encontraron usuarios']);
            }
        } catch (PDOException $e) {
            return $response->withStatus(500)->withJson(['error' => 'Error en la base de datos']);
        }
    }
    // A, B, M
    public function altaUsuario(ServerRequest $request, ResponseInterface $response)
    {
        $data = $request->getParsedBody();
        $usuario = $data['usuario'] ?? "";
        $contrasena = $data['contrasena'] ?? "";
        $nombre = $data['nombre'] ?? "";
        $tipo = $data['tipo'] ?? "";
        $cantidadOperaciones = $data['cantidadOperaciones'] ?? null;
        $tiposPermitidos = ['bartender', 'socio', 'cervecero', 'cocinero', 'mozo'];

        if (empty($usuario) || empty($contrasena) || empty($nombre) || empty($tipo) || $cantidadOperaciones === null) {
            return $response->withStatus(400)->withJson(['error' => 'Completar datos obligatorios: usuario, contrasena, nombre, tipo y cantidadOperaciones.']);
        }
        if ($this->usuarioDAO->obtenerUsuario($usuario)) {
            return $response->withStatus(400)->withJson(['error' => 'Ya existe el usuario: ' . $usuario]);
        }
        $tipo = strtolower($tipo);
        if (!in_array($tipo, $tiposPermitidos)) {
            return $response->withStatus(400)->withJson(['error' => 'Tipo de usuario incorrecto. Debe ser de tipo: bartender, socio, cervecero, cocinero o mozo.']);
        }
        if (!is_numeric($cantidadOperaciones)) {
            return $response->withStatus(400)->withJson(['error' => 'La cantidad de operaciones debe ser un número válido.']);
        }

        $idUsuario = $this->usuarioDAO->crearUsuario($usuario, $contrasena, $nombre, $tipo, $cantidadOperaciones);
        if ($idUsuario) {
            return $response->withStatus(201)->withJson(['mensaje' => 'Usuario creado', 'id' => $idUsuario]);
        } else {
            return $response->withStatus(500)->withJson(['error' => 'No se pudo crear el usuario']);
        }
    }
    public function borrarUsuarioPorId(ServerRequest $request, ResponseInterface $response)
    {
        $parametros = $request->getQueryParams();
        $id = $parametros['id'] ?? null;
        if ($id == null) {
            return $response->withStatus(404)->withJson(['error' => 'Debe ingresar el ID del usuario que desea borrar.']);
        }
        $usuarioExistente = $this->usuarioDAO->obtenerUsuarioPorId($id);
        if (!$usuarioExistente) {
            return $response->withStatus(404)->withJson(['error' => 'Usuario no encontrado']);
        }
        $borrado = $this->usuarioDAO->borrarUsuario($id);
        if ($borrado) {
            return $response->withStatus(200)->withJson(['mensaje' => 'Usuario borrado']);
        } else {
            return $response->withStatus(500)->withJson(['error' => 'No se pudo borrar el usuario']);
        }
    }
    public function modificarUsuarioPorId(ServerRequest $request, ResponseInterface $response)
    {
        $parametros = $request->getParsedBody();
        $id = $parametros['id'] ?? null;
        $usuario = $parametros['usuario'] ?? null;
        $nombre = $parametros['nombre'] ?? null;
        $tipo = $parametros['tipo'] ?? null;
        $cantidadOperaciones = $parametros['cantidadOperaciones'] ?? null;
        $tiposPermitidos = ['bartender', 'socio', 'cervecero', 'cocinero', 'mozo'];

        if ($id == null) {
            return $response->withStatus(404)->withJson(['error' => 'Debe ingresar el ID del usuario que desea modificar.']);
        }
        $usuarioExistente = $this->usuarioDAO->obtenerUsuarioPorId($id);
        if (!$usuarioExistente) {
            return $response->withStatus(404)->withJson(['error' => 'Usuario no encontrado']);
        }
        if ($usuario == null && $nombre == null && $tipo == null && $cantidadOperaciones === null) {
            return $response->withStatus(404)->withJson(['error' => 'Debe ingresar algun campo que desee modificar. Los campos permitidos para modificar son: usuario, nombre, tipo y cantidadOperaciones.']);
        }
        if ($tipo) {
            $tipo = strtolower($tipo);
            if (!in_array($tipo, $tiposPermitidos)) {
                return $response->withStatus(400)->withJson(['error' => 'Tipo de usuario incorrecto. Debe ser de tipo: bartender, socio, cervecero, cocinero o mozo.']);
            }
        }
        if ($cantidadOperaciones !== null && !is_numeric($cantidadOperaciones)) {
            return $response->withStatus(400)->withJson(['error' => 'La cantidad de operaciones debe ser un número entero.']);
        }
        if ($usuario !== null && empty($usuario)) {
            return $response->withStatus(400)->withJson(['error' => 'El campo usuario no puede estar vacío.']);
        }
        if ($nombre !== null && empty($nombre)) {
            return $response->withStatus(400)->withJson(['error' => 'El campo nombre no puede estar vacío.']);
        }
        $nuevosDatos = [
            'ID' => $usuarioExistente['ID'],
            'usuario' => $usuario ?? $usuarioExistente['usuario'],
            'contrasena' => $usuarioExistente['contrasena'],
            'nombre' => $nombre ?? $usuarioExistente['nombre'],
            'tipo' => $tipo ?? $usuarioExistente['tipo'],
            'cantidadOperaciones' => $cantidadOperaciones ?? $usuarioExistente['cantidadOperaciones'],
            'activo' => $usuarioExistente['activo'],
        ];
        $modificado = $this->usuarioDAO->modificarUsuario($id, $nuevosDatos);
        if ($modificado) {
            return $response->withStatus(200)->withJson(['mensaje' => 'Usuario modificado']);
        } else {
            return $response->withStatus(500)->withJson(['error' => 'No se pudo modificar el usuario']);
        }
    }

    // Listados
    public function listarUsuarios(ServerRequest $request, ResponseInterface $response)
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
    public function listarUsuarioPorId(ServerRequest $request, ResponseInterface $response)
    {
        try {
            $parametros = $request->getQueryParams();
            $id = $parametros['id'] ?? null;
            if ($id == null) {
                return $response->withStatus(404)->withJson(['error' => 'Debe ingresar el ID del usuario que desea ver.']);
            }
            $usuarios = $this->usuarioDAO->obtenerUsuarioPorId($id);
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