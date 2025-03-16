<?php

return [
    /*
    |--------------------------------------------------------------------------
    | ZATCA Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration settings for ZATCA e-invoicing system integration
    |
    */

    // API URLs
    'base_url' => env('ZATCA_BASE_URL', 'https://gw-fatoora.zatca.gov.sa/e-invoicing/developer-portal'),
    'compliance_check_url' => env('ZATCA_COMPLIANCE_URL', 'https://gw-fatoora.zatca.gov.sa/e-invoicing/developer-portal/compliance/invoices'),
    'production_url' => env('ZATCA_PRODUCTION_URL', 'https://gw-fatoora.zatca.gov.sa/e-invoicing/developer-portal/invoices'),
    'csr_url' => env('ZATCA_CSR_URL', 'https://gw-fatoora.zatca.gov.sa/e-invoicing/developer-portal/certificate'),

    // Current Phase: 'generation' or 'integration'
    'phase' => env('ZATCA_PHASE', 'integration'),

    // Certificate settings
    'certificate_validity_days' => env('ZATCA_CERT_VALIDITY', 365),

    // Storage paths
    'storage_path' => env('ZATCA_STORAGE_PATH', 'zatca'),

    // Compliance check settings
    'compliance_check_enabled' => env('ZATCA_COMPLIANCE_CHECK', true),

    // QR code settings
    'qr_code_enabled' => env('ZATCA_QR_CODE', true),
];
