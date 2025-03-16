<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'created_at',
        'updated_at',
        'zatca_clearance_status',
        'zatca_clearance_id',
        'zatca_reported_at',
        'zatca_compliant',
        'zatca_xml_path',
        'zatca_hash',
        'uuid',
        'qr_code',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'zatca_reported_at' => 'datetime',
        'zatca_compliant' => 'boolean',
    ];
}
