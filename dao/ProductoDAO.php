<?php

class ProductoDAO
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }
    public function crearProducto($idPedido, $nombre, $tiempoEstimado, $tiempoDeEntrega, $precio, $estado, $sector)
    {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO productos (idPedido, nombre, tiempoEstimado, tiempoDeEntrega, precio, estado, sector) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$idPedido, $nombre, $tiempoEstimado, $tiempoDeEntrega, $precio, $estado, $sector]);
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            echo 'Error al insertar producto: ' . $e->getMessage();
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
}
?>