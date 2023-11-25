<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

require_once './utils/AutentificadorJWT.php';

class AuthTokenMiddleware
{
    private function verificarYObtenerData($token)
    {
        try {
            AutentificadorJWT::VerificarToken($token);
            return AutentificadorJWT::ObtenerData($token);
        } catch (Exception $e) {
            return null;
        }
    }

    private function validarPermisos(Request $request, RequestHandler $handler, $cargosPermitidos = []): ResponseInterface
    {
        $header = $request->getHeaderLine('Authorization');
        $mensaje = 'ok';

        if (strpos($header, 'Bearer') !== false) {
            $token = trim(explode("Bearer", $header)[1]);
            $jsonData = $this->verificarYObtenerData($token);

            if ($jsonData !== null) {
                $cargoEmpleado = $jsonData->cargoEmpleado;
                if (!empty($cargosPermitidos)) {
                    if (!in_array(strtolower($cargoEmpleado), $cargosPermitidos)) {
                        $mensaje = 'No puedes hacer esta accion, solo pueden los usuarios de tipo: ' . implode(', ', $cargosPermitidos) . '.';
                    }
                }
            } else {
                $mensaje = 'El token no es valido.';
            }
        } else {
            $mensaje = 'Formato de token invalido en el encabezado de autorizacion.';
        }

        if ($mensaje === 'ok') {
            return $handler->handle($request);
        } else {
            $response = new Response();
            $payload = json_encode(['mensaje' => $mensaje]);
            $response->getBody()->write($payload);
            return $response;
        }
    }

    public function validarSocio(Request $request, RequestHandler $handler): ResponseInterface
    {
        return $this->validarPermisos($request, $handler, ['socio']);
    }
    public function validarMozo(Request $request, RequestHandler $handler): ResponseInterface
    {
        return $this->validarPermisos($request, $handler, ['mozo']);
    }
    public function validarEmpleado(Request $request, RequestHandler $handler): ResponseInterface
    {
        return $this->validarPermisos($request, $handler, ['bartender', 'cervecero', 'cocinero']);
    }
    public function validarMozoOSocio(Request $request, RequestHandler $handler): ResponseInterface
    {
        return $this->validarPermisos($request, $handler, ['socio', 'mozo']);
    }
}
