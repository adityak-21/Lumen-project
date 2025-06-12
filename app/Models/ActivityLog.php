<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $table = 'user_activity';
    
    protected $fillable = [
        'user_id',
        'login_time',
        'logout_time',
        'duration',
    ];
    public $timestamps = false;
    
    public function users()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

}