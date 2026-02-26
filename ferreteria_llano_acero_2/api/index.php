<?php
// Iniciar sesión solo si no está activa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Incluir archivos con manejo de errores
try {
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../controllers/AuthController.php';
    require_once __DIR__ . '/../controllers/UserController.php';
    require_once __DIR__ . '/../controllers/ProductController.php';
    require_once __DIR__ . '/../controllers/CategoryController.php';
    require_once __DIR__ . '/../controllers/SupplierController.php';
    require_once __DIR__ . '/../controllers/CustomerController.php';
    require_once __DIR__ . '/../controllers/SaleController.php';
    require_once __DIR__ . '/../controllers/InventoryController.php';
    require_once __DIR__ . '/../controllers/DashboardController.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error cargando controladores: ' . $e->getMessage()]);
    exit();
}

// Obtener el input JSON de manera más robusta
$input = [];
$rawInput = file_get_contents('php://input');

if (!empty($rawInput)) {
    $input = json_decode($rawInput, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        // Si no es JSON válido, intentar con form-data
        parse_str($rawInput, $input);
    }
}

// Si todavía está vacío, usar $_POST
if (empty($input)) {
    $input = $_POST;
}

// Log para debugging (remover en producción)
error_log("Input recibido: " . print_r($input, true));

// Verificar si hay acción
if (!isset($input['action']) || empty($input['action'])) {
    error_log("Acción no especificada. Input: " . print_r($input, true));
    echo json_encode(['success' => false, 'message' => 'Acción no especificada']);
    exit;
}

try {
    $authController = new AuthController();
    
    // Acciones que no requieren autenticación
    $publicActions = ['login'];
    
    if (!in_array($input['action'], $publicActions)) {
        if (!$authController->checkAuth()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'No autorizado']);
            exit;
        }
    }

    // Procesar la acción
    switch ($input['action']) {
        // Autenticación
        case 'login':
            if (!isset($input['email']) || empty($input['email']) || !isset($input['password']) || empty($input['password'])) {
                echo json_encode(['success' => false, 'message' => 'Email y contraseña requeridos']);
                break;
            }
            $result = $authController->login($input['email'], $input['password']);
            echo json_encode($result);
            break;

        case 'logout':
            $result = $authController->logout();
            echo json_encode($result);
            break;

        // Usuarios
        case 'getUsers':
            $authController->requireAdmin();
            $userController = new UserController();
            $result = $userController->getUsers();
            echo json_encode($result);
            break;

        case 'getUser':
            $authController->requireAuth();
            $userController = new UserController();
            $id = isset($input['id']) ? $input['id'] : null;
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID de usuario requerido']);
                break;
            }
            $result = $userController->getUser($id);
            echo json_encode($result);
            break;

        case 'createUser':
            $authController->requireAdmin();
            $userController = new UserController();
            $result = $userController->createUser($input);
            echo json_encode($result);
            break;

        case 'updateUser':
            $authController->requireAdmin();
            $userController = new UserController();
            $id = isset($input['id']) ? $input['id'] : null;
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID de usuario requerido']);
                break;
            }
            $result = $userController->updateUser($id, $input);
            echo json_encode($result);
            break;

        case 'deleteUser':
            $authController->requireAdmin();
            $userController = new UserController();
            $id = isset($input['id']) ? $input['id'] : null;
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID de usuario requerido']);
                break;
            }
            $result = $userController->deleteUser($id);
            echo json_encode($result);
            break;

        // Productos
        case 'getProducts':
            $authController->requireEmployee();
            $productController = new ProductController();
            $result = $productController->getProducts();
            echo json_encode($result);
            break;

        case 'getProduct':
            $authController->requireEmployee();
            $productController = new ProductController();
            $id = isset($input['id']) ? $input['id'] : null;
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID de producto requerido']);
                break;
            }
            $result = $productController->getProduct($id);
            echo json_encode($result);
            break;

        case 'createProduct':
            $authController->requireAdmin();
            $productController = new ProductController();
            $result = $productController->createProduct($input);
            echo json_encode($result);
            break;

        case 'updateProduct':
            $authController->requireAdmin();
            $productController = new ProductController();
            $id = isset($input['id']) ? $input['id'] : null;
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID de producto requerido']);
                break;
            }
            $result = $productController->updateProduct($id, $input);
            echo json_encode($result);
            break;

        case 'deleteProduct':
            $authController->requireAdmin();
            $productController = new ProductController();
            $id = isset($input['id']) ? $input['id'] : null;
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID de producto requerido']);
                break;
            }
            $result = $productController->deleteProduct($id);
            echo json_encode($result);
            break;

        case 'getLowStockProducts':
            $authController->requireEmployee();
            $productController = new ProductController();
            $limit = isset($input['limit']) ? $input['limit'] : 10;
            $result = $productController->getLowStockProducts($limit);
            echo json_encode($result);
            break;

        case 'searchProducts':
            $authController->requireEmployee();
            $productController = new ProductController();
            $term = isset($input['term']) ? $input['term'] : '';
            if (empty($term)) {
                echo json_encode(['success' => false, 'message' => 'Término de búsqueda requerido']);
                break;
            }
            $result = $productController->searchProducts($term);
            echo json_encode($result);
            break;

        // Categorías
        case 'getCategories':
            $authController->requireEmployee();
            $categoryController = new CategoryController();
            $result = $categoryController->getCategories();
            echo json_encode($result);
            break;

        case 'getCategory':
            $authController->requireEmployee();
            $categoryController = new CategoryController();
            $id = isset($input['id']) ? $input['id'] : null;
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID de categoría requerido']);
                break;
            }
            $result = $categoryController->getCategory($id);
            echo json_encode($result);
            break;

        case 'createCategory':
            $authController->requireAdmin();
            $categoryController = new CategoryController();
            $result = $categoryController->createCategory($input);
            echo json_encode($result);
            break;

        case 'updateCategory':
            $authController->requireAdmin();
            $categoryController = new CategoryController();
            $id = isset($input['id']) ? $input['id'] : null;
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID de categoría requerido']);
                break;
            }
            $result = $categoryController->updateCategory($id, $input);
            echo json_encode($result);
            break;

        case 'deleteCategory':
            error_log("🔍 API: Solicitando eliminar categoría ID: " . ($input['id'] ?? 'NULL'));
            $authController->requireAdmin();
            $categoryController = new CategoryController();
            $id = isset($input['id']) ? $input['id'] : null;
            if (!$id) {
                error_log("❌ API: ID de categoría requerido");
                echo json_encode(['success' => false, 'message' => 'ID de categoría requerido']);
                break;
            }
            $result = $categoryController->deleteCategory($id);
            error_log("📊 API: Resultado de deleteCategory: " . json_encode($result));
            echo json_encode($result);
            break;

        // Proveedores
        case 'getSuppliers':
            $authController->requireEmployee();
            $supplierController = new SupplierController();
            $result = $supplierController->getSuppliers();
            echo json_encode($result);
            break;

        case 'getSupplier':
            $authController->requireEmployee();
            $supplierController = new SupplierController();
            $id = isset($input['id']) ? $input['id'] : null;
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID de proveedor requerido']);
                break;
            }
            $result = $supplierController->getSupplier($id);
            echo json_encode($result);
            break;

        case 'createSupplier':
            $authController->requireAdmin();
            $supplierController = new SupplierController();
            $result = $supplierController->createSupplier($input);
            echo json_encode($result);
            break;

        case 'updateSupplier':
            $authController->requireAdmin();
            $supplierController = new SupplierController();
            $id = isset($input['id']) ? $input['id'] : null;
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID de proveedor requerido']);
                break;
            }
            $result = $supplierController->updateSupplier($id, $input);
            echo json_encode($result);
            break;

        case 'deleteSupplier':
            $authController->requireAdmin();
            $supplierController = new SupplierController();
            $id = isset($input['id']) ? $input['id'] : null;
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID de proveedor requerido']);
                break;
            }
            $result = $supplierController->deleteSupplier($id);
            echo json_encode($result);
            break;

        case 'searchSuppliers':
            $authController->requireEmployee();
            $supplierController = new SupplierController();
            $term = isset($input['term']) ? $input['term'] : '';
            if (empty($term)) {
                echo json_encode(['success' => false, 'message' => 'Término de búsqueda requerido']);
                break;
            }
            $result = $supplierController->searchSuppliers($term);
            echo json_encode($result);
            break;

        // Clientes
        case 'getCustomers':
            $authController->requireEmployee();
            $customerController = new CustomerController();
            $result = $customerController->getCustomers();
            echo json_encode($result);
            break;

        case 'getCustomer':
            $authController->requireEmployee();
            $customerController = new CustomerController();
            $id = isset($input['id']) ? $input['id'] : null;
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID de cliente requerido']);
                break;
            }
            $result = $customerController->getCustomer($id);
            echo json_encode($result);
            break;

        case 'createCustomer':
            $authController->requireEmployee();
            $customerController = new CustomerController();
            $result = $customerController->createCustomer($input);
            echo json_encode($result);
            break;

        case 'updateCustomer':
            $authController->requireEmployee();
            $customerController = new CustomerController();
            $id = isset($input['id']) ? $input['id'] : null;
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID de cliente requerido']);
                break;
            }
            $result = $customerController->updateCustomer($id, $input);
            echo json_encode($result);
            break;

        case 'deleteCustomer':
            $authController->requireEmployee();
            $customerController = new CustomerController();
            $id = isset($input['id']) ? $input['id'] : null;
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID de cliente requerido']);
                break;
            }
            $result = $customerController->deleteCustomer($id);
            echo json_encode($result);
            break;

        case 'searchCustomers':
            $authController->requireEmployee();
            $customerController = new CustomerController();
            $term = isset($input['term']) ? $input['term'] : '';
            if (empty($term)) {
                echo json_encode(['success' => false, 'message' => 'Término de búsqueda requerido']);
                break;
            }
            $result = $customerController->searchCustomers($term);
            echo json_encode($result);
            break;

        case 'getPurchaseHistory':
            $authController->requireEmployee();
            $customerController = new CustomerController();
            $id_cliente = isset($input['id_cliente']) ? $input['id_cliente'] : null;
            if (!$id_cliente) {
                echo json_encode(['success' => false, 'message' => 'ID de cliente requerido']);
                break;
            }
            $result = $customerController->getPurchaseHistory($id_cliente);
            echo json_encode($result);
            break;

        // Ventas
        case 'getSales':
            $authController->requireEmployee();
            $saleController = new SaleController();
            $limit = isset($input['limit']) ? $input['limit'] : null;
            $result = $saleController->getSales($limit);
            echo json_encode($result);
            break;

        case 'getSale':
            $authController->requireEmployee();
            $saleController = new SaleController();
            $id = isset($input['id']) ? $input['id'] : null;
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID de venta requerido']);
                break;
            }
            $result = $saleController->getSale($id);
            echo json_encode($result);
            break;

        case 'createSale':
            $authController->requireEmployee();
            $saleController = new SaleController();
            if (!isset($input['sale_data']) || !isset($input['details'])) {
                echo json_encode(['success' => false, 'message' => 'Datos de venta y detalles requeridos']);
                break;
            }
            $result = $saleController->createSale($input['sale_data'], $input['details']);
            echo json_encode($result);
            break;

        case 'cancelSale':
            $authController->requireEmployee();
            $saleController = new SaleController();
            $id = isset($input['id']) ? $input['id'] : null;
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID de venta requerido']);
                break;
            }
            $result = $saleController->cancelSale($id);
            echo json_encode($result);
            break;

        case 'getSalesByDate':
            $authController->requireEmployee();
            $saleController = new SaleController();
            $start_date = isset($input['start_date']) ? $input['start_date'] : null;
            $end_date = isset($input['end_date']) ? $input['end_date'] : null;
            if (!$start_date || !$end_date) {
                echo json_encode(['success' => false, 'message' => 'Fecha inicio y fecha fin requeridas']);
                break;
            }
            $result = $saleController->getSalesByDate($start_date, $end_date);
            echo json_encode($result);
            break;

        case 'getSalesStatistics':
            $authController->requireEmployee();
            $saleController = new SaleController();
            $result = $saleController->getSalesStatistics();
            echo json_encode($result);
            break;

        case 'getPaymentMethods':
            $authController->requireEmployee();
            $saleController = new SaleController();
            $result = $saleController->getPaymentMethods();
            echo json_encode($result);
            break;

       
         

       

        // CAMBIO PRINCIPAL: Cambiar requireAdmin() por requireEmployee()
        case 'adjustInventory':
            $authController->requireEmployee();  // ← CAMBIADO AQUÍ
            $inventoryController = new InventoryController();
            $id_producto = isset($input['id_producto']) ? $input['id_producto'] : null;
            $nuevo_stock = isset($input['nuevo_stock']) ? $input['nuevo_stock'] : null;
            $motivo = isset($input['motivo']) ? $input['motivo'] : null;
            
            if (!$id_producto || $nuevo_stock === null || !$motivo) {
                echo json_encode(['success' => false, 'message' => 'ID de producto, nuevo stock y motivo requeridos']);
                break;
            }
            
            $result = $inventoryController->adjustInventory($id_producto, $nuevo_stock, $motivo);
            echo json_encode($result);
            break;

       

        
        // Dashboard
        case 'getGeneralStats':
            $authController->requireEmployee();
            $dashboardController = new DashboardController();
            $result = $dashboardController->getGeneralStats();
            echo json_encode($result);
            break;

        case 'getMonthlySales':
            $authController->requireEmployee();
            $dashboardController = new DashboardController();
            $year = isset($input['year']) ? $input['year'] : null;
            $result = $dashboardController->getMonthlySales($year);
            echo json_encode($result);
            break;

        case 'getTopProducts':
            $authController->requireEmployee();
            $dashboardController = new DashboardController();
            $limit = isset($input['limit']) ? $input['limit'] : 10;
            $result = $dashboardController->getTopProducts($limit);
            echo json_encode($result);
            break;

        case 'getAlerts':
            $authController->requireEmployee();
            $dashboardController = new DashboardController();
            $result = $dashboardController->getAlerts();
            echo json_encode($result);
            break;

        case 'getTodaySales':
            $authController->requireEmployee();
            $dashboardController = new DashboardController();
            $result = $dashboardController->getTodaySales();
            echo json_encode($result);
            break;

        case 'getProductosConMovimientos':
            $authController->requireEmployee();
            $inventoryController = new InventoryController();
            $result = $inventoryController->getProductosConMovimientos();
            echo json_encode($result);
            break;

        case 'getProductosStockBajo':
            $authController->requireEmployee();
            $inventoryController = new InventoryController();
            $result = $inventoryController->getProductosStockBajo();
            echo json_encode($result);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Acción no válida: ' . $input['action']]);
            break;
    }

} catch (Exception $e) {
    error_log("Error en API: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor: ' . $e->getMessage()]);
}
?>