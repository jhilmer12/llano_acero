<?php
require_once 'BaseModel.php';

class CategoryModel extends BaseModel {
    public function __construct($db) {
        parent::__construct($db, 'categorias');
    }

    // SOBRESCRIBIR métodos para quitar la condición de activo
    public function readAll() {
        $query = "SELECT * FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function getById($id, $id_field = 'id') {
        $query = "SELECT * FROM " . $this->table_name . " WHERE " . $id_field . " = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function delete($id, $id_field = 'id') {
        // HARD DELETE - eliminación física
        $query = "DELETE FROM " . $this->table_name . " WHERE " . $id_field . " = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$id]);
    }

    public function countAll() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }

    public function getCategoriasConProductos() {
        $query = "SELECT c.*, COUNT(p.id_producto) as total_productos 
                  FROM categorias c 
                  LEFT JOIN productos p ON c.id_categoria = p.id_categoria AND p.activo = 1
                  GROUP BY c.id_categoria
                  ORDER BY c.nombre";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function getCategoriasActivas() {
        $query = "SELECT c.*, COUNT(p.id_producto) as total_productos 
                  FROM categorias c 
                  LEFT JOIN productos p ON c.id_categoria = p.id_categoria AND p.activo = 1
                  WHERE c.id_categoria IN (SELECT DISTINCT id_categoria FROM productos WHERE activo = 1)
                  GROUP BY c.id_categoria
                  ORDER BY c.nombre";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Método específico para verificar si hay productos activos
    public function tieneProductosActivos($id_categoria) {
        $query = "SELECT COUNT(*) as total 
                  FROM productos 
                  WHERE id_categoria = ? AND activo = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id_categoria]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] > 0;
    }
}
?>