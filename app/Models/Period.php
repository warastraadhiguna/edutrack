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

    protected $casts = [
        'default' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saved(function (self $period): void {
            if (! $period->default) {
                return;
            }

            self::query()
                ->whereKeyNot($period->getKey())
                ->where('default', 1)
                ->update(['default' => 0]);
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
