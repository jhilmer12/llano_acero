<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/CustomerModel.php';
require_once __DIR__ . '/AuthController.php';

class CustomerController {
    private $db;
    private $customerModel;
    private $authController;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->customerModel = new CustomerModel($this->db);
        $this->authController = new AuthController();
    }

    public function getCustomers() {
        try {
            $this->authController->requireEmployee();
            
            $stmt = $this->customerModel->getClientesConCompras();
            $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return ['success' => true, 'data' => $customers];
        } catch (Exception $e) {
            error_log("Error en getCustomers: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al obtener clientes: ' . $e->getMessage()];
        }
    }

    public function getCustomer($id) {
        try {
            $this->authController->requireEmployee();
            
            $customer = $this->customerModel->getById($id, 'id_cliente');
            if ($customer) {
                return ['success' => true, 'data' => $customer];
            } else {
                return ['success' => false, 'message' => 'Cliente no encontrado'];
            }
        } catch (Exception $e) {
            error_log("Error en getCustomer: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al obtener cliente: ' . $e->getMessage()];
        }
    }

    public function createCustomer($data) {
        try {
            $this->authController->requireEmployee();

            // Validar campos requeridos
            if (empty($data['nombre'])) {
                return ['success' => false, 'message' => 'El nombre es requerido'];
            }

            // Validar email único
            if (!empty($data['email'])) {
                $existing = $this->customerModel->buscarClientes($data['email']);
                $existingCustomers = $existing->fetchAll(PDO::FETCH_ASSOC);
                if (count($existingCustomers) > 0) {
                    return ['success' => false, 'message' => 'Ya existe un cliente con ese email'];
                }
            }

            // Filtrar solo los campos necesarios
            $customerData = [
                'nombre' => trim($data['nombre']),
                'telefono' => $data['telefono'] ?? '',
                'email' => $data['email'] ?? '',
                'direccion' => $data['direccion'] ?? ''
            ];

            $id = $this->customerModel->create($customerData);
            if ($id) {
                return [
                    'success' => true, 
                    'message' => 'Cliente creado correctamente', 
                    'id' => $id,
                    'customer' => $customerData
                ];
            } else {
                return ['success' => false, 'message' => 'Error al crear cliente en la base de datos'];
            }
        } catch (Exception $e) {
            error_log("Error en createCustomer: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al crear cliente: ' . $e->getMessage()];
        }
    }

    public function updateCustomer($id, $data) {
        try {
            $this->authController->requireEmployee();

            // Verificar que el cliente existe
            $existingCustomer = $this->customerModel->getById($id, 'id_cliente');
            if (!$existingCustomer) {
                return ['success' => false, 'message' => 'Cliente no encontrado'];
            }

            // Filtrar solo los campos necesarios
            $customerData = [];
            if (isset($data['nombre'])) {
                $customerData['nombre'] = trim($data['nombre']);
            }
            if (isset($data['telefono'])) {
                $customerData['telefono'] = $data['telefono'];
            }
            if (isset($data['email'])) {
                $customerData['email'] = $data['email'];
            }
            if (isset($data['direccion'])) {
                $customerData['direccion'] = $data['direccion'];
            }

            if (empty($customerData)) {
                return ['success' => false, 'message' => 'No hay datos para actualizar'];
            }

            if ($this->customerModel->update($id, $customerData, 'id_cliente')) {
                return [
                    'success' => true, 
                    'message' => 'Cliente actualizado correctamente',
                    'customer' => array_merge($existingCustomer, $customerData)
                ];
            } else {
                return ['success' => false, 'message' => 'Error al actualizar cliente en la base de datos'];
            }
        } catch (Exception $e) {
            error_log("Error en updateCustomer: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al actualizar cliente: ' . $e->getMessage()];
        }
    }

    public function deleteCustomer($id) {
        try {
            $this->authController->requireEmployee();

            // Verificar que el cliente existe
            $customer = $this->customerModel->getById($id, 'id_cliente');
            if (!$customer) {
                return ['success' => false, 'message' => 'Cliente no encontrado'];
            }

            // Verificar si el cliente tiene ventas asociadas
            if ($this->customerModel->tieneVentas($id)) {
                return [
                    'success' => false, 
                    'message' => 'No se puede eliminar el cliente porque tiene ventas asociadas. Se recomienda desactivarlo.'
                ];
            }

            // Realizar eliminación lógica
            if ($this->customerModel->delete($id, 'id_cliente')) {
                return [
                    'success' => true, 
                    'message' => 'Cliente eliminado correctamente',
                    'deleted_customer' => $customer
                ];
            } else {
                return ['success' => false, 'message' => 'Error al eliminar cliente de la base de datos'];
            }
        } catch (Exception $e) {
            error_log("Error en deleteCustomer: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al eliminar cliente: ' . $e->getMessage()];
        }
    }

    public function searchCustomers($term) {
        try {
            $this->authController->requireEmployee();
            
            $stmt = $this->customerModel->buscarClientes($term);
            $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return ['success' => true, 'data' => $customers];
        } catch (Exception $e) {
            error_log("Error en searchCustomers: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al buscar clientes: ' . $e->getMessage()];
        }
    }

    public function getPurchaseHistory($id_cliente) {
        try {
            $this->authController->requireEmployee();
            
            // Verificar que el cliente existe
            $customer = $this->customerModel->getById($id_cliente, 'id_cliente');
            if (!$customer) {
                return ['success' => false, 'message' => 'Cliente no encontrado'];
            }
            
            $stmt = $this->customerModel->getHistorialCompras($id_cliente);
            $purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Obtener detalles para cada venta
            foreach ($purchases as &$purchase) {
                $detalles_stmt = $this->customerModel->getDetallesVenta($purchase['id_venta']);
                $purchase['detalles'] = $detalles_stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            return [
                'success' => true, 
                'data' => $purchases,
                'customer' => $customer
            ];
        } catch (Exception $e) {
            error_log("Error en getPurchaseHistory: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error al obtener historial de compras: ' . $e->getMessage()];
        }
    }
}
?>