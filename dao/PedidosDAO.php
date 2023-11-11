<?php

class PedidosDAO
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function crearPedido($idCliente, $codigoMesa, $estado, $fotoDeLaMesa, $productos, $tiempoDeEntrega, $tiempoEstimado)
    {
        $this->pdo->beginTransaction();
        try {
            $ID = $this->generarCodigoUnico();
            $stmtPedido = $this->pdo->prepare("INSERT INTO pedidos (ID, idCliente, codigoMesa, estado, fotoDeLaMesa, productos, tiempoDeEntrega, tiempoEstimado) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmtPedido->execute([$ID, $idCliente, $codigoMesa, $estado, $fotoDeLaMesa, $productos, $tiempoDeEntrega, $tiempoEstimado]);

            $productosPedido = json_decode($productos, true);
            foreach ($productosPedido as $producto) {
                $idProducto = $producto['idProducto'];
                $cantidad = $producto['cantidad'];
                $stmtActualizarStock = $this->pdo->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?");
                $stmtActualizarStock->execute([$cantidad, $idProducto]);
            }
            $this->pdo->commit();

            return $ID;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
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
                    if (is_string($value) && in_array($key, ['fotoDeLaMesa', 'productos'])) {
                        $decodedValue = json_decode($value);
                        $pedido[$key] = ($decodedValue !== null) ? $decodedValue : $value;
                    }
                }
            }
            return $pedidos;
        } catch (PDOException $e) {
            echo 'Error al listar pedidos: ' . $e->getMessage();
            return false;
        }
    }
    public function obtenerStockPorIdProducto($idProducto)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT stock FROM productos WHERE ID = ?");
            $stmt->execute([$idProducto]);
            $stock = $stmt->fetchColumn();
            return ($stock !== false) ? $stock : 0;
        } catch (PDOException $e) {
            echo 'Error al obtener el stock del producto: ' . $e->getMessage();
            return false;
        }
    }

    public function productoExisteEnBD($idProducto)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM productos WHERE ID = ?");
            $stmt->execute([$idProducto]);
            $count = $stmt->fetchColumn();
            return $count > 0;
        } catch (PDOException $e) {
            echo 'Error al verificar si el producto existe en la base de datos: ' . $e->getMessage();
            return false;
        }
    }

}
