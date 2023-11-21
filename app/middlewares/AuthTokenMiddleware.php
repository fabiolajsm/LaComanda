<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

require_once './utils/AutentificadorJWT.php';

class AuthTokenMiddleware
{
    private function verificarToken($token)
    {
        try {
            AutentificadorJWT::VerificarToken($token);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function validarSocio(Request $request, RequestHandler $handler): ResponseInterface
    {
        $header = $request->getHeaderLine('Authorization');
        $mensaje = 'ok';
        if (strpos($header, 'Bearer') !== false) {
            $token = trim(explode("Bearer", $header)[1]);
            $esTokenValido = $this->verificarToken($token);
            if (!empty($token)) {
                if (!$esTokenValido) {
                    $mensaje = 'El token no es valido';
                } else {
                    $jsonData = AutentificadorJWT::ObtenerData($token);
                    $cargoEmpleado = $jsonData->cargoEmpleado;
                    if (strtolower($cargoEmpleado) !== 'socio') {
                        $mensaje = 'No sos Socio, no puedes hacer esta accion';
                    }
                }
            } else {
                $mensaje = 'El token está vacío.';
            }
        } else {
            $mensaje = 'Formato de token inválido en el encabezado de autorización.';
        }

        if ($mensaje === 'ok') {
            $response = $handler->handle($request);
        } else {
            $response = new Response();
            $payload = json_encode(array('mensaje' => $mensaje));
            $response->getBody()->write($payload);
        }
        return $response;
    }
}
