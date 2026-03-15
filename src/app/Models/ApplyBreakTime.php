<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApplyBreakTime extends Model
{
    use HasFactory;

    protected $fillable = [
        'apply_id',
        'break_time_id',
        'apply_break_start_time',
        'apply_break_end_time'
    ];

    public function apply()
    {
        return $this->belongsTo(Apply::class);
    }

    public function break()
    {
        return $this->belongsTo(BreakTime::class);
    }
}
