<?php

class ProductoDAO
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }
    public function crearProducto($nombre, $precio, $sector, $stock)
    {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO productos (nombre, precio, sector, stock) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nombre, $precio, $sector, $stock]);
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