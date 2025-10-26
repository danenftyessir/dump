<?php
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

    // Find User by Email
    public function findByEmail($email) {
        return $this->first(['email' => $email]);
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
        return password_verify($plainPassword, $hashedPassword);
    }

    // Get Users by Role
    public function getByRole($role) {
        return $this->where(['role' => $role]);
    }

    // Update User Balance
    public function updateBalance($userId, $amount) {
        $user = $this->find($userId);
        if (!$user) {
            return false;
        }
        
        $newBalance = $user['balance'] + $amount;
        return $this->update($userId, ['balance' => $newBalance]);
    }

    // Login User
    public function login($email, $password) {
        $user = $this->findByEmail($email);
        
        if (!$user || !$this->verifyPassword($password, $user['password'])) {
            return false;
        }
        
        // Remove password from returned data
        unset($user['password']);
        return $user;
    }

    // Register New User
    public function register($userData) {
        // Check if email already exists
        if ($this->findByEmail($userData['email'])) {
            throw new Exception('Email already registered');
        }

        // Validate required fields
        $required = ['email', 'password', 'name'];
        foreach ($required as $field) {
            if (empty($userData[$field])) {
                throw new Exception("Field '$field' is required");
            }
        }

        // Set default values
        $userData['role'] = $userData['role'] ?? 'BUYER';
        $userData['balance'] = $userData['balance'] ?? 0;

        return $this->createUser($userData);
    }

    // Check if User is Seller
    public function isSeller($userId) {
        $user = $this->find($userId);
        return $user && $user['role'] === 'SELLER';
    }
}