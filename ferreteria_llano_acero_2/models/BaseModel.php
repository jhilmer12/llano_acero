<?php
class BaseModel {
    protected $conn;
    protected $table_name;

    public function __construct($db, $table) {
        $this->conn = $db;
        $this->table_name = $table;
    }

    // Métodos CRUD genéricos
    public function readAll() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE activo = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function getById($id, $id_field = 'id') {
        $query = "SELECT * FROM " . $this->table_name . " WHERE " . $id_field . " = ? AND activo = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $columns = implode(", ", array_keys($data));
        $placeholders = ":" . implode(", :", array_keys($data));
        
        $query = "INSERT INTO " . $this->table_name . " (" . $columns . ") VALUES (" . $placeholders . ")";
        $stmt = $this->conn->prepare($query);
        
        if ($stmt->execute($data)) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function update($id, $data, $id_field = 'id') {
        $setClause = [];
        foreach ($data as $key => $value) {
            $setClause[] = "$key = :$key";
        }
        $setClause = implode(", ", $setClause);
        
        $query = "UPDATE " . $this->table_name . " SET " . $setClause . " WHERE " . $id_field . " = :id";
        $stmt = $this->conn->prepare($query);
        
        $data['id'] = $id;
        return $stmt->execute($data);
    }

    public function delete($id, $id_field = 'id') {
        $query = "UPDATE " . $this->table_name . " SET activo = 0 WHERE " . $id_field . " = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$id]);
    }

    public function countAll() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " WHERE activo = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }
}
?>