<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class StaffAttendance extends Model
{
    use BelongsToSchool;

    protected $fillable = [
        'staff_id', 'user_id', 'attendance_date', 'clock_in_at', 'clock_out_at',
        'clock_in_method', 'clock_out_method', 'clock_in_ip', 'clock_out_ip',
        'clock_in_latitude', 'clock_in_longitude', 'clock_out_latitude',
        'clock_out_longitude', 'notes',
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'clock_in_at' => 'datetime',
        'clock_out_at' => 'datetime',
        'clock_in_latitude' => 'float',
        'clock_in_longitude' => 'float',
        'clock_out_latitude' => 'float',
        'clock_out_longitude' => 'float',
    ];

    public function staffMember() { return $this->belongsTo(StaffMember::class, 'staff_id'); }
    public function user() { return $this->belongsTo(User::class); }
}
