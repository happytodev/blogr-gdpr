<?php

namespace Happytodev\BlogrGdpr\Models;

use Illuminate\Database\Eloquent\Model;

class GdprRequest extends Model
{
    protected $table = 'blogr_gdpr_requests';

    protected $fillable = [
        'email',
        'request_type',
        'status',
        'notes',
        'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'pending',
    ];
}
