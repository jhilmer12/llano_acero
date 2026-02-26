<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/DashboardModel.php';
require_once __DIR__ . '/../models/SaleModel.php';
require_once __DIR__ . '/../models/ProductModel.php';
require_once __DIR__ . '/AuthController.php';

class DashboardController {
    private $db;
    private $dashboardModel;
    private $saleModel;
    private $productModel;
    private $authController;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->dashboardModel = new DashboardModel($this->db);
        $this->saleModel = new SaleModel($this->db);
        $this->productModel = new ProductModel($this->db);
        $this->authController = new AuthController();
    }

    public function getGeneralStats() {
        try {
            $this->authController->requireEmployee();
            
            $stats = $this->dashboardModel->getEstadisticasGenerales();
            return ['success' => true, 'data' => $stats];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al obtener estadísticas: ' . $e->getMessage()];
        }
    }

    public function getMonthlySales($year = null) {
        try {
            $this->authController->requireEmployee();
            
            $stmt = $this->dashboardModel->getVentasMensuales($year);
            $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return ['success' => true, 'data' => $sales];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al obtener ventas mensuales: ' . $e->getMessage()];
        }
    }

    public function getTopProducts($limit = 10) {
        try {
            $this->authController->requireEmployee();
            
            $stmt = $this->dashboardModel->getProductosMasVendidos($limit);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return ['success' => true, 'data' => $products];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al obtener productos más vendidos: ' . $e->getMessage()];
        }
    }

    public function getAlerts() {
        try {
            $this->authController->requireEmployee();
            
            $alerts = $this->dashboardModel->getAlertas();
            return ['success' => true, 'data' => $alerts];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al obtener alertas: ' . $e->getMessage()];
        }
    }

    public function getTodaySales() {
        try {
            $this->authController->requireEmployee();
            
            $stats = $this->saleModel->getEstadisticasVentas();
            return ['success' => true, 'data' => $stats];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al obtener ventas del día: ' . $e->getMessage()];
        }
    }

    public function getLowStockProducts() {
        try {
            $this->authController->requireEmployee();
            
            $stmt = $this->productModel->getProductosBajoStock();
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return ['success' => true, 'data' => $products];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al obtener productos con stock bajo: ' . $e->getMessage()];
        }
    }
}
?>