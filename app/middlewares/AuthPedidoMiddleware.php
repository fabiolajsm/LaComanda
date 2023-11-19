<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Response;

class AuthPedidoMiddleware
{
    public function validarAltaPedido(Request $request, RequestHandler $handler): ResponseInterface
    {
        $parametros = $request->getParsedBody();
        $cargoEmpleado = $parametros['cargoEmpleado'] ?? null;
        if ($cargoEmpleado === 'socio' || $cargoEmpleado === 'mozo') {
            $response = $handler->handle($request);
        } else {
            $mensaje = 'No sos Socio ni Mozo, no puedes dar de alta un pedido';
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