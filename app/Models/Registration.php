<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Registration extends Model
{
    protected $fillable = [
        'user_id',
        'student_user_id',
        'schedule_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function studentUser()
    {
        return $this->belongsTo(User::class, 'student_user_id');
    }

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }
}
