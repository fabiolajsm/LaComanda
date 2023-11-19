<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Response;

class AuthUsuarioMiddleware
{
    public function validarSocio(Request $request, RequestHandler $handler): ResponseInterface
    {
        $data = $request->getParsedBody();
        $cargoEmpleado = $data['cargoEmpleado'] ?? null;
        if ($cargoEmpleado === 'socio') {
            $response = $handler->handle($request);
        } else {
            $mensaje = 'No sos Socio, no puedes dar de alta ni de baja un usuario';
            if ($cargoEmpleado === null) {
                $mensaje = 'Debe ingresar cargoEmpleado';
            }
            $response = new Response();
            $payload = json_encode(array('mensaje' => $mensaje));
            $response->getBody()->write($payload);
        }
        return $response;
    }
    public function validarSocioParametros(Request $request, RequestHandler $handler): ResponseInterface
    {
        $parametros = $request->getQueryParams();

        $cargoEmpleado = $parametros['cargoEmpleado'] ?? null;
        if ($cargoEmpleado === 'socio') {
            $response = $handler->handle($request);
        } else {
            $mensaje = 'No sos Socio, no puedes ver todos los usuarios';
            if ($cargoEmpleado === null) {
                $mensaje = 'Debe ingresar cargoEmpleado';
            }
            $response = new Response();
            $payload = json_encode(array('mensaje' => $mensaje));
            $response->getBody()->write($payload);
        }
        return $response;
    }
}
?>