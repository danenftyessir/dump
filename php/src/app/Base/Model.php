<?php

namespace Base;

use PDO;

abstract class Model
{
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $hidden = [];
    protected $db;


    // Ctor
    public function __construct(PDO $db) {
        $this->db = $db;
        
        // Auto set table name if not defined
        if (!$this->table) {
            $this->table = strtolower(get_class($this)) . 's';
        }
    }

    // Find Record by Primary Key
    public function find($id) {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $this->hideFields($result) : null;
    }

    // Find All Records
    public function all() {
        $sql = "SELECT * FROM {$this->table}";
        $stmt = $this->db->query($sql);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return array_map([$this, 'hideFields'], $results);
    }

    // Find Records with Selection Conditions
    public function where($conditions = [], $limit = null, $offset = null) {
        $sql = "SELECT * FROM {$this->table}";
        $bindings = [];
        
        if (!empty($conditions)) {
            $whereClauses = [];
            foreach ($conditions as $field => $value) {
                $placeholder = ':' . $field;
                $whereClauses[] = "{$field} = {$placeholder}";
                $bindings[$placeholder] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $whereClauses);
        }
        
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
            if ($offset) {
                $sql .= " OFFSET " . (int)$offset;
            }
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($bindings);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return array_map([$this, 'hideFields'], $results);
    }

    // Find First Record Matching Conditions
    public function first($conditions = []) {
        $results = $this->where($conditions, 1);
        return !empty($results) ? $results[0] : null;
    }

    // Create New Record
    public function create($data) {
        // Filter only fillable fields
        $data = $this->filterFillable($data);

        if (empty($data)) {
            return false;
        }
        
        $fields = array_keys($data);
        $placeholders = array_map(function($field) { return ':' . $field; }, $fields);
        
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
        
        $stmt = $this->db->prepare($sql);
        
        // Bind values
        foreach ($data as $field => $value) {
            $stmt->bindValue(':' . $field, $value);
        }
        
        if ($stmt->execute()) {
            $data[$this->primaryKey] = $this->db->lastInsertId();
            return $this->hideFields($data);
        }
        
        return false;
    }

    // Update Record by Primary Key
    public function update($id, $data) {
        // Filter only fillable fields
        $data = $this->filterFillable($data);

        if (empty($data)) {
            return false;
        }
        
        $setClauses = [];
        foreach ($data as $field => $value) {
            $setClauses[] = "{$field} = :{$field}";
        }
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $setClauses) . " WHERE {$this->primaryKey} = :id";
        
        $stmt = $this->db->prepare($sql);
        
        // Bind values
        foreach ($data as $field => $value) {
            $stmt->bindValue(':' . $field, $value);
        }
        $stmt->bindValue(':id', $id);
        
        return $stmt->execute();
    }

    // Delete Record by Primary Key
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }

    // Execute Query
    public function query($sql, $bindings = []) {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($bindings);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Count records
    public function count($conditions = []) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        $bindings = [];
        
        if (!empty($conditions)) {
            $whereClauses = [];
            foreach ($conditions as $field => $value) {
                $placeholder = ':' . $field;
                $whereClauses[] = "{$field} = {$placeholder}";
                $bindings[$placeholder] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $whereClauses);
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($bindings);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) $result['count'];
    }

    // Hide Fields from Result
    private function hideFields($data) {
        if (!empty($this->hidden) && is_array($data)) {
            foreach ($this->hidden as $field) {
                unset($data[$field]);
            }
        }
        return $data;
    }

    // Filter Fillable Fields
    protected function filterFillable(array $data) {
        if (empty($this->fillable)) {
            return $data;
        }
        
        return array_intersect_key($data, array_flip($this->fillable));
    }

    // Begin Database Transaction
    public function beginTransaction() {
        return $this->db->beginTransaction();
    }

    // Commit Database Transaction
    public function commit() {
        return $this->db->commit();
    }

    // Rollback Database Transaction
    public function rollback() {
        return $this->db->rollback();
    }

    // Get database connection
    public function getConnection() {
        return $this->db;
    }
}