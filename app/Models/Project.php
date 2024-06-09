<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;
    protected $table = 'projects';
    protected $fillable = [
        'name', 'image', 'client_id', 'admin_id', 'start_date', 'end_date', 'status', 'priority', 'description', 'created_by', 'updated_by'
    ];

    public function client(){
        return $this->belongsTo(Client::class);
    }

    public function admin(){
        return $this->belongsTo(Admin::class);
    }

    public function members()
    {
        return $this->belongsToMany(Member::class, 'member_project');
    }

    public function creator()
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(Admin::class, 'updated_by');
    }

    public function features()
    {
        return $this->hasMany(Feature::class);
    }
}
