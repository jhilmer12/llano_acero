<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/SupplierModel.php';
require_once __DIR__ . '/AuthController.php';

class SupplierController {
    private $db;
    private $supplierModel;
    private $authController;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->supplierModel = new SupplierModel($this->db);
        $this->authController = new AuthController();
    }

    public function getSuppliers() {
        try {
            $this->authController->requireEmployee();
            
            $stmt = $this->supplierModel->getProveedoresConProductos();
            $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return ['success' => true, 'data' => $suppliers];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al obtener proveedores: ' . $e->getMessage()];
        }
    }

    public function getSupplier($id) {
        try {
            $this->authController->requireEmployee();
            
            $supplier = $this->supplierModel->getById($id, 'id_proveedor');
            if ($supplier) {
                return ['success' => true, 'data' => $supplier];
            } else {
                return ['success' => false, 'message' => 'Proveedor no encontrado'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al obtener proveedor: ' . $e->getMessage()];
        }
    }

    public function createSupplier($data) {
        try {
            $this->authController->requireAdmin();

            // Validar campos requeridos
            if (empty($data['nombre'])) {
                return ['success' => false, 'message' => 'El nombre es requerido'];
            }

            // Filtrar solo los campos necesarios
            $supplierData = [
                'nombre' => $data['nombre'],
                'contacto' => $data['contacto'] ?? '',
                'telefono' => $data['telefono'] ?? '',
                'email' => $data['email'] ?? '',
                'direccion' => $data['direccion'] ?? ''
            ];

            $id = $this->supplierModel->create($supplierData);
            if ($id) {
                return ['success' => true, 'message' => 'Proveedor creado correctamente', 'id' => $id];
            } else {
                return ['success' => false, 'message' => 'Error al crear proveedor'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al crear proveedor: ' . $e->getMessage()];
        }
    }

    public function updateSupplier($id, $data) {
        try {
            $this->authController->requireAdmin();

            // Filtrar solo los campos necesarios
            $supplierData = [];
            if (isset($data['nombre'])) {
                $supplierData['nombre'] = $data['nombre'];
            }
            if (isset($data['contacto'])) {
                $supplierData['contacto'] = $data['contacto'];
            }
            if (isset($data['telefono'])) {
                $supplierData['telefono'] = $data['telefono'];
            }
            if (isset($data['email'])) {
                $supplierData['email'] = $data['email'];
            }
            if (isset($data['direccion'])) {
                $supplierData['direccion'] = $data['direccion'];
            }

            if (empty($supplierData)) {
                return ['success' => false, 'message' => 'No hay datos para actualizar'];
            }

            if ($this->supplierModel->update($id, $supplierData, 'id_proveedor')) {
                return ['success' => true, 'message' => 'Proveedor actualizado correctamente'];
            } else {
                return ['success' => false, 'message' => 'Error al actualizar proveedor'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al actualizar proveedor: ' . $e->getMessage()];
        }
    }

    public function deleteSupplier($id) {
        try {
            $this->authController->requireAdmin();

            // Verificar si el proveedor tiene productos
            $stmt = $this->supplierModel->getProveedoresConProductos();
            $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($suppliers as $supplier) {
                if ($supplier['id_proveedor'] == $id && $supplier['total_productos'] > 0) {
                    return ['success' => false, 'message' => 'No se puede eliminar un proveedor que tiene productos asociados'];
                }
            }

            if ($this->supplierModel->delete($id, 'id_proveedor')) {
                return ['success' => true, 'message' => 'Proveedor eliminado correctamente'];
            } else {
                return ['success' => false, 'message' => 'Error al eliminar proveedor'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al eliminar proveedor: ' . $e->getMessage()];
        }
    }

    public function searchSuppliers($term) {
        try {
            $this->authController->requireEmployee();
            
            $stmt = $this->supplierModel->buscarProveedores($term);
            $suppliers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return ['success' => true, 'data' => $suppliers];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al buscar proveedores: ' . $e->getMessage()];
        }
    }
}
?>