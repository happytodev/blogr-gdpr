<?php

namespace Happytodev\BlogrGdpr\Models;

use Illuminate\Database\Eloquent\Model;

class ConsentLog extends Model
{
    protected $table = 'blogr_gdpr_consent_logs';

    protected $fillable = [
        'email',
        'ip_address',
        'user_agent',
        'consent_type',
        'consent_given',
        'consent_data',
    ];

    protected $casts = [
        'consent_given' => 'boolean',
        'consent_data' => 'array',
    ];
}
