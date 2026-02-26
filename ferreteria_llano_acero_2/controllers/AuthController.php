<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/UserModel.php';

class AuthController {
    private $db;
    private $userModel;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        
        if (!$this->db) {
            throw new Exception("No se pudo conectar a la base de datos");
        }
        
        $this->userModel = new UserModel($this->db);
        
        // Iniciar sesión solo si no está activa
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function login($email, $password) {
        try {
            if (empty($email) || empty($password)) {
                return ['success' => false, 'message' => 'Email y contraseña son requeridos'];
            }

            $user = $this->userModel->login($email, $password);
            if ($user) {
                $_SESSION['user'] = $user;
                return ['success' => true, 'user' => $user];
            } else {
                return ['success' => false, 'message' => 'Credenciales incorrectas'];
            }
        } catch (Exception $e) {
            error_log("Error en login: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error en el servidor: ' . $e->getMessage()];
        }
    }

    public function logout() {
        try {
            $_SESSION = [];
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }
            session_destroy();
            return ['success' => true, 'message' => 'Sesión cerrada correctamente'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error al cerrar sesión'];
        }
    }

    public function checkAuth() {
        return isset($_SESSION['user']);
    }

    public function getUserRole() {
        return $_SESSION['user']['tipo'] ?? null;
    }

    public function getCurrentUser() {
        return $_SESSION['user'] ?? null;
    }

    public function requireAuth() {
        if (!$this->checkAuth()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'No autorizado']);
            exit;
        }
    }

    public function requireAdmin() {
        $this->requireAuth();
        if ($this->getUserRole() !== 'admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Se requieren permisos de administrador']);
            exit;
        }
    }

    public function requireEmployee() {
        $this->requireAuth();
        $role = $this->getUserRole();
        if (!in_array($role, ['admin', 'empleado'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Se requieren permisos de empleado']);
            exit;
        }
    }
}
?>