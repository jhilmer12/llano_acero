<?php
require_once 'BaseModel.php';

class DashboardModel extends BaseModel {
    protected $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getEstadisticasGenerales() {
        $stats = [];

        // Total productos
        $query_productos = "SELECT COUNT(*) as total FROM productos WHERE activo = 1";
        $stmt = $this->conn->prepare($query_productos);
        $stmt->execute();
        $stats['total_productos'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Total clientes
        $query_clientes = "SELECT COUNT(*) as total FROM clientes WHERE activo = 1";
        $stmt = $this->conn->prepare($query_clientes);
        $stmt->execute();
        $stats['total_clientes'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Ventas del día
        $query_ventas_hoy = "SELECT COUNT(*) as total, COALESCE(SUM(total), 0) as monto 
                            FROM ventas 
                            WHERE DATE(fecha_venta) = CURDATE()";
        $stmt = $this->conn->prepare($query_ventas_hoy);
        $stmt->execute();
        $ventas_hoy = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['ventas_hoy'] = $ventas_hoy['total'];
        $stats['ingresos_hoy'] = $ventas_hoy['monto'];

        // Productos bajo stock
        $query_bajo_stock = "SELECT COUNT(*) as total FROM productos WHERE stock <= 10 AND activo = 1";
        $stmt = $this->conn->prepare($query_bajo_stock);
        $stmt->execute();
        $stats['productos_bajo_stock'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        return $stats;
    }

    public function getVentasMensuales($year = null) {
        if ($year === null) {
            $year = date('Y');
        }

        $query = "SELECT 
                    MONTH(fecha_venta) as mes,
                    COUNT(*) as total_ventas,
                    COALESCE(SUM(total), 0) as ingresos
                  FROM ventas 
                  WHERE YEAR(fecha_venta) = ?
                  GROUP BY MONTH(fecha_venta)
                  ORDER BY mes";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$year]);
        return $stmt;
    }

    public function getProductosMasVendidos($limit = 10) {
        $query = "SELECT 
                    p.nombre,
                    SUM(dv.cantidad) as total_vendido,
                    SUM(dv.subtotal) as ingresos_totales
                  FROM detalles_venta dv
                  LEFT JOIN productos p ON dv.id_producto = p.id_producto
                  GROUP BY dv.id_producto
                  ORDER BY total_vendido DESC
                  LIMIT ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$limit]);
        return $stmt;
    }

    public function getAlertas() {
        $alertas = [];

        // Productos con stock bajo
        $query_stock = "SELECT nombre, stock FROM productos WHERE stock <= 5 AND activo = 1";
        $stmt = $this->conn->prepare($query_stock);
        $stmt->execute();
        $alertas['stock_bajo'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Productos sin stock
        $query_sin_stock = "SELECT nombre FROM productos WHERE stock = 0 AND activo = 1";
        $stmt = $this->conn->prepare($query_sin_stock);
        $stmt->execute();
        $alertas['sin_stock'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $alertas;
    }
}
?>