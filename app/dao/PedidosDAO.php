<?php

class PedidosDAO
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function crearProductoDelPedido($idPedido, $productosPedido, $tiempoEstimado)
    {
        try {
            foreach ($productosPedido as $producto) {
                $idProducto = $producto['idProducto'];
                $cantidad = $producto['cantidad'];

                $stmtProductos = $this->pdo->prepare("INSERT INTO pedidos_productos (idPedido, idProducto, cantidad, tiempoEstimado, tiempoDeEntrega, estado) VALUES (?, ?, ?, ?, ?, ?)");
                $resultInsertProductos = $stmtProductos->execute([$idPedido, $idProducto, $cantidad, $tiempoEstimado, $producto['tiempoDeEntrega'] ?? null, $producto['estado']]);

                if (!$resultInsertProductos) {
                    $errorInfo = $stmtProductos->errorInfo();
                    echo 'Error al insertar productos en la tabla pedidos_productos: ' . $errorInfo[2];
                    throw new Exception("Error al insertar productos en la tabla pedidos_productos.");
                }
                $stmtActualizarStock = $this->pdo->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?");
                $resultActualizarStock = $stmtActualizarStock->execute([$cantidad, $idProducto]);
                if (!$resultActualizarStock) {
                    $errorInfo = $stmtActualizarStock->errorInfo();
                    echo 'Error al actualizar el stock en la tabla productos: ' . $errorInfo[2];
                    throw new Exception("Error al actualizar el stock en la tabla productos.");
                }
            }
            return true;
        } catch (Exception $e) {
            echo 'Error al insertar productos del pedido: ' . $e->getMessage();
            return false;
        }
    }
    public function crearPedido($idCliente, $codigoMesa, $estado, $fotoDeLaMesa, $productos, $tiempoDeEntrega, $tiempoEstimado)
    {
        $this->pdo->beginTransaction();
        try {
            $ID = $this->generarCodigoUnico();
            $stmtPedido = $this->pdo->prepare("INSERT INTO pedidos (ID, idCliente, codigoMesa, estado, fotoDeLaMesa, tiempoDeEntrega, tiempoEstimado, activo) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmtPedido->execute([$ID, $idCliente, $codigoMesa, $estado, $fotoDeLaMesa, $tiempoDeEntrega, $tiempoEstimado, 1]);

            $this->crearProductoDelPedido($ID, json_decode($productos, true), $tiempoEstimado);
            $this->pdo->commit();
            return $ID;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            echo 'Error al insertar pedido: ' . $e->getMessage();
            return false;
        }
    }
    public function borrarPedidoPorId($id)
    {
        $this->pdo->beginTransaction();

        try {
            $stmtUpdateProductos = $this->pdo->prepare("UPDATE pedidos_productos SET estado = 'cancelado' WHERE idPedido = ?");
            $stmtUpdateProductos->execute([$id]);

            $stmtUpdatePedido = $this->pdo->prepare("UPDATE pedidos SET activo = 0 WHERE ID = ?");
            $stmtUpdatePedido->execute([$id]);

            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            echo 'Error al borrar pedido: ' . $e->getMessage();
            return false;
        }
    }
    public function modificarProductoPorId($id, $nuevosDatos)
    {
        try {
            $campos = '';
            $valores = [];
            foreach ($nuevosDatos as $campo => $valor) {
                $campos .= "$campo = ?, ";
                $valores[] = $valor;
            }
            $campos = rtrim($campos, ', ');

            $stmt = $this->pdo->prepare("UPDATE pedidos SET $campos WHERE ID = ? AND activo = 1");
            $valores[] = $id;
            $stmt->execute($valores);

            return true;
        } catch (PDOException $e) {
            echo 'Error al modificar pedido: ' . $e->getMessage();
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
                    if (is_string($value) && in_array($key, ['fotoDeLaMesa'])) {
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
    public function obtenerPedidoPorId($id)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM pedidos WHERE ID = ? AND activo = 1");
            $stmt->execute([$id]);
            $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
            return $pedido;
        } catch (PDOException $e) {
            echo 'Error al verificar si el pedido existe en la base de datos: ' . $e->getMessage();
            return false;
        }
    }
    public function listarProductosEnPedidos()
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM pedidos_productos");
            $stmt->execute();
            $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    public function obtenerProductoPorId($idProducto)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM productos WHERE ID = ? AND activo = 1");
            $stmt->execute([$idProducto]);
            $producto = $stmt->fetch(PDO::FETCH_ASSOC);
            return $producto;
        } catch (PDOException $e) {
            echo 'Error al verificar si el producto existe en la base de datos: ' . $e->getMessage();
            return false;
        }
    }
    public function codigoDeMesaExisteEnBD($codigo)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM mesas WHERE codigo = ? AND activo = 1"); 
            $stmt->execute([$codigo]);
            $mesa = $stmt->fetch(PDO::FETCH_ASSOC);
            return $mesa;
        } catch (PDOException $e) {
            echo 'Error al obtener mesa por codigo: ' . $e->getMessage();
            return false;
        }
    }
}
