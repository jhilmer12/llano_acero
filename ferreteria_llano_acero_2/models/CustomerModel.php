<?php
require_once 'BaseModel.php';

class CustomerModel extends BaseModel {
    public function __construct($db) {
        parent::__construct($db, 'clientes');
    }

    public function getClientesConCompras() {
        $query = "SELECT 
                    c.*, 
                    COUNT(v.id_venta) as total_compras, 
                    COALESCE(SUM(v.total), 0) as total_gastado,
                    MAX(v.fecha_venta) as ultima_compra
                FROM clientes c 
                LEFT JOIN ventas v ON c.id_cliente = v.id_cliente
                WHERE c.activo = 1
                GROUP BY c.id_cliente
                ORDER BY c.nombre ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function buscarClientes($termino) {
        $query = "SELECT * FROM clientes 
                  WHERE (nombre LIKE ? OR email LIKE ? OR telefono LIKE ?) AND activo = 1
                  ORDER BY nombre";
        $stmt = $this->conn->prepare($query);
        $searchTerm = "%$termino%";
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
        return $stmt;
    }

    public function getHistorialCompras($id_cliente) {
        $query = "SELECT 
                    v.*, 
                    u.nombre as vendedor, 
                    mp.nombre as metodo_pago
                FROM ventas v 
                LEFT JOIN usuarios u ON v.id_usuario = u.id_usuario
                LEFT JOIN metodos_pago mp ON v.id_metodo_pago = mp.id_metodo_pago
                WHERE v.id_cliente = ?
                ORDER BY v.fecha_venta DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id_cliente]);
        return $stmt;
    }

    public function getDetallesVenta($id_venta) {
        $query = "SELECT 
                    dv.*, 
                    p.nombre as producto, 
                    p.descripcion 
                FROM detalles_venta dv 
                LEFT JOIN productos p ON dv.id_producto = p.id_producto 
                WHERE dv.id_venta = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id_venta]);
        return $stmt;
    }

    // Método específico para verificar si un cliente tiene ventas
    public function tieneVentas($id_cliente) {
        $query = "SELECT COUNT(*) as total_ventas FROM ventas WHERE id_cliente = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id_cliente]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total_ventas'] > 0;
    }
}
?>