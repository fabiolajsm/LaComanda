<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Response;

class AuthMesaMiddleware
{
    public function validarAltaMesa(Request $request, RequestHandler $handler): ResponseInterface
    {
        $parametros = $request->getParsedBody();
        $cargoEmpleado = $parametros['cargoEmpleado'] ?? null;

        if ($cargoEmpleado === 'socio') {
            $response = $handler->handle($request);
        } else {
            $response = new Response();
            $payload = json_encode(array('mensaje' => 'No sos Socio, no puedes dar de alta una mesa'));
            $response->getBody()->write($payload);
        }
        return $response;
    }
    public function validarModificacionMesa(Request $request, RequestHandler $handler): ResponseInterface
    {
        $parametros = $request->getParsedBody();
        $cargoEmpleado = $parametros['cargoEmpleado'] ?? null;
        $estado = $parametros['estado'] ?? "";
        $estadosPermitidos = ['esperando', 'comiendo', 'pagando', 'cerrada'];

        if ($cargoEmpleado === 'socio' || $cargoEmpleado == 'mozo' && $estado !== 'cerrada') {
            $response = $handler->handle($request);
        } else {
            $response = new Response();
            if ($cargoEmpleado === null) {
                $mensaje = 'Tiene que ingresar un cargoEmpleado';
            } else if (!in_array($estado, $estadosPermitidos)) {
                $mensaje = 'Debe ingresar un estado correcto de tipo: esperando, comiendo, pagando o cerrada.';
            } else if ($cargoEmpleado === 'mozo' && $estado === 'cerrada') {
                $mensaje = 'No sos Socio, no puedes modificar cerrar una mesa';
            } else {
                $mensaje = 'No sos Socio ni Mozo, no puedes modificar una mesa';
            }
            $payload = json_encode(array('mensaje' => $mensaje));
            $response->getBody()->write($payload);
        }
        return $response;
    }
}
?>