<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubmittedAssignment extends Model
{
    use HasFactory;

    protected $table = 'submitted_assignments';

    protected $fillable = [
        'id',
        'user_id',
        'assignment_id',
        'answer',
        'file',
        'note',
        'created_at',
        'updated_at',
    ];

    public function assignment()
    {
        return $this->hasOne('App\Models\Assignment', 'id', 'assignment_id');
    }
    public function user()
    {
        return $this->hasOne('App\Models\User', 'id', 'user_id');
    }
}
