<?php

namespace App\Services\Zatca\Tags;

use App\Services\Zatca\Tag;

class InvoiceDigitalSignature extends Tag
{
    public function __construct($value)
    {
        parent::__construct(7, $value);
    }
}
