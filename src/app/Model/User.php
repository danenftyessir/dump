<?php

namespace Model;

use Base\Model;
use PDO;

class User extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'user_id';
    
    protected $fillable = [
        'email',
        'password', 
        'role',
        'name',
        'address',
        'balance'
    ];
    
    protected $hidden = [
        'password'
    ];

    // Ctor
    public function __construct(PDO $db) {
        parent::__construct($db);
    }

    // Find User by Email
    public function findByEmail($email) {
        return $this->first(['email' => $email]);
    }

    // Find User by Email (for login with password)
    public function findByEmailWithPassword($email) {
        $sql = "SELECT * FROM {$this->table} WHERE email = :email LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    // Create New User with Hashed Password
    public function createUser($data) {
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        return $this->create($data);
    }

    // Verify Password
    public function verifyPassword($plainPassword, $hashedPassword) {
        if (empty($hashedPassword) || empty($plainPassword)) {
            return false;
        }
        
        return password_verify($plainPassword, $hashedPassword);
    }

    // Get Users by Role
    public function getByRole($role) {
        return $this->where(['role' => $role]);
    }

    // Update User Balance
    public function updateBalance($userId, $amount) {
        $sql = "UPDATE {$this->table}
                SET balance = balance + :amount
                WHERE {$this->primaryKey} = :userId";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':amount', (int)$amount, PDO::PARAM_INT);
        $stmt->bindValue(':userId', (int)$userId, PDO::PARAM_INT);
        return $stmt->execute();
    }
}