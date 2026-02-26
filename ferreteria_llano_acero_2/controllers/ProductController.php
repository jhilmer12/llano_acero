<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/ProductModel.php';
require_once __DIR__ . '/../models/CategoryModel.php';
require_once __DIR__ . '/../models/SupplierModel.php';
require_once __DIR__ . '/AuthController.php';

class ProductController {
    private $db;
    private $productModel;
    private $categoryModel;
    private $supplierModel;
    private $authController;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->productModel = new ProductModel($this->db);
        $this->categoryModel = new CategoryModel($this->db);
        $this->supplierModel = new SupplierModel($this->db);
        $this->authController = new AuthController();
    }

    public function getProducts() {
        try {
            $this->authController->requireEmployee();
            
            $stmt = $this->productModel->getProductosConInfo();
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return ['success' => true, 'data' => $products];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al obtener productos: ' . $e->getMessage()];
        }
    }

    public function getProduct($id) {
        try {
            $this->authController->requireEmployee();
            
            $product = $this->productModel->getProductoCompleto($id);
            if ($product) {
                return ['success' => true, 'data' => $product];
            } else {
                return ['success' => false, 'message' => 'Producto no encontrado'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al obtener producto: ' . $e->getMessage()];
        }
    }

    public function createProduct($data) {
        try {
            $this->authController->requireAdmin();

            // Validar campos requeridos
            $required = ['nombre', 'precio', 'id_categoria', 'id_proveedor'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return ['success' => false, 'message' => "El campo $field es requerido"];
                }
            }

            // Validar precio
            if ($data['precio'] <= 0) {
                return ['success' => false, 'message' => 'El precio debe ser mayor a 0'];
            }

            // Validar stock inicial
            if (isset($data['stock']) && $data['stock'] < 0) {
                return ['success' => false, 'message' => 'El stock no puede ser negativo'];
            }

            // Filtrar solo los campos necesarios
            $productData = [
                'nombre' => $data['nombre'],
                'descripcion' => $data['descripcion'] ?? '',
                'precio' => floatval($data['precio']),
                'stock' => isset($data['stock']) ? intval($data['stock']) : 0,
                'stock_minimo' => isset($data['stock_minimo']) ? intval($data['stock_minimo']) : 5,
                'id_categoria' => intval($data['id_categoria']),
                'id_proveedor' => intval($data['id_proveedor'])
            ];

            $id = $this->productModel->create($productData);
            if ($id) {
                return ['success' => true, 'message' => 'Producto creado correctamente', 'id' => $id];
            } else {
                return ['success' => false, 'message' => 'Error al crear producto'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al crear producto: ' . $e->getMessage()];
        }
    }

    public function updateProduct($id, $data) {
        try {
            $this->authController->requireAdmin();

            // Filtrar solo los campos necesarios
            $productData = [];
            if (isset($data['nombre'])) {
                $productData['nombre'] = $data['nombre'];
            }
            if (isset($data['descripcion'])) {
                $productData['descripcion'] = $data['descripcion'];
            }
            if (isset($data['precio'])) {
                if ($data['precio'] <= 0) {
                    return ['success' => false, 'message' => 'El precio debe ser mayor a 0'];
                }
                $productData['precio'] = floatval($data['precio']);
            }
            if (isset($data['stock'])) {
                if ($data['stock'] < 0) {
                    return ['success' => false, 'message' => 'El stock no puede ser negativo'];
                }
                $productData['stock'] = intval($data['stock']);
            }
            if (isset($data['stock_minimo'])) {
                $productData['stock_minimo'] = intval($data['stock_minimo']);
            }
            if (isset($data['id_categoria'])) {
                $productData['id_categoria'] = intval($data['id_categoria']);
            }
            if (isset($data['id_proveedor'])) {
                $productData['id_proveedor'] = intval($data['id_proveedor']);
            }

            if (empty($productData)) {
                return ['success' => false, 'message' => 'No hay datos para actualizar'];
            }

            if ($this->productModel->update($id, $productData, 'id_producto')) {
                return ['success' => true, 'message' => 'Producto actualizado correctamente'];
            } else {
                return ['success' => false, 'message' => 'Error al actualizar producto'];
            }
        } catch (Exception $e) {
            // Capturar errores de triggers
            if (strpos($e->getMessage(), 'stock no puede ser negativo') !== false) {
                return ['success' => false, 'message' => 'No se puede establecer stock negativo'];
            }
            return ['success' => false, 'message' => 'Error al actualizar producto: ' . $e->getMessage()];
        }
    }

    public function deleteProduct($id) {
        try {
            $this->authController->requireAdmin();

            if ($this->productModel->delete($id, 'id_producto')) {
                return ['success' => true, 'message' => 'Producto eliminado correctamente'];
            } else {
                return ['success' => false, 'message' => 'Error al eliminar producto'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al eliminar producto: ' . $e->getMessage()];
        }
    }

    public function getLowStockProducts($limit = 19) {
        try {
            $this->authController->requireEmployee();
            
            $stmt = $this->productModel->getProductosBajoStock($limit);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return ['success' => true, 'data' => $products];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al obtener productos con stock bajo: ' . $e->getMessage()];
        }
    }

    public function searchProducts($term) {
        try {
            $this->authController->requireEmployee();
            
            $stmt = $this->productModel->buscarProductos($term);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return ['success' => true, 'data' => $products];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al buscar productos: ' . $e->getMessage()];
        }
    }

    public function getCategories() {
        try {
            $this->authController->requireEmployee();
            
            $stmt = $this->categoryModel->getCategoriasConProductos();
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return ['success' => true, 'data' => $categories];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al obtener categorías: ' . $e->getMessage()];
        }
    }

    public function getSuppliers() {
        try {
            $this->authController->requireEmployee();
            
            $stmt = $this->supplierModel->getProveedoresActivos();
            $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return ['success' => true, 'data' => $suppliers];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al obtener proveedores: ' . $e->getMessage()];
        }
    }
}
?>