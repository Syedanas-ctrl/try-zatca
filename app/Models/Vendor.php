<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    use HasFactory;

    protected $fillable = [
        'created_at',
        'updated_at',
        'organization_name',
        'vat_number',
        'zatca_onboarded',
        'zatca_onboarded_at',
        'zatca_certificate_info',
        'zatca_certificate_status',
        'zatca_certificate_expiry',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'zatca_onboarded' => 'boolean',
        'zatca_onboarded_at' => 'datetime',
        'zatca_certificate_info' => 'array',
        'zatca_certificate_expiry' => 'date',
        'organization_name' => 'string',
        'vat_number' => 'string',
    ];
}
