<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TutorSubject extends Model
{
    use HasFactory;

    public function subject_to_tutorSchedule()
    {
        return $this->hasMany(TutorSchedule::class,'subject_id','id')
                ->where('start_time', '>=', time());
    }
}
