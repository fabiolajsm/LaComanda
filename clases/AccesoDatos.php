<?php
class AccesoDatos
{
    private static $instance;
    private $pdo;

    private function __construct()
    {
        $dbHost = 'localhost';
        $dbName = 'tu_base_de_datos';
        $dbUser = 'tu_usuario';
        $dbPass = 'tu_contrasena';

        try {
            $this->pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->exec("SET CHARACTER SET utf8");
        } catch (PDOException $e) {
            die("Error: " . $e->getMessage());
        }
    }
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    public function RetornarConsulta($sql)
    {
        return $this->pdo->prepare($sql);
    }
    public function UltimoIdInsertado()
    {
        return $this->pdo->lastInsertId();
    }
}
?>