<?php
require_once 'BaseModel.php';

class SaleModel extends BaseModel {
    public function __construct($db) {
        parent::__construct($db, 'ventas');
    }

    // Sobrescribir el método getById para quitar la condición de activo
    public function getById($id, $id_column = 'id') {
        try {
            $query = "SELECT * FROM {$this->table} WHERE {$id_column} = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error en getById: " . $e->getMessage());
            return false;
        }
    }

    public function registrarVenta($venta_data, $detalles) {
        try {
            $this->conn->beginTransaction();

            // Insertar venta
            $query_venta = "INSERT INTO ventas (total, id_cliente, id_usuario, id_metodo_pago) VALUES (?, ?, ?, ?)";
            $stmt_venta = $this->conn->prepare($query_venta);
            $stmt_venta->execute([
                $venta_data['total'],
                $venta_data['id_cliente'] ?? null,
                $venta_data['id_usuario'],
                $venta_data['id_metodo_pago']
            ]);
            
            $id_venta = $this->conn->lastInsertId();

            // Insertar detalles
            foreach ($detalles as $detalle) {
                $query_detalle = "INSERT INTO detalles_venta (id_venta, id_producto, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)";
                $stmt_detalle = $this->conn->prepare($query_detalle);
                $stmt_detalle->execute([
                    $id_venta,
                    $detalle['id_producto'],
                    $detalle['cantidad'],
                    $detalle['precio_unitario'],
                    $detalle['subtotal']
                ]);
            }

            $this->conn->commit();
            return $id_venta;
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Error al registrar venta: " . $e->getMessage());
            return false;
        }
    }

    public function getVentasConInfo($limit = null) {
        $query = "SELECT v.*, 
                         c.nombre as cliente, 
                         u.nombre as vendedor, 
                         mp.nombre as metodo_pago 
                  FROM ventas v 
                  LEFT JOIN clientes c ON v.id_cliente = c.id_cliente 
                  LEFT JOIN usuarios u ON v.id_usuario = u.id_usuario 
                  LEFT JOIN metodos_pago mp ON v.id_metodo_pago = mp.id_metodo_pago 
                  ORDER BY v.fecha_venta DESC";
        
        if ($limit) {
            $query .= " LIMIT " . intval($limit);
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function getVentasPorFecha($fecha_inicio, $fecha_fin) {
        $query = "SELECT v.*, 
                         c.nombre as cliente, 
                         u.nombre as vendedor, 
                         mp.nombre as metodo_pago 
                  FROM ventas v 
                  LEFT JOIN clientes c ON v.id_cliente = c.id_cliente 
                  LEFT JOIN usuarios u ON v.id_usuario = u.id_usuario 
                  LEFT JOIN metodos_pago mp ON v.id_metodo_pago = mp.id_metodo_pago 
                  WHERE DATE(v.fecha_venta) BETWEEN ? AND ?
                  ORDER BY v.fecha_venta DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$fecha_inicio, $fecha_fin]);
        return $stmt;
    }

    public function getDetallesVenta($id_venta) {
        $query = "SELECT 
                    dv.id_detalle,
                    dv.id_venta,
                    dv.id_producto,
                    dv.cantidad,
                    dv.precio_unitario,
                    dv.subtotal,
                    p.nombre as producto,
                    p.descripcion
                  FROM detalles_venta dv 
                  INNER JOIN productos p ON dv.id_producto = p.id_producto 
                  WHERE dv.id_venta = ?
                  ORDER BY dv.id_detalle ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id_venta]);
        return $stmt;
    }

    public function getEstadisticasVentas() {
        $query = "SELECT 
                    COUNT(*) as total_ventas,
                    COALESCE(SUM(total), 0) as ingresos_totales,
                    AVG(total) as promedio_venta,
                    MAX(total) as venta_maxima,
                    MIN(total) as venta_minima
                  FROM ventas 
                  WHERE DATE(fecha_venta) = CURDATE()";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function anularVenta($id_venta) {
        try {
            $this->conn->beginTransaction();

            // Obtener detalles de la venta para revertir stock
            $detalles = $this->getDetallesVenta($id_venta);
            $detalles_data = $detalles->fetchAll(PDO::FETCH_ASSOC);

            // Revertir stock
            foreach ($detalles_data as $detalle) {
                $query_update_stock = "UPDATE productos SET stock = stock + ? WHERE id_producto = ?";
                $stmt_update = $this->conn->prepare($query_update_stock);
                $stmt_update->execute([$detalle['cantidad'], $detalle['id_producto']]);
            }

            // Eliminar detalles de venta
            $query_delete_detalles = "DELETE FROM detalles_venta WHERE id_venta = ?";
            $stmt_delete_detalles = $this->conn->prepare($query_delete_detalles);
            $stmt_delete_detalles->execute([$id_venta]);

            // Eliminar venta
            $query_delete_venta = "DELETE FROM ventas WHERE id_venta = ?";
            $stmt_delete_venta = $this->conn->prepare($query_delete_venta);
            $stmt_delete_venta->execute([$id_venta]);

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollBack();
            error_log("Error al anular venta: " . $e->getMessage());
            return false;
        }
    }
}
?>