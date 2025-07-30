<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiLog extends Model
{
    use HasFactory;

    protected $table = 'api_logs';

    protected $fillable = [
        'method',
        'url',
        'headers',
        'body',
        'ip_address',
        'user_agent',
        'status_code',
        'response_body',
        'requested_at',
        'responded_at',
    ];

    protected $casts = [
        'headers' => 'array',
        'body' => 'array',   
        'response_body' => 'array',
        'requested_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    public $timestamps = false; 
}