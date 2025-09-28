<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WatchDuration extends Model
{
    use HasFactory;

    protected $fillable = [
        'watched_student_id',
        'watched_course_id',
        'watched_lesson_id',
        'current_duration',
        'watched_counter',
    ];
}
