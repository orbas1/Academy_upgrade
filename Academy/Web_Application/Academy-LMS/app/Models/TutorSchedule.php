<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TutorSchedule extends Model
{
    use HasFactory;

    // Specify which fields are mass assignable
    protected $fillable = [
        'tutor_id',
        'category_id',
        'subject_id',
        'price',
        'start_time',
        'end_time',
        'duration',
        'description',
        'tution_type',
        'status',
        'booking_id'
    ];

    public function schedule_to_tutorCategory()
    {
        return $this->belongsTo(TutorCategory::class,'category_id','id');
    }

    public function schedule_to_tutorSubjects()
    {
        return $this->belongsTo(TutorSubject::class,'subject_id','id');
    }

    public function schedule_to_tutor()
    {
        return $this->belongsTo(User::class,'tutor_id','id');
    }

    public function schedule_to_tutorCanTeach()
    {
        return $this->belongsTo(TutorCanTeach::class, 'subject_id', 'subject_id')
                    ->where('category_id', $this->category_id);
    }
}
