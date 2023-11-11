<?php

class PedidosDAO
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function crearPedido($idCliente, $codigoMesa, $estado, $tiempoEstimado, $tiempoDeEntrega, $fotoDeLaMesa)
    {
        try {
            $ID = $this->generarCodigoUnico();
            $stmt = $this->pdo->prepare("INSERT INTO pedidos (ID, idCliente, codigoMesa, estado, tiempoEstimado, tiempoDeEntrega, fotoDeLaMesa) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$ID, $idCliente, $codigoMesa, $estado, $tiempoEstimado, $tiempoDeEntrega, mb_convert_encoding($fotoDeLaMesa, 'UTF-8')]);
            return $ID;
        } catch (PDOException $e) {
            echo 'Error al insertar pedido: ' . $e->getMessage();
            return false;
        }
    }
    public function codigoExisteEnBD($codigo)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM pedidos WHERE id = ?");
            $stmt->execute([$codigo]);
            $count = $stmt->fetchColumn();
            return $count > 0;
        } catch (PDOException $e) {
            echo 'Error al verificar si el código existe en la base de datos: ' . $e->getMessage();
            return false;
        }
    }
    public function generarCodigoUnico()
    {
        $codigo = '';
        $caracteresPermitidos = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        for ($i = 0; $i < 5; $i++) {
            $codigo .= $caracteresPermitidos[rand(0, strlen($caracteresPermitidos) - 1)];
        }
        while ($this->codigoExisteEnBD($codigo)) {
            $codigo = '';
            for ($i = 0; $i < 5; $i++) {
                $codigo .= $caracteresPermitidos[rand(0, strlen($caracteresPermitidos) - 1)];
            }
        }
        return $codigo;
    }
    public function listarPedidos()
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM pedidos");
            $stmt->execute();
            $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($pedidos as &$pedido) {
                foreach ($pedido as $key => $value) {
                    if (is_string($value) && !mb_check_encoding($value, 'UTF-8')) {
                        $pedido[$key] = mb_convert_encoding($value, 'UTF-8', 'auto');
                    }
                }
            }
            return $pedidos;
        } catch (PDOException $e) {
            echo 'Error al listar pedidos: ' . $e->getMessage();
            return false;
        }
    }
}
