<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/SaleModel.php';
require_once __DIR__ . '/../models/ProductModel.php';
require_once __DIR__ . '/../models/CustomerModel.php';
require_once __DIR__ . '/../models/PaymentMethodModel.php';
require_once __DIR__ . '/AuthController.php';

class SaleController {
    private $db;
    private $saleModel;
    private $productModel;
    private $customerModel;
    private $paymentMethodModel;
    private $authController;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->saleModel = new SaleModel($this->db);
        $this->productModel = new ProductModel($this->db);
        $this->customerModel = new CustomerModel($this->db);
        $this->paymentMethodModel = new PaymentMethodModel($this->db);
        $this->authController = new AuthController();
    }

    public function getSales($limit = null) {
        try {
            $this->authController->requireEmployee();
            
            $stmt = $this->saleModel->getVentasConInfo($limit);
            $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return ['success' => true, 'data' => $sales];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al obtener ventas: ' . $e->getMessage()];
        }
    }

    public function getSale($id) {
        try {
            $this->authController->requireEmployee();
            
            error_log("🔍 SaleController: Buscando venta con ID: " . $id);
            
            // Obtener información básica de la venta con joins
            $query = "SELECT v.*, 
                             c.nombre as cliente, 
                             u.nombre as vendedor, 
                             mp.nombre as metodo_pago 
                      FROM ventas v 
                      LEFT JOIN clientes c ON v.id_cliente = c.id_cliente 
                      LEFT JOIN usuarios u ON v.id_usuario = u.id_usuario 
                      LEFT JOIN metodos_pago mp ON v.id_metodo_pago = mp.id_metodo_pago 
                      WHERE v.id_venta = ?";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);
            $sale = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$sale) {
                error_log("❌ SaleController: Venta no encontrada: " . $id);
                return ['success' => false, 'message' => 'Venta no encontrada'];
            }

            error_log("✅ SaleController: Venta encontrada, obteniendo detalles...");
            
            // Obtener detalles de la venta
            $details = $this->saleModel->getDetallesVenta($id);
            $sale['detalles'] = $details->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("📦 SaleController: Detalles obtenidos: " . count($sale['detalles']));
            
            return ['success' => true, 'data' => $sale];
        } catch (Exception $e) {
            error_log("💥 SaleController Error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al obtener venta: ' . $e->getMessage()];
        }
    }

    public function createSale($saleData, $details) {
        try {
            $this->authController->requireEmployee();

            // Validar datos de la venta
            if (empty($saleData['total']) || $saleData['total'] <= 0) {
                return ['success' => false, 'message' => 'El total debe ser mayor a 0'];
            }

            if (empty($saleData['id_metodo_pago'])) {
                return ['success' => false, 'message' => 'El método de pago es requerido'];
            }

            // Validar detalles
            if (empty($details) || !is_array($details)) {
                return ['success' => false, 'message' => 'Los detalles de la venta son requeridos'];
            }

            // Obtener ID del usuario actual
            $currentUser = $this->authController->getCurrentUser();
            $saleData['id_usuario'] = $currentUser['id_usuario'];

            // Filtrar datos de la venta
            $filteredSaleData = [
                'total' => floatval($saleData['total']),
                'id_cliente' => isset($saleData['id_cliente']) ? intval($saleData['id_cliente']) : null,
                'id_usuario' => intval($saleData['id_usuario']),
                'id_metodo_pago' => intval($saleData['id_metodo_pago'])
            ];

            // Filtrar detalles
            $filteredDetails = [];
            foreach ($details as $detalle) {
                $filteredDetails[] = [
                    'id_producto' => intval($detalle['id_producto']),
                    'cantidad' => intval($detalle['cantidad']),
                    'precio_unitario' => floatval($detalle['precio_unitario']),
                    'subtotal' => floatval($detalle['subtotal'])
                ];
            }

            // Registrar venta
            error_log("🛒 SaleController: Registrando nueva venta...");
            $id_venta = $this->saleModel->registrarVenta($filteredSaleData, $filteredDetails);
            
            if ($id_venta) {
                error_log("✅ SaleController: Venta registrada correctamente. ID: " . $id_venta);
                return ['success' => true, 'message' => 'Venta registrada correctamente', 'id_venta' => $id_venta];
            } else {
                error_log("❌ SaleController: Error al registrar la venta");
                return ['success' => false, 'message' => 'Error al registrar la venta'];
            }
            
        } catch (Exception $e) {
            // Capturar errores de los triggers
            $errorMessage = $e->getMessage();
            error_log("💥 SaleController Error en createSale: " . $errorMessage);
            
            if (strpos($errorMessage, 'Stock insuficiente') !== false) {
                return ['success' => false, 'message' => $errorMessage];
            }
            return ['success' => false, 'message' => 'Error al registrar venta: ' . $errorMessage];
        }
    }

    public function cancelSale($id) {
        try {
            $this->authController->requireEmployee();

            error_log("🗑️ SaleController: Anulando venta ID: " . $id);
            
            if ($this->saleModel->anularVenta($id)) {
                error_log("✅ SaleController: Venta anulada correctamente");
                return ['success' => true, 'message' => 'Venta anulada correctamente'];
            } else {
                error_log("❌ SaleController: Error al anular la venta");
                return ['success' => false, 'message' => 'Error al anular la venta'];
            }
        } catch (Exception $e) {
            error_log("💥 SaleController Error en cancelSale: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al anular venta: ' . $e->getMessage()];
        }
    }

    public function getSalesByDate($startDate, $endDate) {
        try {
            $this->authController->requireEmployee();
            
            $stmt = $this->saleModel->getVentasPorFecha($startDate, $endDate);
            $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return ['success' => true, 'data' => $sales];
        } catch (Exception $e) {
            error_log("💥 SaleController Error en getSalesByDate: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al obtener ventas por fecha: ' . $e->getMessage()];
        }
    }

    public function getSalesStatistics() {
        try {
            $this->authController->requireEmployee();
            
            $stats = $this->saleModel->getEstadisticasVentas();
            return ['success' => true, 'data' => $stats];
        } catch (Exception $e) {
            error_log("💥 SaleController Error en getSalesStatistics: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al obtener estadísticas: ' . $e->getMessage()];
        }
    }

    public function getPaymentMethods() {
        try {
            $this->authController->requireEmployee();
            
            $stmt = $this->paymentMethodModel->getMetodosActivos();
            $methods = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return ['success' => true, 'data' => $methods];
        } catch (Exception $e) {
            error_log("💥 SaleController Error en getPaymentMethods: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al obtener métodos de pago: ' . $e->getMessage()];
        }
    }
}
?>