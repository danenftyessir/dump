<?php

namespace Base;

use PDO;
use Exception;

abstract class Model
{
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $hidden = [];

    // constructor menerima PDO connection
    public function __construct(PDO $db) {
        $this->db = $db;
    }

    // get database connection
    public function getConnection() {
        return $this->db;
    }

    // find by primary key
    public function find($id) {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            return $this->hideAttributes($result);
        }
        
        return null;
    }

    // find first by conditions
    public function first(array $conditions = []) {
        $sql = "SELECT * FROM {$this->table}";
        
        if (!empty($conditions)) {
            $where = [];
            foreach ($conditions as $key => $value) {
                $where[] = "{$key} = :{$key}";
            }
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        
        $sql .= " LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        
        foreach ($conditions as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }
        
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            return $this->hideAttributes($result);
        }
        
        return null;
    }

    // find all by conditions
    public function where(array $conditions = [], $orderBy = null, $limit = null) {
        $sql = "SELECT * FROM {$this->table}";
        
        if (!empty($conditions)) {
            $where = [];
            foreach ($conditions as $key => $value) {
                $where[] = "{$key} = :{$key}";
            }
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        
        if ($limit) {
            $sql .= " LIMIT {$limit}";
        }
        
        $stmt = $this->db->prepare($sql);
        
        foreach ($conditions as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }
        
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return array_map([$this, 'hideAttributes'], $results);
    }

    // get all records
    public function all($orderBy = null, $limit = null) {
        return $this->where([], $orderBy, $limit);
    }

    // create new record
    public function create(array $data) {
        // filter hanya fillable fields
        $data = $this->filterFillable($data);
        
        $fields = array_keys($data);
        $values = array_values($data);
        
        $fieldsList = implode(', ', $fields);
        $placeholders = implode(', ', array_map(fn($f) => ":{$f}", $fields));
        
        $sql = "INSERT INTO {$this->table} ({$fieldsList}) VALUES ({$placeholders}) RETURNING *";
        
        $stmt = $this->db->prepare($sql);
        
        foreach ($data as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }
        
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            return $this->hideAttributes($result);
        }
        
        return null;
    }

    // update record by primary key
    public function update($id, array $data) {
        // filter hanya fillable fields
        $data = $this->filterFillable($data);
        
        $sets = [];
        foreach ($data as $key => $value) {
            $sets[] = "{$key} = :{$key}";
        }
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $sets) . " WHERE {$this->primaryKey} = :id RETURNING *";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        
        foreach ($data as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }
        
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            return $this->hideAttributes($result);
        }
        
        return null;
    }

    // delete record by primary key
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        
        return $stmt->execute();
    }

    // filter hanya fillable attributes
    protected function filterFillable(array $data) {
        if (empty($this->fillable)) {
            return $data;
        }
        
        return array_intersect_key($data, array_flip($this->fillable));
    }

    // hide attributes yang ada di $hidden
    protected function hideAttributes(array $data) {
        if (empty($this->hidden)) {
            return $data;
        }
        
        foreach ($this->hidden as $attribute) {
            unset($data[$attribute]);
        }
        
        return $data;
    }

    // begin transaction
    public function beginTransaction() {
        return $this->db->beginTransaction();
    }

    // commit transaction
    public function commit() {
        return $this->db->commit();
    }

    // rollback transaction
    public function rollback() {
        return $this->db->rollBack();
    }
}