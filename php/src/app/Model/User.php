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

    // Verify Password
    public function verifyPassword($plainPassword, $hashedPassword) {
        if (empty($hashedPassword) || empty($plainPassword)) {
            return false;
        }
        
        return password_verify($plainPassword, $hashedPassword);
    }

    // Check Current Password
    public function checkCurrentPassword($userId, $plainPassword) {
        $sql = "SELECT password FROM {$this->table} WHERE user_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) return false;
        
        return $this->verifyPassword($plainPassword, $user['password']);
    }

    // Update User Password
    public function updatePassword($userId, $newPassword) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        return $this->update($userId, ['password' => $hashedPassword]);
    }

    // Get Users by Role
    public function getByRole($role) {
        return $this->where(['role' => $role]);
    }

    // deduct balance (untuk pembelian)
    public function deductBalance($userId, $amount) {
        if ($amount <= 0) {
            throw new Exception('Amount harus lebih dari 0');
        }

        $sql = "UPDATE {$this->table}
                SET balance = balance - :amount
                WHERE {$this->primaryKey} = :userId AND balance >= :amount";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':amount', $amount, PDO::PARAM_INT);
        $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);

        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            $user = $this->find($userId); 
            if (!$user) {
                throw new Exception('User tidak ditemukan saat pengurangan saldo.');
            }

            throw new Exception('Saldo tidak mencukupi untuk operasi ini.');
        }

        return true;
    }

    // add balance (untuk top up atau refund)
    public function addBalance($userId, $amount) {
        if ($amount <= 0) {
            throw new Exception('Amount harus lebih dari 0');
        }
        
        $sql = "UPDATE {$this->table}
                SET balance = balance + :amount
                WHERE {$this->primaryKey} = :userId";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':amount', $amount, PDO::PARAM_INT);
        $stmt->bindValue(':userId', $userId, PDO::PARAM_INT);
        
        return $stmt->execute();
    }

    // find user by store id (untuk mendapatkan seller dari store)
    public function findByStoreId(int $storeId) {
        $sql = "SELECT u.*
                FROM {$this->table} u
                JOIN stores s ON u.user_id = s.user_id
                WHERE s.store_id = :store_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':store_id', $storeId, PDO::PARAM_INT);
        $stmt->execute();

        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        return $data ?: null;
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