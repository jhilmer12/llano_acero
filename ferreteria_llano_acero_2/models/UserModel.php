<?php
require_once 'BaseModel.php';

class UserModel extends BaseModel {
    public function __construct($db) {
        parent::__construct($db, 'usuarios');
    }

    public function login($email, $password) {
        $query = "SELECT id_usuario, nombre, email, password, tipo FROM usuarios WHERE email = ? AND activo = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() == 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($password, $user['password'])) {
                return $user;
            }
        }
        return false;
    }

    public function createUser($data) {
        // Encriptar contraseña antes de guardar
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        return $this->create($data);
    }

    public function updateUser($id, $data) {
        // Encriptar contraseña si se está actualizando
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        } else {
            // Si no se actualiza la contraseña, remover del array
            unset($data['password']);
        }
        return $this->update($id, $data, 'id_usuario');
    }

    public function getUsersByType($tipo) {
        $query = "SELECT * FROM usuarios WHERE tipo = ? AND activo = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$tipo]);
        return $stmt;
    }
}
?>