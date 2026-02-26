<?php
require_once 'BaseModel.php';

class ProductModel extends BaseModel {
    public function __construct($db) {
        parent::__construct($db, 'productos');
    }

    public function getProductosConInfo() {
        $query = "SELECT p.*, c.nombre as categoria, pr.nombre as proveedor, 
                         u.nombre as usuario_movimiento
                  FROM productos p 
                  LEFT JOIN categorias c ON p.id_categoria = c.id_categoria 
                  LEFT JOIN proveedores pr ON p.id_proveedor = pr.id_proveedor 
                  LEFT JOIN usuarios u ON p.usuario_ultimo_movimiento = u.id_usuario
                  WHERE p.activo = 1
                  ORDER BY p.ultimo_movimiento DESC, p.nombre";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function getProductosBajoStock($limite = 19) {
        $query = "SELECT p.*, c.nombre as categoria 
                  FROM productos p 
                  LEFT JOIN categorias c ON p.id_categoria = c.id_categoria 
                  WHERE p.stock <= p.stock_minimo AND p.activo = 1
                  ORDER BY p.stock ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function getProductosConMovimientos() {
        $query = "SELECT p.*, c.nombre as categoria, pr.nombre as proveedor,
                         u.nombre as usuario_movimiento
                  FROM productos p 
                  LEFT JOIN categorias c ON p.id_categoria = c.id_categoria 
                  LEFT JOIN proveedores pr ON p.id_proveedor = pr.id_proveedor 
                  LEFT JOIN usuarios u ON p.usuario_ultimo_movimiento = u.id_usuario
                  WHERE p.activo = 1 AND p.ultimo_movimiento IS NOT NULL
                  ORDER BY p.ultimo_movimiento DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function getProductosPorCategoria($id_categoria) {
        $query = "SELECT * FROM productos WHERE id_categoria = ? AND activo = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id_categoria]);
        return $stmt;
    }

    public function buscarProductos($termino) {
        $query = "SELECT p.*, c.nombre as categoria 
                  FROM productos p 
                  LEFT JOIN categorias c ON p.id_categoria = c.id_categoria 
                  WHERE (p.nombre LIKE ? OR p.descripcion LIKE ?) AND p.activo = 1
                  ORDER BY p.nombre";
        $stmt = $this->conn->prepare($query);
        $searchTerm = "%$termino%";
        $stmt->execute([$searchTerm, $searchTerm]);
        return $stmt;
    }

    public function getProductoCompleto($id_producto) {
        $query = "SELECT p.*, c.nombre as categoria, pr.nombre as proveedor, 
                         pr.telefono as telefono_proveedor, pr.email as email_proveedor,
                         u.nombre as usuario_movimiento
                  FROM productos p 
                  LEFT JOIN categorias c ON p.id_categoria = c.id_categoria 
                  LEFT JOIN proveedores pr ON p.id_proveedor = pr.id_proveedor 
                  LEFT JOIN usuarios u ON p.usuario_ultimo_movimiento = u.id_usuario
                  WHERE p.id_producto = ? AND p.activo = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id_producto]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Método para actualizar stock y registrar movimiento
    // En tu ProductModel.php, modifica o agrega este método:
    public function actualizarStock($id_producto, $nuevo_stock, $tipo_movimiento, $motivo, $id_usuario) {
        try {
            $query = "UPDATE productos 
                    SET stock = :nuevo_stock,
                        ultimo_movimiento = NOW(),
                        tipo_ultimo_movimiento = :tipo_movimiento,
                        motivo_ultimo_movimiento = :motivo,
                        usuario_ultimo_movimiento = :id_usuario,
                        fecha_modificacion = NOW()
                    WHERE id_producto = :id_producto";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':nuevo_stock', $nuevo_stock);
            $stmt->bindParam(':tipo_movimiento', $tipo_movimiento);
            $stmt->bindParam(':motivo', $motivo);
            $stmt->bindParam(':id_usuario', $id_usuario);
            $stmt->bindParam(':id_producto', $id_producto);

            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error en actualizarStock: " . $e->getMessage());
            return false;
        }
    }
}
?>