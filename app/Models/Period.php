<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Period extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'default',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
