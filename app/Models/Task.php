<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use SoftDeletes;

    protected $fillable = ['title', 'description', 'assignee_id', 'due_date', 'status', 'created_by'];

    protected $dates = [
        'due_date',
        'deleted_at',
    ];
    public function assignee()
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
