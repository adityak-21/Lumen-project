<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'user_id', 'title', 'description', 'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];
    /**
     * The user that owns the notification.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}