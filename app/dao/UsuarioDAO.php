<?php

class UsuarioDAO
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }
    public function login($usuario, $contrasena)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM usuarios WHERE usuario = :usuario AND activo = 1");
            $stmt->execute(['usuario' => strtolower($usuario)]);

            $usuarioEncontrado = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($usuarioEncontrado && password_verify($contrasena, $usuarioEncontrado['contrasena'])) {
                return $usuarioEncontrado;
            } else {
                return null;
            }
        } catch (PDOException $e) {
            echo 'Error en la consulta: ' . $e->getMessage();
            return null;
        }
    }
    // ABM
    public function crearUsuario($usuario, $contrasena, $nombre, $tipo, $cantidadOperaciones)
    {
        try {
            $contrasenaHash = password_hash($contrasena, PASSWORD_DEFAULT);
            $horaActual = date('Y-m-d H:i:s');
            $stmt = $this->pdo->prepare("INSERT INTO usuarios (usuario, contrasena, nombre, tipo, cantidadOperaciones, activo, horaDeAlta, horaDeBaja) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([strtolower($usuario), $contrasenaHash, strtolower($nombre), strtolower($tipo), $cantidadOperaciones, 1, $horaActual, null]);
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            echo 'Error al insertar usuario: ' . $e->getMessage();
            return false;
        }
    }
    public function borrarUsuario($id)
    {
        try {
            $horaBaja = date('Y-m-d H:i:s');
            $stmt = $this->pdo->prepare("UPDATE usuarios SET activo = 0, horaDeBaja = ? WHERE ID = ?");
            $stmt->execute([$horaBaja, $id]);
            return true;
        } catch (PDOException $e) {
            echo 'Error al borrar usuario: ' . $e->getMessage();
            return false;
        }
    }
    public function modificarUsuario($id, $nuevosDatos)
    {
        try {
            $campos = '';
            $valores = [];
            foreach ($nuevosDatos as $campo => $valor) {
                $campos .= "$campo = ?, ";
                $valores[] = $valor;
            }
            $campos = rtrim($campos, ', ');

            $stmt = $this->pdo->prepare("UPDATE usuarios SET $campos WHERE ID = ?");
            $valores[] = $id;
            $stmt->execute($valores);

            return true;
        } catch (PDOException $e) {
            echo 'Error al modificar usuario: ' . $e->getMessage();
            return false;
        }
    }
    // Listados
    public function obtenerUsuarios()
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM usuarios");
            $stmt->execute();
            $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $usuarios;
        } catch (PDOException $e) {
            echo 'Error al listar usuarios: ' . $e->getMessage();
            return false;
        }
    }
    public function obtenerUsuarioPorId($id)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM usuarios WHERE ID = ? AND activo = 1");
            $stmt->execute([$id]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            return $usuario;
        } catch (PDOException $e) {
            echo 'Error al obtener usuario por ID: ' . $e->getMessage();
            return false;
        }
    }
    public function obtenerUsuario($usuario)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM usuarios WHERE usuario = :usuario AND activo = 1");
            $stmt->execute(['usuario' => $usuario]);

            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($usuario) {
                return $usuario;
            } else {
                return null;
            }
        } catch (PDOException $e) {
            echo 'Error en la consulta: ' . $e->getMessage();
            return null;
        }
    }
}
?>