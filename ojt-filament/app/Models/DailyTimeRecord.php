<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyTimeRecord extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'record_date' => 'date',
        'time_in' => 'datetime',
        'time_out' => 'datetime',
    ];
}
