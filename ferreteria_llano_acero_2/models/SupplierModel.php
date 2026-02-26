<?php
require_once 'BaseModel.php';

class SupplierModel extends BaseModel {
    public function __construct($db) {
        parent::__construct($db, 'proveedores');
    }

    public function getProveedoresConProductos() {
        $query = "SELECT pr.*, COUNT(p.id_producto) as total_productos 
                  FROM proveedores pr 
                  LEFT JOIN productos p ON pr.id_proveedor = p.id_proveedor AND p.activo = 1
                  WHERE pr.activo = 1
                  GROUP BY pr.id_proveedor
                  ORDER BY pr.nombre";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function getProveedoresActivos() {
        $query = "SELECT * FROM proveedores WHERE activo = 1 ORDER BY nombre";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function buscarProveedores($termino) {
        $query = "SELECT * FROM proveedores 
                  WHERE (nombre LIKE ? OR contacto LIKE ? OR email LIKE ?) AND activo = 1
                  ORDER BY nombre";
        $stmt = $this->conn->prepare($query);
        $searchTerm = "%$termino%";
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
        return $stmt;
    }
}
?>