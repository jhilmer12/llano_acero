<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/AuthController.php';

class UserController {
    private $db;
    private $userModel;
    private $authController;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->userModel = new UserModel($this->db);
        $this->authController = new AuthController();
    }

    public function getUsers() {
        try {
            $this->authController->requireAdmin();
            
            $stmt = $this->userModel->readAll();
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($users as &$user) {
                unset($user['password']);
            }
            
            return ['success' => true, 'data' => $users];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al obtener usuarios: ' . $e->getMessage()];
        }
    }

    public function getUser($id) {
        try {
            $this->authController->requireAuth();
            
            $user = $this->userModel->getById($id, 'id_usuario');
            if ($user) {
                unset($user['password']);
                return ['success' => true, 'data' => $user];
            } else {
                return ['success' => false, 'message' => 'Usuario no encontrado'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al obtener usuario: ' . $e->getMessage()];
        }
    }

    public function createUser($data) {
        try {
            $this->authController->requireAdmin();

            $required = ['nombre', 'email', 'password', 'tipo'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return ['success' => false, 'message' => "El campo $field es requerido"];
                }
            }

            if (!in_array($data['tipo'], ['admin', 'empleado'])) {
                return ['success' => false, 'message' => 'Tipo de usuario no válido'];
            }

            $userData = [
                'nombre' => $data['nombre'],
                'email' => $data['email'],
                'password' => $data['password'],
                'tipo' => $data['tipo']
            ];

            $id = $this->userModel->createUser($userData);
            if ($id) {
                return ['success' => true, 'message' => 'Usuario creado correctamente', 'id' => $id];
            } else {
                return ['success' => false, 'message' => 'Error al crear usuario'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al crear usuario: ' . $e->getMessage()];
        }
    }

    public function updateUser($id, $data) {
        try {
            $this->authController->requireAdmin();

            $currentUser = $this->authController->getCurrentUser();
            if ($currentUser['id_usuario'] == $id) {
                return ['success' => false, 'message' => 'No puedes modificar tu propio usuario'];
            }

            $userData = [];
            if (isset($data['nombre'])) {
                $userData['nombre'] = $data['nombre'];
            }
            if (isset($data['email'])) {
                $userData['email'] = $data['email'];
            }
            if (isset($data['password']) && !empty($data['password'])) {
                $userData['password'] = $data['password'];
            }
            if (isset($data['tipo'])) {
                if (!in_array($data['tipo'], ['admin', 'empleado'])) {
                    return ['success' => false, 'message' => 'Tipo de usuario no válido'];
                }
                $userData['tipo'] = $data['tipo'];
            }

            if (empty($userData)) {
                return ['success' => false, 'message' => 'No hay datos para actualizar'];
            }

            if ($this->userModel->updateUser($id, $userData)) {
                return ['success' => true, 'message' => 'Usuario actualizado correctamente'];
            } else {
                return ['success' => false, 'message' => 'Error al actualizar usuario'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al actualizar usuario: ' . $e->getMessage()];
        }
    }

    public function deleteUser($id) {
        try {
            $this->authController->requireAdmin();

            $currentUser = $this->authController->getCurrentUser();
            if ($currentUser['id_usuario'] == $id) {
                return ['success' => false, 'message' => 'No puedes eliminar tu propio usuario'];
            }

            if ($this->userModel->delete($id, 'id_usuario')) {
                return ['success' => true, 'message' => 'Usuario eliminado correctamente'];
            } else {
                return ['success' => false, 'message' => 'Error al eliminar usuario'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al eliminar usuario: ' . $e->getMessage()];
        }
    }

    public function getUsersByType($tipo) {
        try {
            $this->authController->requireAdmin();

            $stmt = $this->userModel->getUsersByType($tipo);
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($users as &$user) {
                unset($user['password']);
            }
            
            return ['success' => true, 'data' => $users];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al obtener usuarios: ' . $e->getMessage()];
        }
    }
}
?>