<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;
    protected $table = 'clients';
    protected $fillable = [
        'company_name', 'image', 'email', 'phone', 'description'
    ];

    public function projects(){
        return $this->hasMany(Project::class);
    }
}