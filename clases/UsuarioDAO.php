<?php

class UsuarioDAO
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function crearUsuario($nombre, $tipo, $pendientes, $cantidadOperaciones)
    {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO usuarios (nombre, tipo, pendientes, cantidadOperaciones) VALUES (?, ?, ?, ?)");
            $stmt->execute([strtolower($nombre), strtolower($tipo), $pendientes, $cantidadOperaciones]);
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            echo 'Error al insertar usuario: ' . $e->getMessage();
            return false;
        }
    }
    public function obtenerUsuarios()
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM usuarios");
            $stmt->execute();
            $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($usuarios as &$usuario) {
                $pendientes = $usuario['pendientes'];
                if ($pendientes === null) {
                    $usuario['pendientes'] = [];
                } else {
                    $pendientes = json_decode($pendientes, true);
                    if ($pendientes && json_last_error() === JSON_ERROR_NONE) {
                        $usuario['pendientes'] = $pendientes;
                    }
                }
            }
            return $usuarios;
        } catch (PDOException $e) {
            echo 'Error al listar usuarios: ' . $e->getMessage();
            return false;
        }
    }
}
?>