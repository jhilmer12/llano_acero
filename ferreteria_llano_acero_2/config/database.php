<?php
class Database {
    private $host = "localhost";
    private $db_name = "ferreteria_llano_acero_2";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8", 
                $this->username, 
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        } catch(PDOException $exception) {
            error_log("Error de conexión DB: " . $exception->getMessage());
            // Para debugging, puedes mostrar el error (quitar en producción)
            if (isset($_SESSION['debug']) && $_SESSION['debug'] === true) {
                echo "Error de conexión: " . $exception->getMessage();
            }
            return false;
        }
        return $this->conn;
    }
}
?>