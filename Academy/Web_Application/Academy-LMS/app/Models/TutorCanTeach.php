<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TutorCanTeach extends Model
{
    use HasFactory;

    protected $table = 'tutor_can_teach';

    public function category_to_tutorCategory()
    {
        return $this->belongsTo(TutorCategory::class,'category_id','id');
    }

    public function category_to_tutorSubjects()
    {
        return $this->belongsTo(TutorSubject::class,'subject_id','id');
    }

}
