<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    use HasFactory;
    protected $table = 'members';
    protected $fillable = [
        'category_id', 'designation_id', 'user_id', 'description'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function designation()
    {
        return $this->belongsTo(Designation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function projects()
    {
        return $this->belongsToMany(Project::class, 'member_project');
    }

    public function tasks()
    {
        return $this->belongsToMany(Task::class, 'member_task');
    }
}
