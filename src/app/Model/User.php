<?php

namespace Model;

use Base\Model;
use PDO;
use Exception;

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

    // find user by email (tanpa password untuk keamanan)
    public function findByEmail($email) {
        return $this->first(['email' => $email]);
    }

    // find user by email DENGAN password (khusus untuk login)
    public function findByEmailWithPassword($email) {
        $sql = "SELECT * FROM {$this->table} WHERE email = :email LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC); // tidak hide password
    }

    // create user dengan hashed password
    public function createUser($data) {
        // hash password sebelum insert
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        // set default values
        if (!isset($data['role'])) {
            $data['role'] = 'BUYER';
        }
        
        if (!isset($data['balance'])) {
            $data['balance'] = 0;
        }
        
        return $this->create($data);
    }

    // verify password
    public function verifyPassword($plainPassword, $hashedPassword) {
        return password_verify($plainPassword, $hashedPassword);
    }

    // update password
    public function updatePassword($userId, $newPassword) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        return $this->update($userId, ['password' => $hashedPassword]);
    }

    // get users by role
    public function getByRole($role) {
        return $this->where(['role' => $role]);
    }

    // update user balance
    public function updateBalance($userId, $amount) {
        $user = $this->find($userId);
        
        if (!$user) {
            throw new Exception('User tidak ditemukan');
        }
        
        $newBalance = $user['balance'] + $amount;
        
        // balance tidak boleh negatif
        if ($newBalance < 0) {
            throw new Exception('Balance tidak mencukupi');
        }
        
        return $this->update($userId, ['balance' => $newBalance]);
    }

    // deduct balance (untuk pembelian)
    public function deductBalance($userId, $amount) {
        if ($amount <= 0) {
            throw new Exception('Amount harus lebih dari 0');
        }
        
        return $this->updateBalance($userId, -$amount);
    }

    // add balance (untuk top up atau refund)
    public function addBalance($userId, $amount) {
        if ($amount <= 0) {
            throw new Exception('Amount harus lebih dari 0');
        }
        
        return $this->updateBalance($userId, $amount);
    }

    // check if user is seller
    public function isSeller($userId) {
        $user = $this->find($userId);
        return $user && $user['role'] === 'SELLER';
    }

    // check if user is buyer
    public function isBuyer($userId) {
        $user = $this->find($userId);
        return $user && $user['role'] === 'BUYER';
    }

    // check if email already exists
    public function emailExists($email, $excludeUserId = null) {
        $sql = "SELECT 1 FROM {$this->table} WHERE email = :email";
        $bindings = [':email' => $email];
        
        if ($excludeUserId) {
            $sql .= " AND user_id != :exclude_id";
            $bindings[':exclude_id'] = $excludeUserId;
        }
        
        $sql .= " LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        foreach ($bindings as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        return (bool)$stmt->fetch();
    }

    // login user (return user data jika berhasil)
    public function login($email, $password) {
        $user = $this->findByEmailWithPassword($email);
        
        if (!$user) {
            return false;
        }
        
        if (!$this->verifyPassword($password, $user['password'])) {
            return false;
        }
        
        // remove password dari returned data
        unset($user['password']);
        
        return $user;
    }

    // register new user
    public function register($userData) {
        // validasi email belum terdaftar
        if ($this->emailExists($userData['email'])) {
            throw new Exception('Email sudah terdaftar');
        }

        // validasi required fields
        $required = ['email', 'password', 'name'];
        foreach ($required as $field) {
            if (empty($userData[$field])) {
                throw new Exception("Field {$field} wajib diisi");
            }
        }

        // validasi format email
        if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Format email tidak valid');
        }

        // validasi password strength
        if (strlen($userData['password']) < 8) {
            throw new Exception('Password minimal 8 karakter');
        }

        return $this->createUser($userData);
    }

    // update profile
    public function updateProfile($userId, $data) {
        // tidak boleh update password, email, role, balance lewat sini
        $allowedFields = ['name', 'address'];
        $updateData = [];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }
        
        if (empty($updateData)) {
            throw new Exception('Tidak ada data yang diupdate');
        }
        
        return $this->update($userId, $updateData);
    }

    // get user statistics (untuk admin atau profile)
    public function getUserStats($userId) {
        $user = $this->find($userId);
        
        if (!$user) {
            return null;
        }
        
        $stats = [
            'user_id' => $user['user_id'],
            'name' => $user['name'],
            'role' => $user['role'],
            'balance' => $user['balance'],
            'created_at' => $user['created_at']
        ];
        
        // jika seller, tambah stats toko
        if ($user['role'] === 'SELLER') {
            $sql = "SELECT 
                        s.store_id,
                        s.store_name,
                        COUNT(DISTINCT p.product_id) as total_products,
                        COUNT(DISTINCT o.order_id) as total_orders
                    FROM stores s
                    LEFT JOIN products p ON s.store_id = p.store_id
                    LEFT JOIN orders o ON s.store_id = o.store_id
                    WHERE s.user_id = :user_id
                    GROUP BY s.store_id, s.store_name";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            
            $stats['store'] = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        // jika buyer, tambah stats order
        if ($user['role'] === 'BUYER') {
            $sql = "SELECT 
                        COUNT(*) as total_orders,
                        COALESCE(SUM(total_price), 0) as total_spent
                    FROM orders
                    WHERE buyer_id = :user_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            
            $orderStats = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['orders'] = $orderStats;
        }
        
        return $stats;
    }

    // get semua buyers (untuk admin)
    public function getAllBuyers($page = 1, $limit = 20) {
        $offset = ($page - 1) * $limit;
        
        // count total
        $countSql = "SELECT COUNT(*) as total FROM {$this->table} WHERE role = 'BUYER'";
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute();
        $totalItems = (int)$countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // get buyers
        $sql = "SELECT 
                    user_id,
                    email,
                    name,
                    balance,
                    created_at
                FROM {$this->table}
                WHERE role = 'BUYER'
                ORDER BY created_at DESC
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $buyers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $totalPages = ceil($totalItems / $limit);
        
        return [
            'buyers' => $buyers,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_items' => $totalItems,
                'items_per_page' => $limit
            ]
        ];
    }

    // get semua sellers (untuk admin)
    public function getAllSellers($page = 1, $limit = 20) {
        $offset = ($page - 1) * $limit;
        
        // count total
        $countSql = "SELECT COUNT(*) as total FROM {$this->table} WHERE role = 'SELLER'";
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute();
        $totalItems = (int)$countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // get sellers dengan info toko
        $sql = "SELECT 
                    u.user_id,
                    u.email,
                    u.name,
                    u.balance,
                    u.created_at,
                    s.store_id,
                    s.store_name
                FROM {$this->table} u
                LEFT JOIN stores s ON u.user_id = s.user_id
                WHERE u.role = 'SELLER'
                ORDER BY u.created_at DESC
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $sellers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $totalPages = ceil($totalItems / $limit);
        
        return [
            'sellers' => $sellers,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_items' => $totalItems,
                'items_per_page' => $limit
            ]
        ];
    }
}