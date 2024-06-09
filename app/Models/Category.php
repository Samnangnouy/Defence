<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;
    protected $table = 'categories';
    protected $fillable = [
        'name',
        'note'
    ];

    public function designation(){
        return $this->hasMany(Designation::class, 'category_id', 'id');
    }

    public function members(){
        return $this->hasMany(Member::class, 'category_id', 'id');
    }
}
