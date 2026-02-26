<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/CategoryModel.php';
require_once __DIR__ . '/AuthController.php';

class CategoryController {
    private $db;
    private $categoryModel;
    private $authController;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->categoryModel = new CategoryModel($this->db);
        $this->authController = new AuthController();
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

    public function getCategory($id) {
        try {
            $this->authController->requireEmployee();
            
            $category = $this->categoryModel->getById($id, 'id_categoria');
            if ($category) {
                return ['success' => true, 'data' => $category];
            } else {
                return ['success' => false, 'message' => 'Categoría no encontrada'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al obtener categoría: ' . $e->getMessage()];
        }
    }

    public function createCategory($data) {
        try {
            $this->authController->requireAdmin();

            error_log("📝 CategoryController: Creando categoría con datos: " . print_r($data, true));

            // Validar campos requeridos
            if (empty($data['nombre'])) {
                error_log("❌ CategoryController: Nombre requerido");
                return ['success' => false, 'message' => 'El nombre es requerido'];
            }

            // Filtrar solo los campos necesarios
            $categoryData = [
                'nombre' => trim($data['nombre']),
                'descripcion' => isset($data['descripcion']) ? trim($data['descripcion']) : ''
            ];

            error_log("📦 CategoryController: Datos filtrados: " . print_r($categoryData, true));

            $id = $this->categoryModel->create($categoryData);
            if ($id) {
                error_log("✅ CategoryController: Categoría creada correctamente ID: " . $id);
                return ['success' => true, 'message' => 'Categoría creada correctamente', 'id' => $id];
            } else {
                error_log("❌ CategoryController: Error al crear categoría");
                return ['success' => false, 'message' => 'Error al crear categoría'];
            }
        } catch (Exception $e) {
            error_log("💥 CategoryController Error en createCategory: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al crear categoría: ' . $e->getMessage()];
        }
    }

    public function updateCategory($id, $data) {
        try {
            $this->authController->requireAdmin();

            // Filtrar solo los campos necesarios
            $categoryData = [];
            if (isset($data['nombre'])) {
                $categoryData['nombre'] = $data['nombre'];
            }
            if (isset($data['descripcion'])) {
                $categoryData['descripcion'] = $data['descripcion'];
            }

            if (empty($categoryData)) {
                return ['success' => false, 'message' => 'No hay datos para actualizar'];
            }

            if ($this->categoryModel->update($id, $categoryData, 'id_categoria')) {
                return ['success' => true, 'message' => 'Categoría actualizada correctamente'];
            } else {
                return ['success' => false, 'message' => 'Error al actualizar categoría'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al actualizar categoría: ' . $e->getMessage()];
        }
    }

    public function deleteCategory($id) {
        try {
            $this->authController->requireAdmin();

            error_log("🗑️ CategoryController: Eliminando categoría ID: " . $id);

            // Verificar si la categoría existe primero
            $category = $this->categoryModel->getById($id, 'id_categoria');
            if (!$category) {
                error_log("❌ CategoryController: Categoría no encontrada ID: " . $id);
                return ['success' => false, 'message' => 'Categoría no encontrada'];
            }

            // Verificar si la categoría tiene productos activos
            if ($this->categoryModel->tieneProductosActivos($id)) {
                return ['success' => false, 'message' => 'No se puede eliminar una categoría que tiene productos asociados'];
            }

            // Eliminar la categoría (soft delete)
            if ($this->categoryModel->delete($id, 'id_categoria')) {
                error_log("✅ CategoryController: Categoría eliminada correctamente ID: " . $id);
                return ['success' => true, 'message' => 'Categoría eliminada correctamente'];
            } else {
                error_log("❌ CategoryController: Error al eliminar categoría ID: " . $id);
                return ['success' => false, 'message' => 'Error al eliminar categoría'];
            }
        } catch (Exception $e) {
            error_log("💥 CategoryController Error en deleteCategory: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al eliminar categoría: ' . $e->getMessage()];
        }
    }
}
?>