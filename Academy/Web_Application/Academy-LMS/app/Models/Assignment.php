<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    use HasFactory;

    protected $table    = 'assignments';
    protected $fillable = [
        'id',
        'course_id',
        'title',
        'questions',
        'question_file',
        'total_marks',
        'deadline',
        'note',
        'status',
        'created_at',
        'updated_at',
    ];
}
