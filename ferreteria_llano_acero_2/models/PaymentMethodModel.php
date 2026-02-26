<?php
require_once 'BaseModel.php';

class PaymentMethodModel extends BaseModel {
    public function __construct($db) {
        parent::__construct($db, 'metodos_pago');
    }

    public function getMetodosActivos() {
        $query = "SELECT * FROM metodos_pago ORDER BY nombre";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
}
?>