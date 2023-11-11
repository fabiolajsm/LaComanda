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
            $stmt = $this->pdo->prepare("INSERT INTO mesas (codigo, idCliente, estado) VALUES (?, ?, ?)");
            $stmt->execute([$codigoUnico, $idCliente, $estado]);
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
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM mesas WHERE codigo = ?");
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
}
?>