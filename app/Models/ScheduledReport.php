<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduledReport extends Model
{
    protected $fillable = [
        'name',
        'camera_ids',
        'frequency',
        'send_pdf',
        'send_excel',
        'recipients',
        'include_ai_summary',
        'active',
        'last_sent_at',
        'next_run_at',
    ];

    protected $casts = [
        'camera_ids' => 'array',
        'recipients' => 'array',
        'send_pdf' => 'boolean',
        'send_excel' => 'boolean',
        'include_ai_summary' => 'boolean',
        'active' => 'boolean',
        'last_sent_at' => 'datetime',
        'next_run_at' => 'datetime',
    ];
}