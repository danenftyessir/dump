<?php

namespace Model;

use Base\Model;
use PDO;

class Category extends Model
{
    protected $table = 'categories';
    protected $primaryKey = 'category_id';
    
    protected $fillable = [
        'name'
    ];

    // Ctor
    public function __construct(PDO $db) {
        parent::__construct($db);
    }

    // Find Category by Name
    public function findByName($name) {
        return $this->first(['name' => $name]);
    }
}