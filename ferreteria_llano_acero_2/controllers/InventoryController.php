<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/AuthController.php';

class InventoryController {
    private $db;
    private $authController;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->authController = new AuthController();
    }

    // Obtener TODOS los productos activos
    public function getProductosConMovimientos() {
        try {
            $this->authController->requireAuth();
            
            // Verificar manualmente el rol
            $currentUser = $this->authController->getCurrentUser();
            if (!in_array($currentUser['tipo'], ['admin', 'empleado'])) {
                return ['success' => false, 'message' => 'Se requieren permisos de empleado o administrador'];
            }
            
            $query = "SELECT 
                        p.id_producto,
                        p.nombre,
                        p.descripcion, 
                        p.precio,
                        p.stock,
                        p.stock_minimo,
                        p.ultimo_movimiento,
                        p.tipo_ultimo_movimiento,
                        p.motivo_ultimo_movimiento,
                        c.nombre as categoria,
                        pr.nombre as proveedor,
                        u.nombre as usuario_movimiento,
                        CASE 
                            WHEN p.stock = 0 THEN 'danger'
                            WHEN p.stock <= p.stock_minimo THEN 'warning'
                            ELSE 'success'
                        END as estado_stock
                      FROM productos p
                      LEFT JOIN categorias c ON p.id_categoria = c.id_categoria
                      LEFT JOIN proveedores pr ON p.id_proveedor = pr.id_proveedor
                      LEFT JOIN usuarios u ON p.usuario_ultimo_movimiento = u.id_usuario
                      WHERE p.activo = 1
                      ORDER BY p.nombre ASC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return ['success' => true, 'data' => $productos];
        } catch (Exception $e) {
            error_log("Error en getProductosConMovimientos: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al obtener productos: ' . $e->getMessage()];
        }
    }

    // Obtener productos con stock bajo
    public function getProductosStockBajo() {
        try {
            $this->authController->requireAuth();
            
            $currentUser = $this->authController->getCurrentUser();
            if (!in_array($currentUser['tipo'], ['admin', 'empleado'])) {
                return ['success' => false, 'message' => 'Se requieren permisos de empleado o administrador'];
            }
            
            $query = "SELECT 
                        p.*,
                        c.nombre as categoria,
                        pr.nombre as proveedor,
                        CASE 
                            WHEN p.stock = 0 THEN 'Agotado'
                            WHEN p.stock <= p.stock_minimo THEN 'Stock Bajo'
                            ELSE 'Normal'
                        END as estado
                      FROM productos p
                      LEFT JOIN categorias c ON p.id_categoria = c.id_categoria
                      LEFT JOIN proveedores pr ON p.id_proveedor = pr.id_proveedor
                      WHERE p.activo = 1 
                      AND (p.stock = 0 OR p.stock <= p.stock_minimo)
                      ORDER BY p.stock ASC, p.nombre ASC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return ['success' => true, 'data' => $productos];
        } catch (Exception $e) {
            error_log("Error en getProductosStockBajo: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al obtener productos con stock bajo: ' . $e->getMessage()];
        }
    }

    // Ajustar inventario - MÉTODO CORREGIDO
    public function adjustInventory($id_producto, $nuevo_stock, $motivo) {
        try {
            error_log("🔧 InventoryController: Iniciando adjustInventory");
            error_log("🔧 Parámetros - ID: $id_producto, Nuevo Stock: $nuevo_stock, Motivo: $motivo");
            
            $this->authController->requireAuth();
            
            $currentUser = $this->authController->getCurrentUser();
            if (!in_array($currentUser['tipo'], ['admin', 'empleado'])) {
                error_log("❌ InventoryController: Permisos insuficientes - Usuario: " . $currentUser['tipo']);
                return ['success' => false, 'message' => 'Se requieren permisos de empleado o administrador'];
            }

            // Validaciones básicas
            if ($nuevo_stock < 0) {
                error_log("❌ InventoryController: Stock negativo - $nuevo_stock");
                return ['success' => false, 'message' => 'El stock no puede ser negativo'];
            }

            if (empty($motivo)) {
                error_log("❌ InventoryController: Motivo vacío");
                return ['success' => false, 'message' => 'El motivo es requerido'];
            }

            $id_usuario = $currentUser['id_usuario'];
            error_log("🔧 InventoryController: Usuario ID: $id_usuario");

            // Obtener producto actual
            $product = $this->getProductoById($id_producto);
            if (!$product) {
                error_log("❌ InventoryController: Producto no encontrado - ID: $id_producto");
                return ['success' => false, 'message' => 'Producto no encontrado'];
            }

            $stock_actual = $product['stock'];
            $nombre_producto = $product['nombre'];
            
            error_log("🔧 InventoryController: Producto encontrado - $nombre_producto, Stock actual: $stock_actual");

            // Determinar tipo de movimiento
            $diferencia = $nuevo_stock - $stock_actual;
            if ($diferencia > 0) {
                $tipo_movimiento = 'entrada';
                $descripcion = "Ajuste manual: Entrada de $diferencia unidades";
            } else if ($diferencia < 0) {
                $tipo_movimiento = 'salida';
                $descripcion = "Ajuste manual: Salida de " . abs($diferencia) . " unidades";
            } else {
                $tipo_movimiento = 'ajuste';
                $descripcion = "Ajuste manual: Sin cambio en cantidad";
            }

            $motivo_completo = $descripcion . " - " . $motivo;

            // Usar una transacción para mayor seguridad
            $this->db->beginTransaction();

            try {
                // Actualizar todo en una sola consulta
                $query = "UPDATE productos 
                          SET stock = ?,
                              ultimo_movimiento = NOW(),
                              tipo_ultimo_movimiento = ?,
                              motivo_ultimo_movimiento = ?,
                              usuario_ultimo_movimiento = ?,
                              fecha_modificacion = NOW()
                          WHERE id_producto = ?";

                $stmt = $this->db->prepare($query);
                $result = $stmt->execute([
                    $nuevo_stock, 
                    $tipo_movimiento, 
                    $motivo_completo, 
                    $id_usuario, 
                    $id_producto
                ]);

                if (!$result) {
                    $errorInfo = $stmt->errorInfo();
                    error_log("❌ InventoryController: Error en execute - " . $errorInfo[2]);
                    throw new Exception('Error al ejecutar la actualización: ' . $errorInfo[2]);
                }

                $this->db->commit();
                
                error_log("✅ InventoryController: Ajuste exitoso - Producto: $nombre_producto, Stock anterior: $stock_actual, Stock nuevo: $nuevo_stock");
                
                return [
                    'success' => true, 
                    'message' => "Inventario de '$nombre_producto' ajustado correctamente",
                    'data' => [
                        'producto' => $nombre_producto,
                        'stock_anterior' => $stock_actual,
                        'stock_nuevo' => $nuevo_stock,
                        'diferencia' => $diferencia,
                        'tipo_movimiento' => $tipo_movimiento
                    ]
                ];

            } catch (Exception $e) {
                $this->db->rollBack();
                error_log("❌ InventoryController: Error en transacción - " . $e->getMessage());
                throw $e;
            }
            
        } catch (Exception $e) {
            error_log("💥 InventoryController: Excepción en adjustInventory - " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al ajustar inventario: ' . $e->getMessage()];
        }
    }

    // Método auxiliar para obtener producto por ID
    private function getProductoById($id_producto) {
        try {
            $query = "SELECT * FROM productos WHERE id_producto = ? AND activo = 1";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id_producto]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error en getProductoById: " . $e->getMessage());
            return null;
        }
    }

    // Método para obtener historial de movimientos
    public function getHistorialMovimientos($limit = 50) {
        try {
            $this->authController->requireAuth();
            
            $currentUser = $this->authController->getCurrentUser();
            if (!in_array($currentUser['tipo'], ['admin', 'empleado'])) {
                return ['success' => false, 'message' => 'Se requieren permisos de empleado o administrador'];
            }
            
            $query = "SELECT 
                        p.id_producto,
                        p.nombre as producto,
                        p.ultimo_movimiento as fecha_movimiento,
                        p.tipo_ultimo_movimiento as tipo,
                        p.motivo_ultimo_movimiento as motivo,
                        u.nombre as usuario,
                        c.nombre as categoria
                      FROM productos p
                      LEFT JOIN usuarios u ON p.usuario_ultimo_movimiento = u.id_usuario
                      LEFT JOIN categorias c ON p.id_categoria = c.id_categoria
                      WHERE p.ultimo_movimiento IS NOT NULL
                      ORDER BY p.ultimo_movimiento DESC 
                      LIMIT ?";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(1, $limit, PDO::PARAM_INT);
            $stmt->execute();
            $movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return ['success' => true, 'data' => $movimientos];
        } catch (Exception $e) {
            error_log("Error en getHistorialMovimientos: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al obtener historial: ' . $e->getMessage()];
        }
    }
}
?>