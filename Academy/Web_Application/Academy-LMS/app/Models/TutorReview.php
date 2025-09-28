<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TutorReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'tutor_id',
        'student_id',
        'rating',
        'review',
    ];

    public function review_to_user()
    {
        return $this->belongsTo(User::class,'student_id','id');
    }
}
