<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Consultation extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'email', 'scheduled_at', 'google_event_id'];
    // Преобразование полей в нужные типы
    protected $casts = [
        'scheduled_at' => 'datetime', 
    ];
}
