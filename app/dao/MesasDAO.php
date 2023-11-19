<?php

class MesasDAO
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }
    public function crearMesa($idCliente, $estado)
    {
        try {
            $codigoUnico = $this->generarCodigoUnico();
            $stmt = $this->pdo->prepare("INSERT INTO mesas (codigo, idCliente, estado, activo) VALUES (?, ?, ?, ?)");
            $stmt->execute([$codigoUnico, $idCliente, $estado, 1]);
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            echo 'Error al insertar mesa: ' . $e->getMessage();
            return false;
        }
    }

    private function generarCodigoUnico()
    {
        $codigo = str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);

        while ($this->codigoExisteEnBD($codigo)) {
            $codigo = str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        }
        return $codigo;
    }
    private function codigoExisteEnBD($codigo)
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM mesas WHERE codigo = ? AND activo = 1");
        $stmt->execute([$codigo]);
        $count = $stmt->fetchColumn();
        return $count > 0;
    }
    public function obtenerMesas()
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM mesas");
            $stmt->execute();
            $mesas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $mesas;
        } catch (PDOException $e) {
            echo 'Error al listar mesas: ' . $e->getMessage();
            return false;
        }
    }
    public function obtenerMesaPorIdCliente($idCliente)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM mesas WHERE idCliente = ? AND activo = 1");
            $stmt->execute([$idCliente]);
            $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
            return $cliente;
        } catch (PDOException $e) {
            echo 'Error al verificar si el cliente ya tiene mesa asignada: ' . $e->getMessage();
            return false;
        }
    }
    public function modificarEstadoMesa($idMesa, $estado)
    {
        try {
            $stmt = $this->pdo->prepare("UPDATE mesas SET estado = ? WHERE ID = ? AND activo = 1");
            $stmt->execute([$estado, $idMesa]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            echo 'Error al modificar estado de mesa: ' . $e->getMessage();
            return false;
        }
    }
    public function obtenerMesaPorCodigo($codigo)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM mesas WHERE codigo = ? AND activo = 1");
            $stmt->execute([$codigo]);
            $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
            return $cliente;
        } catch (PDOException $e) {
            echo 'Error al verificar si existe la mesa: ' . $e->getMessage();
            return false;
        }
    }
    public function borrarMesaPorCodigo($codigo)
    {
        try {
            $stmt = $this->pdo->prepare("UPDATE mesas SET activo = 0 WHERE codigo = ?");
            $stmt->execute([$codigo]);
            return true;
        } catch (PDOException $e) {
            echo 'Error al borrar mesa: ' . $e->getMessage();
            return false;
        }
    }
}
?>