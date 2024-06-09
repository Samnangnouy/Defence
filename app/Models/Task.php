<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;
    protected $table = 'tasks';
    protected $fillable = [
        'feature_id',
        'name',
        'start_date',
        'end_date',
        'status',
        'priority',
        'description',
        'created_by',
        'updated_by'
    ];

    public function feature()
    {
        return $this->belongsTo(Feature::class);
    }

    public function members()
    {
        return $this->belongsToMany(Member::class, 'member_task');
    }

    public function creator()
    {
        return $this->belongsTo(Member::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(Member::class, 'updated_by');
    }
}
