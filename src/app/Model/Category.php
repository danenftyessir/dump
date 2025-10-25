<?php
class Category extends Model
{
    protected $table = 'categories';
    protected $primaryKey = 'category_id';
    
    protected $fillable = [
        'name'
    ];

    // Find Category by Name
    public function findByName($name) {
        return $this->first(['name' => $name]);
    }
}