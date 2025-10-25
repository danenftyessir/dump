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
        'balance',
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
}