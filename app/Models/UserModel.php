<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $allowedFields = ['email', 'phone', 'password', 'role', 'marketing_name', 'branch', 'created_at', 'verification_token', 'is_verified'];

    // Tentukan nama kolom kustom untuk timestamps
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at'; // Jika Anda menambahkan kolom ini

    public function getUserById($id)
    {
        return $this->where('id', $id)->first(); // Fetch the first matching record
    }
}
