<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class NotificationRead extends Model
{
    use BelongsToSchool;

    protected $fillable = ['notification_id', 'user_id', 'read_at'];
    protected $casts = ['read_at' => 'datetime'];
}
