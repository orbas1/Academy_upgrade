<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TutorCategory extends Model
{
    use HasFactory;

    public function category_to_tutorSchedule()
    {
        return $this->hasMany(TutorSchedule::class,'category_id','id')
                ->where('start_time', '>=', time());
    }
}
