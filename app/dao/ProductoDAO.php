<?php

class ProductoDAO
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }
    public function crearProducto($nombre, $precio, $sector, $stock, $tiempoEstimado)
    {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO productos (nombre, precio, sector, stock, tiempoEstimado, activo) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nombre, $precio, $sector, $stock, $tiempoEstimado, 1]);
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            echo 'Error al insertar producto: ' . $e->getMessage();
            return false;
        }
    }
    public function borrarProductoPorId($id)
    {
        try {
            $stmt = $this->pdo->prepare("UPDATE productos SET activo = 0 WHERE ID = ?");
            $stmt->execute([$id]);
            return true;
        } catch (PDOException $e) {
            echo 'Error al borrar producto: ' . $e->getMessage();
            return false;
        }
    }
    public function modificarProducto($id, $nuevosDatos)
    {
        try {
            $campos = '';
            $valores = [];
            foreach ($nuevosDatos as $campo => $valor) {
                $campos .= "$campo = ?, ";
                $valores[] = $valor;
            }
            $campos = rtrim($campos, ', ');

            $stmt = $this->pdo->prepare("UPDATE productos SET $campos WHERE ID = ?");
            $valores[] = $id;
            $stmt->execute($valores);

            return true;
        } catch (PDOException $e) {
            echo 'Error al modificar Producto: ' . $e->getMessage();
            return false;
        }
    }
    public function obtenerProductos()
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM productos");
            $stmt->execute();
            $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $productos;
        } catch (PDOException $e) {
            echo 'Error al listar productos: ' . $e->getMessage();
            return false;
        }
    }
    public function obtenerProductoPorId($id)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM productos WHERE ID = ? AND activo = 1");
            $stmt->execute([$id]);
            $producto = $stmt->fetch(PDO::FETCH_ASSOC);
            return $producto;
        } catch (PDOException $e) {
            echo 'Error al obtener producto por ID: ' . $e->getMessage();
            return false;
        }
    }
    public function obtenerProducto($nombre, $sector)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM productos WHERE nombre = :nombre AND sector = :sector AND activo = 1");
            $stmt->execute([$nombre, $sector]);

            $producto = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($producto) {
                return $producto;
            } else {
                return null;
            }
        } catch (PDOException $e) {
            echo 'Error en la consulta: ' . $e->getMessage();
            return null;
        }
    }
    public function listarProductosPorSector($sector)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM productos WHERE sector = ? AND activo = 1");
            $stmt->execute([$sector]);
            $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $productos;
        } catch (PDOException $e) {
            echo 'Error al listar productos por sector: ' . $e->getMessage();
            return false;
        }
    }
    public function listarPedidosPorProductos($idsProductos)
    {
        try {
            $inClause = str_repeat('?,', count($idsProductos) - 1) . '?';
            $stmt = $this->pdo->prepare("SELECT * FROM pedidos_productos WHERE idProducto IN ($inClause) AND estado = 'pendiente'");
            $stmt->execute($idsProductos);
            $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $pedidos;
        } catch (PDOException $e) {
            echo 'Error al listar pedidos por productos: ' . $e->getMessage();
            return false;
        }
    }
}
?>